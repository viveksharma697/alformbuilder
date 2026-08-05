@php $required = !empty($field['required']); @endphp

@switch($field['type'])
    @case('section_heading')
        <div class="border-b border-gray-200 pb-2">
            <h3 class="font-semibold text-gray-800">{{ $field['label'] ?: 'Section Heading' }}</h3>
        </div>
        @break

    @case('text')
    @case('email')
    @case('phone')
    @case('url')
    @case('password')
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                {{ $field['label'] ?: 'Label' }}
                @if($required)<span class="text-red-500 ml-0.5">*</span>@endif
            </label>
            <input type="{{ $field['type'] === 'password' ? 'password' : 'text' }}"
                placeholder="{{ $field['placeholder'] ?: '' }}"
                disabled class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 text-sm text-gray-400 cursor-not-allowed">
            @if(!empty($field['help_text']))<p class="text-xs text-gray-400 mt-1">{{ $field['help_text'] }}</p>@endif
        </div>
        @break

    @case('textarea')
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                {{ $field['label'] ?: 'Label' }}
                @if($required)<span class="text-red-500 ml-0.5">*</span>@endif
            </label>
            <textarea rows="3" placeholder="{{ $field['placeholder'] ?: '' }}"
                disabled class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 text-sm text-gray-400 cursor-not-allowed resize-none"></textarea>
            @if(!empty($field['help_text']))<p class="text-xs text-gray-400 mt-1">{{ $field['help_text'] }}</p>@endif
        </div>
        @break

    @case('number')
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                {{ $field['label'] ?: 'Label' }}
                @if($required)<span class="text-red-500 ml-0.5">*</span>@endif
            </label>
            <input type="number" placeholder="{{ $field['placeholder'] ?: '0' }}"
                disabled class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 text-sm text-gray-400 cursor-not-allowed">
            @if(!empty($field['help_text']))<p class="text-xs text-gray-400 mt-1">{{ $field['help_text'] }}</p>@endif
        </div>
        @break

    @case('date')
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                {{ $field['label'] ?: 'Label' }}
                @if($required)<span class="text-red-500 ml-0.5">*</span>@endif
            </label>
            <input type="date" disabled class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 text-sm text-gray-400 cursor-not-allowed">
            @if(!empty($field['help_text']))<p class="text-xs text-gray-400 mt-1">{{ $field['help_text'] }}</p>@endif
        </div>
        @break

    @case('dropdown')
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                {{ $field['label'] ?: 'Label' }}
                @if($required)<span class="text-red-500 ml-0.5">*</span>@endif
            </label>
            <select disabled class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 text-sm text-gray-400 cursor-not-allowed">
                <option>{{ $field['placeholder'] ?: 'Select an option' }}</option>
                @foreach($field['options'] ?? [] as $option)
                    <option>{{ $option['label'] }}</option>
                @endforeach
            </select>
            @if(!empty($field['help_text']))<p class="text-xs text-gray-400 mt-1">{{ $field['help_text'] }}</p>@endif
        </div>
        @break

    @case('radio')
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                {{ $field['label'] ?: 'Label' }}
                @if($required)<span class="text-red-500 ml-0.5">*</span>@endif
            </label>
            <div class="space-y-1.5">
                @foreach($field['options'] ?? [] as $option)
                    <label class="flex items-center gap-2 cursor-not-allowed">
                        <input type="radio" disabled class="text-indigo-600">
                        <span class="text-sm text-gray-600">{{ $option['label'] }}</span>
                    </label>
                @endforeach
            </div>
            @if(!empty($field['help_text']))<p class="text-xs text-gray-400 mt-1">{{ $field['help_text'] }}</p>@endif
        </div>
        @break

    @case('checkbox')
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                {{ $field['label'] ?: 'Label' }}
                @if($required)<span class="text-red-500 ml-0.5">*</span>@endif
            </label>
            <div class="space-y-1.5">
                @foreach($field['options'] ?? [] as $option)
                    <label class="flex items-center gap-2 cursor-not-allowed">
                        <input type="checkbox" disabled class="rounded text-indigo-600">
                        <span class="text-sm text-gray-600">{{ $option['label'] }}</span>
                    </label>
                @endforeach
            </div>
            @if(!empty($field['help_text']))<p class="text-xs text-gray-400 mt-1">{{ $field['help_text'] }}</p>@endif
        </div>
        @break

    @case('file_upload')
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                {{ $field['label'] ?: 'Label' }}
                @if($required)<span class="text-red-500 ml-0.5">*</span>@endif
            </label>
            <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center bg-gray-50">
                <svg class="w-6 h-6 text-gray-400 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                <span class="text-xs text-gray-400">
                    {{ $field['placeholder'] ?: 'Click to upload or drag & drop' }}
                    @if(!empty($field['validation']['allowed_types']))<br><span class="text-xs text-gray-400">{{ $field['validation']['allowed_types'] }}</span>@endif
                </span>
            </div>
            @if(!empty($field['help_text']))<p class="text-xs text-gray-400 mt-1">{{ $field['help_text'] }}</p>@endif
        </div>
        @break

    @case('rating')
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                {{ $field['label'] ?: 'Rating' }}
                @if($required)<span class="text-red-500 ml-0.5">*</span>@endif
            </label>
            <div class="flex gap-1">
                @for($i = 1; $i <= ($field['max_rating'] ?? 5); $i++)
                    <span class="text-2xl text-gray-300 cursor-not-allowed">★</span>
                @endfor
            </div>
            @if(!empty($field['help_text']))<p class="text-xs text-gray-400 mt-1">{{ $field['help_text'] }}</p>@endif
        </div>
        @break

    @default
        <div class="text-sm text-gray-500 italic">{{ ucfirst($field['type']) }}: {{ $field['label'] }}</div>
@endswitch
