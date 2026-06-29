@props(['name', 'value' => '', 'label' => 'Description'])
<label class="block text-sm font-black text-slate-700">{{ $label }}</label>
<div class="mt-2 overflow-hidden rounded-2xl border border-slate-300 bg-white" x-data="adminRichEditor(@js(old($name, $value)), @js($name))" x-init="init()">
    <div class="flex flex-wrap gap-1 border-b border-slate-200 bg-slate-50 p-2">
        <button type="button" class="admin-editor-button" @click="command('formatBlock', 'h2')">H2</button>
        <button type="button" class="admin-editor-button" @click="command('formatBlock', 'h3')">H3</button>
        <button type="button" class="admin-editor-button font-black" @click="command('bold')">B</button>
        <button type="button" class="admin-editor-button italic" @click="command('italic')">I</button>
        <button type="button" class="admin-editor-button underline" @click="command('underline')">U</button>
        <button type="button" class="admin-editor-button" @click="command('insertUnorderedList')">• List</button>
        <button type="button" class="admin-editor-button" @click="command('insertOrderedList')">1. List</button>
        <button type="button" class="admin-editor-button" @click="command('formatBlock', 'blockquote')">Quote</button>
        <button type="button" class="admin-editor-button" @click="createLink()">Link</button>
        <button type="button" class="admin-editor-button" @click="command('removeFormat')">Clear</button>
    </div>
    <div x-ref="editor" contenteditable="true" @input="sync()" class="admin-rich-editor min-h-[260px] p-5 text-sm leading-7 text-slate-700" role="textbox" aria-multiline="true"></div>
    <textarea name="{{ $name }}" x-model="value" hidden></textarea>
</div>
