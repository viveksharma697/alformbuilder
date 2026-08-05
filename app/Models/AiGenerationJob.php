<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiGenerationJob extends Model
{
    protected $fillable = [
        'user_id', 'form_id', 'type', 'prompt', 'status',
        'ai_model', 'prompt_tokens', 'completion_tokens', 'latency_ms',
        'retry_count', 'result_schema', 'error_message',
    ];

    protected $casts = [
        'result_schema' => 'array',
        'prompt_tokens' => 'integer',
        'completion_tokens' => 'integer',
        'latency_ms' => 'integer',
        'retry_count' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function form()
    {
        return $this->belongsTo(Form::class);
    }

    public function getTotalTokensAttribute(): int
    {
        return $this->prompt_tokens + $this->completion_tokens;
    }
}
