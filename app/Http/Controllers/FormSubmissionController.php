<?php

namespace App\Http\Controllers;

use App\Jobs\DispatchWebhookJob;
use App\Models\Form;
use App\Models\FormSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FormSubmissionController extends Controller
{
    public function fill(string $slug)
    {
        $form = Form::where('slug', $slug)
            ->where('status', 'published')
            ->where('accepts_submissions', true)
            ->firstOrFail();

        return view('forms.fill', compact('form'));
    }

    public function submit(Request $request, string $slug)
    {
        $form = Form::where('slug', $slug)
            ->where('status', 'published')
            ->where('accepts_submissions', true)
            ->firstOrFail();

        // Rate limiting per IP
        $key = 'form_submit:' . $form->id . ':' . $request->ip();
        if (cache()->get($key, 0) >= config('app.form_submission_rate_limit', 10)) {
            return back()->withErrors(['_' => 'Too many submissions. Please try again later.']);
        }

        // Server-side validation derived from schema
        $rules = $form->buildValidationRules();
        $validated = $request->validate($rules);

        // Handle file uploads
        $files = [];
        foreach ($form->all_fields as $field) {
            if ($field['type'] === 'file_upload' && $request->hasFile('fields.' . $field['key'])) {
                $path = $request->file('fields.' . $field['key'])->store('submissions/' . $form->id, 'local');
                $files[$field['key']] = $path;
                unset($validated['fields'][$field['key']]);
            }
        }

        $submission = FormSubmission::create([
            'form_id' => $form->id,
            'data' => $validated['fields'] ?? [],
            'files' => $files,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'form_version' => $form->version,
            'completed_at' => now(),
        ]);

        cache()->increment($key);
        cache()->put($key, cache()->get($key, 1), now()->addHour());

        // Dispatch webhooks
        foreach ($form->webhooks()->where('active', true)->get() as $webhook) {
            if (in_array('submission.created', $webhook->events)) {
                DispatchWebhookJob::dispatch($webhook->id, 'submission.created', [
                    'submission_id' => $submission->id,
                    'form_title' => $form->title,
                    'data' => $submission->data,
                    'submitted_at' => $submission->created_at->toIso8601String(),
                ]);
            }
        }

        $message = $form->settings['submit_message'] ?? 'Thank you for your submission!';
        $redirect = $form->settings['redirect_url'] ?? '';

        if ($redirect) {
            return redirect($redirect)->with('success', $message);
        }

        return back()->with('success', $message);
    }

    public function index(Form $form)
    {
        $this->authorize('view', $form);

        $query = $form->submissions()->orderByDesc('created_at');

        if (request('search')) {
            $search = request('search');
            $query->where(function ($q) use ($search) {
                $q->whereRaw("JSON_SEARCH(data, 'one', ?) IS NOT NULL", ["%$search%"]);
            });
        }

        $submissions = $query->paginate(20);

        return view('forms.submissions.index', compact('form', 'submissions'));
    }

    public function exportCsv(Form $form)
    {
        $this->authorize('view', $form);

        $fields = $form->all_fields;
        $fieldKeys = array_column(
            array_filter($fields, fn($f) => $f['type'] !== 'section_heading'),
            'key'
        );
        $fieldLabels = array_column(
            array_filter($fields, fn($f) => $f['type'] !== 'section_heading'),
            'label',
            'key'
        );

        $headers = array_merge(['Submission ID', 'Submitted At'], array_values($fieldLabels));

        $filename = 'submissions_' . $form->slug . '_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($form, $fieldKeys, $headers) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);

            $form->submissions()->orderByDesc('created_at')->chunk(200, function ($submissions) use ($handle, $fieldKeys) {
                foreach ($submissions as $sub) {
                    $row = [$sub->id, $sub->created_at->toDateTimeString()];
                    foreach ($fieldKeys as $key) {
                        $val = $sub->data[$key] ?? '';
                        if (is_array($val)) $val = implode(', ', $val);
                        $row[] = $val;
                    }
                    fputcsv($handle, $row);
                }
            });
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
