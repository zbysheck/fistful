<?php

namespace App\Services;

use App\Models\Inspiration;
use App\Models\Work;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Wypełnia stronę dzieła: najpierw pewne powiązania z Wikidaty, potem AI (Gemini + Google Search).
 */
class InspirationGenerator
{
    public function __construct(private Wikidata $wikidata, private Gemini $gemini) {}

    public function limitReached(): bool
    {
        return $this->usedToday() >= (int) config('services.gemini.daily_limit', 200);
    }

    public function usedToday(): int
    {
        return (int) Cache::get($this->counterKey(), 0);
    }

    /**
     * Zwraca false, gdy ktoś inny już generuje tę stronę albo limit dzienny się skończył.
     */
    public function generate(Work $work): bool
    {
        if ($this->limitReached()) {
            return false;
        }

        // Atomowo zajmujemy stronę, żeby dwa kliknięcia nie generowały jej dwa razy.
        $claimed = Work::query()
            ->whereKey($work->id)
            ->where(fn ($q) => $q->whereIn('status', [Work::STUB, Work::FAILED])
                ->orWhere(fn ($q) => $q->where('status', Work::GENERATING)->where('updated_at', '<', now()->subMinutes(5))))
            ->update(['status' => Work::GENERATING, 'updated_at' => now()]);

        if ($claimed === 0) {
            return false;
        }

        Cache::add($this->counterKey(), 0, now()->addDay());
        Cache::increment($this->counterKey());
        set_time_limit(180);

        try {
            $known = $work->wikidata_id !== null ? $this->wikidata->connections($work->wikidata_id) : ['inspired_by' => [], 'inspired' => []];

            foreach ($known['inspired_by'] as $item) {
                $this->connect($this->workFor($item['wikidata_id'], $item['title']), $work, null, null, Inspiration::ORIGIN_WIKIDATA, true);
            }
            foreach ($known['inspired'] as $item) {
                $this->connect($work, $this->workFor($item['wikidata_id'], $item['title']), null, null, Inspiration::ORIGIN_WIKIDATA, true);
            }

            if ($this->gemini->enabled()) {
                $this->fromAi($work, $known);
            }

            $work->forceFill(['status' => Work::READY, 'generated_at' => now()])->save();

            return true;
        } catch (Throwable $e) {
            Log::error('Generation failed', ['work' => $work->slug, 'error' => $e->getMessage()]);
            $work->forceFill(['status' => Work::FAILED])->save();

            return false;
        }
    }

    /**
     * @param  array{inspired_by: list<array{wikidata_id: string, title: string}>, inspired: list<array{wikidata_id: string, title: string}>}  $known
     */
    private function fromAi(Work $work, array $known): void
    {
        $answer = $this->gemini->askWithSearch($this->prompt($work, $known));
        $data = $answer !== null ? Gemini::json($answer) : null;
        if ($data === null) {
            Log::warning('AI answer unusable', ['work' => $work->slug, 'answer' => mb_strcut((string) $answer, 0, 500, 'UTF-8')]);

            return;
        }

        /** @var list<array{dir: string, title: string, year: int|null, note: string, url: string}> $items */
        $items = [];
        foreach (['inspired_by', 'inspired'] as $dir) {
            foreach (array_slice((array) ($data[$dir] ?? []), 0, 12) as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $title = trim((string) ($row['title'] ?? ''));
                $note = trim((string) ($row['note'] ?? ''));
                $url = trim((string) ($row['source_url'] ?? ''));
                $year = is_numeric($row['year'] ?? null) ? (int) $row['year'] : null;
                if ($title === '' || mb_strlen($title) > 200 || $note === '' || mb_strlen($note) > 600) {
                    continue;
                }
                // Bez źródła powiązanie odpada: AI nie może dopisywać niczego "z głowy".
                if (preg_match('~^https?://[^\s]+$~', $url) !== 1 || strlen($url) > 2000) {
                    continue;
                }
                $items[] = ['dir' => $dir, 'title' => $title, 'year' => $year, 'note' => $note, 'url' => $url];
            }
        }

        $items = $this->withLiveSources($items);
        if ($items === []) {
            return;
        }

        $matches = $this->wikidata->searchMany(array_map(
            fn (array $i): string => $i['title'],
            $items,
        ));

        foreach ($items as $i => $item) {
            $match = $matches[$i] ?? null;
            $other = $match !== null
                ? $this->workFor($match['wikidata_id'], $match['title'], $match['description'], $item['year'])
                : $this->workByTitle($item['title'], $item['year']);

            if ($other->id === $work->id) {
                continue;
            }

            [$source, $target] = $item['dir'] === 'inspired_by' ? [$other, $work] : [$work, $other];
            $this->connect($source, $target, $item['note'], $item['url'], Inspiration::ORIGIN_AI, false);
        }
    }

