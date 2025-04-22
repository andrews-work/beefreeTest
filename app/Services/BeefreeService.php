<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BeefreeService
{
    public function getAuthToken()
    {
        $url = config('services.beefree.url');
        $clientId = config('services.beefree.client');
        $clientSecret = config('services.beefree.secret');


        $uid = 'user_'. Auth::id();

        Log::debug("Attempting auth with", [
            'uid' => $uid,
            'client_id' => $clientId,
            'auth_url' => $url
        ]);

        $response = Http::post($url, [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'uid' => $uid,
        ]);

        if ($response->successful()) {
            return $response->json()['access_token'];
        }

        Log::error('BeeFree auth failed', [
            'status' => $response->status(),
            'response' => $response->json()
        ]);

        throw new Exception($response->json()['message'] ?? 'Authentication failed');
    }
}
