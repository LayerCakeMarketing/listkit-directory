<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;

    /**
     * Create a new message instance.
     */
    public function __construct($user)
    {
        $this->user = $user;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to ' . site_setting('site_name', 'Listerino'),
            from: [
                'address' => site_setting('email_from_address', config('mail.from.address')),
                'name' => site_setting('email_from_name', config('mail.from.name')),
            ]
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.welcome',
            with: [
                'siteName' => site_setting('site_name', 'Listerino'),
                'siteTagline' => site_setting('site_tagline', 'Your Local Directory'),
                'primaryColor' => site_setting('primary_color', '#3B82F6'),
                'logoUrl' => site_setting('logo_url', '/images/listerino_logo.svg'),
            ]
        );
    }
}