<x-app-layout>
    @section('title', 'Review Import')
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Review Imported Form</h2>
            <a href="{{ route('imports.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Back</a>
        </div>
    </x-slot>

    <div class="max-w-4xl mx-auto py-6 px-4" x-data="importPreview()">
        <div class="mb-4 bg-blue-50 border border-blue-200 rounded-lg p-4">
            <p class="text-sm text-blue-800">
                <strong>File:</strong> {{ $import->original_filename }} |
                <strong>Type:</strong> {{ strtoupper($import->file_type) }}
                @if(!empty($import->preview_schema['_import_layout']))
                    | <strong>Layout:</strong> {{ $import->preview_schema['_import_layout'] }}
                @endif
                @if(!empty($import->preview_schema['_unparseable_elements']))
                    | <strong class="text-orange-600">Skipped element types:</strong>
                    {{ implode(', ', array_map(fn($c) => class_basename($c), $import->preview_schema['_unparseable_elements'])) }}
                @endif
            </p>
        </div>

        <form method="POST" action="{{ route('imports.commit', $import->id) }}">
            @csrf
            <input type="hidden" name="schema" x-bind:value="JSON.stringify(schema)">

            <div class="bg-white rounded-xl border border-gray-200 p-5 mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Form Title</label>
                <input type="text" name="title" x-model="schema.title"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500" required>
            </div>

            @foreach($import->preview_schema['sections'] ?? [] as $si => $section)
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden mb-4">
                <div class="px-5 py-3 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
                    <input type="text" x-model="schema.sections[{{ $si }}].title"
                        class="text-sm font-semibold text-gray-700 bg-transparent border-0 focus:ring-0 px-0">
                    <span class="text-xs text-gray-400">{{ count($section['fields'] ?? []) }} fields</span>
                </div>

                <div class="p-5 space-y-4">
                    @foreach($section['fields'] ?? [] as $fi => $field)
                    <div class="border border-gray-200 rounded-lg p-3 hover:border-indigo-300 transition-colors">
                        <div class="flex items-start gap-3">
                            <div class="flex-1 grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Label</label>
                                    <input type="text"
                                        x-model="schema.sections[{{ $si }}].fields[{{ $fi }}].label"
                                        class="w-full text-sm px-2 py-1.5 border border-gray-300 rounded focus:ring-1 focus:ring-indigo-400">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Field Type</label>
                                    <select x-model="schema.sections[{{ $si }}].fields[{{ $fi }}].type"
                                        class="w-full text-sm px-2 py-1.5 border border-gray-300 rounded focus:ring-1 focus:ring-indigo-400">
                                        @foreach(['text','textarea','number','email','phone','date','dropdown','radio','checkbox','file_upload','rating','url','section_heading','password'] as $type)
                                            <option value="{{ $type }}" {{ ($field['type'] ?? 'text') === $type ? 'selected' : '' }}>
                                                {{ ucwords(str_replace('_', ' ', $type)) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <label class="flex items-center gap-1.5 text-xs text-gray-600 mt-5">
                                <input type="checkbox"
                                    x-model="schema.sections[{{ $si }}].fields[{{ $fi }}].required"
                                    class="rounded text-indigo-600">
                                Required
                            </label>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endforeach

            @error('schema')<div class="mb-4 text-red-600 text-sm bg-red-50 border border-red-200 rounded-lg p-3">{{ $message }}</div>@enderror

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('imports.index') }}" class="text-sm text-gray-500 hover:text-gray-700 px-4 py-2">Cancel</a>
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-6 py-2 rounded-lg">
                    Create Form from Import →
                </button>
            </div>
        </form>
    </div>

    @push('scripts')
    <script>
    function importPreview() {
        return {
            schema: @js($import->preview_schema),
        };
    }
    </script>
    @endpush
</x-app-layout>
