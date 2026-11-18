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
                'limit' => $limit,
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

        return $results;
    }

    /**
     * Najlepsze dopasowanie dla każdego tytułu (równolegle). Klucze jak w wejściu.
     *
     * @param  array<int, string>  $queries
     * @return array<int, array{wikidata_id: string, title: string, description: string|null}|null>
     */
    public function searchMany(array $queries): array
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
                            'limit' => 1,
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
            $item = $response instanceof Response ? $response->json('search.0') : null;
            $out[$key] = is_array($item) && is_string($item['id'] ?? null) && is_string($item['label'] ?? null)
                ? [
                    'wikidata_id' => $item['id'],
                    'title' => $item['label'],
                    'description' => is_string($item['description'] ?? null) ? $item['description'] : null,
                ]
                : null;
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
            $response = $this->client()->get(self::API, [
                'action' => 'wbgetentities',
                'ids' => $id,
                'props' => 'labels|descriptions|claims',
                'languages' => 'en',
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

        $title = $entity['labels']['en']['value'] ?? null;
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
     *
     * @return array{inspired_by: list<array{wikidata_id: string, title: string}>, inspired: list<array{wikidata_id: string, title: string}>}
     */
    public function connections(string $id): array
    {
        $empty = ['inspired_by' => [], 'inspired' => []];
        if (preg_match('/^Q\d+$/', $id) !== 1) {
            return $empty;
        }

        $query = <<<SPARQL
        SELECT ?dir ?item ?itemLabel WHERE {
          { wd:{$id} wdt:P941|wdt:P144 ?item . BIND("by" AS ?dir) }
          UNION
          { ?item wdt:P941|wdt:P144 wd:{$id} . BIND("to" AS ?dir) }
          SERVICE wikibase:label { bd:serviceParam wikibase:language "en". }
        } LIMIT 60
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
            $key = ($row['dir']['value'] ?? '') === 'by' ? 'inspired_by' : 'inspired';
            $out[$key][] = ['wikidata_id' => $m[1], 'title' => $label];
        }

        return $out;
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
