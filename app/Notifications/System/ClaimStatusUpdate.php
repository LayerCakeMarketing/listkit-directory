<?php

namespace App\Notifications\System;

use App\Models\AppNotification;
use App\Models\Claim;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ClaimStatusUpdate extends Notification implements ShouldQueue
{
    use Queueable;

    protected Claim $claim;
    protected string $status;
    protected ?string $reason;

    /**
     * Create a new notification instance.
     */
    public function __construct(Claim $claim, string $status, ?string $reason = null)
    {
        $this->claim = $claim;
        $this->status = $status;
        $this->reason = $reason;
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
        $businessName = $this->claim->place->title;
        
        $mail = match($this->status) {
            'approved' => (new MailMessage)
                ->subject("Claim Approved - {$businessName}")
                ->greeting("Congratulations {$notifiable->firstname}!")
                ->line("Your claim for {$businessName} has been approved.")
                ->line("You now have full access to manage your business listing.")
                ->action('Manage Your Business', url("/places/{$this->claim->place->slug}/edit")),
            
            'rejected' => (new MailMessage)
                ->subject("Claim Rejected - {$businessName}")
                ->greeting("Hello {$notifiable->firstname},")
                ->line("Unfortunately, your claim for {$businessName} has been rejected.")
                ->line($this->reason ? "Reason: {$this->reason}" : "Please contact support for more information.")
                ->action('Contact Support', url('/contact')),
            
            'expired' => (new MailMessage)
                ->subject("Claim Expired - {$businessName}")
                ->greeting("Hello {$notifiable->firstname},")
                ->line("Your claim for {$businessName} has expired.")
                ->line("You can submit a new claim if you still wish to manage this business.")
                ->action('Submit New Claim', url("/places/{$this->claim->place->slug}")),
            
            default => (new MailMessage)
                ->subject("Claim Update - {$businessName}")
                ->greeting("Hello {$notifiable->firstname},")
                ->line("There's an update regarding your claim for {$businessName}.")
                ->action('View Details', url("/places/{$this->claim->place->slug}/claim"))
        };

        return $mail->line('Thank you for using our platform!');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        // Create app notification record
        AppNotification::createClaimNotification($this->claim, $this->status, $this->reason);

        return [
            'claim_id' => $this->claim->id,
            'place_id' => $this->claim->place_id,
            'place_name' => $this->claim->place->title,
            'status' => $this->status,
            'reason' => $this->reason,
        ];
    }
}