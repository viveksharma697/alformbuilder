<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormSubmission extends Model
{
    protected $fillable = [
        'form_id', 'data', 'files', 'ip_address', 'user_agent', 'form_version', 'completed_at',
    ];

    protected $casts = [
        'data' => 'array',
        'files' => 'array',
        'completed_at' => 'datetime',
    ];

    public function form()
    {
        return $this->belongsTo(Form::class);
    }
}
