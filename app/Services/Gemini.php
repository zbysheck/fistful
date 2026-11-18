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
     * Pyta model z wyszukiwaniem Google i zwraca tekst odpowiedzi (albo null).
     */
    public function askWithSearch(string $prompt): ?string
    {
        $key = config('services.gemini.key');
        $models = config('services.gemini.model');
        if (! is_string($key) || $key === '' || ! is_string($models)) {
            return null;
        }

        // Darmowe modele bywają przeciążone (503, 429), więc próbujemy kolejnych z listy.
        foreach (array_filter(array_map('trim', explode(',', $models))) as $model) {
            try {
                $response = Http::withHeaders(['x-goog-api-key' => $key])
                    ->connectTimeout(5)
                    ->timeout(90)
                    ->post('https://generativelanguage.googleapis.com/v1beta/models/'.$model.':generateContent', [
                        'contents' => [['parts' => [['text' => $prompt]]]],
                        'tools' => [['google_search' => (object) []]],
                        'generationConfig' => ['thinkingConfig' => ['thinkingLevel' => 'low']],
                    ]);
            } catch (Throwable $e) {
                Log::warning('Gemini: no response', ['model' => $model, 'error' => $e->getMessage()]);

                continue;
            }

            if (! $response->successful()) {
                Log::warning('Gemini: API error', [
                    'model' => $model,
                    'status' => $response->status(),
                    'body' => mb_strcut($response->body(), 0, 500, 'UTF-8'),
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
