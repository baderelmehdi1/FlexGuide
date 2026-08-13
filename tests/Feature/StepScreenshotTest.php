<?php

namespace Tests\Feature;

use App\Enums\GuideStatus;
use App\Enums\Role;
use App\Models\Category;
use App\Models\Guide;
use App\Models\Step;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role as PermissionRole;
use Tests\TestCase;

class StepScreenshotTest extends TestCase
{
    use RefreshDatabase;

    private function stepFor(User $owner): Step
    {
        $category = Category::create(['name' => 'Deposits', 'slug' => 'deposits', 'order' => 1]);

        $guide = Guide::create([
            'category_id' => $category->id,
            'title' => 'Opening an account',
            'slug' => 'opening-an-account',
            'language' => 'en',
            'status' => GuideStatus::Draft,
            'created_by' => $owner->id,
        ]);

        return Step::create([
            'guide_id' => $guide->id,
            'order' => 1,
        ]);
    }

    public function test_author_can_upload_a_screenshot(): void
    {
        Storage::fake('public');
        PermissionRole::findOrCreate(Role::Contributor->value, 'web');
        $owner = User::factory()->create();
        $owner->assignRole(Role::Contributor->value);
        $step = $this->stepFor($owner);

        $response = $this->actingAs($owner)->post("/steps/{$step->id}/screenshots", [
            'file' => UploadedFile::fake()->image('screen.png', 800, 600),
        ]);

        $response->assertCreated();
        $this->assertSame(1, $step->fresh()->getMedia('screenshots')->count());
    }

    public function test_non_owner_contributor_cannot_upload(): void
    {
        Storage::fake('public');
        PermissionRole::findOrCreate(Role::Contributor->value, 'web');
        $owner = User::factory()->create();
        $owner->assignRole(Role::Contributor->value);
        $other = User::factory()->create();
        $other->assignRole(Role::Contributor->value);
        $step = $this->stepFor($owner);

        $this->actingAs($other)
            ->post("/steps/{$step->id}/screenshots", [
                'file' => UploadedFile::fake()->image('screen.png'),
            ])
            ->assertForbidden();
    }

    public function test_author_can_delete_a_screenshot(): void
    {
        Storage::fake('public');
        PermissionRole::findOrCreate(Role::Contributor->value, 'web');
        $owner = User::factory()->create();
        $owner->assignRole(Role::Contributor->value);
        $step = $this->stepFor($owner);

        $media = $step->addMedia(UploadedFile::fake()->image('screen.png'))->toMediaCollection('screenshots');

        $this->actingAs($owner)
            ->delete("/steps/{$step->id}/screenshots/{$media->id}")
            ->assertRedirect();

        $this->assertSame(0, $step->fresh()->getMedia('screenshots')->count());
    }
}
