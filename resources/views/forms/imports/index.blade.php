<x-app-layout>
    @section('title', 'Import Form')
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Import from Word / Excel</h2>
            <a href="{{ route('forms.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← My Forms</a>
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto py-6 px-4">
        {{-- Upload Card --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
            <h3 class="font-semibold text-gray-800 mb-2">Upload a Document</h3>
            <p class="text-sm text-gray-500 mb-4">
                Upload a <strong>.docx</strong> or <strong>.xlsx</strong> file and we'll convert it into an editable form.
                Word: headings become sections, questions become fields.
                Excel: header row layout or two-column layout (label | type).
            </p>

            <div id="upload-zone"
                class="border-2 border-dashed border-gray-300 rounded-xl p-8 text-center cursor-pointer hover:border-indigo-400 hover:bg-indigo-50 transition-colors"
                ondrop="handleDrop(event)" ondragover="event.preventDefault()">
                <svg class="w-10 h-10 text-gray-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <p class="text-sm font-medium text-gray-700 mb-1">Drag & drop or click to upload</p>
                <p class="text-xs text-gray-400">.docx, .xlsx — max 10MB</p>
                <input type="file" id="file-input" accept=".docx,.xlsx" class="hidden" onchange="uploadFile(this.files[0])">
                <button onclick="document.getElementById('file-input').click()"
                    class="mt-3 text-sm text-indigo-600 font-medium hover:text-indigo-800">Browse files</button>
            </div>

            <div id="upload-progress" class="hidden mt-4">
                <div class="flex items-center gap-3 bg-indigo-50 border border-indigo-200 rounded-lg p-3">
                    <svg class="w-5 h-5 text-indigo-600 animate-spin flex-shrink-0" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    <div>
                        <p class="text-sm font-medium text-indigo-800" id="upload-status">Uploading...</p>
                        <p class="text-xs text-indigo-600" id="upload-sub"></p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Import history --}}
        @if($imports->count() > 0)
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100">
                <h3 class="font-semibold text-gray-700 text-sm">Recent Imports</h3>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500">File</th>
                        <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500">Type</th>
                        <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500">Status</th>
                        <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500">Date</th>
                        <th class="px-4 py-2.5"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($imports as $import)
                    <tr>
                        <td class="px-4 py-2.5 text-gray-700 truncate max-w-[180px]">{{ $import->original_filename }}</td>
                        <td class="px-4 py-2.5 uppercase text-xs font-mono text-gray-500">{{ $import->file_type }}</td>
                        <td class="px-4 py-2.5">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium
                                {{ $import->status === 'completed' ? 'bg-green-100 text-green-700' : ($import->status === 'failed' ? 'bg-red-100 text-red-700' : ($import->status === 'preview_ready' ? 'bg-blue-100 text-blue-700' : 'bg-yellow-100 text-yellow-700')) }}">
                                {{ ucfirst(str_replace('_', ' ', $import->status)) }}
                            </span>
                        </td>
                        <td class="px-4 py-2.5 text-gray-500 text-xs">{{ $import->created_at->diffForHumans() }}</td>
                        <td class="px-4 py-2.5">
                            @if($import->status === 'preview_ready')
                                <a href="{{ route('imports.preview', $import->id) }}" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">
                                    Review →
                                </a>
                            @elseif($import->status === 'completed' && $import->form_id)
                                <a href="{{ route('forms.builder', $import->form_id) }}" class="text-xs text-green-600 hover:text-green-800 font-medium">
                                    Open Form →
                                </a>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="px-4 py-3">{{ $imports->links() }}</div>
        </div>
        @endif
    </div>

    @push('scripts')
    <script>
    function handleDrop(e) {
        e.preventDefault();
        const file = e.dataTransfer.files[0];
        if (file) uploadFile(file);
    }

    async function uploadFile(file) {
        if (!file) return;
        const allowed = ['application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                         'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
        if (!allowed.includes(file.type) && !file.name.match(/\.(docx|xlsx)$/i)) {
            alert('Please upload a .docx or .xlsx file');
            return;
        }

        const progress = document.getElementById('upload-progress');
        const status = document.getElementById('upload-status');
        const sub = document.getElementById('upload-sub');
        progress.classList.remove('hidden');
        status.textContent = 'Uploading ' + file.name + '...';

        const fd = new FormData();
        fd.append('file', file);
        fd.append('_token', '{{ csrf_token() }}');

        try {
            const res = await fetch('{{ route("imports.upload") }}', { method: 'POST', body: fd });
            const data = await res.json();
            if (!data.import_id) throw new Error(data.message || 'Upload failed');

            status.textContent = 'Processing...';
            sub.textContent = 'Parsing document structure';

            await pollImportStatus(data.import_id);
        } catch(e) {
            status.textContent = '❌ ' + e.message;
            sub.textContent = '';
        }
    }

    async function pollImportStatus(importId) {
        const status = document.getElementById('upload-status');
        const sub = document.getElementById('upload-sub');

        for (let i = 0; i < 60; i++) {
            await new Promise(r => setTimeout(r, 2000));
            const res = await fetch(`/imports/${importId}/status`);
            const data = await res.json();

            if (data.status === 'preview_ready') {
                status.textContent = '✅ Ready! Redirecting to preview...';
                setTimeout(() => window.location.href = `/imports/${importId}/preview`, 800);
                return;
            } else if (data.status === 'failed') {
                status.textContent = '❌ Failed: ' + (data.error || 'Unknown error');
                sub.textContent = '';
                return;
            }
            sub.textContent = `Attempt ${i+1}...`;
        }
        status.textContent = '❌ Timed out. Please try again.';
    }
    </script>
    @endpush
</x-app-layout>
