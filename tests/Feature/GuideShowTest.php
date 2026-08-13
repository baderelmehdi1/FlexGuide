<?php

namespace Tests\Feature;

use App\Enums\GuideStatus;
use App\Enums\Role;
use App\Models\Category;
use App\Models\Guide;
use App\Models\Step;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role as PermissionRole;
use Tests\TestCase;

class GuideShowTest extends TestCase
{
    use RefreshDatabase;

    private function makeGuide(GuideStatus $status, ?int $createdBy = null): Guide
    {
        $category = Category::create(['name' => 'Deposits', 'slug' => 'deposits', 'order' => 1]);

        $guide = Guide::create([
            'category_id' => $category->id,
            'title' => 'Opening an account',
            'slug' => 'opening-an-account',
            'language' => 'en',
            'status' => $status,
            'created_by' => $createdBy,
        ]);

        Step::create([
            'guide_id' => $guide->id,
            'order' => 1,
            'title' => 'First step',
            'body' => '<p>Do the thing.</p>',
        ]);

        return $guide;
    }

    public function test_published_guide_is_visible_to_any_authenticated_user(): void
    {
        $guide = $this->makeGuide(GuideStatus::Published);

        $this->actingAs(User::factory()->create())
            ->get("/guides/{$guide->slug}")
            ->assertInertia(fn (Assert $page) => $page
                ->component('Guides/Show')
                ->where('guide.title', 'Opening an account')
                ->has('guide.steps', 1)
            );
    }

    public function test_draft_guide_is_hidden_from_unrelated_viewer(): void
    {
        $owner = User::factory()->create();
        $guide = $this->makeGuide(GuideStatus::Draft, $owner->id);

        $this->actingAs(User::factory()->create())
            ->get("/guides/{$guide->slug}")
            ->assertForbidden();
    }

    public function test_draft_guide_is_visible_to_its_author(): void
    {
        $owner = User::factory()->create();
        $guide = $this->makeGuide(GuideStatus::Draft, $owner->id);

        $this->actingAs($owner)
            ->get("/guides/{$guide->slug}")
            ->assertInertia(fn (Assert $page) => $page->component('Guides/Show'));
    }

    public function test_draft_guide_is_visible_to_an_approver(): void
    {
        PermissionRole::findOrCreate(Role::Approver->value, 'web');
        $approver = User::factory()->create();
        $approver->assignRole(Role::Approver->value);

        $owner = User::factory()->create();
        $guide = $this->makeGuide(GuideStatus::Draft, $owner->id);

        $this->actingAs($approver)
            ->get("/guides/{$guide->slug}")
            ->assertInertia(fn (Assert $page) => $page
                ->component('Guides/Show')
                ->where('guide.canEdit', true)
            );
    }
}
