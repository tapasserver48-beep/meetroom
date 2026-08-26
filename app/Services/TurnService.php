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
        $this->keyId = config('services.cloudflare.turn_key_id');
        $this->keySecret = config('services.cloudflare.turn_key_secret');
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
            return $this->getStunOnlyServers();
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->keySecret,
                'Content-Type' => 'application/json',
            ])->post("https://rtc.live.cloudflare.com/v1/turn/keys/{$this->keyId}/credentials/generate-ice-servers", [
                'ttl' => $ttl,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['iceServers']) && is_array($data['iceServers'])) {
                    return $data['iceServers'];
                }
            }
        } catch (\Exception $e) {
            \Log::error('Failed to fetch TURN credentials: ' . $e->getMessage());
        }

        return $this->getStunOnlyServers();
    }

    protected function getStunOnlyServers(): array
    {
        return [
            [
                'urls' => [
                    'stun:stun.l.google.com:19302',
                    'stun:stun1.l.google.com:19302',
                ],
            ],
        ];
    }
}