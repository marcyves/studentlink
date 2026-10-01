<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Throwable;

class IpGeolocator
{
    /**
     * Country and city from the free ipwho.is lookup.
     * Private, local, and unresolved addresses stay empty. No paid API is called.
     *
     * @return array{country: ?string, city: ?string}
     */
    public function locate(?string $ip): array
    {
        $empty = ['country' => null, 'city' => null];

        if (! is_string($ip) || filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return $empty;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return $empty;
        }

        if (! config('studentlink.geolocate_logins')) {
            return $empty;
        }

        try {
            $response = Http::timeout(2)
                ->connectTimeout(1)
                ->acceptJson()
                ->get('https://ipwho.is/'.rawurlencode($ip));

            if (! $response->successful()) {
                return $empty;
            }

            $data = $response->json();

            if (! is_array($data) || ($data['success'] ?? false) !== true) {
                return $empty;
            }

            return [
                'country' => $this->place($data['country'] ?? null),
                'city' => $this->place($data['city'] ?? null),
            ];
        } catch (Throwable) {
            return $empty;
        }
    }

    private function place(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        return mb_substr($value, 0, 120);
    }
}
