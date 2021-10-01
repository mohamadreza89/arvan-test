<?php

namespace App\Services;

use App\Comment;
use Illuminate\Support\Facades\Log;

class CostService
{
    protected $commentCost;

    protected $articleCost;

    /**
     * CostService constructor.
     *
     * @param $commentCost
     * @param $articleCost
     */
    public function __construct($commentCost, $articleCost)
    {
        $this->commentCost = $commentCost;
        $this->articleCost = $articleCost;
    }

    public function commentCost($user_id)
    {
        return Comment::where("user_id", $user_id)->count() >= 5 ? $this->commentCost: 0;
    }

    public function articleCost($user_id)
    {
        return $this->articleCost;
    }

}