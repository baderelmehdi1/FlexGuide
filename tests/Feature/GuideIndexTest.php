<?php

namespace Tests\Feature;

use App\Enums\GuideStatus;
use App\Models\Category;
use App\Models\Guide;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class GuideIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/guides')->assertRedirect('/login');
    }

    public function test_authenticated_user_sees_published_guides_and_category_tree(): void
    {
        $category = Category::create(['name' => 'Deposits', 'slug' => 'deposits', 'order' => 1]);

        Guide::create([
            'category_id' => $category->id,
            'title' => 'Opening an account',
            'slug' => 'opening-an-account',
            'language' => 'en',
            'status' => GuideStatus::Published,
        ]);

        Guide::create([
            'category_id' => $category->id,
            'title' => 'Someone else draft',
            'slug' => 'someone-else-draft',
            'language' => 'en',
            'status' => GuideStatus::Draft,
        ]);

        $this->actingAs(User::factory()->create())
            ->get('/guides')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Guides/Index')
                ->has('guides', 1)
                ->where('guides.0.title', 'Opening an account')
                ->has('sidebarCategories', 1)
                ->where('sidebarCategories.0.name', 'Deposits')
            );
    }
}
