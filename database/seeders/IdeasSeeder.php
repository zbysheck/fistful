<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MediumType;
use App\Models\IdeaType;
use App\Models\Medium;
use App\Models\Idea;

class IdeasSeeder extends Seeder
{
    public function run(): void
    {
        // Medium Types
        $filmType = MediumType::firstOrCreate(['name' => 'Film']);

        // Idea Types
        $remakeType = IdeaType::firstOrCreate(['name' => 'REMAKE']);
        $inspired = IdeaType::firstOrCreate(['name' => 'INSPIRED']);
        $inspiredBy = IdeaType::firstOrCreate(['name' => 'INSPIRED_BY']);
        IdeaType::firstOrCreate(['name' => 'HOMAGE']);

        // Medium
        $yojimbo = Medium::firstOrCreate([
            'title' => 'Yojimbo',
            'slug' => 'Yojimbo',
            'description' => '1961 samurai film by Akira Kurosawa.',
            //'year' => 1961,
            'medium_type_id' => $filmType->id,
        ]);

        $fistful = Medium::firstOrCreate([
            'title' => 'A Fistful of Dollars',
            'slug' => 'A_Fistful_of_Dollars',
            'description' => 'Unofficial remake of Yojimbo directed by Sergio Leone.',
            //'year' => 1964,
            'medium_type_id' => $filmType->id,
        ]);

        // Idea
        Idea::firstOrCreate([
            'inspired_id' => $yojimbo->id,
            'inspired_by_id' => $fistful->id,
            'idea_type_id' => $remakeType->id,
            'description' => 'A Fistful of Dollars is a near scene-for-scene remake of Yojimbo, made without permission from Kurosawa.',
        ]);







        $yojimbo2 = Medium::firstOrCreate([
            'slug' => 'the-bodyguard'
        ], [
            'title' => 'The Bodyguard',
            'description' => 'An American remake of Yojimbo released in 1996.',
            'medium_type_id' => $filmType->id,
        ]);

        $django = Medium::firstOrCreate([
            'slug' => 'django'
        ], [
            'title' => 'Django',
            'description' => '1966 Spaghetti Western clearly influenced by Leone’s style.',
            'medium_type_id' => $filmType->id,
        ]);

        $killBill = Medium::firstOrCreate([
            'slug' => 'kill-bill-vol-2'
        ], [
            'title' => 'Kill Bill Vol. 2',
            'description' => 'Tarantino homage to Leone’s aesthetic and Morricone’s music.',
            'medium_type_id' => $filmType->id,
        ]);

        Idea::firstOrCreate([
            'inspired_id' => $yojimbo2->id,
            'inspired_by_id' => $yojimbo->id,
        ], [
            'idea_type_id' => $remakeType->id,
            'description' => 'The Bodyguard is an official American remake of Yojimbo.',
        ]);

        Idea::firstOrCreate([
            'inspired_id' => $django->id,
            'inspired_by_id' => $fistful->id,
        ], [
            'idea_type_id' => $inspired->id,
            'description' => 'Django follows Leone’s gritty Western style and lone antihero archetype.',
        ]);

        Idea::firstOrCreate([
            'inspired_id' => $killBill->id,
            'inspired_by_id' => $fistful->id,
        ], [
            'idea_type_id' => $inspired->id,
            'description' => 'Kill Bill Vol. 2 borrows Leone’s visual language and music pacing.',
        ]);



    }
}

