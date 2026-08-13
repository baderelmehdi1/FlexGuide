<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LocaleSwitchTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_locale_is_arabic_and_rtl(): void
    {
        $this->get('/login')
            ->assertInertia(fn (Assert $page) => $page
                ->where('locale.current', 'ar')
                ->where('locale.direction', 'rtl')
            );
    }

    public function test_switching_to_english_persists_in_session(): void
    {
        $this->post('/locale', ['locale' => 'en'])->assertRedirect();

        $this->get('/login')
            ->assertInertia(fn (Assert $page) => $page
                ->where('locale.current', 'en')
                ->where('locale.direction', 'ltr')
            );
    }

    public function test_invalid_locale_is_rejected(): void
    {
        $this->post('/locale', ['locale' => 'fr'])->assertSessionHasErrors('locale');
    }
}
