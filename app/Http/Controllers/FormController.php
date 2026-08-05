<?php

namespace App\Http\Controllers;

use App\Models\Form;
use App\Models\FormVersion;
use App\Services\FormSchemaValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FormController extends Controller
{
    public function __construct(private FormSchemaValidator $validator) {}

    public function index()
    {
        $forms = Form::owned(auth()->id())
            ->withCount('submissions')
            ->orderByDesc('updated_at')
            ->paginate(12);

        return view('forms.index', compact('forms'));
    }

    public function create()
    {
        return view('forms.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:200',
            'description' => 'nullable|string|max:1000',
        ]);

        $schema = [
            'title' => $request->title,
            'description' => $request->description ?? '',
            'sections' => [
                ['id' => 'section_1', 'title' => 'Section 1', 'fields' => []],
            ],
        ];

        $form = Form::create([
            'user_id' => auth()->id(),
            'title' => $request->title,
            'description' => $request->description,
            'schema' => $schema,
            'settings' => ['submit_message' => 'Thank you for your submission!', 'redirect_url' => ''],
        ]);

        return redirect()->route('forms.builder', $form);
    }

    public function show(Form $form)
    {
        $this->authorize('view', $form);
        return redirect()->route('forms.builder', $form);
    }

    public function builder(Form $form)
    {
        $this->authorize('view', $form);
        return view('forms.builder', compact('form'));
    }

    public function update(Request $request, Form $form)
    {
        $this->authorize('update', $form);

        $request->validate([
            'schema' => 'required|array',
            'title' => 'required|string|max:200',
            'description' => 'nullable|string|max:1000',
            'status' => 'sometimes|in:draft,published,archived',
            'settings' => 'nullable|array',
        ]);

        $schema = $request->schema;
        $errors = $this->validator->validate($schema);
        if (!empty($errors)) {
            return response()->json(['errors' => $errors], 422);
        }

        DB::transaction(function () use ($form, $request, $schema) {
            // Save current version before updating
            FormVersion::create([
                'form_id' => $form->id,
                'user_id' => auth()->id(),
                'version' => $form->version,
                'label' => 'Auto-save v' . $form->version,
                'schema' => $form->schema,
                'settings' => $form->settings,
            ]);

            $form->update([
                'title' => $request->title,
                'description' => $request->description,
                'schema' => $schema,
                'settings' => $request->settings ?? $form->settings,
                'status' => $request->status ?? $form->status,
                'version' => $form->version + 1,
                'published_at' => $request->status === 'published' && !$form->published_at ? now() : $form->published_at,
            ]);
        });

        return response()->json(['message' => 'Form saved', 'version' => $form->fresh()->version]);
    }

    public function destroy(Form $form)
    {
        $this->authorize('delete', $form);
        $form->delete();
        return redirect()->route('forms.index')->with('success', 'Form deleted.');
    }

    public function versions(Form $form)
    {
        $this->authorize('view', $form);
        $versions = $form->versions()->with('user')->paginate(20);
        return view('forms.versions', compact('form', 'versions'));
    }

    public function restoreVersion(Form $form, FormVersion $version)
    {
        $this->authorize('update', $form);
        abort_if($version->form_id !== $form->id, 404);

        DB::transaction(function () use ($form, $version) {
            FormVersion::create([
                'form_id' => $form->id,
                'user_id' => auth()->id(),
                'version' => $form->version,
                'label' => 'Before restore to v' . $version->version,
                'schema' => $form->schema,
                'settings' => $form->settings,
            ]);

            $form->update([
                'schema' => $version->schema,
                'settings' => $version->settings ?? $form->settings,
                'version' => $form->version + 1,
            ]);
        });

        return back()->with('success', 'Form restored to version ' . $version->version);
    }
}
