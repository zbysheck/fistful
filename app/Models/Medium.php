<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Medium extends Model
{
    protected $fillable = ['title', 'slug', 'description', 'year', 'media_type_id'];

    public function type(): BelongsTo
    {
        return $this->belongsTo(MediumType::class, 'medium_type_id');
    }

    public function inspired(): HasMany
    {
        return $this->hasMany(Idea::class, 'inspired_by_id');
    }

    public function inspiredBy(): HasMany
    {
        return $this->hasMany(Idea::class, 'inspired_id');
    }
}

