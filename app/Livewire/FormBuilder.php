<?php

namespace App\Livewire;

use App\Models\Form;
use App\Models\FormVersion;
use App\Services\FormSchemaValidator;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;

class FormBuilder extends Component
{
    public Form $form;
    public array $schema = [];
    public string $rawJson = '';
    public bool $showRawEditor = false;
    public bool $jsonValid = true;
    public array $jsonErrors = [];
    public ?string $editingFieldId = null;
    public array $editingField = [];
    public string $activeSection = 'section_1';
    public bool $saved = false;
    public string $saveMessage = '';

    protected FormSchemaValidator $validator;

    public function boot(FormSchemaValidator $validator): void
    {
        $this->validator = $validator;
    }

    public function mount(Form $form): void
    {
        $this->form = $form;
        $this->schema = $form->schema ?? $this->defaultSchema();
        $this->syncRawJson();
    }

    private function defaultSchema(): array
    {
        return [
            'title' => $this->form->title,
            'description' => $this->form->description ?? '',
            'sections' => [
                ['id' => 'section_1', 'title' => 'Section 1', 'fields' => []],
            ],
        ];
    }

    public function addField(string $type, string $sectionId): void
    {
        $id = 'field_' . Str::random(8);
        $label = ucwords(str_replace('_', ' ', $type));
        $field = [
            'id' => $id,
            'type' => $type,
            'key' => Str::snake($type) . '_' . substr($id, -4),
            'label' => $label,
            'placeholder' => '',
            'help_text' => '',
            'required' => false,
            'default' => '',
            'options' => in_array($type, ['dropdown', 'radio', 'checkbox']) ? [
                ['value' => 'option_1', 'label' => 'Option 1'],
                ['value' => 'option_2', 'label' => 'Option 2'],
            ] : [],
            'validation' => (object) [],
            'max_rating' => $type === 'rating' ? 5 : null,
        ];

        foreach ($this->schema['sections'] as &$section) {
            if ($section['id'] === $sectionId) {
                $section['fields'][] = $field;
                break;
            }
        }
        unset($section);

        $this->syncRawJson();
        $this->openFieldEditor($id);
    }

    public function addSection(): void
    {
        $id = 'section_' . Str::random(8);
        $this->schema['sections'][] = [
            'id' => $id,
            'title' => 'New Section',
            'fields' => [],
        ];
        $this->activeSection = $id;
        $this->syncRawJson();
    }

    public function removeSection(string $sectionId): void
    {
        $this->schema['sections'] = array_values(
            array_filter($this->schema['sections'], fn($s) => $s['id'] !== $sectionId)
        );
        if (empty($this->schema['sections'])) {
            $this->addSection();
        }
        $this->activeSection = $this->schema['sections'][0]['id'];
        $this->syncRawJson();
    }

    public function openFieldEditor(string $fieldId): void
    {
        $this->editingFieldId = $fieldId;
        foreach ($this->schema['sections'] as $section) {
            foreach ($section['fields'] as $field) {
                if ($field['id'] === $fieldId) {
                    $this->editingField = $field;
                    return;
                }
            }
        }
    }

    public function closeFieldEditor(): void
    {
        $this->editingFieldId = null;
        $this->editingField = [];
    }

    public function updateEditingField(): void
    {
        foreach ($this->schema['sections'] as &$section) {
            foreach ($section['fields'] as &$field) {
                if ($field['id'] === $this->editingFieldId) {
                    $field = array_merge($field, $this->editingField);
                    // Ensure key is valid snake_case
                    $field['key'] = Str::snake(preg_replace('/[^a-zA-Z0-9\s_]/', '', $field['key']));
                    break 2;
                }
            }
        }
        unset($section, $field);
        $this->syncRawJson();
    }

    public function duplicateField(string $fieldId, string $sectionId): void
    {
        foreach ($this->schema['sections'] as &$section) {
            if ($section['id'] !== $sectionId) continue;
            foreach ($section['fields'] as $i => $field) {
                if ($field['id'] === $fieldId) {
                    $copy = $field;
                    $copy['id'] = 'field_' . Str::random(8);
                    $copy['key'] = $field['key'] . '_copy';
                    $copy['label'] = $field['label'] . ' (Copy)';
                    array_splice($section['fields'], $i + 1, 0, [$copy]);
                    break 2;
                }
            }
        }
        unset($section);
        $this->syncRawJson();
    }

