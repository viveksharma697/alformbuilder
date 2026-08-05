<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormImport extends Model
{
    protected $fillable = [
        'user_id', 'form_id', 'original_filename', 'stored_path',
        'file_type', 'status', 'preview_schema', 'mapping', 'error_message',
    ];

    protected $casts = [
        'preview_schema' => 'array',
        'mapping' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function form()
    {
        return $this->belongsTo(Form::class);
    }
}
