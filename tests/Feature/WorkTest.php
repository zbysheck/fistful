<?php

namespace Tests\Feature;

use App\Models\Inspiration;
use App\Models\Work;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class WorkTest extends TestCase
{
    use RefreshDatabase;

    private function work(string $title, ?int $year = null, string $status = Work::READY): Work
    {
        return Work::query()->create([
            'slug' => Work::uniqueSlug($title, $year),
            'title' => $title,
            'year' => $year,
            'status' => $status,
        ]);
    }

    public function test_connection_shows_on_both_pages(): void
    {
        $yojimbo = $this->work('Yojimbo', 1961);
        $fistful = $this->work('A Fistful of Dollars', 1964);
        Inspiration::query()->create([
            'source_work_id' => $yojimbo->id,
            'target_work_id' => $fistful->id,
            'note' => "Sergio Leone's *A Fistful of Dollars* follows the plot of Kurosawa's *Yojimbo*.",
            'origin' => Inspiration::ORIGIN_MANUAL,
            'verified' => true,
        ]);

        $this->get('/work/yojimbo')->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Work')
            ->has('inspired', 1)
            ->where('inspired.0.work.title', 'A Fistful of Dollars')
            ->has('inspiredBy', 0));

        $this->get('/work/a-fistful-of-dollars')->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->has('inspiredBy', 1)
            ->where('inspiredBy.0.work.title', 'Yojimbo'));
    }

    public function test_note_must_name_both_works(): void
    {
        $a = $this->work('Street of Crocodiles', 1986);
        $b = $this->work("Jacob's Ladder", 1990);
        $good = new Inspiration(['note' => "Adrian Lyne modeled the death imagery in *Jacob's Ladder* on the Brothers Quay's *Street of Crocodiles*."]);
        $good->setRelation('source', $a)->setRelation('target', $b);
        $bad = new Inspiration(['note' => 'Lyne modeled the death imagery on this animation.']);
        $bad->setRelation('source', $a)->setRelation('target', $b);

        $this->assertTrue($good->noteNamesBothWorks());
        $this->assertFalse($bad->noteNamesBothWorks());
    }

    public function test_search_merges_local_and_wikidata(): void
    {
        $this->work('Silent Hill', 1999);
        Http::fake([
            'www.wikidata.org/*' => Http::response(['search' => [
                ['id' => 'Q1', 'label' => 'Silent Hill 2', 'description' => '2001 video game'],
            ]]),
        ]);

        $this->getJson('/search?q=silent')
            ->assertOk()
            ->assertJsonPath('0.url', '/work/silent-hill')
            ->assertJsonPath('1.url', '/open/Q1');
    }

    public function test_generation_uses_wikidata_and_ai(): void
    {
        config(['services.gemini.key' => 'test', 'services.gemini.model' => 'm']);
        $work = Work::query()->create(['slug' => 'yojimbo', 'title' => 'Yojimbo', 'year' => 1961, 'wikidata_id' => 'Q823422', 'status' => Work::STUB]);

        Http::fake([
            'query.wikidata.org/*' => Http::response(['results' => ['bindings' => [
                ['dir' => ['value' => 'to'], 'item' => ['value' => 'http://www.wikidata.org/entity/Q2'], 'itemLabel' => ['value' => 'A Fistful of Dollars']],
            ]]]),
            'generativelanguage.googleapis.com/*' => Http::response(['candidates' => [['content' => ['parts' => [['text' => json_encode([
                'inspired_by' => [
                    ['title' => 'Red Harvest', 'year' => 1929, 'note' => "Kurosawa's *Yojimbo* borrows its premise from Dashiell Hammett's *Red Harvest*.", 'source_url' => 'https://example.com/ok'],
                    ['title' => 'Fake Book', 'year' => 1900, 'note' => '*Yojimbo* and *Fake Book*.', 'source_url' => 'https://example.com/missing'],
                    ['title' => 'No Source', 'note' => '*Yojimbo* and *No Source*.', 'source_url' => ''],
                ],
                'inspired' => [],
            ])]]]]]]),
            'example.com/missing' => Http::response('', 404),
            'example.com/*' => Http::response('<p>Yojimbo was based on Red Harvest by Hammett.</p>'),
            'www.wikidata.org/*' => Http::response(['search' => [['id' => 'Q3', 'label' => 'Red Harvest', 'description' => 'novel']]]),
        ]);

        $this->postJson('/work/yojimbo/generate')->assertOk()->assertJson(['status' => Work::READY]);

        $this->assertSame(Work::READY, $work->refresh()->status);
        $this->assertSame(['A Fistful of Dollars'], $work->inspired()->with('target')->get()->pluck('target.title')->all());
        $this->assertSame(['Red Harvest'], $work->inspiredBy()->with('source')->get()->pluck('source.title')->all());
        $this->assertFalse($work->inspiredBy()->firstOrFail()->verified);
    }

    public function test_admin_requires_password(): void
    {
        config(['services.admin.password' => 'secret']);

        $this->get('/admin')->assertRedirect('/admin/login');
        $this->post('/admin/login', ['password' => 'nope'])->assertSessionHasErrors('password');
        $this->post('/admin/login', ['password' => 'secret'])->assertRedirect('/admin');
        $this->get('/admin')->assertOk();
    }
}
