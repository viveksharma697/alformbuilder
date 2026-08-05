<?php

namespace App\Jobs;

use App\Models\FormImport;
use App\Services\DocumentImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessFormImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 180;

    public function __construct(
        public readonly int $importId
    ) {}

    public function handle(DocumentImportService $importService): void
    {
        $import = FormImport::findOrFail($this->importId);
        $import->update(['status' => 'processing']);

        try {
            $filePath = Storage::disk('local')->path($import->stored_path);

            $schema = match ($import->file_type) {
                'docx' => $importService->importDocx($filePath),
                'xlsx' => $importService->importXlsx($filePath),
            };

            $import->update([
                'status' => 'preview_ready',
                'preview_schema' => $schema,
            ]);

            Log::info('Form import processed', ['import_id' => $import->id, 'file_type' => $import->file_type]);
        } catch (\Throwable $e) {
            $import->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            Log::error('Form import failed', ['import_id' => $import->id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }
}
