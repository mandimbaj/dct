<?php

namespace App\Models\DataQuality;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DqaSimilarityScore extends DqaReportModel
{
    protected $table = 'dqa_similar_indicators_score';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
