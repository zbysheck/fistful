<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Katalog wszystkich dzieł: z niego wyszukiwarka "udaje", że strona ma więcej treści.
 */
class Wikidata
{
    private const API = 'https://www.wikidata.org/w/api.php';

    private const SPARQL = 'https://query.wikidata.org/sparql';

    /**
     * @return list<array{wikidata_id: string, title: string, description: string|null}>
     */
    public function search(string $query, int $limit = 8): array
    {
        try {
            $response = $this->client()->get(self::API, [
                'action' => 'wbsearchentities',
                'search' => $query,
                'language' => 'en',
                'uselang' => 'en',
                'type' => 'item',
                'limit' => 30,
                'format' => 'json',
            ]);
        } catch (Throwable $e) {
            Log::warning('Wikidata search failed', ['error' => $e->getMessage()]);

            return [];
        }

        $results = [];
        foreach ((array) $response->json('search', []) as $item) {
            if (! is_array($item) || ! is_string($item['id'] ?? null)) {
                continue;
            }
            $label = $item['label'] ?? ($item['display']['label']['value'] ?? null);
            if (! is_string($label) || $label === '') {
                continue;
            }
            $description = $item['description'] ?? null;
            $results[] = [
                'wikidata_id' => $item['id'],
                'title' => $label,
                'description' => is_string($description) ? $description : null,
            ];
        }

        return array_slice(self::rank($results), 0, $limit);
    }

    /**
     * Dzieła (film, powieść, gra...) na górę, strony ujednoznaczniające i rośliny na dół.
     * Wikidata sortuje po trafności etykiety, więc np. film "Jacob's Ladder" bywa dopiero 7.
     *
     * @template T of array<string, mixed>
     *
     * @param  list<T>  $items
     * @return list<T>
     */
    public static function rank(array $items): array
    {
        $score = function (array $item): int {
            $d = mb_strtolower(is_string($item['description'] ?? null) ? $item['description'] : '');
            $s = 0;
            if (preg_match('/\b(film|movie|novel|book|story|stories|video game|game|comic|manga|anime|series|album|song|painting|play|opera|poem|musical|novella|television|tv|short|collection|franchise|myth|epic|scripture|text)\b/', $d) === 1) {
                $s += 3;
            }
            if (preg_match('/\b(1[0-9]{3}|20[0-9]{2})\b/', $d) === 1) {
                $s += 1;
            }
            if (str_contains($d, 'disambiguation') || str_contains($d, 'species') || str_contains($d, 'genus') || $d === '') {
                $s -= 3;
            }

            return $s;
        };

        $indexed = [];
        foreach ($items as $i => $item) {
            $indexed[] = [$score($item), $i, $item];
        }
        usort($indexed, fn (array $a, array $b) => [$b[0], $a[1]] <=> [$a[0], $b[1]]);

        return array_map(fn (array $x) => $x[2], $indexed);
    }

    /**
     * Najlepsze dopasowanie dla każdego tytułu (równolegle). Klucze jak w wejściu.
     * Gdy znamy rok, wybieramy wynik, którego opis go zawiera (np. "1961 film by Akira Kurosawa").
     *
     * @param  array<int, string>  $queries
     * @param  array<int, int|null>  $years
     * @return array<int, array{wikidata_id: string, title: string, description: string|null}|null>
     */
    public function searchMany(array $queries, array $years = []): array
    {
        if ($queries === []) {
            return [];
        }

        try {
            $responses = Http::pool(function (Pool $pool) use ($queries) {
                $requests = [];
                foreach ($queries as $key => $query) {
                    $requests[] = $pool->as((string) $key)
                        ->withHeaders(['User-Agent' => $this->userAgent()])
                        ->timeout(10)
                        ->get(self::API, [
                            'action' => 'wbsearchentities',
                            'search' => $query,
                            'language' => 'en',
                            'uselang' => 'en',
                            'type' => 'item',
                            'limit' => 15,
                            'format' => 'json',
                        ]);
                }

                return $requests;
            });
        } catch (Throwable $e) {
            Log::warning('Wikidata batch search failed', ['error' => $e->getMessage()]);

            return array_map(fn () => null, $queries);
        }

        $out = [];
        foreach ($queries as $key => $query) {
            $response = $responses[(string) $key] ?? null;
            $candidates = [];
            foreach ($response instanceof Response ? (array) $response->json('search', []) : [] as $c) {
                if (is_array($c) && is_string($c['id'] ?? null) && is_string($c['label'] ?? null)) {
                    $candidates[] = [
                        'wikidata_id' => $c['id'],
                        'title' => $c['label'],
                        'description' => is_string($c['description'] ?? null) ? $c['description'] : null,
                    ];
                }
            }
            $candidates = self::rank($candidates);
            $year = $years[$key] ?? null;
            $item = $candidates[0] ?? null;
            if ($year !== null) {
                foreach ($candidates as $candidate) {
                    if (str_contains((string) ($candidate['description'] ?? ''), (string) $year)) {
                        $item = $candidate;
                        break;
                    }
                }
            }
            $out[$key] = $item;
        }

        return $out;
    }

