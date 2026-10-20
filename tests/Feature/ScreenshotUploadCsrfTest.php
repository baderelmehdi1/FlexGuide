<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Guide;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EnforceScreenshotCsrf extends ValidateCsrfToken
{
    // Laravel normally bypasses CSRF verification during PHPUnit requests.
    protected function runningUnitTests(): bool
    {
        return false;
    }
}

class ScreenshotUploadCsrfTest extends TestCase
{
    use RefreshDatabase;

    public function test_upload_after_login_accepts_current_cookie_header_and_rejects_stale_page_token(): void
    {
        Storage::fake('public');
        $this->app->bind(ValidateCsrfToken::class, EnforceScreenshotCsrf::class);
        Role::findOrCreate('contributor', 'web');
        $owner = User::factory()->create(['password' => 'synthetic-test-password-only']);
        $owner->assignRole('contributor');
        $category = Category::create(['name' => 'Deposits', 'slug' => 'deposits']);
        $guide = Guide::create([
            'category_id' => $category->id,
            'title' => 'Upload after login',
            'slug' => 'upload-after-login',
            'status' => 'draft',
            'language' => 'en',
            'created_by' => $owner->id,
        ]);
        $step = $guide->steps()->create(['order' => 1]);

        $this->get('/login')->assertOk();
        $pageToken = $this->app['session.store']->token();
        $login = $this->post('/login', [
            'email' => $owner->email,
            'password' => 'synthetic-test-password-only',
            '_token' => $pageToken,
        ]);
        $login->assertRedirect('/guides');
        $this->assertAuthenticatedAs($owner);
        $this->assertNotSame($pageToken, $this->app['session.store']->token());
        $cookie = $login->getCookie('XSRF-TOKEN', false);
        $this->assertNotNull($cookie);

        $this->withHeader('X-CSRF-TOKEN', $pageToken)->post("/steps/{$step->id}/screenshots", [
            'file' => UploadedFile::fake()->image('screen.png', 800, 600),
        ])->assertStatus(419);
        $this->assertSame(0, $step->fresh()->getMedia('screenshots')->count());

        $this->flushHeaders()->withHeader('X-XSRF-TOKEN', $cookie->getValue())
            ->post("/steps/{$step->id}/screenshots", [
                'file' => UploadedFile::fake()->image('screen.png', 800, 600),
            ])->assertCreated();

        $media = $step->fresh()->getFirstMedia('screenshots');
        $this->assertNotNull($media);
        $this->assertFileExists($media->getPath());
        $this->assertFileExists($media->getPath('thumb'));
    }
}
