<x-app-layout>
    @section('title', 'New Form')
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Create New Form</h2>
    </x-slot>

    <div class="max-w-2xl mx-auto py-8 px-4">
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <form method="POST" action="{{ route('forms.store') }}">
                @csrf
                <div class="mb-5">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Form Title *</label>
                    <input type="text" name="title" value="{{ old('title') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                        placeholder="e.g., Job Application Form" required maxlength="200" autofocus>
                    @error('title')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Description (optional)</label>
                    <textarea name="description" rows="3"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                        placeholder="What is this form for?">{{ old('description') }}</textarea>
                </div>
                <div class="flex items-center justify-between">
                    <a href="{{ route('forms.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Cancel</a>
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 rounded-lg text-sm font-medium">
                        Create &amp; Open Builder →
                    </button>
                </div>
            </form>
        </div>

        <div class="mt-6 bg-gradient-to-r from-purple-50 to-indigo-50 rounded-xl border border-indigo-100 p-5">
            <h3 class="font-medium text-gray-800 mb-2 flex items-center gap-2">
                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                Or generate with AI
            </h3>
            <p class="text-sm text-gray-600 mb-3">Describe what kind of form you need and AI will build it instantly.</p>
            <div class="flex gap-2">
                <input type="text" id="ai-prompt-input" placeholder="e.g., internship application with skills and resume upload"
                    class="flex-1 px-3 py-2 border border-indigo-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 bg-white">
                <button onclick="startAiGeneration()" id="ai-gen-btn"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium whitespace-nowrap">
                    Generate
                </button>
            </div>
            <div id="ai-status" class="hidden mt-3 text-sm text-indigo-700 flex items-center gap-2">
                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                Generating your form...
            </div>
        </div>

        <div class="mt-4 bg-white rounded-xl border border-gray-200 p-5">
            <h3 class="font-medium text-gray-800 mb-2 flex items-center gap-2">
                <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                Or import from Word / Excel
            </h3>
            <p class="text-sm text-gray-600 mb-3">Upload a .docx or .xlsx file to convert it into an editable form.</p>
            <a href="{{ route('imports.index') }}" class="text-sm text-indigo-600 font-medium hover:underline">Go to Import →</a>
        </div>
    </div>

    @push('scripts')
    <script>
    async function startAiGeneration() {
        const prompt = document.getElementById('ai-prompt-input').value.trim();
        if (!prompt || prompt.length < 10) {
            alert('Please enter a more detailed description (at least 10 characters).');
            return;
        }

        const btn = document.getElementById('ai-gen-btn');
        const status = document.getElementById('ai-status');
        btn.disabled = true;
        status.classList.remove('hidden');

        try {
            const res = await fetch('{{ route("ai.generate") }}', {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
                body: JSON.stringify({ prompt })
            });
            const data = await res.json();
            if (!data.job_id) throw new Error('No job ID returned');

            // Poll for completion
            await pollJobStatus(data.job_id);
        } catch(e) {
            status.classList.add('hidden');
            btn.disabled = false;
            alert('Failed to start AI generation. Is your API key configured?');
        }
    }

    async function pollJobStatus(jobId) {
        const status = document.getElementById('ai-status');

        for (let i = 0; i < 60; i++) {
            await new Promise(r => setTimeout(r, 2000));
            const res = await fetch(`/ai/status/${jobId}`);
            const data = await res.json();

            if (data.status === 'completed') {
                // Create form with the AI schema and redirect to builder
                const formRes = await fetch('{{ route("forms.store") }}', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
                    body: JSON.stringify({
                        title: data.schema?.title || 'AI Generated Form',
                        description: data.schema?.description || '',
                        _from_ai: jobId
                    })
                });
                // redirect handled by server
                window.location.href = '{{ route("forms.index") }}?ai_job=' + jobId;
                return;
            } else if (data.status === 'failed') {
                status.innerHTML = '<span class="text-red-600">Generation failed: ' + (data.error || 'Unknown error') + '</span>';
                document.getElementById('ai-gen-btn').disabled = false;
                return;
            }
        }

        status.innerHTML = '<span class="text-red-600">Timed out. Please try again.</span>';
        document.getElementById('ai-gen-btn').disabled = false;
    }
    </script>
    @endpush
</x-app-layout>
