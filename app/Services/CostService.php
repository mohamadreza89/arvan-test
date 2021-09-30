<?php

namespace App\Services;

use App\Comment;
use Illuminate\Support\Facades\Log;

class CostService
{
    public function commentCost($user_id)
    {
        return Comment::where("user_id", $user_id)->count() >= 5 ? 5000: 0;
    }

    public function articleCost($user_id)
    {
        return 5000;
    }

}