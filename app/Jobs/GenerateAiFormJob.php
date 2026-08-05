<?php

namespace App\Jobs;

use App\Models\AiGenerationJob;
use App\Models\Form;
use App\Services\AiFormService;
use App\Services\FormSchemaValidator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateAiFormJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(
        public readonly int $aiJobId
    ) {}

    public function handle(AiFormService $aiService, FormSchemaValidator $validator): void
    {
        $aiJob = AiGenerationJob::findOrFail($this->aiJobId);
        $aiJob->update(['status' => 'processing']);

        try {
            $existingSchema = null;
            if ($aiJob->form_id && $aiJob->type === 'edit') {
                $form = Form::find($aiJob->form_id);
                $existingSchema = $form?->schema;
            }

            $result = $aiService->generateSchema($aiJob->prompt, $existingSchema);
            $schema = $result['schema'];

            // Validate schema
            $errors = $validator->validate($schema);
            if (!empty($errors)) {
                // Attempt repair
                $schema = $validator->repair($schema);
                $errors = $validator->validate($schema);
                if (!empty($errors)) {
                    throw new \RuntimeException('Schema validation failed after repair: ' . implode('; ', $errors));
                }
            }

            $aiJob->update([
                'status' => 'completed',
                'result_schema' => $schema,
                'ai_model' => $result['model'],
                'prompt_tokens' => $result['prompt_tokens'],
                'completion_tokens' => $result['completion_tokens'],
                'latency_ms' => $result['latency_ms'],
            ]);

            Log::info('AI form generated', [
                'ai_job_id' => $aiJob->id,
                'model' => $result['model'],
                'tokens' => $result['prompt_tokens'] + $result['completion_tokens'],
                'latency_ms' => $result['latency_ms'],
            ]);
        } catch (\Throwable $e) {
            $aiJob->increment('retry_count');
            $aiJob->update([
                'status' => $this->attempts() >= $this->tries ? 'failed' : 'pending',
                'error_message' => $e->getMessage(),
            ]);
            Log::error('AI form generation failed', [
                'ai_job_id' => $aiJob->id,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
