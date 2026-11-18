<?php

namespace App\Support;

/**
 * Stałe listy: media dzieł i typy powiązań. Kolejność = kolejność wyświetlania.
 */
class Taxonomy
{
    public const MEDIA = ['book', 'film', 'tv', 'game', 'comic', 'music', 'art', 'theatre', 'myth', 'other'];

    public const KINDS = ['adaptation', 'remake', 'story', 'style', 'reference', 'parody', 'background'];

    public static function medium(mixed $value): ?string
    {
        $value = is_string($value) ? strtolower(trim($value)) : '';

        return in_array($value, self::MEDIA, true) ? $value : null;
    }

    public static function kind(mixed $value): ?string
    {
        $value = is_string($value) ? strtolower(trim($value)) : '';

        return in_array($value, self::KINDS, true) ? $value : null;
    }

    /**
     * Medium z opisu Wikidaty, np. "1990 film directed by Adrian Lyne" -> film.
     */
    public static function mediumFromDescription(?string $description): ?string
    {
        $d = mb_strtolower((string) $description);
        if ($d === '') {
            return null;
        }

        $rules = [
            'game' => '/\b(video game|board game|game)\b/',
            'tv' => '/\b(television|tv series|tv program|web series|miniseries|episode|anime television)\b/',
            'comic' => '/\b(comic|manga|graphic novel|webcomic|manhwa)\b/',
            'film' => '/\b(film|movie|short film|animated feature)\b/',
            'music' => '/\b(album|song|single|opera|symphony|composition|musical work)\b/',
            'theatre' => '/\b(play|musical|ballet|stage)\b/',
            'art' => '/\b(painting|sculpture|artwork|photograph|mural|print)\b/',
            'myth' => '/\b(myth|mythology|legend|folk tale|fairy tale|scripture|religious text|biblical|book of the bible|epic poem|sacred text)\b/',
            'book' => '/\b(novel|novella|book|short story|story collection|collection of short stories|poem|poetry|essay|literary work|written work)\b/',
        ];

        foreach ($rules as $medium => $pattern) {
            if (preg_match($pattern, $d) === 1) {
                return $medium;
            }
        }

        return null;
    }
}
