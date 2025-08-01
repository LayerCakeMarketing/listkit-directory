<?php

namespace App\Notifications\System;

use App\Models\AppNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SystemMessage extends Notification implements ShouldQueue
{
    use Queueable;

    protected string $title;
    protected string $message;
    protected ?string $actionUrl;
    protected string $priority;
    protected array $metadata;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        string $title,
        string $message,
        ?string $actionUrl = null,
        string $priority = AppNotification::PRIORITY_NORMAL,
        array $metadata = []
    ) {
        $this->title = $title;
        $this->message = $message;
        $this->actionUrl = $actionUrl;
        $this->priority = $priority;
        $this->metadata = $metadata;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->title)
            ->greeting("Hello {$notifiable->firstname}!")
            ->line($this->message);

        if ($this->actionUrl) {
            $mail->action('View Details', url($this->actionUrl));
        }

        return $mail->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        // Create app notification record
        AppNotification::create([
            'type' => AppNotification::TYPE_SYSTEM,
            'title' => $this->title,
            'message' => $this->message,
            'recipient_id' => $notifiable->id,
            'action_url' => $this->actionUrl,
            'priority' => $this->priority,
            'metadata' => $this->metadata,
        ]);

        return [
            'title' => $this->title,
            'message' => $this->message,
            'action_url' => $this->actionUrl,
            'priority' => $this->priority,
            'metadata' => $this->metadata,
        ];
    }
}