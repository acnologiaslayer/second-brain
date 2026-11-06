<x-app-layout>
    <x-slot name="title">Organize</x-slot>
    <div class="max-w-4xl mx-auto space-y-6">
        <div>
            <div class="eyebrow">Library</div>
            <h1 class="page-title mt-1">Organize</h1>
            <p class="text-sm text-zinc-500 mt-1">Group notes into categories. Tags are created automatically from your notes.</p>
        </div>

        <section class="card">
            <div class="p-5 border-b border-zinc-200">
                <h2 class="eyebrow">Categories · {{ str_pad($categories->count(), 2, '0', STR_PAD_LEFT) }}</h2>
                <form method="POST" action="{{ route('categories.store') }}" class="mt-3 flex flex-col sm:flex-row gap-2">
                    @csrf
                    <input name="name" placeholder="Category name" required class="input sm:max-w-[200px]">
                    <input name="description" placeholder="Short description (optional)" class="input">
                    <button class="btn-primary shrink-0"><x-icon name="plus" class="w-4 h-4" /> Add</button>
                </form>
                @error('name')<p class="text-xs text-red-600 mt-2">{{ $message }}</p>@enderror
            </div>
            <ul class="divide-y divide-zinc-200">
                @forelse ($categories as $c)
                    <li x-data="{ edit: false }" class="px-5 py-3">
                        <div x-show="!edit" class="flex items-center gap-3">
                            <span class="led bg-brand-500"></span>
                            <a href="{{ route('notes.index', ['category' => $c->id]) }}" class="flex-1 min-w-0 group">
                                <div class="text-sm font-medium text-zinc-900 group-hover:text-brand-700">{{ $c->name }}</div>
                                <div class="text-xs text-zinc-500 truncate">{{ $c->description ?: 'No description' }}</div>
                            </a>
                            <span class="font-mono text-[11px] text-zinc-500 hidden sm:block tabular-nums">{{ str_pad($c->notes_count, 2, '0', STR_PAD_LEFT) }} notes</span>
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
                    <li class="px-5 py-8 text-center font-mono text-xs text-zinc-400">// no categories yet</li>
                @endforelse
            </ul>
        </section>

        <section class="card card-pad">
            <h2 class="eyebrow">Tags · {{ str_pad($tags->count(), 2, '0', STR_PAD_LEFT) }}</h2>
            <form method="POST" action="{{ route('tags.store') }}" class="mt-3 flex gap-2 max-w-sm">
                @csrf
                <input name="name" placeholder="New tag" required maxlength="50" class="input">
                <button class="btn-secondary shrink-0"><x-icon name="plus" class="w-4 h-4" /> Add</button>
            </form>
            <div class="mt-4 flex flex-wrap gap-2">
                @forelse ($tags as $t)
                    <span class="inline-flex items-center border border-zinc-300 bg-paper font-mono text-[12px] text-zinc-700">
                        <a href="{{ route('notes.index', ['tag' => $t->id]) }}" class="px-2.5 py-1 hover:text-brand-600">#{{ $t->name }} <span class="text-zinc-400">{{ $t->notes_count }}</span></a>
                        <form method="POST" action="{{ route('tags.destroy', $t) }}" onsubmit="return confirm('Delete tag #{{ $t->name }}? Notes are kept.')">@csrf @method('DELETE')
                            <button class="px-1.5 py-1 border-l border-zinc-200 text-zinc-400 hover:text-err" title="Delete tag">×</button>
                        </form>
                    </span>
                @empty
                    <p class="text-sm text-zinc-400">Add tags here or while editing a note.</p>
                @endforelse
            </div>
        </section>
    </div>
</x-app-layout>
