<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $form->title }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 min-h-screen py-8 px-4 font-sans antialiased">
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="bg-indigo-600 px-6 py-5">
                <h1 class="text-xl font-bold text-white">{{ $form->title }}</h1>
                @if($form->description)
                    <p class="text-indigo-200 text-sm mt-1">{{ $form->description }}</p>
                @endif
            </div>

            @if(session('success'))
                <div class="m-6 p-4 bg-green-50 border border-green-200 text-green-800 rounded-lg text-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="m-6 p-4 bg-red-50 border border-red-200 text-red-800 rounded-lg text-sm">
                    <p class="font-medium mb-1">Please fix the following errors:</p>
                    <ul class="list-disc list-inside space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @unless(session('success'))
            <form method="POST" action="{{ route('forms.submit', $form->slug) }}" enctype="multipart/form-data">
                @csrf
                <div class="p-6 space-y-6">
                    @foreach($form->schema['sections'] ?? [] as $si => $section)
                        @if(count($form->schema['sections']) > 1)
                            <div class="border-b border-gray-200 pb-2 mb-4">
                                <h2 class="text-base font-semibold text-gray-800">{{ $section['title'] }}</h2>
                            </div>
                        @endif

                        @foreach($section['fields'] ?? [] as $field)
                            @php $key = 'fields.' . $field['key']; $required = !empty($field['required']); @endphp

                            @switch($field['type'])
                                @case('section_heading')
                                    <h3 class="font-semibold text-gray-800 border-b border-gray-200 pb-2">{{ $field['label'] }}</h3>
                                    @break

                                @case('text')
                                @case('email')
                                @case('phone')
                                @case('url')
                                @case('password')
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                                            {{ $field['label'] }}@if($required)<span class="text-red-500 ml-1">*</span>@endif
                                        </label>
                                        <input type="{{ $field['type'] === 'password' ? 'password' : ($field['type'] === 'email' ? 'email' : ($field['type'] === 'url' ? 'url' : 'text')) }}"
                                            name="{{ $key }}" value="{{ old($key, $field['default'] ?? '') }}"
                                            placeholder="{{ $field['placeholder'] }}"
                                            {{ $required ? 'required' : '' }}
                                            class="w-full px-3 py-2 border {{ $errors->has($key) ? 'border-red-400' : 'border-gray-300' }} rounded-lg focus:ring-2 focus:ring-indigo-500 text-sm">
                                        @if($field['help_text'])<p class="text-xs text-gray-500 mt-1">{{ $field['help_text'] }}</p>@endif
                                        @error($key)<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                                    </div>
                                    @break

                                @case('textarea')
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                                            {{ $field['label'] }}@if($required)<span class="text-red-500 ml-1">*</span>@endif
                                        </label>
                                        <textarea name="{{ $key }}" rows="4"
                                            placeholder="{{ $field['placeholder'] }}"
                                            {{ $required ? 'required' : '' }}
                                            class="w-full px-3 py-2 border {{ $errors->has($key) ? 'border-red-400' : 'border-gray-300' }} rounded-lg focus:ring-2 focus:ring-indigo-500 text-sm">{{ old($key, $field['default'] ?? '') }}</textarea>
                                        @if($field['help_text'])<p class="text-xs text-gray-500 mt-1">{{ $field['help_text'] }}</p>@endif
                                        @error($key)<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                                    </div>
                                    @break

                                @case('number')
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                                            {{ $field['label'] }}@if($required)<span class="text-red-500 ml-1">*</span>@endif
                                        </label>
                                        <input type="number" name="{{ $key }}" value="{{ old($key, $field['default'] ?? '') }}"
                                            placeholder="{{ $field['placeholder'] }}"
                                            {{ isset($field['validation']['min']) ? 'min="'.$field['validation']['min'].'"' : '' }}
                                            {{ isset($field['validation']['max']) ? 'max="'.$field['validation']['max'].'"' : '' }}
                                            {{ $required ? 'required' : '' }}
                                            class="w-full px-3 py-2 border {{ $errors->has($key) ? 'border-red-400' : 'border-gray-300' }} rounded-lg focus:ring-2 focus:ring-indigo-500 text-sm">
                                        @error($key)<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                                    </div>
                                    @break

                                @case('date')
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                                            {{ $field['label'] }}@if($required)<span class="text-red-500 ml-1">*</span>@endif
                                        </label>
                                        <input type="date" name="{{ $key }}" value="{{ old($key, $field['default'] ?? '') }}"
                                            {{ $required ? 'required' : '' }}
                                            class="w-full px-3 py-2 border {{ $errors->has($key) ? 'border-red-400' : 'border-gray-300' }} rounded-lg focus:ring-2 focus:ring-indigo-500 text-sm">
                                        @error($key)<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                                    </div>
                                    @break

                                @case('dropdown')
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                                            {{ $field['label'] }}@if($required)<span class="text-red-500 ml-1">*</span>@endif
                                        </label>
                                        <select name="{{ $key }}" {{ $required ? 'required' : '' }}
                                            class="w-full px-3 py-2 border {{ $errors->has($key) ? 'border-red-400' : 'border-gray-300' }} rounded-lg focus:ring-2 focus:ring-indigo-500 text-sm">
                                            <option value="">{{ $field['placeholder'] ?: '-- Select --' }}</option>
                                            @foreach($field['options'] ?? [] as $option)
                                                <option value="{{ $option['value'] }}" {{ old($key) === $option['value'] ? 'selected' : '' }}>
                                                    {{ $option['label'] }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error($key)<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                                    </div>
                                    @break

                                @case('radio')
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            {{ $field['label'] }}@if($required)<span class="text-red-500 ml-1">*</span>@endif
                                        </label>
                                        <div class="space-y-2">
                                            @foreach($field['options'] ?? [] as $option)
                                                <label class="flex items-center gap-2.5 cursor-pointer">
                                                    <input type="radio" name="{{ $key }}" value="{{ $option['value'] }}"
                                                        {{ old($key) === $option['value'] ? 'checked' : '' }}
                                                        {{ $required ? 'required' : '' }}
                                                        class="text-indigo-600 focus:ring-indigo-500">
                                                    <span class="text-sm text-gray-700">{{ $option['label'] }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                        @error($key)<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                                    </div>
                                    @break

                                @case('checkbox')
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            {{ $field['label'] }}@if($required)<span class="text-red-500 ml-1">*</span>@endif
                                        </label>
                                        <div class="space-y-2">
                                            @foreach($field['options'] ?? [] as $option)
                                                <label class="flex items-center gap-2.5 cursor-pointer">
                                                    <input type="checkbox" name="{{ $key }}[]" value="{{ $option['value'] }}"
                                                        {{ is_array(old($key)) && in_array($option['value'], old($key, [])) ? 'checked' : '' }}
                                                        class="rounded text-indigo-600 focus:ring-indigo-500">
                                                    <span class="text-sm text-gray-700">{{ $option['label'] }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                        @error($key)<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                                    </div>
                                    @break

                                @case('file_upload')
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                                            {{ $field['label'] }}@if($required)<span class="text-red-500 ml-1">*</span>@endif
                                        </label>
                                        <input type="file" name="{{ $key }}"
                                            {{ $required ? 'required' : '' }}
                                            {{ !empty($field['validation']['allowed_types']) ? 'accept=".' . implode(',.',explode(',', $field['validation']['allowed_types'])) . '"' : '' }}
                                            class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                                        @if($field['help_text'])<p class="text-xs text-gray-500 mt-1">{{ $field['help_text'] }}</p>@endif
                                        @error($key)<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                                    </div>
                                    @break

                                @case('rating')
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            {{ $field['label'] }}@if($required)<span class="text-red-500 ml-1">*</span>@endif
                                        </label>
                                        <div class="flex gap-1" x-data="{ rating: {{ old($key, 0) }} }">
                                            @for($i = 1; $i <= ($field['max_rating'] ?? 5); $i++)
                                            <label class="cursor-pointer">
                                                <input type="radio" name="{{ $key }}" value="{{ $i }}" class="sr-only"
                                                    x-on:change="rating = {{ $i }}">
                                                <span class="text-3xl transition-colors" :class="rating >= {{ $i }} ? 'text-yellow-400' : 'text-gray-300'">★</span>
                                            </label>
                                            @endfor
                                        </div>
                                        @error($key)<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                                    </div>
                                    @break
                            @endswitch
                        @endforeach
                    @endforeach
                </div>

                <div class="px-6 pb-6">
                    <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2.5 rounded-lg text-sm transition-colors">
                        Submit
                    </button>
                </div>
            </form>
            @endunless
        </div>

        <p class="text-center text-xs text-gray-400 mt-4">
            Powered by <a href="{{ route('forms.index') }}" class="text-indigo-400 hover:underline">AI Form Builder</a>
        </p>
    </div>

    @vite(['resources/js/app.js'])
</body>
</html>
