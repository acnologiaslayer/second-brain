<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">{{ $note->exists ? 'Edit note' : 'New note' }}</h2></x-slot>
    <div class="py-8 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <form method="POST" action="{{ $note->exists ? route('notes.update', $note) : route('notes.store') }}" class="bg-white rounded-lg shadow-sm p-6 space-y-4">
            @csrf
            @if ($note->exists) @method('PUT') @endif
            @if (! $note->exists && request('content'))
                <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 text-sm rounded p-3">Review this AI-generated content before saving. AI output may be incorrect.</div>
            @endif
            <div>
                <x-input-label for="title" value="Title" />
                <x-text-input id="title" name="title" class="w-full mt-1" :value="old('title', $note->title)" required maxlength="255" />
                <x-input-error :messages="$errors->get('title')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="content" value="Content" />
                <textarea id="content" name="content" rows="14" required class="w-full mt-1 rounded-md border-gray-300 font-mono text-sm">{{ old('content', $note->content) }}</textarea>
                <x-input-error :messages="$errors->get('content')" class="mt-1" />
            </div>
            <div class="grid md:grid-cols-3 gap-4">
                <div>
                    <x-input-label for="note_type" value="Type" />
                    <select id="note_type" name="note_type" class="w-full mt-1 rounded-md border-gray-300">
                        @foreach (\App\Models\Note::TYPES as $t)<option @selected(old('note_type', $note->note_type) == $t)>{{ $t }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="category_id" value="Category" />
                    <select id="category_id" name="category_id" class="w-full mt-1 rounded-md border-gray-300">
                        <option value="">None</option>
                        @foreach ($categories as $c)<option value="{{ $c->id }}" @selected(old('category_id', $note->category_id) == $c->id)>{{ $c->name }}</option>@endforeach
                    </select>
                    <x-input-error :messages="$errors->get('category_id')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="tags" value="Tags (comma separated)" />
                    <x-text-input id="tags" name="tags" class="w-full mt-1" :value="old('tags', $note->exists ? $note->tags->pluck('name')->implode(', ') : '')" />
                </div>
            </div>
            <div class="flex gap-6 text-sm">
                <label class="flex items-center gap-2"><input type="checkbox" name="is_favorite" value="1" class="rounded" @checked(old('is_favorite', $note->is_favorite))> Favorite</label>
                <label class="flex items-center gap-2"><input type="checkbox" name="is_pinned" value="1" class="rounded" @checked(old('is_pinned', $note->is_pinned))> Pinned</label>
            </div>
            <div class="flex justify-end gap-3">
                <a href="{{ $note->exists ? route('notes.show', $note) : route('notes.index') }}" class="px-4 py-2 text-sm text-gray-600">Cancel</a>
                <x-primary-button>Save note</x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>
