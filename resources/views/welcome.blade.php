<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Form Builder</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 min-h-screen">

    <!-- Nav -->
    <nav class="bg-white border-b border-gray-200">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center gap-2">
                    <svg class="w-7 h-7 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <span class="text-xl font-bold text-gray-900">AI Form Builder</span>
                </div>
                <div class="flex items-center gap-4">
                    @auth
                        <a href="{{ route('forms.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">My Forms</a>
                    @else
                        <a href="{{ route('login') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900">Log in</a>
                        <a href="{{ route('register') }}" class="bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-indigo-700">Get Started</a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero -->
    <section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pt-20 pb-16 text-center">
        <span class="inline-block bg-indigo-100 text-indigo-700 text-xs font-semibold px-3 py-1 rounded-full mb-6 uppercase tracking-wide">AI-Powered</span>
        <h1 class="text-5xl font-extrabold text-gray-900 leading-tight mb-6">
            Build forms in seconds<br>with the power of AI
        </h1>
        <p class="text-xl text-gray-500 max-w-2xl mx-auto mb-10">
            Describe what you need. Let AI generate the form. Drag, drop, and customise. Collect submissions — all from one place.
        </p>
        <div class="flex flex-wrap justify-center gap-4">
            <a href="{{ route('register') }}" class="bg-indigo-600 text-white px-8 py-3 rounded-xl text-base font-semibold hover:bg-indigo-700 shadow-md">
                Start building for free
            </a>
            <a href="{{ route('login') }}" class="bg-white text-gray-700 border border-gray-300 px-8 py-3 rounded-xl text-base font-semibold hover:bg-gray-50">
                Try demo &rarr; demo@formbuilder.app / password
            </a>
        </div>
    </section>

    <!-- Features -->
    <section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pb-20">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">

            <div class="bg-white rounded-2xl border border-gray-200 p-7 shadow-sm">
                <div class="w-11 h-11 bg-indigo-100 rounded-xl flex items-center justify-center mb-5">
                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">AI Generation</h3>
                <p class="text-gray-500 text-sm leading-relaxed">Type a plain-English description and get a complete, validated form schema — fields, sections, validation rules, and placeholders — in seconds.</p>
            </div>

            <div class="bg-white rounded-2xl border border-gray-200 p-7 shadow-sm">
                <div class="w-11 h-11 bg-violet-100 rounded-xl flex items-center justify-center mb-5">
                    <svg class="w-6 h-6 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Drag &amp; Drop Builder</h3>
                <p class="text-gray-500 text-sm leading-relaxed">14 field types. Reorder sections and fields by dragging. Edit labels, placeholders, help text, and validation rules inline. Live raw JSON editor for power users.</p>
            </div>

            <div class="bg-white rounded-2xl border border-gray-200 p-7 shadow-sm">
                <div class="w-11 h-11 bg-emerald-100 rounded-xl flex items-center justify-center mb-5">
                    <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Submissions &amp; Export</h3>
                <p class="text-gray-500 text-sm leading-relaxed">Collect responses with server-side validation. Search, filter, and view all submissions. Export to CSV with one click.</p>
            </div>

            <div class="bg-white rounded-2xl border border-gray-200 p-7 shadow-sm">
                <div class="w-11 h-11 bg-amber-100 rounded-xl flex items-center justify-center mb-5">
                    <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Import from Word / Excel</h3>
                <p class="text-gray-500 text-sm leading-relaxed">Upload an existing .docx or .xlsx file. Preview and adjust the auto-detected schema before committing — no manual re-entry needed.</p>
            </div>

            <div class="bg-white rounded-2xl border border-gray-200 p-7 shadow-sm">
                <div class="w-11 h-11 bg-rose-100 rounded-xl flex items-center justify-center mb-5">
                    <svg class="w-6 h-6 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Version History</h3>
                <p class="text-gray-500 text-sm leading-relaxed">Every save creates a full schema snapshot. Roll back to any previous version with one click — even undo a restore.</p>
            </div>

            <div class="bg-white rounded-2xl border border-gray-200 p-7 shadow-sm">
                <div class="w-11 h-11 bg-sky-100 rounded-xl flex items-center justify-center mb-5">
                    <svg class="w-6 h-6 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Webhook Notifications</h3>
                <p class="text-gray-500 text-sm leading-relaxed">Trigger Zapier, Slack, or any custom endpoint on submission. HMAC-SHA256 signed payloads, configurable event filters, auto-disabled after failures.</p>
            </div>

        </div>
    </section>

    <!-- Field types -->
    <section class="bg-white border-y border-gray-200 py-16">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-2xl font-bold text-gray-900 mb-3">14 field types, out of the box</h2>
            <p class="text-gray-500 mb-10">Everything you need for real-world forms — no plugins required.</p>
            <div class="flex flex-wrap justify-center gap-3">
                @foreach (['Text', 'Textarea', 'Email', 'Phone', 'Number', 'Date', 'Dropdown', 'Radio', 'Checkbox', 'File Upload', 'Rating', 'Scale', 'URL', 'Section Heading'] as $type)
                    <span class="bg-gray-100 text-gray-700 text-sm px-4 py-2 rounded-full font-medium">{{ $type }}</span>
                @endforeach
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-20 text-center">
        <h2 class="text-3xl font-extrabold text-gray-900 mb-4">Ready to build your first form?</h2>
        <p class="text-gray-500 mb-8">Try the demo account or create a free account and start collecting responses today.</p>
        <div class="flex flex-wrap justify-center gap-4">
            <a href="{{ route('register') }}" class="bg-indigo-600 text-white px-8 py-3 rounded-xl text-base font-semibold hover:bg-indigo-700 shadow-md">Create account</a>
            <a href="{{ route('login') }}" class="bg-white text-gray-700 border border-gray-300 px-8 py-3 rounded-xl text-base font-semibold hover:bg-gray-50">Log in</a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="border-t border-gray-200 py-8">
        <div class="max-w-6xl mx-auto px-4 text-center text-sm text-gray-400">
            Built with Laravel 11, Livewire 3, and OpenAI &mdash; AI Form Builder
        </div>
    </footer>

</body>
</html>
