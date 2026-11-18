<?php

use App\Support\Taxonomy;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('works', function (Blueprint $table) {
            // book, film, tv, game, comic, music, art, theatre, myth, other
            $table->string('medium', 20)->nullable()->after('year');
        });

        Schema::table('inspirations', function (Blueprint $table) {
            // adaptation, remake, story, style, reference, background, parody (null = ogólna inspiracja)
            $table->string('kind', 20)->nullable()->after('target_work_id');
        });

        // Istniejące dzieła: medium z opisu Wikidaty.
        foreach (DB::table('works')->whereNotNull('description')->get(['id', 'description']) as $work) {
            $medium = Taxonomy::mediumFromDescription(is_string($work->description) ? $work->description : null);
            if ($medium !== null) {
                DB::table('works')->where('id', $work->id)->update(['medium' => $medium]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('works', fn (Blueprint $table) => $table->dropColumn('medium'));
        Schema::table('inspirations', fn (Blueprint $table) => $table->dropColumn('kind'));
    }
};
