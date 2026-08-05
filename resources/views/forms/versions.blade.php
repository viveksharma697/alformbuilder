<x-app-layout>
    @section('title', 'Version History')
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('forms.builder', $form) }}" class="text-indigo-600 hover:text-indigo-800 text-sm">← Back to Builder</a>
            <span class="text-gray-300">|</span>
            <h2 class="text-xl font-semibold text-gray-800">Version History — {{ $form->title }}</h2>
        </div>
    </x-slot>

    <div class="max-w-4xl mx-auto py-6 px-4">
        <p class="text-sm text-gray-500 mb-4">Current version: <strong>v{{ $form->version }}</strong>. Click "Restore" to roll back to a previous version.</p>

        @if($versions->isEmpty())
            <div class="bg-white rounded-xl border p-8 text-center text-gray-400">
                No previous versions saved yet. Versions are saved automatically each time you save the form.
            </div>
        @else
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Version</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Label</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Saved By</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($versions as $version)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-mono text-indigo-600">v{{ $version->version }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $version->label ?: '—' }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ $version->user->name ?? 'Unknown' }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ $version->created_at->format('M d, Y H:i') }}</td>
                                <td class="px-4 py-3">
                                    <form method="POST" action="{{ route('forms.versions.restore', [$form, $version]) }}"
                                          onsubmit="return confirm('Restore to v{{ $version->version }}? Current state will be saved.')">
                                        @csrf
                                        <button type="submit" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">
                                            Restore
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $versions->links() }}</div>
        @endif
    </div>
</x-app-layout>
