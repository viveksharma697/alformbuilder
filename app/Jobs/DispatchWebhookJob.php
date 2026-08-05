<?php

namespace App\Jobs;

use App\Models\Webhook;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DispatchWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly int $webhookId,
        public readonly string $event,
        public readonly array $payload
    ) {}

    public function handle(): void
    {
        $webhook = Webhook::find($this->webhookId);
        if (!$webhook || !$webhook->active) {
            return;
        }

        $body = json_encode([
            'event' => $this->event,
            'form_id' => $webhook->form_id,
            'payload' => $this->payload,
            'timestamp' => now()->toIso8601String(),
        ]);

        $headers = ['Content-Type' => 'application/json'];
        if ($webhook->secret) {
            $signature = hash_hmac('sha256', $body, $webhook->secret);
            $headers['X-Form-Builder-Signature'] = 'sha256=' . $signature;
        }

        try {
            $response = Http::withHeaders($headers)->timeout(10)->post($webhook->url, json_decode($body, true));

            if ($response->successful()) {
                $webhook->update([
                    'last_triggered_at' => now(),
                    'failure_count' => 0,
                ]);
            } else {
                $webhook->increment('failure_count');
                Log::warning('Webhook delivery failed', [
                    'webhook_id' => $webhook->id,
                    'status' => $response->status(),
                ]);
                // Disable after 10 consecutive failures
                if ($webhook->failure_count >= 10) {
                    $webhook->update(['active' => false]);
                }
                throw new \RuntimeException("Webhook returned HTTP {$response->status()}");
            }
        } catch (\Exception $e) {
            $webhook->increment('failure_count');
            throw $e;
        }
    }
}
