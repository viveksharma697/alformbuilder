<x-app-layout>
    @section('title', 'Responses')
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <a href="{{ route('forms.index') }}" class="text-sm text-gray-500 hover:text-gray-700">My Forms</a>
                <span class="text-gray-400 mx-2">/</span>
                <span class="text-sm text-gray-800 font-medium">{{ $form->title }}</span>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('forms.builder', $form) }}" class="text-sm text-indigo-600 hover:text-indigo-800">Edit Form</a>
                <a href="{{ route('forms.submissions.export', $form) }}"
                    class="bg-green-600 hover:bg-green-700 text-white text-sm px-4 py-2 rounded-lg font-medium flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Export CSV
                </a>
            </div>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">{{ $submissions->total() }} Responses</h2>
                <p class="text-sm text-gray-500">for "{{ $form->title }}"</p>
            </div>
            <form method="GET" class="flex items-center gap-2">
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="Search responses..."
                    class="text-sm px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                <button type="submit" class="text-sm text-white bg-gray-700 hover:bg-gray-800 px-3 py-2 rounded-lg">Search</button>
            </form>
        </div>

        @if($submissions->isEmpty())
            <div class="bg-white rounded-xl border border-gray-200 p-12 text-center">
                <p class="text-gray-500">No submissions yet.</p>
                @if($form->status === 'published')
                    <a href="{{ $form->public_url }}" target="_blank" class="mt-2 inline-block text-sm text-indigo-600 hover:underline">Share the form link →</a>
                @else
                    <p class="text-sm text-yellow-600 mt-2">Publish your form to start receiving responses.</p>
                @endif
            </div>
        @else
            @php
                $fields = array_filter($form->all_fields, fn($f) => $f['type'] !== 'section_heading');
                $headerFields = array_slice($fields, 0, 5); // show first 5 columns
            @endphp
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">#</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Submitted</th>
                                @foreach($headerFields as $field)
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">
                                        {{ Str::limit($field['label'], 20) }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($submissions as $sub)
                                <tr class="hover:bg-gray-50 cursor-pointer" onclick="toggleDetails({{ $sub->id }})">
                                    <td class="px-4 py-3 text-gray-500">{{ $sub->id }}</td>
                                    <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $sub->created_at->format('M d, Y H:i') }}</td>
                                    @foreach($headerFields as $field)
                                        <td class="px-4 py-3 text-gray-800 max-w-xs truncate">
                                            @php $val = $sub->data[$field['key']] ?? '—'; @endphp
                                            {{ is_array($val) ? implode(', ', $val) : Str::limit((string)$val, 40) }}
                                        </td>
                                    @endforeach
                                </tr>
                                <tr id="detail-{{ $sub->id }}" class="hidden bg-indigo-50">
                                    <td colspan="{{ count($headerFields) + 2 }}" class="px-6 py-4">
                                        <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                                            @foreach($form->all_fields as $field)
                                                @if($field['type'] !== 'section_heading')
                                                    <div>
                                                        <p class="text-xs font-medium text-gray-500">{{ $field['label'] }}</p>
                                                        <p class="text-sm text-gray-800">
                                                            @php $val = $sub->data[$field['key']] ?? null; @endphp
                                                            {{ is_array($val) ? implode(', ', $val) : ($val ?? '—') }}
                                                        </p>
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>
                                        <p class="text-xs text-gray-400 mt-3">IP: {{ $sub->ip_address }} | Form v{{ $sub->form_version }}</p>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="mt-4">{{ $submissions->links() }}</div>
        @endif
    </div>

    @push('scripts')
    <script>
    function toggleDetails(id) {
        const row = document.getElementById('detail-' + id);
        row.classList.toggle('hidden');
    }
    </script>
    @endpush
</x-app-layout>
