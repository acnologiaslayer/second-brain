<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        return view('categories.index', [
            'categories' => $request->user()->categories()->withCount('notes')->orderBy('name')->get(),
            'tags' => $request->user()->tags()->withCount('notes')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $request->user()->categories()->create($this->validated($request));
        return back()->with('status', 'Category created.');
    }

    public function update(Request $request, Category $category)
    {
        $this->authorize('update', $category);
        $category->update($this->validated($request, $category));
        return back()->with('status', 'Category updated.');
    }

    public function destroy(Category $category)
    {
        $this->authorize('delete', $category);
        $category->delete(); // notes keep existing, category_id nulled by FK
        return back()->with('status', 'Category deleted.');
    }

    protected function validated(Request $request, ?Category $category = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('categories')->where('user_id', $request->user()->id)->ignore($category)],
            'description' => ['nullable', 'string', 'max:500'],
        ]);
    }
}