    /**
     * Odrzuca powiązania, których źródło nie istnieje (404 itp.) - typowy objaw zmyślonego linku.
     *
     * @param  list<array{dir: string, title: string, year: int|null, note: string, url: string}>  $items
     * @return list<array{dir: string, title: string, year: int|null, note: string, url: string}>
     */
    private function withLiveSources(array $items): array
    {
        if ($items === []) {
            return [];
        }

        try {
            $responses = Http::pool(function (Pool $pool) use ($items) {
                $requests = [];
                foreach ($items as $i => $item) {
                    $requests[] = $pool->as((string) $i)
                        ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; FistfulOfIdeas/0.1)'])
                        ->connectTimeout(4)
                        ->timeout(8)
                        ->get($item['url']);
                }

                return $requests;
            });
        } catch (Throwable) {
            return $items;
        }

        return array_values(array_filter($items, function (array $item, int $i) use ($responses): bool {
            $response = $responses[(string) $i] ?? null;
            if (! $response instanceof Response) {
                return true; // timeout itp.: nie wiemy, zostawiamy do ręcznego sprawdzenia
            }

            return ! in_array($response->status(), [404, 410], true);
        }, ARRAY_FILTER_USE_BOTH));
    }

    private function connect(Work $source, Work $target, ?string $note, ?string $url, string $origin, bool $verified): void
    {
        if ($source->id === $target->id) {
            return;
        }

        DB::transaction(function () use ($source, $target, $note, $url, $origin, $verified) {
            $existing = Inspiration::query()
                ->where('source_work_id', $source->id)
                ->where('target_work_id', $target->id)
                ->first();

            if ($existing === null) {
                Inspiration::query()->create([
                    'source_work_id' => $source->id,
                    'target_work_id' => $target->id,
                    'note' => $note,
                    'source_url' => $url,
                    'origin' => $origin,
                    'verified' => $verified,
                ]);

                return;
            }

            // Uzupełniamy brakujące pola, nie nadpisujemy tego, co już ktoś ustalił.
            $existing->fill([
                'note' => $existing->note ?? $note,
                'source_url' => $existing->source_url ?? $url,
            ])->save();
        });
    }

    public function workFor(string $wikidataId, string $title, ?string $description = null, ?int $year = null): Work
    {
        $work = Work::query()->where('wikidata_id', $wikidataId)->first();
        if ($work !== null) {
            return $work;
        }

        return Work::query()->create([
            'slug' => Work::uniqueSlug($title, $year),
            'title' => $title,
            'description' => $description !== null ? mb_substr($description, 0, 250) : null,
            'year' => $year,
            'wikidata_id' => $wikidataId,
            'status' => Work::STUB,
        ]);
    }

    private function workByTitle(string $title, ?int $year): Work
    {
        $work = Work::query()->where('title', $title)->when($year !== null, fn ($q) => $q->where('year', $year))->first();

        return $work ?? Work::query()->create([
            'slug' => Work::uniqueSlug($title, $year),
            'title' => $title,
            'year' => $year,
            'status' => Work::STUB,
        ]);
    }

    /**
     * @param  array{inspired_by: list<array{wikidata_id: string, title: string}>, inspired: list<array{wikidata_id: string, title: string}>}  $known
     */
    private function prompt(Work $work, array $known): string
    {
        $name = $work->title.($work->year !== null ? ' ('.$work->year.')' : '').($work->description !== null ? ' - '.$work->description : '');
        $knownBy = implode('; ', array_column($known['inspired_by'], 'title')) ?: 'none';
        $knownTo = implode('; ', array_column($known['inspired'], 'title')) ?: 'none';

        return <<<PROMPT
        You are writing entries for "Fistful of Ideas", a wiki that tracks how creative works inspire each other
        (books, films, TV, games, comics, music, art, myths, religious texts). Use Google Search.

        The work: {$name}

        Find documented connections in both directions:
        - "inspired_by": earlier works that this work drew on (adaptation, remake, homage, borrowed plot, style,
          imagery, names, characters).
        - "inspired": later works that drew on this work.

        Already known (include them too, with a note and source): inspired by: {$knownBy}. Inspired: {$knownTo}.

        Rules:
        - Only connections that a creator stated or that a reliable source (encyclopedia, interview, reputable
          press, scholarly text) documents. No guesses, no "fans think", no mere genre similarity.
        - Each connection must have "source_url": a real page you found that states the connection.
        - "note": ONE natural English sentence that names BOTH works by their titles (in *asterisks*) and makes the
          direction clear, so it reads correctly on either work's page. Never write "this film", "the novel" etc.
          instead of a title. Example: "Adrian Lyne modeled the death imagery in *Jacob's Ladder* on the Brothers
          Quay's *Street of Crocodiles*."
        - "title": the other work's common English title. "year": its year of first release, or null.
        - At most 10 per direction, most significant first. Empty lists are fine.

        Reply with only a JSON object:
        {"inspired_by": [{"title": "", "year": 0, "note": "", "source_url": ""}], "inspired": [...]}
        PROMPT;
    }

    private function counterKey(): string
    {
        return 'ai-generations:'.now()->toDateString();
    }
}
