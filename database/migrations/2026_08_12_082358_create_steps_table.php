<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guide_id')
                ->constrained('guides')
                ->cascadeOnDelete();
            $table->unsignedInteger('order')->default(0);
            $table->string('title')->nullable();
            $table->longText('body')->nullable();
            $table->string('warning')->nullable();
            $table->timestamps();

            $table->index(['guide_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('steps');
    }
};
