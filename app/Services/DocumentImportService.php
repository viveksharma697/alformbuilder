<?php

namespace App\Services;

use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use PhpOffice\PhpSpreadsheet\IOFactory as SpreadsheetIOFactory;
use Illuminate\Support\Str;

class DocumentImportService
{
    public function __construct(
        private FormSchemaValidator $validator
    ) {}

    /**
     * Import a .docx file and convert to form schema.
     * Strategy: Parse deterministically (headings→sections, questions→fields),
     * then infer types from labels where ambiguous.
     */
    public function importDocx(string $filePath): array
    {
        $phpWord = WordIOFactory::load($filePath);
        $sections = [];
        $currentSection = ['id' => 'section_1', 'title' => 'General', 'fields' => []];
        $sectionIndex = 1;
        $fieldIndex = 1;
        $unparseable = [];

        foreach ($phpWord->getSections() as $docSection) {
            foreach ($docSection->getElements() as $element) {
                if ($element instanceof \PhpOffice\PhpWord\Element\TextRun ||
                    $element instanceof \PhpOffice\PhpWord\Element\Text) {
                    $text = $this->extractText($element);
                    if (!empty(trim($text))) {
                        $field = $this->textToField($text, $fieldIndex++);
                        if ($field) {
                            $currentSection['fields'][] = $field;
                        }
                    }
                } elseif ($element instanceof \PhpOffice\PhpWord\Element\Title) {
                    // Save current section, start new one
                    if (!empty($currentSection['fields'])) {
                        $sections[] = $currentSection;
                    }
                    $sectionIndex++;
                    $currentSection = [
                        'id' => 'section_' . $sectionIndex,
                        'title' => $this->extractText($element) ?: 'Section ' . $sectionIndex,
                        'fields' => [],
                    ];
                } elseif ($element instanceof \PhpOffice\PhpWord\Element\ListItem) {
                    // Add as option to last field, or as new field
                    $text = $this->extractText($element);
                    if (!empty($text)) {
                        $lastField = &$currentSection['fields'][count($currentSection['fields']) - 1] ?? null;
                        if ($lastField && in_array($lastField['type'], ['dropdown', 'radio', 'checkbox'])) {
                            $lastField['options'][] = ['value' => Str::slug($text), 'label' => $text];
                        } else {
                            $field = $this->textToField($text, $fieldIndex++);
                            if ($field) {
                                $currentSection['fields'][] = $field;
                            }
                        }
                        unset($lastField);
                    }
                } elseif ($element instanceof \PhpOffice\PhpWord\Element\Table) {
                    $tableFields = $this->parseTable($element, $fieldIndex);
                    $fieldIndex += count($tableFields);
                    $currentSection['fields'] = array_merge($currentSection['fields'], $tableFields);
                } else {
                    $class = get_class($element);
                    if (!in_array($class, $unparseable)) {
                        $unparseable[] = $class;
                    }
                }
            }
        }

        if (!empty($currentSection['fields'])) {
            $sections[] = $currentSection;
        }

        // Fallback: if no sections created at all, create one
        if (empty($sections)) {
            $sections = [['id' => 'section_1', 'title' => 'Imported Form', 'fields' => []]];
        }

        $schema = [
            'title' => 'Imported Form',
            'description' => 'Imported from Word document',
            'sections' => $sections,
            '_unparseable_elements' => $unparseable,
        ];

        return $this->validator->repair($schema);
    }

    /**
     * Import a .xlsx file and convert to form schema.
     * Supported layouts:
     *   Layout 1 (header-row): Row 1 = column headers → each header becomes a field
     *   Layout 2 (two-column): Column A = field label, Column B = field type (optional)
     */
    public function importXlsx(string $filePath): array
    {
        $spreadsheet = SpreadsheetIOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        // Detect layout
        $firstRow = array_values($rows)[0] ?? [];
        $layout = $this->detectExcelLayout($rows);

        $fields = [];
        $fieldIndex = 1;

        if ($layout === 'header-row') {
            // Row 1 = headers, each header is a field
            $headers = array_values($firstRow);
            foreach ($headers as $header) {
                if (!empty(trim((string) $header))) {
                    $fields[] = $this->labelToField(trim((string) $header), $fieldIndex++);
                }
            }
        } elseif ($layout === 'two-column') {
            // Col A = label, Col B = type hint (optional)
            $isFirst = true;
            foreach ($rows as $row) {
                if ($isFirst) { $isFirst = false; continue; } // skip header row
                $label = trim((string) ($row['A'] ?? ''));
                $typeHint = trim((string) ($row['B'] ?? ''));
                if (!empty($label)) {
                    $field = $this->labelToField($label, $fieldIndex++);
                    if (!empty($typeHint)) {
                        $field = $this->applyTypeHint($field, $typeHint);
                    }
                    $fields[] = $field;
                }
            }
        }

        $schema = [
            'title' => 'Imported Form',
            'description' => 'Imported from Excel file',
            'sections' => [
                ['id' => 'section_1', 'title' => 'Imported Fields', 'fields' => $fields],
            ],
            '_import_layout' => $layout,
        ];

        return $this->validator->repair($schema);
    }

