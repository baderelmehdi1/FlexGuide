<?php

namespace Tests\Feature;

use App\Enums\GuideStatus;
use App\Enums\Role;
use App\Models\Category;
use App\Models\Guide;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role as PermissionRole;
use Tests\TestCase;

class GuideBuilderTest extends TestCase
{
    use RefreshDatabase;

    private function contributor(): User
    {
        PermissionRole::findOrCreate(Role::Contributor->value, 'web');
        $user = User::factory()->create();
        $user->assignRole(Role::Contributor->value);

        return $user;
    }

    public function test_viewer_cannot_reach_the_builder(): void
    {
        PermissionRole::findOrCreate(Role::Viewer->value, 'web');
        $viewer = User::factory()->create();
        $viewer->assignRole(Role::Viewer->value);

        $this->actingAs($viewer)->get('/guides/create')->assertForbidden();
    }

    public function test_contributor_can_create_a_draft_guide(): void
    {
        $category = Category::create(['name' => 'Deposits', 'slug' => 'deposits', 'order' => 1]);
        $contributor = $this->contributor();

        $this->actingAs($contributor)
            ->post('/guides', [
                'title' => 'Closing an account',
                'description' => 'How to close a customer account.',
                'category_id' => $category->id,
                'language' => 'en',
            ])
            ->assertRedirect();

        $guide = Guide::firstWhere('title', 'Closing an account');

        $this->assertNotNull($guide);
        $this->assertSame(GuideStatus::Draft, $guide->status);
        $this->assertSame($contributor->id, $guide->created_by);
        $this->assertSame('closing-an-account', $guide->slug);
    }

    public function test_contributor_cannot_edit_another_contributors_guide(): void
    {
        $category = Category::create(['name' => 'Deposits', 'slug' => 'deposits', 'order' => 1]);
        $owner = $this->contributor();
        $other = $this->contributor();

        $guide = Guide::create([
            'category_id' => $category->id,
            'title' => 'Owned guide',
            'slug' => 'owned-guide',
            'language' => 'en',
            'status' => GuideStatus::Draft,
            'created_by' => $owner->id,
        ]);

        $this->actingAs($other)->get("/guides/{$guide->slug}/edit")->assertForbidden();
    }

    public function test_author_can_add_a_step_with_sanitized_body_and_submit_for_review(): void
    {
        $category = Category::create(['name' => 'Deposits', 'slug' => 'deposits', 'order' => 1]);
        $contributor = $this->contributor();

        $guide = Guide::create([
            'category_id' => $category->id,
            'title' => 'Opening an account',
            'slug' => 'opening-an-account',
            'language' => 'en',
            'status' => GuideStatus::Draft,
            'created_by' => $contributor->id,
        ]);

        $this->actingAs($contributor)
            ->post("/guides/{$guide->slug}/steps")
            ->assertRedirect();

        $step = $guide->steps()->first();
        $this->assertNotNull($step);

        $this->actingAs($contributor)
            ->patch("/steps/{$step->id}", [
                'title' => 'Open STDCUSAC',
                'warning' => 'Double-check the branch code.',
                'body' => '<p onclick="alert(1)">Navigate to <strong>STDCUSAC</strong>.</p><script>alert(2)</script>',
            ])
            ->assertRedirect();

        $step->refresh();
        $this->assertSame('Open STDCUSAC', $step->title);
        $this->assertStringNotContainsString('<script>', $step->body);
        $this->assertStringNotContainsString('onclick', $step->body);
        $this->assertStringContainsString('<strong>STDCUSAC</strong>', $step->body);

        $this->actingAs($contributor)
            ->post("/guides/{$guide->slug}/submit")
            ->assertRedirect("/guides/{$guide->slug}");

        $this->assertSame(GuideStatus::Pending, $guide->fresh()->status);
    }

    public function test_cannot_submit_for_review_without_steps(): void
    {
        $category = Category::create(['name' => 'Deposits', 'slug' => 'deposits', 'order' => 1]);
        $contributor = $this->contributor();

        $guide = Guide::create([
            'category_id' => $category->id,
            'title' => 'Empty guide',
            'slug' => 'empty-guide',
            'language' => 'en',
            'status' => GuideStatus::Draft,
            'created_by' => $contributor->id,
        ]);

        $this->actingAs($contributor)
            ->post("/guides/{$guide->slug}/submit")
            ->assertStatus(422);

        $this->assertSame(GuideStatus::Draft, $guide->fresh()->status);
    }
}
