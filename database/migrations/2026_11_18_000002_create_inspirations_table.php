<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inspirations', function (Blueprint $table) {
            $table->id();
            // source zainspirowało target
            $table->foreignId('source_work_id')->constrained('works')->cascadeOnDelete();
            $table->foreignId('target_work_id')->constrained('works')->cascadeOnDelete();
            $table->text('note')->nullable();
            $table->string('source_url', 2048)->nullable();
            $table->string('origin', 20)->default('manual');
            $table->boolean('verified')->default(false)->index();
            $table->timestamps();

            $table->unique(['source_work_id', 'target_work_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspirations');
    }
};
