<?php

namespace App\Jobs;

use App\Mail\CampaignEmail;
use App\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $recipient,
        public int $templateId,
        public string $templateName,
        public string $subject,
        public User $user
    ) {}

    public function handle()
    {
        try {
            $template = EmailTemplate::with('content')->findOrFail($this->templateId);

            Log::debug('Preparing to send email', [
                'recipient' => $this->recipient,
                'template_size' => strlen($template->content->content_html) . ' bytes'
            ]);

            $email = new CampaignEmail(
                $template->content->content_html,
                $this->templateName,
                $this->user
            );

            $email->subject($this->subject);

            Mail::to($this->recipient)->send($email);

            Log::info('Email sent successfully', [
                'recipient' => $this->recipient,
                'template' => $this->templateName
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send email', [
                'recipient' => $this->recipient,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::critical('SendEmailJob failed after all attempts', [
            'recipient' => $this->recipient,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
