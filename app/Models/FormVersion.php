<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormVersion extends Model
{
    protected $fillable = [
        'form_id', 'user_id', 'version', 'label', 'schema', 'settings',
    ];

    protected $casts = [
        'schema' => 'array',
        'settings' => 'array',
        'version' => 'integer',
    ];

    public function form()
    {
        return $this->belongsTo(Form::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
