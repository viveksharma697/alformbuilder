<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Form extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'title', 'description', 'slug', 'status',
        'schema', 'settings', 'version', 'accepts_submissions', 'published_at',
    ];

    protected $casts = [
        'schema' => 'array',
        'settings' => 'array',
        'accepts_submissions' => 'boolean',
        'published_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (Form $form) {
            if (empty($form->slug)) {
                $form->slug = static::uniqueSlug($form->title);
            }
        });
    }

    public static function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: Str::random(8);
        $slug = $base;
        $i = 1;
        while (static::where('slug', $slug)->exists()) {
            $slug = "$base-$i";
            $i++;
        }
        return $slug;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function submissions()
    {
        return $this->hasMany(FormSubmission::class);
    }

    public function versions()
    {
        return $this->hasMany(FormVersion::class)->orderByDesc('version');
    }

    public function webhooks()
    {
        return $this->hasMany(Webhook::class);
    }

    public function aiJobs()
    {
        return $this->hasMany(AiGenerationJob::class);
    }

    public function getPublicUrlAttribute(): string
    {
        return route('forms.fill', $this->slug);
    }

    public function getAllFieldsAttribute(): array
    {
        $fields = [];
        $sections = $this->schema['sections'] ?? [];
        foreach ($sections as $section) {
            foreach ($section['fields'] ?? [] as $field) {
                $fields[] = $field;
            }
        }
        return $fields;
    }

    public function buildValidationRules(): array
    {
        $rules = [];
        foreach ($this->all_fields as $field) {
            if (in_array($field['type'], ['section_heading'])) {
                continue;
            }
            $fieldRules = [];
            if (!empty($field['required'])) {
                $fieldRules[] = 'required';
            } else {
                $fieldRules[] = 'nullable';
            }
            $fieldRules = array_merge($fieldRules, $this->buildFieldRules($field));
            $rules['fields.' . $field['key']] = $fieldRules;
        }
        return $rules;
    }

    private function buildFieldRules(array $field): array
    {
        $rules = [];
        $v = $field['validation'] ?? [];
        switch ($field['type']) {
            case 'email':
                $rules[] = 'email';
                break;
            case 'number':
                $rules[] = 'numeric';
                if (isset($v['min'])) $rules[] = 'min:' . $v['min'];
                if (isset($v['max'])) $rules[] = 'max:' . $v['max'];
                break;
            case 'text':
            case 'textarea':
                if (isset($v['min_length'])) $rules[] = 'min:' . $v['min_length'];
                if (isset($v['max_length'])) $rules[] = 'max:' . $v['max_length'];
                if (isset($v['regex'])) $rules[] = 'regex:/' . $v['regex'] . '/';
                break;
            case 'url':
                $rules[] = 'url';
                break;
            case 'date':
                $rules[] = 'date';
                if (isset($v['min_date'])) $rules[] = 'after_or_equal:' . $v['min_date'];
                if (isset($v['max_date'])) $rules[] = 'before_or_equal:' . $v['max_date'];
                break;
            case 'file_upload':
                $rules[] = 'file';
                if (isset($v['max_size_kb'])) $rules[] = 'max:' . $v['max_size_kb'];
                if (isset($v['allowed_types'])) $rules[] = 'mimes:' . $v['allowed_types'];
                break;
            case 'dropdown':
            case 'radio':
                $options = array_column($field['options'] ?? [], 'value');
                if (!empty($options)) $rules[] = 'in:' . implode(',', $options);
                break;
            case 'checkbox':
                $rules[] = 'array';
                break;
            case 'rating':
                $rules[] = 'integer';
                $rules[] = 'min:1';
                $rules[] = 'max:' . ($field['max_rating'] ?? 5);
                break;
            case 'phone':
                if (isset($v['regex'])) {
                    $rules[] = 'regex:/' . $v['regex'] . '/';
                }
                break;
        }
        return $rules;
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeOwned($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