    /**
     * @return array{wikidata_id: string, title: string, description: string|null, year: int|null}|null
     */
    public function entity(string $id): ?array
    {
        if (preg_match('/^Q\d+$/', $id) !== 1) {
            return null;
        }

        try {
            // Duże elementy (np. The Matrix) mają setki stwierdzeń, więc dłuższy limit czasu.
            $response = $this->client()->timeout(25)->get(self::API, [
                'action' => 'wbgetentities',
                'ids' => $id,
                'props' => 'labels|descriptions|claims',
                // Część elementów (np. The Matrix) ma etykietę tylko jako "mul" (wspólna dla wszystkich języków).
                'languages' => 'en|mul',
                'format' => 'json',
            ]);
        } catch (Throwable $e) {
            Log::warning('Wikidata entity failed', ['id' => $id, 'error' => $e->getMessage()]);

            return null;
        }

        $entity = $response->json('entities.'.$id);
        if (! is_array($entity)) {
            return null;
        }

        $title = $entity['labels']['en']['value'] ?? ($entity['labels']['mul']['value'] ?? null);
        if (! is_string($title) || $title === '') {
            return null;
        }
        $description = $entity['descriptions']['en']['value'] ?? null;

        return [
            'wikidata_id' => $id,
            'title' => $title,
            'description' => is_string($description) ? $description : null,
            'year' => $this->year(is_array($entity['claims'] ?? null) ? $entity['claims'] : []),
        ];
    }

    /**
     * Gotowe powiązania z Wikidaty: P941 (inspired by) i P144 (based on), w obie strony.
     * P144 traktujemy jako adaptację.
     *
     * @return array{inspired_by: list<array{wikidata_id: string, title: string, description: string|null, kind: string|null}>, inspired: list<array{wikidata_id: string, title: string, description: string|null, kind: string|null}>}
     */
    public function connections(string $id): array
    {
        $empty = ['inspired_by' => [], 'inspired' => []];
        if (preg_match('/^Q\d+$/', $id) !== 1) {
            return $empty;
        }

        $query = <<<SPARQL
        SELECT ?dir ?p ?item ?itemLabel ?itemDescription WHERE {
          VALUES ?p { wdt:P941 wdt:P144 }
          { wd:{$id} ?p ?item . BIND("by" AS ?dir) }
          UNION
          { ?item ?p wd:{$id} . BIND("to" AS ?dir) }
          SERVICE wikibase:label { bd:serviceParam wikibase:language "en,mul". }
        } LIMIT 80
        SPARQL;

        try {
            $response = $this->client()->timeout(20)->get(self::SPARQL, ['query' => $query, 'format' => 'json']);
        } catch (Throwable $e) {
            Log::warning('Wikidata SPARQL failed', ['id' => $id, 'error' => $e->getMessage()]);

            return $empty;
        }

        $out = $empty;
        foreach ((array) $response->json('results.bindings', []) as $row) {
            if (! is_array($row)) {
                continue;
            }
            $uri = $row['item']['value'] ?? '';
            $label = $row['itemLabel']['value'] ?? '';
            if (! is_string($uri) || ! is_string($label) || preg_match('~/(Q\d+)$~', $uri, $m) !== 1 || $label === $m[1]) {
                continue;
            }
            $description = $row['itemDescription']['value'] ?? null;
            $key = ($row['dir']['value'] ?? '') === 'by' ? 'inspired_by' : 'inspired';
            $out[$key][] = [
                'wikidata_id' => $m[1],
                'title' => $label,
                'description' => is_string($description) ? $description : null,
                'kind' => str_ends_with((string) ($row['p']['value'] ?? ''), 'P144') ? 'adaptation' : null,
            ];
        }

        return $out;
    }

    /**
     * Tekst artykułu z angielskiej Wikipedii dla elementu Wikidaty (do kontekstu dla AI).
     *
     * @return array{url: string, text: string}|null
     */
    public function wikipediaArticle(string $id, int $maxChars = 30000): ?array
    {
        if (preg_match('/^Q\d+$/', $id) !== 1) {
            return null;
        }

        try {
            $title = $this->client()->get(self::API, [
                'action' => 'wbgetentities',
                'ids' => $id,
                'props' => 'sitelinks',
                'sitefilter' => 'enwiki',
                'format' => 'json',
            ])->json('entities.'.$id.'.sitelinks.enwiki.title');

            if (! is_string($title) || $title === '') {
                return null;
            }

            $pages = $this->client()->timeout(15)->get('https://en.wikipedia.org/w/api.php', [
                'action' => 'query',
                'prop' => 'extracts',
                'explaintext' => 1,
                'redirects' => 1,
                'titles' => $title,
                'format' => 'json',
            ])->json('query.pages');
        } catch (Throwable $e) {
            Log::warning('Wikipedia fetch failed', ['id' => $id, 'error' => $e->getMessage()]);

            return null;
        }

        $page = is_array($pages) ? reset($pages) : null;
        $text = is_array($page) && is_string($page['extract'] ?? null) ? $page['extract'] : '';
        if (trim($text) === '') {
            return null;
        }

        return [
            'url' => 'https://en.wikipedia.org/wiki/'.str_replace(' ', '_', $title),
            'text' => mb_substr($text, 0, $maxChars),
        ];
    }

    /**
     * @param  array<string, mixed>  $claims
     */
    private function year(array $claims): ?int
    {
        // P577 data publikacji, P571 powstanie, P1191 premiera
        foreach (['P577', 'P571', 'P1191'] as $property) {
            $list = $claims[$property] ?? null;
            if (! is_array($list)) {
                continue;
            }
            foreach ($list as $claim) {
                $time = is_array($claim) ? ($claim['mainsnak']['datavalue']['value']['time'] ?? null) : null;
                if (is_string($time) && preg_match('/^([+-])0*(\d{1,4})-/', $time, $m) === 1) {
                    return (int) (($m[1] === '-' ? '-' : '').$m[2]);
                }
            }
        }

        return null;
    }

    private function client(): PendingRequest
    {
        return Http::withHeaders(['User-Agent' => $this->userAgent()])->connectTimeout(5)->timeout(10);
    }

    private function userAgent(): string
    {
        return 'FistfulOfIdeas/0.1 ('.config('app.url').')';
    }
}
