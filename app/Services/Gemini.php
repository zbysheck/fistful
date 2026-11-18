<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class Gemini
{
    public function enabled(): bool
    {
        $key = config('services.gemini.key');

        return is_string($key) && $key !== '';
    }

    /**
     * Pyta model. Najpierw z wyszukiwaniem Google (jeśli $search), a gdy żaden model go nie obsłuży
     * (np. darmowy poziom ma zerowy limit na wyszukiwanie), zwraca null - wtedy wołający może spróbować bez.
     */
    public function ask(string $prompt, bool $search): ?string
    {
        $key = config('services.gemini.key');
        $models = config('services.gemini.model');
        if (! is_string($key) || $key === '' || ! is_string($models)) {
            return null;
        }

        $body = [
            'contents' => [['parts' => [['text' => $prompt]]]],
            'generationConfig' => ['thinkingConfig' => ['thinkingLevel' => 'low']],
        ];
        if ($search) {
            $body['tools'] = [['google_search' => (object) []]];
        }

        // Darmowe modele bywają przeciążone (503, 429), więc próbujemy kolejnych z listy.
        foreach (array_filter(array_map('trim', explode(',', $models))) as $model) {
            try {
                $response = Http::withHeaders(['x-goog-api-key' => $key])
                    ->connectTimeout(5)
                    ->timeout(50)
                    ->post('https://generativelanguage.googleapis.com/v1beta/models/'.$model.':generateContent', $body);
            } catch (Throwable $e) {
                Log::warning('Gemini: no response', ['model' => $model, 'search' => $search, 'error' => $e->getMessage()]);

                continue;
            }

            if (! $response->successful()) {
                Log::info('Gemini: API error', [
                    'model' => $model,
                    'search' => $search,
                    'status' => $response->status(),
                    'body' => mb_strcut($response->body(), 0, 300, 'UTF-8'),
                ]);

                continue;
            }

            $text = '';
            foreach ((array) $response->json('candidates.0.content.parts', []) as $part) {
                if (is_array($part) && is_string($part['text'] ?? null) && ($part['thought'] ?? false) !== true) {
                    $text .= $part['text'];
                }
            }

            if (trim($text) !== '') {
                return $text;
            }
        }

        return null;
    }

    /**
     * Wyciąga pierwszy obiekt JSON z odpowiedzi (model może dodać ```json i komentarz).
     *
     * @return array<mixed>|null
     */
    public static function json(string $text): ?array
    {
        if (preg_match('/```(?:json)?\s*(\{.*\})\s*```/s', $text, $m) === 1) {
            $text = $m[1];
        } else {
            $start = strpos($text, '{');
            $end = strrpos($text, '}');
            if ($start === false || $end === false || $end <= $start) {
                return null;
            }
            $text = substr($text, $start, $end - $start + 1);
        }

        $data = json_decode($text, true);

        return is_array($data) ? $data : null;
    }
}
