<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    protected $fillable = [
        "user_id",
        "debt",
        "credit",
        "balance",
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
