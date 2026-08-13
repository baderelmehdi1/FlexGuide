<?php

namespace Tests\Feature;

use App\Enums\GuideStatus;
use App\Enums\Role;
use App\Models\Category;
use App\Models\Guide;
use App\Models\Step;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role as PermissionRole;
use Tests\TestCase;

class StepReorderTest extends TestCase
{
    use RefreshDatabase;

    public function test_author_can_reorder_steps(): void
    {
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

        $first = Step::create(['guide_id' => $guide->id, 'order' => 1, 'title' => 'First']);
        $second = Step::create(['guide_id' => $guide->id, 'order' => 2, 'title' => 'Second']);

        $this->actingAs($owner)
            ->patch("/guides/{$guide->slug}/steps/reorder", [
                'stepIds' => [$second->id, $first->id],
            ])
            ->assertRedirect();

        $this->assertSame(1, $second->fresh()->order);
        $this->assertSame(2, $first->fresh()->order);
    }
}
