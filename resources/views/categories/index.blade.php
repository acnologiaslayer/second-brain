<x-app-layout>
    <x-slot name="title">Organize</x-slot>
    <div class="max-w-4xl mx-auto space-y-6">
        <div>
            <h1 class="page-title">Organize</h1>
            <p class="text-sm text-zinc-500 mt-1">Group notes with categories. Tags are created automatically from your notes.</p>
        </div>

        <section class="card">
            <div class="p-5 border-b border-zinc-100">
                <h2 class="text-sm font-semibold text-zinc-900">Categories</h2>
                <form method="POST" action="{{ route('categories.store') }}" class="mt-3 flex flex-col sm:flex-row gap-2">
                    @csrf
                    <input name="name" placeholder="Category name" required class="input sm:max-w-[200px]">
                    <input name="description" placeholder="Short description (optional)" class="input">
                    <button class="btn-primary shrink-0"><x-icon name="plus" class="w-4 h-4" /> Add</button>
                </form>
                @error('name')<p class="text-xs text-red-600 mt-2">{{ $message }}</p>@enderror
            </div>
            <ul class="divide-y divide-zinc-100">
                @forelse ($categories as $c)
                    <li x-data="{ edit: false }" class="px-5 py-3">
                        <div x-show="!edit" class="flex items-center gap-3">
                            <span class="grid place-items-center w-9 h-9 rounded-lg bg-brand-50 text-brand-600"><x-icon name="folder" class="w-4 h-4" /></span>
                            <a href="{{ route('notes.index', ['category' => $c->id]) }}" class="flex-1 min-w-0 group">
                                <div class="text-sm font-medium text-zinc-900 group-hover:text-brand-700">{{ $c->name }}</div>
                                <div class="text-xs text-zinc-500 truncate">{{ $c->description ?: $c->notes_count.' '.Str::plural('note', $c->notes_count) }}</div>
                            </a>
                            @if ($c->description)<span class="text-xs text-zinc-400 hidden sm:block">{{ $c->notes_count }} {{ Str::plural('note', $c->notes_count) }}</span>@endif
                            <button @click="edit = true" class="btn-ghost btn-sm">Edit</button>
                            <form method="POST" action="{{ route('categories.destroy', $c) }}" onsubmit="return confirm('Delete this category? Its notes will be kept.')">@csrf @method('DELETE')
                                <button class="btn-ghost btn-sm text-zinc-400 hover:text-red-600" title="Delete"><x-icon name="trash" class="w-4 h-4" /></button></form>
                        </div>
                        <form x-show="edit" x-cloak method="POST" action="{{ route('categories.update', $c) }}" class="flex flex-col sm:flex-row gap-2">
                            @csrf @method('PUT')
                            <input name="name" value="{{ $c->name }}" class="input sm:max-w-[200px]">
                            <input name="description" value="{{ $c->description }}" placeholder="Description" class="input">
                            <div class="flex gap-2 shrink-0"><button class="btn-primary">Save</button><button type="button" @click="edit = false" class="btn-ghost">Cancel</button></div>
                        </form>
                    </li>
                @empty
                    <li class="px-5 py-10 text-center text-sm text-zinc-400">No categories yet. Add one above.</li>
                @endforelse
            </ul>
        </section>

        <section class="card card-pad">
            <h2 class="text-sm font-semibold text-zinc-900 flex items-center gap-2"><x-icon name="tag" class="w-4 h-4 text-zinc-400" /> Tags</h2>
            <div class="mt-3 flex flex-wrap gap-2">
                @forelse ($tags as $t)
                    <a href="{{ route('notes.index', ['tag' => $t->id]) }}" class="inline-flex items-center gap-1.5 rounded-full border border-zinc-200 bg-white px-3 py-1 text-sm text-zinc-700 hover:border-brand-300 hover:text-brand-700 transition">
                        #{{ $t->name }} <span class="text-xs text-zinc-400">{{ $t->notes_count }}</span></a>
                @empty
                    <p class="text-sm text-zinc-400">Add tags while editing a note.</p>
                @endforelse
            </div>
        </section>
    </div>
</x-app-layout>
