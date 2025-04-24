<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class BeefreeService
{
    public function getCredentials()
    {
        return [
            'clientId' => config('services.beefree.client'),
            'clientSecret' => config('services.beefree.secret'),
            'uid' => 'user_' . Auth::id(),
        ];
    }

    public function getTemplate()
    {
        return Cache::remember('beefree_template', now()->addWeek(), function () {
            $response = Http::get(config('services.beefree.template_url'));
            if (!$response->successful()) {
                throw new Exception("Failed to fetch template");
            }
            return $response->json();
        });
    }
}
