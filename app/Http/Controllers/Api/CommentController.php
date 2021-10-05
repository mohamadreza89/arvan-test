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
use App\UseCases\CommentCreation;
use Illuminate\Auth\Access\AuthorizationException;
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
     *
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
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(
        CreateComment $request,
        CommentCreation $commentCreation,
        Article $article
    ) {
        try {
            $comment = $commentCreation->fire(auth()->user(), $request->all(), $article);
        } catch (AuthorizationException $exception) {
            return response()->json(["message" => "insufficient balance"])->setStatusCode(406);
        }

        return $this->respondWithTransformer($comment);
    }

    /**
     * Delete the comment given by its id.
     *
     * @param DeleteComment $request
     * @param $article
     * @param Comment $comment
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function destroy(DeleteComment $request, $article, Comment $comment)
    {
        $comment->delete();

        return $this->respondSuccess();
    }
}
