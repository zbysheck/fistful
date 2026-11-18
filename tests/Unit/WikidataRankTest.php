<?php

namespace Tests\Unit;

use App\Services\Wikidata;
use PHPUnit\Framework\TestCase;

class WikidataRankTest extends TestCase
{
    public function test_creative_works_come_first(): void
    {
        $ranked = Wikidata::rank([
            ['id' => 'a', 'description' => 'ladder to heaven seen by the biblical patriarch Jacob'],
            ['id' => 'b', 'description' => 'Wikimedia disambiguation page'],
            ['id' => 'c', 'description' => '1990 film directed by Adrian Lyne'],
            ['id' => 'd', 'description' => 'species of plant'],
        ]);

        $this->assertSame(['c', 'a', 'b', 'd'], array_column($ranked, 'id'));
    }
}
