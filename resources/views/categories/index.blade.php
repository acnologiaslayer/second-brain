<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Categories &amp; Tags</h2></x-slot>
    <div class="py-8 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <div class="bg-white rounded-lg shadow-sm p-6">
            <form method="POST" action="{{ route('categories.store') }}" class="flex flex-wrap gap-2 mb-2">
                @csrf
                <input name="name" placeholder="New category name" required class="rounded-md border-gray-300 flex-1">
                <input name="description" placeholder="Description (optional)" class="rounded-md border-gray-300 flex-1">
                <x-primary-button>Add</x-primary-button>
            </form>
            <x-input-error :messages="$errors->get('name')" class="mb-2" />
            <div class="divide-y">
                @forelse ($categories as $c)
                    <div x-data="{ edit: false }" class="py-3">
                        <div x-show="!edit" class="flex justify-between items-center">
                            <div>
                                <a href="{{ route('notes.index', ['category' => $c->id]) }}" class="font-medium hover:text-indigo-600">{{ $c->name }}</a>
                                <span class="text-xs text-gray-400">{{ $c->notes_count }} notes</span>
                                @if ($c->description)<div class="text-sm text-gray-500">{{ $c->description }}</div>@endif
                            </div>
                            <div class="flex gap-3 text-sm">
                                <button @click="edit = true" class="text-indigo-600">Edit</button>
                                <form method="POST" action="{{ route('categories.destroy', $c) }}" onsubmit="return confirm('Delete category? Notes will be kept.')">@csrf @method('DELETE')<button class="text-red-600">Delete</button></form>
                            </div>
                        </div>
                        <form x-show="edit" x-cloak method="POST" action="{{ route('categories.update', $c) }}" class="flex gap-2">
                            @csrf @method('PUT')
                            <input name="name" value="{{ $c->name }}" class="rounded-md border-gray-300 flex-1">
                            <input name="description" value="{{ $c->description }}" class="rounded-md border-gray-300 flex-1">
                            <button class="bg-indigo-600 text-white px-3 rounded-md text-sm">Save</button>
                            <button type="button" @click="edit = false" class="text-sm text-gray-500">Cancel</button>
                        </form>
                    </div>
                @empty <p class="text-gray-500 text-sm">No categories yet.</p> @endforelse
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="font-semibold mb-3">Tags</h3>
            <p class="text-sm text-gray-500 mb-3">Tags are created automatically when you add them to notes.</p>
            <div class="flex flex-wrap gap-2">
                @forelse ($tags as $t)
                    <a href="{{ route('notes.index', ['tag' => $t->id]) }}" class="bg-gray-100 hover:bg-indigo-50 px-3 py-1 rounded-full text-sm">#{{ $t->name }} <span class="text-gray-400">{{ $t->notes_count }}</span></a>
                @empty <p class="text-gray-500 text-sm">No tags yet.</p> @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
