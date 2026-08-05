<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessFormImportJob;
use App\Models\Form;
use App\Models\FormImport;
use App\Services\FormSchemaValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FormImportController extends Controller
{
    public function __construct(private FormSchemaValidator $validator) {}

    public function index()
    {
        $imports = FormImport::where('user_id', auth()->id())
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('forms.imports.index', compact('imports'));
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:docx,xlsx|max:10240',
        ]);

        $file = $request->file('file');
        $ext = $file->getClientOriginalExtension();
        $path = $file->store('imports/' . auth()->id(), 'local');

        $import = FormImport::create([
            'user_id' => auth()->id(),
            'original_filename' => $file->getClientOriginalName(),
            'stored_path' => $path,
            'file_type' => $ext,
            'status' => 'pending',
        ]);

        ProcessFormImportJob::dispatch($import->id);

        return response()->json([
            'import_id' => $import->id,
            'status' => 'pending',
            'message' => 'File uploaded. Processing started.',
        ]);
    }

    public function status(int $importId)
    {
        $import = FormImport::where('id', $importId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        return response()->json([
            'id' => $import->id,
            'status' => $import->status,
            'schema' => $import->preview_schema,
            'error' => $import->error_message,
        ]);
    }

    public function preview(int $importId)
    {
        $import = FormImport::where('id', $importId)
            ->where('user_id', auth()->id())
            ->where('status', 'preview_ready')
            ->firstOrFail();

        return view('forms.imports.preview', compact('import'));
    }

    public function commit(Request $request, int $importId)
    {
        $import = FormImport::where('id', $importId)
            ->where('user_id', auth()->id())
            ->where('status', 'preview_ready')
            ->firstOrFail();

        $request->validate([
            'schema' => 'required|array',
            'title' => 'required|string|max:200',
        ]);

        $schema = $request->schema;
        $errors = $this->validator->validate($schema);
        if (!empty($errors)) {
            return back()->withErrors(['schema' => implode(', ', $errors)]);
        }

        $form = Form::create([
            'user_id' => auth()->id(),
            'title' => $request->title,
            'description' => $schema['description'] ?? '',
            'schema' => $schema,
            'settings' => ['submit_message' => 'Thank you for your submission!'],
        ]);

        $import->update(['status' => 'completed', 'form_id' => $form->id]);

        return redirect()->route('forms.builder', $form)
            ->with('success', 'Form imported successfully!');
    }
}
