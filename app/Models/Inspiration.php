<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $source_work_id
 * @property int $target_work_id
 * @property string|null $note
 * @property string|null $source_url
 * @property string $origin
 * @property bool $verified
 * @property-read Work $source
 * @property-read Work $target
 */
class Inspiration extends Model
{
    public const ORIGIN_WIKIDATA = 'wikidata';

    public const ORIGIN_AI = 'ai';

    public const ORIGIN_MANUAL = 'manual';

    protected $fillable = ['source_work_id', 'target_work_id', 'note', 'source_url', 'origin', 'verified'];

    protected function casts(): array
    {
        return ['verified' => 'boolean'];
    }

    /** @return BelongsTo<Work, $this> */
    public function source(): BelongsTo
    {
        return $this->belongsTo(Work::class, 'source_work_id');
    }

    /** @return BelongsTo<Work, $this> */
    public function target(): BelongsTo
    {
        return $this->belongsTo(Work::class, 'target_work_id');
    }

    /**
     * Notatka ma działać z obu stron, więc musi wymieniać tytuły obu dzieł.
     */
    public function noteNamesBothWorks(): bool
    {
        if ($this->note === null || trim($this->note) === '') {
            return true;
        }

        return self::mentions($this->note, $this->source->title) && self::mentions($this->note, $this->target->title);
    }

    public static function mentions(string $note, string $title): bool
    {
        $normalize = fn (string $s): string => mb_strtolower((string) preg_replace('/[^\p{L}\p{N} ]+/u', ' ', $s));
        $head = preg_split('/[:(]/', $title);
        $short = (string) preg_replace('/^(the|a|an) /i', '', trim(is_array($head) ? $head[0] : $title));

        return str_contains($normalize($note), trim($normalize($short)));
    }
}
