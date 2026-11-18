<?php

namespace Tests\Unit;

use App\Support\Taxonomy;
use PHPUnit\Framework\TestCase;

class TaxonomyTest extends TestCase
{
    public function test_medium_from_wikidata_description(): void
    {
        $this->assertSame('film', Taxonomy::mediumFromDescription('1990 film directed by Adrian Lyne'));
        $this->assertSame('game', Taxonomy::mediumFromDescription('1999 horror video game'));
        $this->assertSame('book', Taxonomy::mediumFromDescription('1934 Bruno Schulz short story collection'));
        $this->assertSame('book', Taxonomy::mediumFromDescription('1929 novel by Dashiell Hammett'));
        $this->assertSame('tv', Taxonomy::mediumFromDescription('American anthology television series'));
        $this->assertNull(Taxonomy::mediumFromDescription('Ukrainian boxer'));
    }
}
