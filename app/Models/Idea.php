<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Idea extends Model
{
    protected $fillable = ['inspired_id', 'inspired_by_id', 'reference_type_id', 'description'];

    public function inspired(): BelongsTo
    {
        return $this->belongsTo(Medium::class, 'inspired_id');
    }

    public function inspiredBy(): BelongsTo
    {
        return $this->belongsTo(Medium::class, 'inspired_by_id');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(IdeaType::class, 'idea_type_id');
    }
}
