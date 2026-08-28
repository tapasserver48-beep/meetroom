<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class TurnService
{
    protected string $keyId;
    protected string $keySecret;
    protected string $baseUrl = 'https://rtc.live.cloudflare.com/v1/turn/keys';

    public function __construct()
    {
        $this->keyId = (string) config('services.cloudflare.turn_key_id', '');
        $this->keySecret = (string) config('services.cloudflare.turn_key_secret', '');
    }

    /**
     * Get ICE servers — Render-native, no Cloudflare dependency required.
     *
     * Always returns a working set of public STUN + free TURN (openrelay)
     * so calls connect even when the network blocks Cloudflare TURN.
     * If Cloudflare credentials are configured, its TURN is prepended as the
     * primary relay (most reliable on Render), but the public fallback remains
     * so "TURN allocate timed out" against one provider does not break the call.
     */
    public function getIceServers(int $ttl = 86400): array
    {
        $cacheKey = "turn_ice_servers_v2_{$this->keyId}";

        return Cache::remember($cacheKey, now()->addSeconds($ttl - 300), function () use ($ttl) {
            $cloudflare = $this->fetchIceServers($ttl);
            $fallback  = $this->getPublicFallbackServers();

            // If Cloudflare returned usable TURN, keep it first (best on Render),
            // otherwise just use the public fallback.
            $hasTurn = collect($cloudflare)->contains(fn($s) => isset($s['username']));
            if ($hasTurn) {
                // Deduplicate: keep Cloudflare TURN + public STUN/TURN (no duplicates)
                return array_values(array_merge($cloudflare, $fallback));
            }
            return $fallback;
        });
    }

    protected function fetchIceServers(int $ttl): array
    {
        if (!$this->keyId || !$this->keySecret) {
            \Log::warning('[TurnService] Missing Cloudflare TURN credentials — using STUN-only fallback');
            return $this->getStunOnlyServers();
        }

        try {
            $url = "https://rtc.live.cloudflare.com/v1/turn/keys/{$this->keyId}/credentials/generate-ice-servers";

            \Log::info('[TurnService] Fetching TURN credentials from Cloudflare', [
                'key_id' => $this->keyId,
                'url' => $url,
                'ttl' => $ttl,
            ]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->keySecret,
                'Content-Type' => 'application/json',
            ])->timeout(10)->post($url, [
                'ttl' => $ttl,
            ]);

            \Log::info('[TurnService] Cloudflare API response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if ($response->successful()) {
                $data = $response->json();

                // Cloudflare returns iceServers in TWO shapes depending on version:
                //   • an array:  [ { urls, username, credential }, ... ]
                //   • a single object: { urls, username, credential }
                // It may also be wrapped as { result: { iceServers: ... } }.
                // Normalize everything into a flat list of { urls, username, credential }.
                $servers = $data['iceServers']
                    ?? ($data['result']['iceServers'] ?? null)
                    ?? ($data['result'] ?? null);

                if ($servers !== null) {
                    // Single-object shape -> wrap in an array.
                    if (isset($servers['urls'])) {
                        $servers = [$servers];
                    }

                    if (is_array($servers)) {
                        $normalized = collect($servers)
                            ->filter(fn($s) => isset($s['urls']))
                            ->map(function ($s) {
                                $urls = $s['urls'];
                                if (is_string($urls)) {
                                    $urls = [$urls];
                                }

                                // Keep only firewall-friendly transports.
                                // • Drop port 53 (browsers block it and it times out).
                                // • Drop UDP TURN (most networks/ISP block UDP 3478);
                                //   rely on TLS/TCP TURN (turns:443 / turns:5349 /
                                //   turn:80) which traverse almost any firewall.
                                // STUN (UDP 3478) is kept — it still helps when UDP is allowed.
                                $urls = array_values(array_filter($urls, function ($u) {
                                    if (str_contains($u, ':53')) {
                                        return false;
                                    }
                                    if (str_contains($u, 'transport=udp')) {
                                        return false;
                                    }
                                    return true;
                                }));

                                return [
                                    'urls' => $urls,
                                    'username' => $s['username'] ?? null,
                                    'credential' => $s['credential'] ?? null,
                                ];
                            })
                            ->filter(fn($s) => count($s['urls']) > 0)
                            ->values()
                            ->toArray();

                        if (count($normalized) > 0) {
                            \Log::info('[TurnService] Got TURN credentials', [
                                'server_count' => count($normalized),
                                'servers' => collect($normalized)->map(fn($s) => $s['urls'])->toArray(),
                            ]);
                            return $normalized;
                        }
                    }
                }
                \Log::warning('[TurnService] Cloudflare returned success but no usable iceServers in response', ['data' => $data]);
            } else {
                \Log::error('[TurnService] Cloudflare API failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('[TurnService] Exception fetching TURN credentials', [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
        }

        \Log::warning('[TurnService] Falling back to STUN-only servers');
        return $this->getStunOnlyServers();
    }

    /**
     * Public STUN + free TURN that works without any Cloudflare account.
     * Hosted outside Render so it traverses the same firewall issues, but on
     * a different provider/domain — when Cloudflare TURN times out (701) this
     * fallback relay can still connect.
     */
    protected function getPublicFallbackServers(): array
    {
        return [
            [
                'urls' => [
                    'stun:stun.l.google.com:19302',
                    'stun:stun1.l.google.com:19302',
                    'stun:stun2.l.google.com:19302',
                    'stun:stun3.l.google.com:19302',
                    'stun:stun4.l.google.com:19302',
                ],
            ],
            [
                'urls' => 'stun:global.stun.twilio.com:3478',
            ],
            // Free public TURN — openrelay.metered.ca (no API key needed, widely reachable on 80/443)
            [
                'urls' => [
                    'turn:openrelay.metered.ca:80',
                    'turn:openrelay.metered.ca:443',
                    'turn:openrelay.metered.ca:443?transport=tcp',
                ],
                'username' => 'openrelayproject',
                'credential' => 'openrelayproject',
            ],
        ];
    }

    protected function getStunOnlyServers(): array
    {
        return $this->getPublicFallbackServers();
    }
}
