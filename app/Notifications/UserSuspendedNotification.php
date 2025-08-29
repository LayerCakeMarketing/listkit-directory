<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserSuspendedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $reason;
    protected $suspendedBy;

    /**
     * Create a new notification instance.
     */
    public function __construct($reason, $suspendedBy = null)
    {
        $this->reason = $reason;
        $this->suspendedBy = $suspendedBy;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Account Has Been Suspended')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Your account on ' . config('app.name') . ' has been suspended.')
            ->line('**Reason for suspension:** ' . $this->reason)
            ->line('If you believe this suspension was made in error, or if you have questions about this action, please contact our support team.')
            ->line('You will not be able to access your account until the suspension is lifted.')
            ->salutation('Best regards,')
            ->salutation(config('app.name') . ' Team');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'account_suspended',
            'reason' => $this->reason,
            'suspended_by' => $this->suspendedBy,
        ];
    }
}