    public function deleteField(string $fieldId, string $sectionId): void
    {
        foreach ($this->schema['sections'] as &$section) {
            if ($section['id'] !== $sectionId) continue;
            $section['fields'] = array_values(
                array_filter($section['fields'], fn($f) => $f['id'] !== $fieldId)
            );
            break;
        }
        unset($section);

        if ($this->editingFieldId === $fieldId) {
            $this->closeFieldEditor();
        }
        $this->syncRawJson();
    }

    public function moveField(string $fieldId, string $sectionId, string $direction): void
    {
        foreach ($this->schema['sections'] as &$section) {
            if ($section['id'] !== $sectionId) continue;
            $fields = &$section['fields'];
            foreach ($fields as $i => $field) {
                if ($field['id'] !== $fieldId) continue;
                $target = $direction === 'up' ? $i - 1 : $i + 1;
                if ($target >= 0 && $target < count($fields)) {
                    [$fields[$i], $fields[$target]] = [$fields[$target], $fields[$i]];
                }
                break 2;
            }
        }
        unset($section, $fields);
        $this->syncRawJson();
    }

    public function reorderFields(array $fieldIds, string $sectionId): void
    {
        foreach ($this->schema['sections'] as &$section) {
            if ($section['id'] !== $sectionId) continue;
            $indexed = collect($section['fields'])->keyBy('id');
            $section['fields'] = array_values(
                array_filter(array_map(fn($id) => $indexed->get($id), $fieldIds))
            );
            break;
        }
        unset($section);
        $this->syncRawJson();
    }

    public function updateSectionTitle(string $sectionId, string $title): void
    {
        foreach ($this->schema['sections'] as &$section) {
            if ($section['id'] === $sectionId) {
                $section['title'] = $title;
                break;
            }
        }
        unset($section);
        $this->syncRawJson();
    }

    public function addOption(): void
    {
        $this->editingField['options'][] = [
            'value' => 'option_' . (count($this->editingField['options']) + 1),
            'label' => 'Option ' . (count($this->editingField['options']) + 1),
        ];
    }

    public function removeOption(int $index): void
    {
        array_splice($this->editingField['options'], $index, 1);
        $this->editingField['options'] = array_values($this->editingField['options']);
    }

    public function updateRawJson(string $json): void
    {
        $this->rawJson = $json;
        $decoded = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->jsonValid = false;
            $this->jsonErrors = ['Invalid JSON: ' . json_last_error_msg()];
            return;
        }

        $errors = $this->validator->validate($decoded);
        $this->jsonErrors = $errors;
        $this->jsonValid = empty($errors);

        if ($this->jsonValid) {
            $this->schema = $decoded;
        }
    }

    public function toggleRawEditor(): void
    {
        $this->showRawEditor = !$this->showRawEditor;
        if ($this->showRawEditor) {
            $this->syncRawJson();
        }
    }

    private function syncRawJson(): void
    {
        $this->rawJson = json_encode($this->schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    public function save(): void
    {
        $errors = $this->validator->validate($this->schema);
        if (!empty($errors)) {
            $this->jsonErrors = $errors;
            $this->saved = false;
            return;
        }

        FormVersion::create([
            'form_id' => $this->form->id,
            'user_id' => auth()->id(),
            'version' => $this->form->version,
            'label' => 'Auto-save v' . $this->form->version,
            'schema' => $this->form->schema,
            'settings' => $this->form->settings,
        ]);

        $this->form->update([
            'schema' => $this->schema,
            'title' => $this->schema['title'],
            'description' => $this->schema['description'] ?? '',
            'version' => $this->form->version + 1,
        ]);

        $this->saved = true;
        $this->saveMessage = 'Saved successfully!';
        $this->jsonErrors = [];

        $this->dispatch('form-saved');
    }

    public function publish(): void
    {
        $this->save();
        if ($this->saved) {
            $this->form->update([
                'status' => 'published',
                'accepts_submissions' => true,
                'published_at' => $this->form->published_at ?? now(),
            ]);
            $this->saveMessage = 'Form published!';
        }
    }

    #[On('field-reordered')]
    public function handleFieldReorder(array $fieldIds, string $sectionId): void
    {
        $this->reorderFields($fieldIds, $sectionId);
    }

    #[On('ai-schema-applied')]
    public function applyAiSchema(array $schema): void
    {
        $repaired = app(FormSchemaValidator::class)->repair($schema);
        $errors = $this->validator->validate($repaired);
        if (empty($errors)) {
            $this->schema = $repaired;
            $this->syncRawJson();
            $this->dispatch('form-saved');
        }
    }

    public function render()
    {
        return view('livewire.form-builder');
    }
}
