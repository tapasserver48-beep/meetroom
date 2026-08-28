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
     * Get ICE servers with TURN credentials from Cloudflare
     */
    public function getIceServers(int $ttl = 86400): array
    {
        $cacheKey = "turn_ice_servers_{$this->keyId}";

        return Cache::remember($cacheKey, now()->addSeconds($ttl - 300), function () use ($ttl) {
            return $this->fetchIceServers($ttl);
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
                            ->map(fn($s) => [
                                'urls' => $s['urls'],
                                'username' => $s['username'] ?? null,
                                'credential' => $s['credential'] ?? null,
                            ])
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

    protected function getStunOnlyServers(): array
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
            [
                'urls' => 'stun:stun.nextcloud.com:443',
            ],
        ];
    }
}
