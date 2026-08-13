<?php

use App\Http\Controllers\Admin\AdminCategoryController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\GuideBuilderController;
use App\Http\Controllers\GuideController;
use App\Http\Controllers\GuidePdfController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\StepController;
use App\Http\Controllers\StepScreenshotController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route(request()->user() ? 'guides.index' : 'login');
})->name('home');

Route::post('/locale', [LocaleController::class, 'update'])->name('locale.update');

Route::middleware('auth')->group(function () {
    Route::get('/guides', [GuideController::class, 'index'])->name('guides.index');

    Route::middleware('ensure.role:contributor')->group(function () {
        Route::get('/guides/create', [GuideBuilderController::class, 'create'])->name('guides.create');
        Route::post('/guides', [GuideBuilderController::class, 'store'])->name('guides.store');
        Route::get('/guides/{guide:slug}/edit', [GuideBuilderController::class, 'edit'])->name('guides.edit');
        Route::patch('/guides/{guide:slug}', [GuideBuilderController::class, 'update'])->name('guides.update');
        Route::post('/guides/{guide:slug}/submit', [GuideBuilderController::class, 'submitForReview'])->name('guides.submit');

        Route::post('/guides/{guide:slug}/steps', [StepController::class, 'store'])->name('steps.store');
        Route::patch('/guides/{guide:slug}/steps/reorder', [StepController::class, 'reorder'])->name('steps.reorder');
        Route::patch('/steps/{step}', [StepController::class, 'update'])->name('steps.update');
        Route::delete('/steps/{step}', [StepController::class, 'destroy'])->name('steps.destroy');

        Route::post('/steps/{step}/screenshots', [StepScreenshotController::class, 'store'])->name('steps.screenshots.store');
        Route::delete('/steps/{step}/screenshots/{media}', [StepScreenshotController::class, 'destroy'])->name('steps.screenshots.destroy');
        Route::patch('/media/{media}/annotations', [StepScreenshotController::class, 'annotate'])->name('media.annotations.update');
        Route::post('/media/{media}/redact', [StepScreenshotController::class, 'redact'])->name('media.redact');
    });

    Route::get('/guides/{guide:slug}', [GuideController::class, 'show'])->name('guides.show');
    Route::get('/guides/{guide:slug}/pdf', [GuidePdfController::class, 'show'])->name('guides.pdf');

    Route::middleware('ensure.role:approver')->group(function () {
        Route::get('/review', [ReviewController::class, 'index'])->name('review.index');
        Route::post('/review/{guide:slug}/publish', [ReviewController::class, 'publish'])->name('review.publish');
        Route::post('/review/{guide:slug}/send-back', [ReviewController::class, 'sendBack'])->name('review.sendBack');
    });

    Route::middleware('ensure.role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [AdminController::class, 'index'])->name('index');

        Route::get('/categories', [AdminCategoryController::class, 'index'])->name('categories.index');
        Route::post('/categories', [AdminCategoryController::class, 'store'])->name('categories.store');
        Route::patch('/categories/{category}', [AdminCategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{category}', [AdminCategoryController::class, 'destroy'])->name('categories.destroy');

        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
        Route::patch('/users/{user}/role', [AdminUserController::class, 'updateRole'])->name('users.updateRole');
        Route::patch('/users/{user}/password', [AdminUserController::class, 'resetPassword'])->name('users.resetPassword');
    });
});

require __DIR__.'/auth.php';
