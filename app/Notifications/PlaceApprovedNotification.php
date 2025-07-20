<?php

namespace App\Notifications;

use App\Models\Place;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PlaceApprovedNotification extends Notification
{
    use Queueable;

    public $place;
    public $notes;

    /**
     * Create a new notification instance.
     */
    public function __construct(Place $place, ?string $notes = null)
    {
        $this->place = $place;
        $this->notes = $notes;
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
            ->subject('Your place has been approved!')
            ->greeting('Great news!')
            ->line("Your place \"{$this->place->title}\" has been approved and is now live.")
            ->action('View Your Place', url($this->place->canonical_url));
        
        if ($this->notes) {
            $message->line('Approval notes from the admin:')
                    ->line($this->notes);
        }
        
        return $message->line('Thank you for contributing to our directory!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'place_approved',
            'place_id' => $this->place->id,
            'place_title' => $this->place->title,
            'place_url' => $this->place->canonical_url,
            'notes' => $this->notes,
            'approved_at' => now()->toIso8601String(),
        ];
    }
}