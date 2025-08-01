<?php

namespace App\Jobs;

use App\Models\Claim;
use App\Services\StripeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RefundVerificationFee implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $claim;

    /**
     * Create a new job instance.
     */
    public function __construct(Claim $claim)
    {
        $this->claim = $claim;
    }

    /**
     * Execute the job.
     */
    public function handle(StripeService $stripeService): void
    {
        try {
            // Check if fee should be refunded
            if (!$this->claim->shouldRefundFee()) {
                Log::info('Claim does not require fee refund', ['claim_id' => $this->claim->id]);
                return;
            }

            // Process refund
            $refund = $stripeService->refundVerificationFee($this->claim);
            
            Log::info('Verification fee refunded successfully', [
                'claim_id' => $this->claim->id,
                'refund_id' => $refund->id,
                'amount' => $refund->amount / 100,
            ]);

            // TODO: Send email notification to user about refund
            // Notification::send($this->claim->user, new VerificationFeeRefunded($this->claim, $refund));
            
        } catch (\Exception $e) {
            Log::error('Failed to refund verification fee', [
                'claim_id' => $this->claim->id,
                'error' => $e->getMessage(),
            ]);
            
            // Re-throw to mark job as failed
            throw $e;
        }
    }
}
