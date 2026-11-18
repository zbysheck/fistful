<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $slug
 * @property string $title
 * @property string|null $description
 * @property int|null $year
 * @property string|null $medium
 * @property string|null $wikidata_id
 * @property string $status
 * @property CarbonImmutable|null $generated_at
 */
class Work extends Model
{
    public const STUB = 'stub';

    public const GENERATING = 'generating';

    public const READY = 'ready';

    public const FAILED = 'failed';

    protected $fillable = ['slug', 'title', 'description', 'year', 'medium', 'wikidata_id', 'status', 'generated_at'];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'generated_at' => 'immutable_datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Powiązania, w których to dzieło czerpało z innych ("Inspired by").
     *
     * @return HasMany<Inspiration, $this>
     */
    public function inspiredBy(): HasMany
    {
        return $this->hasMany(Inspiration::class, 'target_work_id');
    }

    /**
     * Powiązania, w których to dzieło zainspirowało inne ("Inspired").
     *
     * @return HasMany<Inspiration, $this>
     */
    public function inspired(): HasMany
    {
        return $this->hasMany(Inspiration::class, 'source_work_id');
    }

    public static function uniqueSlug(string $title, ?int $year = null): string
    {
        $base = Str::slug($title) ?: 'work';

        foreach (array_filter([$base, $year !== null ? $base.'-'.$year : null]) as $candidate) {
            if (! self::query()->where('slug', $candidate)->exists()) {
                return $candidate;
            }
        }

        $i = 2;
        while (self::query()->where('slug', $base.'-'.$i)->exists()) {
            $i++;
        }

        return $base.'-'.$i;
    }

    /**
     * @return array{slug: string, title: string, year: int|null, description: string|null, medium: string|null}
     */
    public function summary(): array
    {
        return [
            'slug' => $this->slug,
            'title' => $this->title,
            'year' => $this->year,
            'description' => $this->description,
            'medium' => $this->medium,
        ];
    }
}
