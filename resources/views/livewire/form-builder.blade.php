<div class="flex h-[calc(100vh-49px)]" x-data="formBuilderApp(@this)">

    {{-- Left Panel: Section/Field list --}}
    <div class="w-80 bg-white border-r border-gray-200 flex flex-col overflow-hidden">
        {{-- Form Meta --}}
        <div class="p-4 border-b border-gray-100">
            <input type="text" wire:model.blur="schema.title"
                class="w-full text-sm font-semibold border-0 border-b border-transparent hover:border-gray-300 focus:border-indigo-400 focus:ring-0 px-0 py-1 text-gray-800 bg-transparent"
                placeholder="Form title">
            <input type="text" wire:model.blur="schema.description"
                class="w-full text-xs border-0 border-b border-transparent hover:border-gray-300 focus:border-indigo-400 focus:ring-0 px-0 py-1 text-gray-500 bg-transparent mt-1"
                placeholder="Description (optional)">
        </div>

        {{-- Sections list --}}
        <div class="flex-1 overflow-y-auto p-3 space-y-3">
            @foreach($schema['sections'] ?? [] as $si => $section)
                <div class="border border-gray-200 rounded-lg overflow-hidden {{ $activeSection === $section['id'] ? 'border-indigo-300 ring-1 ring-indigo-300' : '' }}">
                    <div class="flex items-center gap-2 px-3 py-2 bg-gray-50 cursor-pointer"
                         wire:click="$set('activeSection', '{{ $section['id'] }}')">
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                        <span class="text-sm font-medium text-gray-700 flex-1 truncate">{{ $section['title'] ?: 'Section ' . ($si+1) }}</span>
                        <span class="text-xs text-gray-400">{{ count($section['fields'] ?? []) }} fields</span>
                        @if(count($schema['sections']) > 1)
                        <button wire:click.stop="removeSection('{{ $section['id'] }}')" class="text-gray-300 hover:text-red-400 ml-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                        @endif
                    </div>

                    @if($activeSection === $section['id'])
                    <div id="field-list-{{ $section['id'] }}" class="px-2 py-1 min-h-[40px]" x-init="initSortable($el, '{{ $section['id'] }}')">
                        @foreach($section['fields'] ?? [] as $field)
                        <div class="flex items-center gap-2 px-2 py-1.5 rounded cursor-pointer hover:bg-gray-50 group mb-1
                                {{ $editingFieldId === $field['id'] ? 'bg-indigo-50 ring-1 ring-indigo-200' : '' }}"
                             data-id="{{ $field['id'] }}"
                             wire:click="openFieldEditor('{{ $field['id'] }}')">
                            <span class="text-gray-300 drag-handle cursor-grab" title="Drag to reorder">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/></svg>
                            </span>
                            <span class="text-xs font-mono text-indigo-400 w-16 truncate flex-shrink-0">{{ $field['type'] }}</span>
                            <span class="text-xs text-gray-700 flex-1 truncate">{{ $field['label'] ?: '(unlabeled)' }}</span>
                            <div class="hidden group-hover:flex items-center gap-0.5">
                                <button wire:click.stop="moveField('{{ $field['id'] }}', '{{ $section['id'] }}', 'up')" class="p-0.5 text-gray-400 hover:text-gray-600" title="Move up">↑</button>
                                <button wire:click.stop="moveField('{{ $field['id'] }}', '{{ $section['id'] }}', 'down')" class="p-0.5 text-gray-400 hover:text-gray-600" title="Move down">↓</button>
                                <button wire:click.stop="duplicateField('{{ $field['id'] }}', '{{ $section['id'] }}')" class="p-0.5 text-gray-400 hover:text-blue-500" title="Duplicate">⧉</button>
                                <button wire:click.stop="deleteField('{{ $field['id'] }}', '{{ $section['id'] }}')" class="p-0.5 text-gray-400 hover:text-red-500" title="Delete">✕</button>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="p-3 border-t border-gray-100 space-y-2">
            <button wire:click="addSection" class="w-full text-sm text-gray-600 hover:text-indigo-600 flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-indigo-50">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add Section
            </button>
            {{-- Save / Publish --}}
            <div class="flex gap-2">
                <button wire:click="save" class="flex-1 bg-gray-800 hover:bg-gray-900 text-white text-sm font-medium px-3 py-2 rounded-lg">
                    Save
                </button>
                <button wire:click="publish" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-3 py-2 rounded-lg">
                    Publish
                </button>
            </div>
            @if($saved)
            <p class="text-xs text-green-600 text-center">{{ $saveMessage }}</p>
            @endif
            @if(!empty($jsonErrors))
            <div class="text-xs text-red-600 bg-red-50 rounded px-2 py-1">
                @foreach($jsonErrors as $err)
                    <div>• {{ $err }}</div>
                @endforeach
            </div>
            @endif
        </div>
    </div>

    {{-- Center Panel: Form Canvas --}}
    <div class="flex-1 overflow-y-auto bg-gray-50 relative" x-show="!showRawEditor">
        <div class="max-w-2xl mx-auto py-6 px-4">
            {{-- AI Generation Panel --}}
            <div class="mb-4 bg-gradient-to-r from-purple-50 to-indigo-50 border border-indigo-200 rounded-xl p-4">
                <div class="flex items-center gap-2 mb-2">
                    <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    <span class="text-sm font-semibold text-indigo-800">AI Assistant</span>
                </div>
                <div class="flex gap-2">
                    <input type="text" x-model="aiPrompt" placeholder="e.g., add an emergency contact section"
                        class="flex-1 text-sm px-3 py-1.5 border border-indigo-200 rounded-lg focus:ring-2 focus:ring-indigo-400 bg-white"
                        @keydown.enter="startAiEdit()">
                    <button @click="startAiEdit()" :disabled="aiLoading"
                        class="bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white text-sm px-3 py-1.5 rounded-lg font-medium whitespace-nowrap">
                        <span x-show="!aiLoading">Apply AI</span>
                        <span x-show="aiLoading" class="flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            Working...
                        </span>
                    </button>
                </div>
                <p x-show="aiStatus" x-text="aiStatus" class="text-xs mt-1.5 text-indigo-700"></p>
            </div>

            {{-- Form Preview --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-gray-100">
                    <h1 class="text-xl font-bold text-gray-900">{{ $schema['title'] ?? 'Untitled Form' }}</h1>
                    @if($schema['description'] ?? false)
                        <p class="text-sm text-gray-500 mt-1">{{ $schema['description'] }}</p>
                    @endif
                </div>

                @foreach($schema['sections'] ?? [] as $si => $section)
                    <div class="border-b border-gray-100 last:border-0">
                        @if(count($schema['sections']) > 1)
                        <div class="px-6 py-3 bg-gray-50 flex items-center justify-between">
                            <input type="text" value="{{ $section['title'] }}"
                                wire:change="updateSectionTitle('{{ $section['id'] }}', $event.target.value)"
                                class="text-sm font-semibold text-gray-700 bg-transparent border-0 border-b border-transparent hover:border-gray-300 focus:border-indigo-400 focus:ring-0 px-0">
                        </div>
                        @endif

                        <div class="p-6 space-y-5" id="canvas-{{ $section['id'] }}">
                            @forelse($section['fields'] ?? [] as $field)
                                <div class="group relative cursor-pointer rounded-lg border-2 transition-colors
                                    {{ $editingFieldId === $field['id'] ? 'border-indigo-400 bg-indigo-50' : 'border-transparent hover:border-gray-200' }}"
                                     wire:click="openFieldEditor('{{ $field['id'] }}')">
                                    <div class="px-3 py-3">
                                        @include('livewire.partials.field-preview', ['field' => $field])
                                    </div>
                                    {{-- Field action buttons --}}
                                    <div class="absolute top-2 right-2 hidden group-hover:flex gap-1">
                                        <button wire:click.stop="duplicateField('{{ $field['id'] }}', '{{ $section['id'] }}')"
                                            class="p-1 bg-white border border-gray-200 rounded text-gray-400 hover:text-blue-600 shadow-sm text-xs" title="Duplicate">⧉</button>
                                        <button wire:click.stop="deleteField('{{ $field['id'] }}', '{{ $section['id'] }}')"
                                            class="p-1 bg-white border border-gray-200 rounded text-gray-400 hover:text-red-600 shadow-sm text-xs" title="Delete">✕</button>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-10 text-sm text-gray-400 border-2 border-dashed border-gray-200 rounded-lg">
                                    Click a field type on the right to add it here
                                </div>
                            @endforelse
                        </div>
                    </div>
                @endforeach

                <div class="p-6 bg-gray-50">
                    <button disabled class="w-full bg-indigo-600 text-white py-2 rounded-lg text-sm font-medium opacity-70">Submit Form</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Raw JSON Editor Panel --}}
    <div class="flex-1 bg-gray-900 text-green-400 font-mono text-xs overflow-auto" x-show="showRawEditor" wire:ignore>
        <div class="p-4">
            <div class="flex items-center justify-between mb-2">
                <span class="text-gray-400">Schema JSON Editor</span>
                <span class="{{ $jsonValid ? 'text-green-400' : 'text-red-400' }}">
                    {{ $jsonValid ? '✓ Valid' : '✗ Invalid' }}
                </span>
            </div>
            <textarea id="raw-editor"
                class="w-full h-[calc(100vh-140px)] bg-gray-900 text-green-300 font-mono text-xs resize-none focus:outline-none"
                x-on:change="$wire.updateRawJson($el.value)">{{ $rawJson }}</textarea>
        </div>
    </div>

    {{-- Right Panel: Field Types + Editor --}}
    <div class="w-72 bg-white border-l border-gray-200 flex flex-col overflow-hidden">

        {{-- Toggle: Field Types / Raw JSON --}}
        <div class="flex border-b border-gray-200">
            <button @click="$wire.set('showRawEditor', false)"
                class="flex-1 text-xs py-2.5 font-medium transition-colors"
                :class="!$wire.showRawEditor ? 'text-indigo-600 border-b-2 border-indigo-600' : 'text-gray-500 hover:text-gray-700'">
                Fields
            </button>
            <button @click="$wire.toggleRawEditor()"
                class="flex-1 text-xs py-2.5 font-medium transition-colors"
                :class="$wire.showRawEditor ? 'text-indigo-600 border-b-2 border-indigo-600' : 'text-gray-500 hover:text-gray-700'">
                JSON
            </button>
        </div>

        @if($editingFieldId && !empty($editingField))
            {{-- Field Editor --}}
            <div class="flex-1 overflow-y-auto p-4">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-semibold text-gray-800">Edit Field</h3>
                    <button wire:click="closeFieldEditor" class="text-gray-400 hover:text-gray-600">✕</button>
                </div>

                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Label *</label>
                        <input type="text" wire:model.live="editingField.label" wire:change="updateEditingField"
                            class="w-full text-sm px-2 py-1.5 border border-gray-300 rounded-lg focus:ring-1 focus:ring-indigo-400">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Field Key (snake_case)</label>
                        <input type="text" wire:model.live="editingField.key" wire:change="updateEditingField"
                            class="w-full text-xs px-2 py-1.5 border border-gray-300 rounded-lg focus:ring-1 focus:ring-indigo-400 font-mono">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Placeholder</label>
                        <input type="text" wire:model.live="editingField.placeholder" wire:change="updateEditingField"
                            class="w-full text-sm px-2 py-1.5 border border-gray-300 rounded-lg focus:ring-1 focus:ring-indigo-400">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Help Text</label>
                        <input type="text" wire:model.live="editingField.help_text" wire:change="updateEditingField"
                            class="w-full text-sm px-2 py-1.5 border border-gray-300 rounded-lg focus:ring-1 focus:ring-indigo-400">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Default Value</label>
                        <input type="text" wire:model.live="editingField.default" wire:change="updateEditingField"
                            class="w-full text-sm px-2 py-1.5 border border-gray-300 rounded-lg focus:ring-1 focus:ring-indigo-400">
                    </div>

                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" wire:model.live="editingField.required" wire:change="updateEditingField" class="rounded">
                        <span class="text-xs text-gray-700 font-medium">Required field</span>
                    </label>

                    {{-- Type-specific validation --}}
                    @if(in_array($editingField['type'] ?? '', ['text', 'textarea', 'password']))
                        <div class="border-t border-gray-100 pt-3">
                            <p class="text-xs font-semibold text-gray-500 mb-2 uppercase tracking-wide">Validation</p>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="text-xs text-gray-500">Min Length</label>
                                    <input type="number" wire:model.live="editingField.validation.min_length" wire:change="updateEditingField" min="0"
                                        class="w-full text-xs px-2 py-1 border border-gray-300 rounded">
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500">Max Length</label>
                                    <input type="number" wire:model.live="editingField.validation.max_length" wire:change="updateEditingField" min="0"
                                        class="w-full text-xs px-2 py-1 border border-gray-300 rounded">
                                </div>
                            </div>
                            <div class="mt-2">
                                <label class="text-xs text-gray-500">Regex Pattern</label>
                                <input type="text" wire:model.live="editingField.validation.regex" wire:change="updateEditingField"
                                    class="w-full text-xs px-2 py-1 border border-gray-300 rounded font-mono" placeholder="[a-zA-Z0-9]+">
                            </div>
                        </div>
                    @elseif(($editingField['type'] ?? '') === 'number')
                        <div class="border-t border-gray-100 pt-3">
                            <p class="text-xs font-semibold text-gray-500 mb-2 uppercase tracking-wide">Validation</p>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="text-xs text-gray-500">Min</label>
                                    <input type="number" wire:model.live="editingField.validation.min" wire:change="updateEditingField"
                                        class="w-full text-xs px-2 py-1 border border-gray-300 rounded">
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500">Max</label>
                                    <input type="number" wire:model.live="editingField.validation.max" wire:change="updateEditingField"
                                        class="w-full text-xs px-2 py-1 border border-gray-300 rounded">
                                </div>
                            </div>
                        </div>
                    @elseif(($editingField['type'] ?? '') === 'file_upload')
                        <div class="border-t border-gray-100 pt-3">
                            <p class="text-xs font-semibold text-gray-500 mb-2 uppercase tracking-wide">File Validation</p>
                            <div>
                                <label class="text-xs text-gray-500">Max Size (KB)</label>
                                <input type="number" wire:model.live="editingField.validation.max_size_kb" wire:change="updateEditingField" min="1"
                                    class="w-full text-xs px-2 py-1 border border-gray-300 rounded" placeholder="5120">
                            </div>
                            <div class="mt-2">
                                <label class="text-xs text-gray-500">Allowed Types</label>
                                <input type="text" wire:model.live="editingField.validation.allowed_types" wire:change="updateEditingField"
                                    class="w-full text-xs px-2 py-1 border border-gray-300 rounded" placeholder="pdf,doc,docx,jpg">
                            </div>
                        </div>
                    @elseif(($editingField['type'] ?? '') === 'date')
                        <div class="border-t border-gray-100 pt-3">
                            <p class="text-xs font-semibold text-gray-500 mb-2 uppercase tracking-wide">Date Validation</p>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="text-xs text-gray-500">Min Date</label>
                                    <input type="date" wire:model.live="editingField.validation.min_date" wire:change="updateEditingField"
                                        class="w-full text-xs px-2 py-1 border border-gray-300 rounded">
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500">Max Date</label>
                                    <input type="date" wire:model.live="editingField.validation.max_date" wire:change="updateEditingField"
                                        class="w-full text-xs px-2 py-1 border border-gray-300 rounded">
                                </div>
                            </div>
                        </div>
                    @elseif(($editingField['type'] ?? '') === 'rating')
                        <div class="border-t border-gray-100 pt-3">
                            <p class="text-xs font-semibold text-gray-500 mb-2 uppercase tracking-wide">Rating</p>
                            <div>
                                <label class="text-xs text-gray-500">Max Stars</label>
                                <input type="number" wire:model.live="editingField.max_rating" wire:change="updateEditingField" min="2" max="10"
                                    class="w-full text-xs px-2 py-1 border border-gray-300 rounded">
                            </div>
                        </div>
                    @endif

                    {{-- Options for choice fields --}}
                    @if(in_array($editingField['type'] ?? '', ['dropdown', 'radio', 'checkbox']))
                        <div class="border-t border-gray-100 pt-3">
                            <p class="text-xs font-semibold text-gray-500 mb-2 uppercase tracking-wide">Options</p>
                            <div class="space-y-2">
                                @foreach($editingField['options'] ?? [] as $oi => $option)
                                <div class="flex items-center gap-2">
                                    <input type="text" wire:model.live="editingField.options.{{ $oi }}.label" wire:change="updateEditingField"
                                        placeholder="Label" class="flex-1 text-xs px-2 py-1 border border-gray-300 rounded">
                                    <input type="text" wire:model.live="editingField.options.{{ $oi }}.value" wire:change="updateEditingField"
                                        placeholder="Value" class="w-20 text-xs px-2 py-1 border border-gray-300 rounded font-mono">
                                    <button wire:click="removeOption({{ $oi }})" class="text-gray-300 hover:text-red-400 text-xs">✕</button>
                                </div>
                                @endforeach
                            </div>
                            <button wire:click="addOption" class="mt-2 text-xs text-indigo-600 hover:text-indigo-800 flex items-center gap-1">
                                + Add Option
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        @else
            {{-- Field Types Palette --}}
            <div class="flex-1 overflow-y-auto p-3">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide px-1 mb-2">Standard Fields</p>
                <div class="grid grid-cols-3 gap-1.5 mb-4">
                    @php
                        $fieldTypes = [
                            ['type' => 'text', 'label' => 'Text', 'icon' => 'T'],
                            ['type' => 'textarea', 'label' => 'Textarea', 'icon' => '¶'],
                            ['type' => 'number', 'label' => 'Number', 'icon' => '#'],
                            ['type' => 'email', 'label' => 'Email', 'icon' => '@'],
                            ['type' => 'phone', 'label' => 'Phone', 'icon' => '☎'],
                            ['type' => 'date', 'label' => 'Date', 'icon' => '📅'],
                            ['type' => 'dropdown', 'label' => 'Dropdown', 'icon' => '▾'],
                            ['type' => 'radio', 'label' => 'Radio', 'icon' => '◉'],
                            ['type' => 'checkbox', 'label' => 'Checkbox', 'icon' => '☑'],
                            ['type' => 'file_upload', 'label' => 'File', 'icon' => '📎'],
                            ['type' => 'rating', 'label' => 'Rating', 'icon' => '★'],
                            ['type' => 'url', 'label' => 'URL', 'icon' => '🔗'],
                        ];
                    @endphp
                    @foreach($fieldTypes as $ft)
                        <button wire:click="addField('{{ $ft['type'] }}', '{{ $activeSection }}')"
                            class="flex flex-col items-center gap-1 p-2 bg-gray-50 hover:bg-indigo-50 hover:border-indigo-300 border border-gray-200 rounded-lg transition-colors text-center cursor-pointer">
                            <span class="text-lg leading-none">{{ $ft['icon'] }}</span>
                            <span class="text-xs text-gray-600">{{ $ft['label'] }}</span>
                        </button>
                    @endforeach
                </div>

                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide px-1 mb-2">Layout</p>
                <div class="grid grid-cols-2 gap-1.5">
                    <button wire:click="addField('section_heading', '{{ $activeSection }}')"
                        class="flex items-center gap-2 p-2 bg-gray-50 hover:bg-indigo-50 border border-gray-200 hover:border-indigo-300 rounded-lg text-xs text-gray-600 cursor-pointer">
                        <span>H1</span> Heading
                    </button>
                    <button wire:click="addField('password', '{{ $activeSection }}')"
                        class="flex items-center gap-2 p-2 bg-gray-50 hover:bg-indigo-50 border border-gray-200 hover:border-indigo-300 rounded-lg text-xs text-gray-600 cursor-pointer">
                        <span>🔒</span> Password
                    </button>
                </div>
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
function formBuilderApp(livewireComponent) {
    return {
        aiPrompt: '',
        aiLoading: false,
        aiStatus: '',
        showRawEditor: @js($showRawEditor),

        initSortable(el, sectionId) {
            if (window.Sortable) {
                Sortable.create(el, {
                    handle: '.drag-handle',
                    animation: 150,
                    onEnd: (evt) => {
                        const ids = Array.from(el.querySelectorAll('[data-id]')).map(el => el.dataset.id);
                        livewireComponent.dispatch('field-reordered', { fieldIds: ids, sectionId: sectionId });
                    }
                });
            }
        },

        async startAiEdit() {
            if (!this.aiPrompt.trim() || this.aiLoading) return;
            this.aiLoading = true;
            this.aiStatus = 'Starting AI generation...';

            try {
                const formId = {{ $form->id }};
                const res = await fetch(`/ai/edit/${formId}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                    body: JSON.stringify({ prompt: this.aiPrompt })
                });
                const data = await res.json();
                if (!data.job_id) throw new Error('No job created');

                this.aiStatus = 'Processing...';
                await this.pollAndApply(data.job_id, formId);
            } catch (e) {
                this.aiStatus = '❌ Failed: ' + e.message;
            } finally {
                this.aiLoading = false;
            }
        },

        async pollAndApply(jobId, formId) {
            for (let i = 0; i < 60; i++) {
                await new Promise(r => setTimeout(r, 2000));
                const res = await fetch(`/ai/status/${jobId}`);
                const data = await res.json();

                if (data.status === 'completed') {
                    // Apply schema to form
                    const applyRes = await fetch(`/ai/apply/${formId}`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                        body: JSON.stringify({ ai_job_id: jobId })
                    });
                    const applyData = await applyRes.json();
                    if (applyData.form) {
                        // Reload the page to show the updated form
                        this.aiStatus = '✅ Applied! Refreshing...';
                        setTimeout(() => window.location.reload(), 800);
                    }
                    return;
                } else if (data.status === 'failed') {
                    throw new Error(data.error || 'AI generation failed');
                }
                this.aiStatus = `Processing... (attempt ${i+1})`;
            }
            throw new Error('Timed out');
        }
    };
}
</script>
@endpush
