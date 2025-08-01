<?php

namespace App\Notifications;

use App\Models\VerificationCode;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ClaimVerificationCode extends Notification implements ShouldQueue
{
    use Queueable;

    protected $verificationCode;

    /**
     * Create a new notification instance.
     */
    public function __construct(VerificationCode $verificationCode)
    {
        $this->verificationCode = $verificationCode;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $claim = $this->verificationCode->verifiable;
        $place = $claim->place;

        return (new MailMessage)
            ->subject('Verify Your Business Claim - ' . config('app.name'))
            ->greeting('Hello!')
            ->line('You have requested to claim ownership of "' . $place->title . '" on ' . config('app.name') . '.')
            ->line('Your verification code is:')
            ->line('**' . $this->verificationCode->code . '**')
            ->line('This code will expire in 30 minutes.')
            ->line('If you did not request this verification, please ignore this email.')
            ->salutation('Thank you for using ' . config('app.name') . '!');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'verification_code' => $this->verificationCode->code,
            'expires_at' => $this->verificationCode->expires_at,
        ];
    }
}