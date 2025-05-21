<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('idea_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('ideas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inspired_id')->constrained('media')->onDelete('cascade');
            $table->foreignId('inspired_by_id')->constrained('media')->onDelete('cascade');
            $table->foreignId('idea_type_id')->constrained('idea_types')->onDelete('cascade');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ideas');
        Schema::dropIfExists('idea_types');
    }
};
