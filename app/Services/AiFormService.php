<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class AiFormService
{
    public function __construct(
        private FormSchemaValidator $validator
    ) {}

    public function generateSchema(string $prompt, ?array $existingSchema = null): array
    {
        $systemPrompt = $this->buildSystemPrompt();
        $userMessage = $this->buildUserMessage($prompt, $existingSchema);

        $startTime = microtime(true);

        try {
            $client = \OpenAI::factory()
                ->withApiKey(config('openai.api_key'))
                ->withBaseUri(config('openai.base_uri', 'openrouter.ai/api/v1'))
                ->withHttpHeader('HTTP-Referer', config('openai.site_url', 'http://localhost:8000'))
                ->withHttpHeader('X-Title', config('openai.site_name', 'AI Form Builder'))
                ->make();

            $response = $client->chat()->create([
                'model' => config('openai.model', 'meta-llama/llama-3.1-8b-instruct:free'),
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userMessage],
                ],
                'temperature' => 0.3,
                'max_tokens' => 4000,
            ]);

            $latencyMs = (int) ((microtime(true) - $startTime) * 1000);
            $content = $response->choices[0]->message->content;

            return [
                'schema' => $this->parseAndRepairSchema($content),
                'model' => $response->model,
                'prompt_tokens' => $response->usage->promptTokens,
                'completion_tokens' => $response->usage->completionTokens,
                'latency_ms' => $latencyMs,
            ];
        } catch (\Exception $e) {
            $latencyMs = (int) ((microtime(true) - $startTime) * 1000);
            Log::error('AI generation failed', ['error' => $e->getMessage(), 'prompt' => $prompt]);
            throw $e;
        }
    }

    private function parseAndRepairSchema(string $jsonContent): array
    {
        // Strip markdown code fences that free models often add
        $jsonContent = preg_replace('/^```(?:json)?\s*/m', '', $jsonContent);
        $jsonContent = preg_replace('/\s*```$/m', '', $jsonContent);
        $jsonContent = trim($jsonContent);

        // Try direct parse
        $decoded = json_decode($jsonContent, true);
        if (json_last_error() === JSON_ERROR_NONE && \is_array($decoded)) {
            $schema = $decoded['schema'] ?? $decoded;
            return $this->validator->repair($schema);
        }

        // Extract the first { ... } block (handles preamble text from chatty models)
        if (preg_match('/(\{.*\})/s', $jsonContent, $m)) {
            $decoded = json_decode($m[1], true);
            if (json_last_error() === JSON_ERROR_NONE && \is_array($decoded)) {
                $schema = $decoded['schema'] ?? $decoded;
                return $this->validator->repair($schema);
            }
        }

        throw new \RuntimeException('AI returned unparseable JSON: ' . substr($jsonContent, 0, 200));
    }

    private function buildSystemPrompt(): string
    {
        $allowedTypes = implode(', ', FormSchemaValidator::ALLOWED_TYPES);

        return <<<PROMPT
You are a form builder AI assistant. Generate form schemas as valid JSON.

OUTPUT CONTRACT:
Return a JSON object with a "schema" key containing the form schema.

The schema structure MUST follow this exact format:
{
  "schema": {
    "title": "Form Title",
    "description": "Brief description",
    "sections": [
      {
        "id": "section_1",
        "title": "Section Title",
        "fields": [
          {
            "id": "field_uuid",
            "type": "text",
            "key": "snake_case_key",
            "label": "Human Readable Label",
            "placeholder": "Helpful placeholder",
            "help_text": "Optional help text",
            "required": true,
            "default": "",
            "options": [],
            "validation": {}
          }
        ]
      }
    ]
  }
}

ALLOWED FIELD TYPES: $allowedTypes

FIELD TYPE RULES:
- dropdown/radio/checkbox: include "options" array with [{value: "...", label: "..."}, ...]
- number: use validation: {min: N, max: N}
- text/textarea: use validation: {min_length: N, max_length: N}
- file_upload: use validation: {max_size_kb: N, allowed_types: "pdf,doc,docx"}
- rating: include "max_rating": 5
- section_heading: only needs id, type, label — no key, no validation

IMPORTANT:
- All keys must be unique snake_case strings
- Never invent field types outside the allowed list
- Always include sensible placeholders and help text
- Make forms practical and user-friendly
- Output ONLY the raw JSON object. No markdown, no code fences, no explanation text before or after.
PROMPT;
    }

    private function buildUserMessage(string $prompt, ?array $existingSchema): string
    {
        if ($existingSchema !== null) {
            $schemaJson = json_encode($existingSchema, JSON_PRETTY_PRINT);
            return "Here is the EXISTING form schema:\n```json\n$schemaJson\n```\n\nApply this edit instruction to the schema: $prompt\n\nReturn the complete updated schema.";
        }
        return "Create a form for: $prompt";
    }
}
