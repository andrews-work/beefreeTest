<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BeefreeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class BeeFreeController extends Controller
{
    public function __construct(
        protected BeefreeService $beefreeService
    ) {}

    public function getToken(Request $request)
    {
        try {
            if (!Auth::id()) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            Log::debug("BeeFreeController called - testing with hardcoded UID");

            $token = $this->beefreeService->getAuthToken();

            return response()->json([
                'token' => $token,
                'message' => 'Authentication token generated successfully'
            ]);

        } catch (\Exception $e) {
            Log::error("BeeFree token generation failed: " . $e->getMessage());
            return response()->json([
                'error' => 'Failed to generate authentication token',
                'details' => $e->getMessage()
            ], 500);
        }
    }
}
