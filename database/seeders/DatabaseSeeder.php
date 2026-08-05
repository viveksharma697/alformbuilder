<?php

namespace Database\Seeders;

use App\Models\Form;
use App\Models\FormSubmission;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'demo@formbuilder.app'],
            [
                'name' => 'Demo User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        $forms = [
            ['title' => 'Job Application Form', 'description' => 'Apply for a position at our company', 'status' => 'published', 'schema' => $this->jobApplicationSchema()],
            ['title' => 'Customer Feedback Survey', 'description' => 'Help us improve our service', 'status' => 'published', 'schema' => $this->feedbackSurveySchema()],
            ['title' => 'Event Registration', 'description' => 'Register for our upcoming event', 'status' => 'draft', 'schema' => $this->eventRegistrationSchema()],
        ];

        foreach ($forms as $formData) {
            $form = Form::firstOrCreate(
                ['user_id' => $user->id, 'title' => $formData['title']],
                [
                    'description' => $formData['description'],
                    'status' => $formData['status'],
                    'schema' => $formData['schema'],
                    'settings' => ['submit_message' => 'Thank you for your submission!', 'redirect_url' => ''],
                    'accepts_submissions' => true,
                    'published_at' => $formData['status'] === 'published' ? now() : null,
                ]
            );

            if ($formData['status'] === 'published' && $form->submissions()->count() === 0) {
                for ($i = 0; $i < 5; $i++) {
                    FormSubmission::create([
                        'form_id' => $form->id,
                        'data' => $this->generateSampleData($formData['schema']),
                        'ip_address' => '127.0.0.' . ($i + 1),
                        'user_agent' => 'Mozilla/5.0 (Demo Browser)',
                        'form_version' => 1,
                        'completed_at' => now()->subDays($i),
                    ]);
                }
            }
        }

        $this->command->info('Seeded: demo@formbuilder.app / password — ' . count($forms) . ' sample forms.');
    }

    private function generateSampleData(array $schema): array
    {
        $data = [];
        foreach ($schema['sections'] ?? [] as $section) {
            foreach ($section['fields'] ?? [] as $field) {
                if ($field['type'] === 'section_heading') continue;
                $data[$field['key']] = match($field['type']) {
                    'email' => 'user' . rand(1, 99) . '@example.com',
                    'phone' => '+1-555-' . sprintf('%04d', rand(1000, 9999)),
                    'number' => rand(18, 65),
                    'date' => now()->subYears(rand(20, 40))->format('Y-m-d'),
                    'rating' => rand(3, 5),
                    'dropdown', 'radio' => $field['options'][0]['value'] ?? 'option_1',
                    'checkbox' => [$field['options'][0]['value'] ?? 'option_1'],
                    'textarea' => 'Sample response for ' . $field['label'] . '. This is demo data.',
                    default => 'Sample ' . $field['label'],
                };
            }
        }
        return $data;
    }

    private function jobApplicationSchema(): array
    {
        return ['title' => 'Job Application Form', 'description' => 'Please fill out all required fields', 'sections' => [['id' => 'section_personal', 'title' => 'Personal Information', 'fields' => [['id' => 'f1', 'type' => 'text', 'key' => 'full_name', 'label' => 'Full Name', 'placeholder' => 'John Doe', 'help_text' => '', 'required' => true, 'default' => '', 'options' => [], 'validation' => ['min_length' => 2, 'max_length' => 100]], ['id' => 'f2', 'type' => 'email', 'key' => 'email', 'label' => 'Email Address', 'placeholder' => 'john@example.com', 'help_text' => '', 'required' => true, 'default' => '', 'options' => [], 'validation' => []], ['id' => 'f3', 'type' => 'phone', 'key' => 'phone', 'label' => 'Phone Number', 'placeholder' => '+1-555-000-0000', 'help_text' => '', 'required' => true, 'default' => '', 'options' => [], 'validation' => []], ['id' => 'f4', 'type' => 'date', 'key' => 'date_of_birth', 'label' => 'Date of Birth', 'placeholder' => '', 'help_text' => '', 'required' => false, 'default' => '', 'options' => [], 'validation' => []]]], ['id' => 'section_professional', 'title' => 'Professional Details', 'fields' => [['id' => 'f5', 'type' => 'dropdown', 'key' => 'position', 'label' => 'Position Applied For', 'placeholder' => 'Select position', 'help_text' => '', 'required' => true, 'default' => '', 'options' => [['value' => 'engineer', 'label' => 'Software Engineer'], ['value' => 'designer', 'label' => 'UI Designer'], ['value' => 'manager', 'label' => 'Project Manager']], 'validation' => []], ['id' => 'f6', 'type' => 'number', 'key' => 'experience_years', 'label' => 'Years of Experience', 'placeholder' => '5', 'help_text' => '', 'required' => true, 'default' => '', 'options' => [], 'validation' => ['min' => 0, 'max' => 50]], ['id' => 'f7', 'type' => 'textarea', 'key' => 'cover_letter', 'label' => 'Cover Letter', 'placeholder' => 'Tell us about yourself...', 'help_text' => 'Max 500 words', 'required' => true, 'default' => '', 'options' => [], 'validation' => ['max_length' => 2000]], ['id' => 'f8', 'type' => 'file_upload', 'key' => 'resume', 'label' => 'Resume / CV', 'placeholder' => 'Upload your resume', 'help_text' => 'PDF or DOC, max 5MB', 'required' => true, 'default' => '', 'options' => [], 'validation' => ['max_size_kb' => 5120, 'allowed_types' => 'pdf,doc,docx']]]]]];
    }

    private function feedbackSurveySchema(): array
    {
        return ['title' => 'Customer Feedback Survey', 'description' => 'Your feedback helps us improve', 'sections' => [['id' => 'section_1', 'title' => 'Your Experience', 'fields' => [['id' => 'g1', 'type' => 'rating', 'key' => 'overall_rating', 'label' => 'Overall Satisfaction', 'placeholder' => '', 'help_text' => '1 = Very Dissatisfied, 5 = Very Satisfied', 'required' => true, 'default' => '', 'options' => [], 'validation' => [], 'max_rating' => 5], ['id' => 'g2', 'type' => 'radio', 'key' => 'would_recommend', 'label' => 'Would you recommend us?', 'placeholder' => '', 'help_text' => '', 'required' => true, 'default' => '', 'options' => [['value' => 'yes', 'label' => 'Yes'], ['value' => 'no', 'label' => 'No'], ['value' => 'maybe', 'label' => 'Maybe']], 'validation' => []], ['id' => 'g3', 'type' => 'checkbox', 'key' => 'features_used', 'label' => 'Which features did you use?', 'placeholder' => '', 'help_text' => '', 'required' => false, 'default' => '', 'options' => [['value' => 'form_builder', 'label' => 'Form Builder'], ['value' => 'ai_generation', 'label' => 'AI Generation'], ['value' => 'import', 'label' => 'Document Import']], 'validation' => []], ['id' => 'g4', 'type' => 'textarea', 'key' => 'comments', 'label' => 'Additional Comments', 'placeholder' => 'Any other feedback?', 'help_text' => '', 'required' => false, 'default' => '', 'options' => [], 'validation' => ['max_length' => 1000]], ['id' => 'g5', 'type' => 'email', 'key' => 'email', 'label' => 'Email (optional)', 'placeholder' => 'for follow-up', 'help_text' => 'We will not spam you', 'required' => false, 'default' => '', 'options' => [], 'validation' => []]]]]];
    }

    private function eventRegistrationSchema(): array
    {
        return ['title' => 'Event Registration', 'description' => 'Register for our upcoming tech conference', 'sections' => [['id' => 'section_1', 'title' => 'Registration Details', 'fields' => [['id' => 'e1', 'type' => 'text', 'key' => 'full_name', 'label' => 'Full Name', 'placeholder' => 'Your name', 'help_text' => '', 'required' => true, 'default' => '', 'options' => [], 'validation' => []], ['id' => 'e2', 'type' => 'email', 'key' => 'email', 'label' => 'Email', 'placeholder' => 'you@example.com', 'help_text' => '', 'required' => true, 'default' => '', 'options' => [], 'validation' => []], ['id' => 'e3', 'type' => 'dropdown', 'key' => 'ticket_type', 'label' => 'Ticket Type', 'placeholder' => 'Select', 'help_text' => '', 'required' => true, 'default' => '', 'options' => [['value' => 'standard', 'label' => 'Standard ($99)'], ['value' => 'vip', 'label' => 'VIP ($199)'], ['value' => 'student', 'label' => 'Student ($49)']], 'validation' => []], ['id' => 'e4', 'type' => 'checkbox', 'key' => 'dietary', 'label' => 'Dietary Requirements', 'placeholder' => '', 'help_text' => '', 'required' => false, 'default' => '', 'options' => [['value' => 'veg', 'label' => 'Vegetarian'], ['value' => 'vegan', 'label' => 'Vegan'], ['value' => 'gf', 'label' => 'Gluten Free']], 'validation' => []]]]]];
    }
}
