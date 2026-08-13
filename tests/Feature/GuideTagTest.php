<?php

namespace Tests\Feature;

use App\Enums\GuideStatus;
use App\Enums\Role;
use App\Models\Category;
use App\Models\Guide;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role as PermissionRole;
use Tests\TestCase;

class GuideTagTest extends TestCase
{
    use RefreshDatabase;

    private function guideFor(User $owner): Guide
    {
        $category = Category::create(['name' => 'Deposits', 'slug' => 'deposits', 'order' => 1]);

        return Guide::create([
            'category_id' => $category->id,
            'title' => 'Opening an account',
            'slug' => 'opening-an-account',
            'language' => 'en',
            'status' => GuideStatus::Draft,
            'created_by' => $owner->id,
        ]);
    }

    public function test_author_can_attach_new_and_existing_tags(): void
    {
        PermissionRole::findOrCreate(Role::Contributor->value, 'web');
        $owner = User::factory()->create();
        $owner->assignRole(Role::Contributor->value);
        $guide = $this->guideFor($owner);
        $existing = Tag::create(['name' => 'STDCUSAC']);

        $this->actingAs($owner)
            ->patch("/guides/{$guide->slug}", [
                'title' => $guide->title,
                'category_id' => $guide->category_id,
                'language' => $guide->language,
                'tags' => ['STDCUSAC', 'New Accounts'],
            ])
            ->assertRedirect();

        $guide->refresh();
        $this->assertCount(2, $guide->tags);
        $this->assertTrue($guide->tags->contains('id', $existing->id));
        $this->assertTrue($guide->tags->pluck('name')->contains('New Accounts'));
        $this->assertSame(1, Tag::where('name', 'STDCUSAC')->count());
    }

    public function test_removing_all_tags_detaches_them(): void
    {
        PermissionRole::findOrCreate(Role::Contributor->value, 'web');
        $owner = User::factory()->create();
        $owner->assignRole(Role::Contributor->value);
        $guide = $this->guideFor($owner);
        $guide->tags()->attach(Tag::create(['name' => 'Old Tag'])->id);

        $this->actingAs($owner)
            ->patch("/guides/{$guide->slug}", [
                'title' => $guide->title,
                'category_id' => $guide->category_id,
                'language' => $guide->language,
                'tags' => [],
            ])
            ->assertRedirect();

        $this->assertCount(0, $guide->fresh()->tags);
        $this->assertDatabaseHas('tags', ['name' => 'Old Tag']);
    }
}
