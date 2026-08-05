<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateAiFormJob;
use App\Models\AiGenerationJob;
use App\Models\Form;
use App\Services\AiFormService;
use App\Services\FormSchemaValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AiFormController extends Controller
{
    public function __construct(private FormSchemaValidator $validator) {}

    public function createFromPrompt(Request $request)
    {
        $request->validate([
            'prompt' => 'required|string|min:10|max:1000',
        ]);

        $aiJob = AiGenerationJob::create([
            'user_id' => auth()->id(),
            'type' => 'create',
            'prompt' => $request->prompt,
            'status' => 'processing',
        ]);

        $startTime = microtime(true);

        try {
            $aiService = app(AiFormService::class);
            $result = $aiService->generateSchema($request->prompt);
            $schema = $result['schema'];

            $errors = $this->validator->validate($schema);
            if (!empty($errors)) {
                $schema = $this->validator->repair($schema);
            }

            $aiJob->update([
                'status' => 'completed',
                'result_schema' => $schema,
                'ai_model' => $result['model'],
                'prompt_tokens' => $result['prompt_tokens'],
                'completion_tokens' => $result['completion_tokens'],
                'latency_ms' => $result['latency_ms'],
            ]);

            // Create the form immediately with the AI schema
            $form = Form::create([
                'user_id' => auth()->id(),
                'title' => $schema['title'] ?? 'AI Generated Form',
                'description' => $schema['description'] ?? '',
                'schema' => $schema,
                'settings' => ['submit_message' => 'Thank you for your submission!', 'redirect_url' => ''],
            ]);

            return response()->json([
                'status' => 'completed',
                'form_id' => $form->id,
                'redirect' => route('forms.builder', $form),
            ]);
        } catch (\Throwable $e) {
            $aiJob->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'latency_ms' => (int) ((microtime(true) - $startTime) * 1000),
            ]);

            return response()->json([
                'status' => 'failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function editWithAi(Request $request, Form $form)
    {
        $this->authorize('update', $form);

        $request->validate([
            'prompt' => 'required|string|min:5|max:1000',
        ]);

        $aiJob = AiGenerationJob::create([
            'user_id' => auth()->id(),
            'form_id' => $form->id,
            'type' => 'edit',
            'prompt' => $request->prompt,
            'status' => 'pending',
        ]);

        GenerateAiFormJob::dispatch($aiJob->id);

        return response()->json([
            'job_id' => $aiJob->id,
            'status' => 'pending',
        ]);
    }

    public function jobStatus(int $jobId)
    {
        $aiJob = AiGenerationJob::where('id', $jobId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $response = [
            'id' => $aiJob->id,
            'status' => $aiJob->status,
            'type' => $aiJob->type,
            'error' => $aiJob->error_message,
        ];

        if ($aiJob->status === 'completed') {
            $response['schema'] = $aiJob->result_schema;
            $response['model'] = $aiJob->ai_model;
            $response['tokens'] = $aiJob->total_tokens;
            $response['latency_ms'] = $aiJob->latency_ms;
        }

        return response()->json($response);
    }

    public function applyToForm(Request $request, Form $form)
    {
        $this->authorize('update', $form);

        $request->validate([
            'ai_job_id' => 'required|integer|exists:ai_generation_jobs,id',
        ]);

        $aiJob = AiGenerationJob::where('id', $request->ai_job_id)
            ->where('user_id', auth()->id())
            ->where('status', 'completed')
            ->firstOrFail();

        $schema = $aiJob->result_schema;
        $errors = $this->validator->validate($schema);
        if (!empty($errors)) {
            return response()->json(['errors' => $errors], 422);
        }

        DB::transaction(function () use ($form, $schema, $aiJob) {
            \App\Models\FormVersion::create([
                'form_id' => $form->id,
                'user_id' => auth()->id(),
                'version' => $form->version,
                'label' => 'Before AI edit: ' . substr($aiJob->prompt, 0, 50),
                'schema' => $form->schema,
                'settings' => $form->settings,
            ]);

            $form->update([
                'schema' => $schema,
                'title' => $schema['title'] ?? $form->title,
                'description' => $schema['description'] ?? $form->description,
                'version' => $form->version + 1,
            ]);
        });

        return response()->json([
            'message' => 'Schema applied successfully',
            'form' => ['id' => $form->id, 'schema' => $form->fresh()->schema],
        ]);
    }
}
