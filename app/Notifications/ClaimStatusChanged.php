<?php

namespace App\Notifications;

use App\Models\Claim;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ClaimStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    protected $claim;
    protected $action;
    protected $reason;

    /**
     * Create a new notification instance.
     */
    public function __construct(Claim $claim, string $action, ?string $reason = null)
    {
        $this->claim = $claim;
        $this->action = $action;
        $this->reason = $reason;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable)
    {
        $businessName = $this->claim->place->title ?? 'Unknown Business';
        
        switch ($this->action) {
            case 'approved':
                return (new MailMessage)
                    ->subject('Your business claim has been approved!')
                    ->greeting('Great news!')
                    ->line("Your claim for **{$businessName}** has been approved.")
                    ->line('You can now manage your business listing, respond to reviews, and access all owner features.')
                    ->action('Manage Your Business', url('/myplaces'))
                    ->line('Thank you for claiming your business!');
                    
            case 'rejected':
                return (new MailMessage)
                    ->subject('Your business claim needs attention')
                    ->greeting('Hello,')
                    ->line("Your claim for **{$businessName}** could not be approved.")
                    ->line($this->reason ? "Reason: {$this->reason}" : 'Please contact support for more information.')
                    ->line('You may submit a new claim with additional verification.')
                    ->action('Contact Support', url('/support'))
                    ->line('We\'re here to help if you have questions.');
                    
            case 'expired':
            case 'unclaimed':
                return (new MailMessage)
                    ->subject('Your business claim has been removed')
                    ->greeting('Hello,')
                    ->line("Your claim for **{$businessName}** has been removed from our system.")
                    ->line($this->reason ?? 'The claim was cancelled by an administrator.')
                    ->line('You may submit a new claim at any time.')
                    ->action('Browse Businesses', url('/places'))
                    ->line('Thank you for using our platform.');
                    
            default:
                return (new MailMessage)
                    ->subject('Business claim status update')
                    ->line("The status of your claim for **{$businessName}** has been updated.")
                    ->line("New status: {$this->action}")
                    ->action('View Details', url('/myplaces'));
        }
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable)
    {
        return [
            'claim_id' => $this->claim->id,
            'place_id' => $this->claim->place_id,
            'place_name' => $this->claim->place->title ?? 'Unknown Business',
            'action' => $this->action,
            'reason' => $this->reason,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}