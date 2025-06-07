<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SendEmailJob;
use App\Mail\CampaignEmail;
use App\Models\EmailTemplate;
use App\Services\BeefreeService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
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
            $template = $this->beefreeService->getTemplate();

            if ($request->has('template_id')) {
                $savedTemplate = EmailTemplate::with('content')
                    ->find($request->template_id);

                if ($savedTemplate && $savedTemplate->content) {
                    $template = $savedTemplate->content->content_json;
                } else {
                    Log::warning("Template not found or has no content", [
                        'template_id' => $request->template_id
                    ]);
                }
            }

            return response()->json([
                'credentials' => $this->beefreeService->getCredentials(),
                'template' => $template,
                'message' => 'Template loaded'
            ]);

        } catch (Exception $e) {
            Log::error("Failed to fetch Beefree data: " . $e->getMessage());
            return response()->json([
                'error' => 'Failed to fetch data',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function listTemplates()
    {
        return EmailTemplate::where('is_autosave', false)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function save(Request $request)
    {
        try {
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

            $template = EmailTemplate::create([
                'user_id' => null,
                'name' => $jsonData['title'] ?? 'Untitled Template ' . now()->format('Y-m-d H:i'),
                'subject' => $jsonData['title'] ?? 'Untitled Template ' . now()->format('Y-m-d H:i'),
                'is_autosave' => $request->is_autosave ?? false
            ]);

            $template->content()->create([
                'content_json' => $jsonData,
                'content_html' => $request->html
            ]);

            if (!$request->is_autosave) {
                EmailTemplate::where('is_autosave', true)->delete();
            }

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

    public function next(Request $request)
    {
        Log::info('Next step initiated');
        try {
            $request->validate([
                'template_id' => 'required|integer|exists:email_templates,id'
            ]);

            Log::info('Template ID received', ['template_id' => $request->template_id]);

            return response()->json([
                'redirect_url' => route('continue', ['template' => $request->template_id])
            ]);

        } catch (Exception $e) {
            Log::error("Next step failed: " . $e->getMessage());
            return response()->json([
                'error' => 'Failed to proceed to next step: ' . $e->getMessage()
            ], 500);
        }
    }

    public function showContinue($template)
    {
        Log::info('Rendering Continue page', [
            'template_id' => $template,
            'route' => 'continue'
        ]);

        $templateData = EmailTemplate::with('content')->find($template);

        if (!$templateData) {
            Log::error('Template not found', ['template_id' => $template]);
            return redirect()->back()->withErrors(['Template not found']);
        }

        return Inertia::render('Continue', [
            'templateId' => $template,
            'templateData' => $templateData
        ]);
    }

    public function sendEmail(Request $request)
    {
        try {
            Log::info('Email scheduling request received');

            $validated = $request->validate([
                'template_id' => 'required|integer|exists:email_templates,id',
                'recipients' => 'required|array|min:1',
                'recipients.*' => 'required|email',
                'scheduled_at' => 'required|date|after_or_equal:now',
                'html_content' => 'required|string'
            ]);

            $templateId = (int) $validated['template_id'];
            $template = EmailTemplate::findOrFail($templateId);

            Log::info('Dispatching email jobs', [
                'template_id' => $template->id,
                'job_count' => count($validated['recipients'])
            ]);

            foreach ($validated['recipients'] as $recipient) {
                SendEmailJob::dispatch(
                    $recipient,
                    $templateId,
                    $template->name,
                    $request->subject ?? $template->subject ?? $template->name,
                    null
                )->delay(Carbon::parse($validated['scheduled_at']));

                Log::debug('Job dispatched for recipient', [
                    'recipient' => $recipient,
                    'scheduled_at' => $validated['scheduled_at']
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Emails scheduled successfully',
                'scheduled_at' => $validated['scheduled_at'],
                'recipient_count' => count($validated['recipients'])
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to schedule emails: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, EmailTemplate $template)
    {
        $request->validate([
            'name' => 'required|string|max:255'
        ]);

        $template->update([
            'name' => $request->name
        ]);

        return response()->json(['success' => true]);
    }

    public function destroy(EmailTemplate $template)
    {
        $template->delete();

        return response()->json(['success' => true]);
    }

    public function updateSubject(Request $request, EmailTemplate $template)
    {
        $request->validate([
            'subject' => 'required|string|max:255'
        ]);

        $template->update([
            'subject' => $request->subject
        ]);

        return response()->json(['success' => true]);
    }
}
