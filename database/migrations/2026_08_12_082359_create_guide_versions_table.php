<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guide_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guide_id')
                ->constrained('guides')
                ->cascadeOnDelete();
            $table->json('snapshot');
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();

            $table->index(['guide_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guide_versions');
    }
};
