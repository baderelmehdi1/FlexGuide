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

class MediaAnnotationTest extends TestCase
{
    use RefreshDatabase;

    public function test_author_can_save_box_and_point_annotations(): void
    {
        Storage::fake('public');
        PermissionRole::findOrCreate(Role::Contributor->value, 'web');
        $owner = User::factory()->create();
        $owner->assignRole(Role::Contributor->value);

        $category = Category::create(['name' => 'Deposits', 'slug' => 'deposits', 'order' => 1]);
        $guide = Guide::create([
            'category_id' => $category->id,
            'title' => 'Opening an account',
            'slug' => 'opening-an-account',
            'language' => 'en',
            'status' => GuideStatus::Draft,
            'created_by' => $owner->id,
        ]);
        $step = Step::create(['guide_id' => $guide->id, 'order' => 1]);
        $media = $step->addMedia(UploadedFile::fake()->image('screen.png'))->toMediaCollection('screenshots');

        $shapes = [
            ['type' => 'box', 'x' => 10, 'y' => 10, 'w' => 20, 'h' => 15],
            ['type' => 'point', 'x' => 50, 'y' => 50, 'text' => 'Click here'],
        ];

        $this->actingAs($owner)
            ->patch("/media/{$media->id}/annotations", ['shapes' => $shapes])
            ->assertRedirect();

        $this->assertSame($shapes, $media->fresh()->getCustomProperty('annotations'));
    }

    public function test_annotation_coordinates_out_of_range_are_rejected(): void
    {
        Storage::fake('public');
        PermissionRole::findOrCreate(Role::Contributor->value, 'web');
        $owner = User::factory()->create();
        $owner->assignRole(Role::Contributor->value);

        $category = Category::create(['name' => 'Deposits', 'slug' => 'deposits', 'order' => 1]);
        $guide = Guide::create([
            'category_id' => $category->id,
            'title' => 'Opening an account',
            'slug' => 'opening-an-account',
            'language' => 'en',
            'status' => GuideStatus::Draft,
            'created_by' => $owner->id,
        ]);
        $step = Step::create(['guide_id' => $guide->id, 'order' => 1]);
        $media = $step->addMedia(UploadedFile::fake()->image('screen.png'))->toMediaCollection('screenshots');

        $this->actingAs($owner)
            ->patch("/media/{$media->id}/annotations", [
                'shapes' => [['type' => 'box', 'x' => 150, 'y' => 10, 'w' => 20, 'h' => 15]],
            ])
            ->assertSessionHasErrors('shapes.0.x');
    }

    public function test_non_owner_cannot_annotate(): void
    {
        Storage::fake('public');
        PermissionRole::findOrCreate(Role::Contributor->value, 'web');
        $owner = User::factory()->create();
        $owner->assignRole(Role::Contributor->value);
        $other = User::factory()->create();
        $other->assignRole(Role::Contributor->value);

        $category = Category::create(['name' => 'Deposits', 'slug' => 'deposits', 'order' => 1]);
        $guide = Guide::create([
            'category_id' => $category->id,
            'title' => 'Opening an account',
            'slug' => 'opening-an-account',
            'language' => 'en',
            'status' => GuideStatus::Draft,
            'created_by' => $owner->id,
        ]);
        $step = Step::create(['guide_id' => $guide->id, 'order' => 1]);
        $media = $step->addMedia(UploadedFile::fake()->image('screen.png'))->toMediaCollection('screenshots');

        $this->actingAs($other)
            ->patch("/media/{$media->id}/annotations", ['shapes' => []])
            ->assertForbidden();
    }
}
