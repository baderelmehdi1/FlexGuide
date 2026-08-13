<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')
                ->constrained('categories')
                ->restrictOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();

            /*
             | The language this GUIDE is written in -- independent of
             | whatever the interface language is. An English procedure must
             | stay LTR even when the signed-in user's UI is Arabic.
             */
            $table->string('language', 5)->default('ar');

            $table->enum('status', ['draft', 'pending', 'published'])->default('draft');
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'category_id']);
            $table->index(['status', 'language']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guides');
    }
};
