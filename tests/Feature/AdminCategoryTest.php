<?php

namespace Tests\Feature;

use App\Enums\GuideStatus;
use App\Enums\Role;
use App\Models\Category;
use App\Models\Guide;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role as PermissionRole;
use Tests\TestCase;

class AdminCategoryTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        PermissionRole::findOrCreate(Role::Admin->value, 'web');
        $user = User::factory()->create();
        $user->assignRole(Role::Admin->value);

        return $user;
    }

    public function test_non_admin_cannot_reach_admin_area(): void
    {
        PermissionRole::findOrCreate(Role::Approver->value, 'web');
        $approver = User::factory()->create();
        $approver->assignRole(Role::Approver->value);

        $this->actingAs($approver)->get('/admin')->assertForbidden();
        $this->actingAs($approver)->get('/admin/categories')->assertForbidden();
    }

    public function test_admin_can_create_a_top_level_category(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/categories', ['name' => 'Trade Finance'])
            ->assertRedirect();

        $this->assertDatabaseHas('categories', ['name' => 'Trade Finance', 'slug' => 'trade-finance', 'parent_id' => null]);
    }

    public function test_admin_can_create_a_subcategory(): void
    {
        $admin = $this->admin();
        $parent = Category::create(['name' => 'Lending', 'slug' => 'lending', 'order' => 1]);

        $this->actingAs($admin)
            ->post('/admin/categories', ['name' => 'Mortgages', 'parent_id' => $parent->id])
            ->assertRedirect();

        $this->assertDatabaseHas('categories', ['name' => 'Mortgages', 'parent_id' => $parent->id]);
    }

    public function test_admin_can_rename_and_reorder_a_category(): void
    {
        $admin = $this->admin();
        $category = Category::create(['name' => 'Old Name', 'slug' => 'old-name', 'order' => 1]);

        $this->actingAs($admin)
            ->patch("/admin/categories/{$category->id}", ['name' => 'New Name', 'parent_id' => null, 'order' => 5])
            ->assertRedirect();

        $category->refresh();
        $this->assertSame('New Name', $category->name);
        $this->assertSame(5, $category->order);
    }

    public function test_category_cannot_be_moved_under_its_own_descendant(): void
    {
        $admin = $this->admin();
        $parent = Category::create(['name' => 'Lending', 'slug' => 'lending', 'order' => 1]);
        $child = Category::create(['name' => 'Mortgages', 'slug' => 'mortgages', 'parent_id' => $parent->id, 'order' => 1]);

        $this->actingAs($admin)
            ->patch("/admin/categories/{$parent->id}", ['name' => 'Lending', 'parent_id' => $child->id, 'order' => 1])
            ->assertSessionHasErrors('parent_id');
    }

    public function test_category_cannot_be_deleted_while_it_has_guides(): void
    {
        $admin = $this->admin();
        $category = Category::create(['name' => 'Lending', 'slug' => 'lending', 'order' => 1]);
        Guide::create([
            'category_id' => $category->id,
            'title' => 'A guide',
            'slug' => 'a-guide',
            'language' => 'en',
            'status' => GuideStatus::Draft,
        ]);

        $this->actingAs($admin)
            ->delete("/admin/categories/{$category->id}")
            ->assertSessionHasErrors('category');

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_category_cannot_be_deleted_while_it_has_subcategories(): void
    {
        $admin = $this->admin();
        $parent = Category::create(['name' => 'Lending', 'slug' => 'lending', 'order' => 1]);
        Category::create(['name' => 'Mortgages', 'slug' => 'mortgages', 'parent_id' => $parent->id, 'order' => 1]);

        $this->actingAs($admin)
            ->delete("/admin/categories/{$parent->id}")
            ->assertSessionHasErrors('category');
    }

    public function test_empty_category_can_be_deleted(): void
    {
        $admin = $this->admin();
        $category = Category::create(['name' => 'Empty', 'slug' => 'empty', 'order' => 1]);

        $this->actingAs($admin)
            ->delete("/admin/categories/{$category->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }
}
