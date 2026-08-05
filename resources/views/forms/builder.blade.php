<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $form->title }} - Builder | {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-gray-100 font-sans antialiased">
    <nav class="bg-white border-b border-gray-200 px-4 py-2 flex items-center justify-between sticky top-0 z-50 shadow-sm">
        <div class="flex items-center gap-3">
            <a href="{{ route('forms.index') }}" class="text-gray-400 hover:text-gray-600 p-1">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div class="w-px h-5 bg-gray-200"></div>
            <span class="text-sm font-semibold text-gray-800">{{ $form->title }}</span>
            <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $form->status === 'published' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                {{ ucfirst($form->status) }}
            </span>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('forms.versions', $form) }}" class="text-xs text-gray-500 hover:text-gray-700 px-3 py-1.5 rounded-lg hover:bg-gray-100">
                History (v{{ $form->version }})
            </a>
            @if($form->status === 'published')
                <a href="{{ $form->public_url }}" target="_blank" class="text-xs text-green-600 hover:text-green-800 px-3 py-1.5 rounded-lg hover:bg-green-50 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    View Live
                </a>
            @endif
            <a href="{{ route('forms.submissions', $form) }}" class="text-xs text-gray-600 px-3 py-1.5 rounded-lg hover:bg-gray-100">
                Responses
            </a>
        </div>
    </nav>

    <livewire:form-builder :form="$form" />

    @livewireScripts
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.2/Sortable.min.js"></script>
</body>
</html>
