<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Email;

class Letter extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(public Email $email)
    {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->email->campaign->subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'mail.letter',
            with: [
                'name' => $this->email->subscriber->name,
                'subject' => $this->email->campaign->subject,
                'content' => $this->email->campaign->content,
                'track_open' => route('email.tracking.pixel', ['token' => $this->email->tracking_token]),
                'track_click' => route('email.tracking.click', ['token' => $this->email->tracking_token]),
                'track_unsubscribe' => route('email.tracking.unsubscribe', ['token' => $this->email->tracking_token]),
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
