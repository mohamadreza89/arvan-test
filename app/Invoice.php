<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Invoice extends Model
{
    protected $fillable = [
        "reason",
        "amount",
        "user_id",
    ];

    public function reason(): MorphTo
    {
        return $this->morphTo("reason");
    }
}
