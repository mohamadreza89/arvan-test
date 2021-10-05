<?php

namespace App\UseCases;

use App\Article;
use App\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class CommentCreation extends BaseUseCase
{
    public function fire(User $user, array $data, Article $article)
    {
        $commentCost = $this->costService->commentCost($user->id);
        if (!$this->checkBalance(auth()->id(), $commentCost)) {
            throw new AuthorizationException();
        }

        return DB::transaction(function () use ($commentCost, $article, $data, $user) {
            $comment = $article->comments()->create([
                'body'    => $data['comment']['body'],
                'user_id' => $user->id,
            ]);

            $this->deductCommentCost($commentCost);
            $this->createInvoice($user, $commentCost, $comment);

            return $comment;
        });
    }

    /**
     * @param $commentCost
     */
    protected function deductCommentCost($commentCost)
    {
        $commentCost && $this->accountingService->deductFromWallet(auth()->id(), $commentCost);
    }

    protected function checkBalance(int $id, int $commentCost): bool
    {
        return $this->accountingService->userBalance($id) >= $commentCost;
    }
}