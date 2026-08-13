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

class ReviewQueueTest extends TestCase
{
    use RefreshDatabase;

    private function pendingGuide(User $author): Guide
    {
        $category = Category::create(['name' => 'Deposits', 'slug' => 'deposits', 'order' => 1]);

        $guide = Guide::create([
            'category_id' => $category->id,
            'title' => 'Opening an account',
            'slug' => 'opening-an-account',
            'language' => 'en',
            'status' => GuideStatus::Pending,
            'created_by' => $author->id,
        ]);

        Step::create(['guide_id' => $guide->id, 'order' => 1, 'title' => 'Step one', 'body' => '<p>Do it.</p>']);

        return $guide;
    }

    private function approver(): User
    {
        PermissionRole::findOrCreate(Role::Approver->value, 'web');
        $user = User::factory()->create();
        $user->assignRole(Role::Approver->value);

        return $user;
    }

    public function test_contributor_cannot_reach_the_review_queue(): void
    {
        PermissionRole::findOrCreate(Role::Contributor->value, 'web');
        $contributor = User::factory()->create();
        $contributor->assignRole(Role::Contributor->value);

        $this->actingAs($contributor)->get('/review')->assertForbidden();
    }

    public function test_approver_sees_pending_guides_in_queue(): void
    {
        PermissionRole::findOrCreate(Role::Contributor->value, 'web');
        $author = User::factory()->create();
        $author->assignRole(Role::Contributor->value);
        $guide = $this->pendingGuide($author);
        $approver = $this->approver();

        $this->actingAs($approver)
            ->get('/review')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Review/Index')
                ->has('guides', 1)
                ->where('guides.0.title', $guide->title)
            );
    }

    public function test_approver_can_publish_a_pending_guide_and_a_version_snapshot_is_created(): void
    {
        PermissionRole::findOrCreate(Role::Contributor->value, 'web');
        $author = User::factory()->create();
        $author->assignRole(Role::Contributor->value);
        $guide = $this->pendingGuide($author);
        $approver = $this->approver();

        $this->actingAs($approver)
            ->post("/review/{$guide->slug}/publish")
            ->assertRedirect('/review');

        $guide->refresh();
        $this->assertSame(GuideStatus::Published, $guide->status);
        $this->assertSame($approver->id, $guide->updated_by);

        $version = $guide->versions()->first();
        $this->assertNotNull($version);
        $this->assertSame('Opening an account', $version->snapshot['title']);
        $this->assertCount(1, $version->snapshot['steps']);
        $this->assertSame('Step one', $version->snapshot['steps'][0]['title']);
    }

    public function test_approver_can_send_a_pending_guide_back_to_draft(): void
    {
        PermissionRole::findOrCreate(Role::Contributor->value, 'web');
        $author = User::factory()->create();
        $author->assignRole(Role::Contributor->value);
        $guide = $this->pendingGuide($author);
        $approver = $this->approver();

        $this->actingAs($approver)
            ->post("/review/{$guide->slug}/send-back")
            ->assertRedirect('/review');

        $this->assertSame(GuideStatus::Draft, $guide->fresh()->status);
        $this->assertCount(0, $guide->versions);
    }

    public function test_author_cannot_publish_their_own_guide(): void
    {
        PermissionRole::findOrCreate(Role::Contributor->value, 'web');
        $author = User::factory()->create();
        $author->assignRole(Role::Contributor->value);
        $guide = $this->pendingGuide($author);

        $this->actingAs($author)
            ->post("/review/{$guide->slug}/publish")
            ->assertForbidden();
    }

    public function test_cannot_publish_a_guide_that_is_not_pending(): void
    {
        PermissionRole::findOrCreate(Role::Contributor->value, 'web');
        $author = User::factory()->create();
        $author->assignRole(Role::Contributor->value);
        $guide = $this->pendingGuide($author);
        $guide->update(['status' => GuideStatus::Draft]);
        $approver = $this->approver();

        $this->actingAs($approver)
            ->post("/review/{$guide->slug}/publish")
            ->assertStatus(422);
    }
}
