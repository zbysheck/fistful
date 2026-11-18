<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('works', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('description')->nullable();
            $table->smallInteger('year')->nullable();
            $table->string('wikidata_id', 20)->nullable()->unique();
            // stub = strona jeszcze nie wygenerowana, generating = trwa, ready = gotowa, failed = nie wyszło
            $table->string('status', 20)->default('stub')->index();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('works');
    }
};
