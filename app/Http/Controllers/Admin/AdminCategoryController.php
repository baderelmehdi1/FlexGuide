<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class AdminCategoryController extends Controller
{
    public function index(): InertiaResponse
    {
        $categories = Category::query()
            ->withCount(['guides', 'children'])
            ->orderBy('parent_id')
            ->orderBy('order')
            ->get();

        return Inertia::render('Admin/Categories', [
            'categories' => $categories->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'parentId' => $category->parent_id,
                'order' => $category->order,
                'guidesCount' => $category->guides_count,
                'childrenCount' => $category->children_count,
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
        ]);

        $nextOrder = (int) Category::where('parent_id', $data['parent_id'] ?? null)->max('order') + 1;

        Category::create([
            'name' => $data['name'],
            'slug' => $this->uniqueSlug($data['name']),
            'parent_id' => $data['parent_id'] ?? null,
            'order' => $nextOrder,
        ]);

        return back()->with('status', 'Category created.');
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
            'order' => ['required', 'integer', 'min:0'],
        ]);

        if ($this->wouldCreateCycle($category, $data['parent_id'] ?? null)) {
            throw ValidationException::withMessages([
                'parent_id' => 'A category cannot be moved under itself or one of its own subcategories.',
            ]);
        }

        $category->update([
            'name' => $data['name'],
            'slug' => $data['name'] === $category->name ? $category->slug : $this->uniqueSlug($data['name'], $category->id),
            'parent_id' => $data['parent_id'] ?? null,
            'order' => $data['order'],
        ]);

        return back()->with('status', 'Category updated.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->guides()->exists()) {
            throw ValidationException::withMessages([
                'category' => 'This category still has guides in it -- move or delete them first.',
            ]);
        }

        if ($category->children()->exists()) {
            throw ValidationException::withMessages([
                'category' => 'This category still has subcategories -- move or delete them first.',
            ]);
        }

        $category->delete();

        return back()->with('status', 'Category deleted.');
    }

    /**
     * Walks up the proposed new parent's own ancestry looking for the
     * category being moved -- without this, re-parenting a category under
     * its own descendant would silently create an unreachable loop in the
     * tree (CategoryTree.vue recurses on children() and would recurse
     * forever, or -- more likely -- just never render the cycle at all).
     */
    private function wouldCreateCycle(Category $category, ?int $newParentId): bool
    {
        if ($newParentId === null) {
            return false;
        }

        if ($newParentId === $category->id) {
            return true;
        }

        $current = Category::find($newParentId);

        while ($current) {
            if ($current->id === $category->id) {
                return true;
            }

            $current = $current->parent;
        }

        return false;
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $suffix = 1;

        while (
            Category::where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
