<?php

namespace App\Http\Controllers\Api;

use App\Article;
use App\Comment;
use App\Concerns\CreatesInvoice;
use App\Http\Requests\Api\CreateComment;
use App\Http\Requests\Api\DeleteComment;
use App\RealWorld\Transformers\CommentTransformer;
use App\Services\AccountingService;
use App\Services\CostService;
use Illuminate\Support\Facades\DB;

class CommentController extends ApiController
{
    use CreatesInvoice;

    /**
     * CommentController constructor.
     *
     * @param CommentTransformer $transformer
     */
    public function __construct(CommentTransformer $transformer)
    {
        $this->transformer = $transformer;

        $this->middleware('auth.api')->except('index');
        $this->middleware('auth.api:optional')->only('index');
    }

    /**
     * Get all the comments of the article given by its slug.
     *
     * @param Article $article
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Article $article)
    {
        $comments = $article->comments()->get();

        return $this->respondWithTransformer($comments);
    }

    /**
     * Add a comment to the article given by its slug and return the comment if successful.
     *
     * @param CreateComment $request
     * @param Article $article
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(CreateComment $request, Article $article, CostService $costService, AccountingService $accountingService)
    {
        $commentCost = $costService->commentCost(auth()->id());
        if (! $this->checkBalance(auth()->id() ,$accountingService, $commentCost)){
            return response()->json(["message"=>"insufficient balance"])->setStatusCode(406);
        }

        $comment = DB::transaction(function () use ($accountingService, $commentCost, $article, $request) {
            $comment = $article->comments()->create([
                'body'    => $request->input('comment.body'),
                'user_id' => auth()->id(),
            ]);

            $this->deductCommentCost($commentCost, $accountingService);
            $this->createInvoice(auth()->user(), $commentCost, $comment);

            return $comment;
        });
        return $this->respondWithTransformer($comment);
    }

    /**
     * Delete the comment given by its id.
     *
     * @param DeleteComment $request
     * @param $article
     * @param Comment $comment
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(DeleteComment $request, $article, Comment $comment)
    {
        $comment->delete();

        return $this->respondSuccess();
    }

    /**
     * @param $commentCost
     * @param AccountingService $accountingService
     */
    protected function deductCommentCost($commentCost, AccountingService $accountingService)
    {
        $commentCost && $accountingService->deductFromWallet(auth()->id(), $commentCost);

    }

    protected function checkBalance(int $id, AccountingService $accountingService, int $commentCost)
    {
        return $accountingService->userBalance($id) >= $commentCost;
    }
}
