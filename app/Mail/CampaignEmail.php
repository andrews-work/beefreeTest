<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CampaignEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $htmlContent,
        public string $templateName,
        public User $user
    ) {
        Log::debug('Creating CampaignEmail mailable', [
            'template' => $templateName,
            'user_id' => $user->id,
            'content_size' => strlen($htmlContent) . ' bytes'
        ]);
    }

    public function envelope(): Envelope
    {
        $fromAddress = new Address('hello@demomailtrap.co', 'Your Sender Name');
        $replyToAddress = new Address($this->user->email, $this->user->name);

        Log::debug('Building email envelope', [
            'subject' => $this->subject,
            'from' => $fromAddress->address,
            'replyTo' => $replyToAddress->address,
            'config_from' => config('mail.from.address')
        ]);

        return new Envelope(
            subject: $this->subject,
            from: $fromAddress,
            replyTo: [$replyToAddress]
        );
    }

    public function content(): Content
    {
        Log::debug('Building email content', [
            'content_type' => 'html',
            'content_size' => strlen($this->htmlContent) . ' bytes'
        ]);

        return new Content(
            htmlString: $this->htmlContent
        );
    }

    public function attachments(): array
    {
        return [];
    }

    public function build()
    {
        $this->withSymfonyMessage(function ($message) {
            Log::debug('Final message headers before sending', [
                'from' => array_map(fn($a) => $a->getAddress(), $message->getFrom()),
                'to' => array_map(fn($a) => $a->getAddress(), $message->getTo()),
                'subject' => $message->getSubject(),
                'headers' => $message->getHeaders()->toString()
            ]);
        });

        return $this;
    }
}
