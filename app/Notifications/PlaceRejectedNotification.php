<?php

namespace App\Notifications;

use App\Models\Place;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PlaceRejectedNotification extends Notification
{
    use Queueable;

    public $place;
    public $reason;

    /**
     * Create a new notification instance.
     */
    public function __construct(Place $place, ?string $reason = null)
    {
        $this->place = $place;
        $this->reason = $reason;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Your place submission needs revision')
            ->greeting('Hello!')
            ->line("Your place submission \"{$this->place->title}\" has been reviewed and needs some changes before it can be approved.");
        
        if ($this->reason) {
            $message->line('Feedback from the reviewer:')
                    ->line($this->reason);
        }
        
        return $message->line('Please update your submission based on the feedback and resubmit for review.')
                       ->action('Edit Your Place', url("/places/{$this->place->id}/edit"))
                       ->line('Thank you for your understanding.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'place_rejected',
            'place_id' => $this->place->id,
            'place_title' => $this->place->title,
            'place_edit_url' => "/places/{$this->place->id}/edit",
            'reason' => $this->reason,
            'rejected_at' => now()->toIso8601String(),
        ];
    }
}