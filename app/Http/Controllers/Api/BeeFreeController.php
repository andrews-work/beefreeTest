<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use App\Services\BeefreeService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class BeeFreeController extends Controller
{
    public function __construct(
        protected BeefreeService $beefreeService
    ) {}

    public function index()
    {
        return Inertia::render('Dashboard');
    }

    public function credentials(Request $request)
    {
        try {
            if (!Auth::check()) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $userId = Auth::id();

            return response()->json([
                'credentials' => $this->beefreeService->getCredentials(),
                'template' => $this->beefreeService->getTemplate(),
                'message' => 'Base template loaded'
            ]);

        } catch (Exception $e) {
            Log::error("Failed to fetch Beefree data: " . $e->getMessage());
            return response()->json([
                'error' => 'Failed to fetch data',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function save(Request $request)
    {
        try {
            $userId = Auth::id();
            if (!$userId) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $jsonData = $request->json;
            if (is_string($jsonData)) {
                $jsonData = json_decode($jsonData, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception('Invalid JSON data');
                }
            }

            if (!is_array($jsonData)) {
                throw new Exception('The json field must be a valid JSON array');
            }

            $template = EmailTemplate::updateOrCreate(
                ['user_id' => $userId],
                [
                    'name' => $jsonData['title'] ?? 'Untitled Template',
                    'content_json' => $jsonData,
                    'content_html' => $request->html
                ]
            );

            Log::debug('Template saved', [
                'user_id' => $userId,
                'template_id' => $template->id,
                'template_name' => $template->name,
                // 'content_json' => json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                // 'content_html' => $this->formatHtmlForLogging($request->html)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Template saved successfully',
                'template_id' => $template->id
            ]);

        } catch (Exception $e) {
            Log::error("Save failed: " . $e->getMessage());
            return response()->json([
                'error' => 'Failed to save data',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    private function formatHtmlForLogging(?string $html): string
    {
        if (empty($html)) {
            return '[No HTML content]';
        }

        $html = preg_replace('/></', ">\n<", $html);
        $html = preg_replace('/(<[^\/][^>]*>)/', "$1\n", $html);
        $html = preg_replace('/(<\/[^>]*>)/', "\n$1", $html);

        $lines = explode("\n", $html);
        $indented = [];
        $indentLevel = 0;

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (empty($trimmed)) continue;

            if (strpos($trimmed, '</') === 0) {
                $indentLevel--;
            }

            $indented[] = str_repeat('    ', $indentLevel) . $trimmed;

            if (strpos($trimmed, '<') === 0 &&
                strpos($trimmed, '</') !== 0 &&
                !preg_match('/<[^>]+\/>/', $trimmed)) {
                $indentLevel++;
            }
        }

        return implode("\n", $indented);
    }
}