    private function detectExcelLayout(array $rows): string
    {
        if (count($rows) < 2) return 'header-row';

        $firstRow = array_values($rows)[0] ?? [];
        $secondRow = array_values($rows)[1] ?? [];

        // If the second column of first row looks like a field type, it's two-column
        $colB = trim((string) ($firstRow['B'] ?? ''));
        if (in_array(strtolower($colB), ['type', 'field type', 'input type'])) {
            return 'two-column';
        }

        return 'header-row';
    }

    private function extractText($element): string
    {
        if (method_exists($element, 'getText')) {
            return $element->getText();
        }
        if (method_exists($element, 'getElements')) {
            $text = '';
            foreach ($element->getElements() as $child) {
                $text .= $this->extractText($child);
            }
            return $text;
        }
        return '';
    }

    private function parseTable(\PhpOffice\PhpWord\Element\Table $table, int &$fieldIndex): array
    {
        $fields = [];
        $rows = $table->getRows();
        if (empty($rows)) return $fields;

        // Treat first row as headers if it looks like a question row
        foreach ($rows as $row) {
            $cells = $row->getCells();
            if (empty($cells)) continue;

            $label = trim($this->extractText($cells[0]));
            if (!empty($label)) {
                $fields[] = $this->textToField($label, $fieldIndex++);
            }
        }

        return array_filter($fields);
    }

    private function textToField(string $text, int $index): ?array
    {
        $text = trim($text);
        if (empty($text)) return null;

        // Detect if it looks like a question (ends with ? or : or has typical question words)
        $isQuestion = str_ends_with($text, '?') || str_ends_with($text, ':') ||
            preg_match('/^(what|when|where|who|how|please|enter|provide|select|choose|describe|list|upload)/i', $text);

        if (!$isQuestion && strlen($text) < 3) {
            return null;
        }

        // Clean up label
        $label = rtrim($text, '?:');

        return $this->labelToField($label, $index);
    }

    private function labelToField(string $label, int $index): array
    {
        $type = $this->inferFieldType($label);
        $key = Str::snake(preg_replace('/[^a-zA-Z0-9\s]/', '', $label));
        $key = substr($key, 0, 50) ?: 'field_' . $index;

        $field = [
            'id' => 'field_' . $index,
            'type' => $type,
            'key' => $key,
            'label' => $label,
            'placeholder' => '',
            'help_text' => '',
            'required' => false,
            'default' => '',
            'options' => [],
            'validation' => (object) [],
        ];

        // Add options for choice types
        if (in_array($type, ['dropdown', 'radio', 'checkbox'])) {
            $field['options'] = [
                ['value' => 'option_1', 'label' => 'Option 1'],
                ['value' => 'option_2', 'label' => 'Option 2'],
            ];
        }

        if ($type === 'rating') {
            $field['max_rating'] = 5;
        }

        return $field;
    }

    private function applyTypeHint(array $field, string $hint): array
    {
        $map = [
            'text' => 'text', 'string' => 'text', 'short text' => 'text',
            'textarea' => 'textarea', 'long text' => 'textarea', 'paragraph' => 'textarea',
            'number' => 'number', 'integer' => 'number', 'numeric' => 'number',
            'email' => 'email', 'phone' => 'phone', 'mobile' => 'phone',
            'date' => 'date', 'dropdown' => 'dropdown', 'select' => 'dropdown',
            'radio' => 'radio', 'checkbox' => 'checkbox', 'file' => 'file_upload',
            'upload' => 'file_upload', 'url' => 'url', 'rating' => 'rating',
        ];

        $type = $map[strtolower($hint)] ?? null;
        if ($type) {
            $field['type'] = $type;
        }

        return $field;
    }

    private function inferFieldType(string $label): string
    {
        $lower = strtolower($label);
        if (str_contains($lower, 'email')) return 'email';
        if (preg_match('/phone|mobile|cell|tel/i', $lower)) return 'phone';
        if (preg_match('/date|birth|dob|when/i', $lower)) return 'date';
        if (preg_match('/url|website|link/i', $lower)) return 'url';
        if (preg_match('/describe|description|comment|note|message|feedback|summary|bio|about/i', $lower)) return 'textarea';
        if (preg_match('/number|amount|count|quantity|age|year|score|total/i', $lower)) return 'number';
        if (preg_match('/gender|sex|marital|status|type|category|level|country|state|city/i', $lower)) return 'dropdown';
        if (preg_match('/agree|consent|terms|accept/i', $lower)) return 'checkbox';
        if (preg_match('/rating|rate|satisfaction|stars/i', $lower)) return 'rating';
        if (preg_match('/resume|cv|upload|attach|document|file/i', $lower)) return 'file_upload';
        return 'text';
    }
}
