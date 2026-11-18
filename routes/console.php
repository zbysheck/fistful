<?php

use App\Services\InspirationGenerator;
use App\Services\Wikidata;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Narzędzia: php artisan works:find Street_of_Crocodiles   (podkreślnik = spacja)
Artisan::command('works:find {query}', function (Wikidata $wikidata) {
    foreach ($wikidata->search(str_replace('_', ' ', is_string($q = $this->argument('query')) ? $q : '')) as $item) {
        $this->line($item['wikidata_id'].'  '.$item['title'].'  — '.($item['description'] ?? ''));
    }
})->purpose('Search Wikidata for works');

// php artisan works:generate Q823422   (zakłada stronę dzieła i ją generuje)
Artisan::command('works:generate {qid}', function (Wikidata $wikidata, InspirationGenerator $generator) {
    $entity = $wikidata->entity(is_string($qid = $this->argument('qid')) ? $qid : '');
    if ($entity === null) {
        $this->error('Not found');

        return 1;
    }
    $work = $generator->workFor($entity['wikidata_id'], $entity['title'], $entity['description'], $entity['year']);
    $generator->generate($work);
    $work->refresh();
    $this->info($work->title.' -> '.$work->status);
    foreach ($work->inspiredBy()->with('source')->get() as $i) {
        $this->line('  BY  '.$i->source->title.' ['.$i->origin.'] '.$i->note.' '.$i->source_url);
    }
    foreach ($work->inspired()->with('target')->get() as $i) {
        $this->line('  TO  '.$i->target->title.' ['.$i->origin.'] '.$i->note.' '.$i->source_url);
    }

    return 0;
})->purpose('Create and generate a work page');
