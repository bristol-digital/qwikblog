@props([
    'name',
    'selected' => [],
    'available' => [],
    'placeholder' => 'Type and press Enter, or use commas to separate multiple',
    'chipClass' => 'bg-blue-100 text-blue-800',
    'chipButtonClass' => 'text-blue-700 hover:text-blue-900',
])

{{--
    Chip-input widget.

    Behaviour:
    - Hidden input (name="{$name}") holds the comma-joined value the form submits.
    - Selected chips render up top with × to remove.
    - Free-text input below: press Enter to commit one value, or type/paste
      with commas to commit several at once. The watch on `typing` is what
      makes "Foo, Bar, Baz" turn into three chips automatically.
    - The "Existing:" row shows previously-used values (filtered to those
      not already selected) — clicking promotes one into selected.
--}}
<div
    x-data="{
        selected: @js(array_values($selected)),
        available: @js(array_values($available)),
        typing: '',
        init() {
            // Splits any commas in the input as the user types or pastes.
            // Everything before the last comma is committed as chips;
            // anything after the last comma stays in the input as in-progress.
            this.$watch('typing', () => this.handleComma());
        },
        handleComma() {
            if (!this.typing.includes(',')) return;
            const parts = this.typing.split(',');
            const remaining = parts.pop();
            parts.forEach(part => this.commit(part));
            this.typing = (remaining || '').trimStart();
        },
        commit(raw) {
            const item = String(raw || '').trim();
            if (item && !this.selected.includes(item)) {
                this.selected.push(item);
            }
        },
        unselected() {
            return this.available.filter(a => !this.selected.includes(a));
        },
        add(item) {
            // Used by Enter key and by clicks on suggestion chips.
            // Splits on commas too so a paste-then-Enter works in one go.
            String(item || '').split(',').forEach(part => this.commit(part));
            this.typing = '';
        },
        remove(item) {
            this.selected = this.selected.filter(s => s !== item);
        },
        commitTyping() {
            // Belt-and-braces: on form submit or input blur, commit any
            // half-typed value so users don't lose what they just typed.
            if (this.typing.trim() !== '') {
                this.add(this.typing);
            }
        }
    }"
    x-on:submit.window="commitTyping()"
    class="border rounded p-3 bg-white"
>
    <input type="hidden" name="{{ $name }}" :value="selected.join(', ')">

    {{-- Selected chips --}}
    <div class="flex flex-wrap gap-2" x-show="selected.length > 0">
        <template x-for="item in selected" :key="item">
            <span class="inline-flex items-center gap-1 {{ $chipClass }} text-sm px-3 py-1 rounded">
                <span x-text="item"></span>
                <button
                    type="button"
                    x-on:click="remove(item)"
                    class="{{ $chipButtonClass }} font-bold leading-none text-base"
                    aria-label="Remove"
                >&times;</button>
            </span>
        </template>
    </div>

    {{-- Free-text entry --}}
    <input
        type="text"
        x-model="typing"
        x-on:keydown.enter.prevent="add(typing)"
        x-on:blur="commitTyping()"
        placeholder="{{ $placeholder }}"
        class="w-full mt-3 px-3 py-1.5 border rounded text-sm focus:outline-none focus:ring-2 focus:ring-slate-500"
    >

    {{-- Suggestion chips: existing values not already selected --}}
    <div class="mt-3 flex flex-wrap gap-2 items-center" x-show="unselected().length > 0">
        <span class="text-xs text-gray-500 mr-1">Existing:</span>
        <template x-for="item in unselected()" :key="item">
            <button
                type="button"
                x-on:click="add(item)"
                class="text-xs bg-gray-100 hover:bg-gray-200 px-2 py-1 rounded"
                x-text="item"
            ></button>
        </template>
    </div>
</div>
