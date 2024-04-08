<?php

namespace App\Jobs;

use AmoCRM\Exceptions\AmoCRMApiException;
use AmoCRM\Exceptions\AmoCRMMissedTokenException;
use App\DTO\AmoWebhookDto;
use App\Exceptions\NotAmoAccountIdException;
use App\Services\ProfitCalculationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessAmoWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $webhook;

    /**
     * Create a new job instance.
     */
    public function __construct($webhook)
    {
        $this->webhook = $webhook;
    }

    /**
     * Execute the job.
     */
    public function handle(ProfitCalculationService $profitCalculationService): void
    {
        try {
            $profitCalculationService->handleWebhook(AmoWebhookDto::make($this->webhook));
        } catch (AmoCRMMissedTokenException|NotAmoAccountIdException|AmoCRMApiException $e) {
            Log::error($e->getMessage());
        }
    }
}
