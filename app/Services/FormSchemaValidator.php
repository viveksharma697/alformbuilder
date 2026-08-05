<?php

namespace App\Services;

class FormSchemaValidator
{
    const ALLOWED_TYPES = [
        'text', 'textarea', 'number', 'email', 'phone', 'date',
        'dropdown', 'radio', 'checkbox', 'file_upload',
        'section_heading', 'rating', 'url', 'password',
    ];

    public function validate(array $schema): array
    {
        $errors = [];

        if (empty($schema['title'])) {
            $errors[] = 'Schema must have a title.';
        }

        if (!isset($schema['sections']) || !is_array($schema['sections'])) {
            $errors[] = 'Schema must have a sections array.';
            return $errors;
        }

        $seenKeys = [];
        foreach ($schema['sections'] as $si => $section) {
            if (empty($section['fields']) || !is_array($section['fields'])) {
                continue;
            }
            foreach ($section['fields'] as $fi => $field) {
                $loc = "sections[$si].fields[$fi]";
                if (empty($field['type'])) {
                    $errors[] = "$loc: field type is required.";
                } elseif (!in_array($field['type'], self::ALLOWED_TYPES)) {
                    $errors[] = "$loc: unknown field type '{$field['type']}'.";
                }
                if (empty($field['key'])) {
                    $errors[] = "$loc: field key is required.";
                } elseif (isset($seenKeys[$field['key']])) {
                    $errors[] = "$loc: duplicate field key '{$field['key']}'.";
                } else {
                    $seenKeys[$field['key']] = true;
                }
                if (empty($field['label'])) {
                    $errors[] = "$loc: field label is required.";
                }
            }
        }

        return $errors;
    }

    public function repair(array $schema): array
    {
        // Ensure required top-level keys
        $schema['title'] = $schema['title'] ?? 'Untitled Form';
        $schema['description'] = $schema['description'] ?? '';
        $schema['sections'] = $schema['sections'] ?? [];

        $seenKeys = [];
        foreach ($schema['sections'] as $si => &$section) {
            $section['id'] = $section['id'] ?? 'section_' . ($si + 1);
            $section['title'] = $section['title'] ?? 'Section ' . ($si + 1);
            $section['fields'] = $section['fields'] ?? [];

            foreach ($section['fields'] as $fi => &$field) {
                // Sanitize field type
                if (empty($field['type']) || !in_array($field['type'], self::ALLOWED_TYPES)) {
                    $field['type'] = $this->inferType($field);
                }

                // Ensure key
                if (empty($field['key'])) {
                    $base = \Illuminate\Support\Str::snake($field['label'] ?? 'field_' . $fi);
                    $field['key'] = $base;
                }
                // Deduplicate key
                $key = $field['key'];
                if (isset($seenKeys[$key])) {
                    $field['key'] = $key . '_' . $fi;
                }
                $seenKeys[$field['key']] = true;

                $field['id'] = $field['id'] ?? 'field_' . uniqid();
                $field['label'] = $field['label'] ?? ucwords(str_replace('_', ' ', $field['key']));
                $field['placeholder'] = $field['placeholder'] ?? '';
                $field['help_text'] = $field['help_text'] ?? '';
                $field['required'] = $field['required'] ?? false;
                $field['validation'] = $field['validation'] ?? (object) [];
                $field['options'] = $field['options'] ?? [];
            }
            unset($field);
        }
        unset($section);

        return $schema;
    }

    private function inferType(array $field): string
    {
        $label = strtolower($field['label'] ?? '');
        if (str_contains($label, 'email')) return 'email';
        if (str_contains($label, 'phone') || str_contains($label, 'mobile')) return 'phone';
        if (str_contains($label, 'date') || str_contains($label, 'birth')) return 'date';
        if (str_contains($label, 'url') || str_contains($label, 'website')) return 'url';
        if (!empty($field['options'])) return 'dropdown';
        return 'text';
    }
}
