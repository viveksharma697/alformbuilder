<x-app-layout>
    @section('title', 'My Forms')
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">My Forms</h2>
            <div class="flex items-center gap-3">
                <a href="{{ route('imports.index') }}" class="text-sm text-gray-600 hover:text-gray-800 flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    Import
                </a>
                <a href="{{ route('forms.create') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-lg flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    New Form
                </a>
            </div>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        @if($forms->isEmpty())
            <div class="text-center py-20">
                <div class="w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No forms yet</h3>
                <p class="text-gray-500 mb-6">Create your first form manually or let AI generate one for you.</p>
                <a href="{{ route('forms.create') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-lg text-sm font-medium">Create Your First Form</a>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                @foreach($forms as $form)
                    <div class="bg-white rounded-xl border border-gray-200 hover:shadow-md transition-shadow">
                        <div class="p-5">
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex-1 min-w-0">
                                    <h3 class="font-semibold text-gray-900 truncate">{{ $form->title }}</h3>
                                    <p class="text-sm text-gray-500 mt-0.5 line-clamp-2">{{ $form->description ?: 'No description' }}</p>
                                </div>
                                <span class="ml-2 flex-shrink-0 px-2 py-0.5 rounded-full text-xs font-medium
                                    {{ $form->status === 'published' ? 'bg-green-100 text-green-700' : ($form->status === 'archived' ? 'bg-gray-100 text-gray-600' : 'bg-yellow-100 text-yellow-700') }}">
                                    {{ ucfirst($form->status) }}
                                </span>
                            </div>
                            <div class="flex items-center gap-4 text-xs text-gray-500 mb-4">
                                <span class="flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                    {{ $form->submissions_count }} responses
                                </span>
                                <span class="flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    v{{ $form->version }}
                                </span>
                                <span>{{ $form->updated_at->diffForHumans() }}</span>
                            </div>
                            <div class="flex items-center gap-2 border-t border-gray-100 pt-3">
                                <a href="{{ route('forms.builder', $form) }}" class="flex-1 text-center text-sm font-medium text-indigo-600 hover:text-indigo-800 py-1.5 rounded-lg hover:bg-indigo-50">
                                    Edit
                                </a>
                                <a href="{{ route('forms.submissions', $form) }}" class="flex-1 text-center text-sm font-medium text-gray-600 hover:text-gray-800 py-1.5 rounded-lg hover:bg-gray-50">
                                    Responses
                                </a>
                                @if($form->status === 'published')
                                    <a href="{{ $form->public_url }}" target="_blank" class="flex-1 text-center text-sm font-medium text-green-600 hover:text-green-800 py-1.5 rounded-lg hover:bg-green-50">
                                        Fill
                                    </a>
                                @endif
                                <form method="POST" action="{{ route('forms.destroy', $form) }}" onsubmit="return confirm('Delete this form?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="p-1.5 text-gray-400 hover:text-red-600 rounded-lg hover:bg-red-50">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="mt-6">{{ $forms->links() }}</div>
        @endif
    </div>
</x-app-layout>
