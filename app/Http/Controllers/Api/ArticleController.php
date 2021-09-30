<?php

namespace App\Http\Controllers\Api;

use App\Services\AccountingService;
use App\Services\CostService;
use App\Tag;
use App\Article;
use App\RealWorld\Paginate\Paginate;
use App\RealWorld\Filters\ArticleFilter;
use App\Http\Requests\Api\CreateArticle;
use App\Http\Requests\Api\UpdateArticle;
use App\Http\Requests\Api\DeleteArticle;
use App\RealWorld\Transformers\ArticleTransformer;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;

class ArticleController extends ApiController
{
    /**
     * ArticleController constructor.
     *
     * @param ArticleTransformer $transformer
     */
    public function __construct(ArticleTransformer $transformer)
    {
        $this->transformer = $transformer;

        $this->middleware('auth.api')->except(['index', 'show']);
        $this->middleware('auth.api:optional')->only(['index', 'show']);
    }

    /**
     * Get all the articles.
     *
     * @param ArticleFilter $filter
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(ArticleFilter $filter)
    {
        $articles = new Paginate(Article::loadRelations()->filter($filter));

        return $this->respondWithPagination($articles);
    }

    /**
     * Create a new article and return the article if successful.
     *
     * @param CreateArticle $request
     * @param AccountingService $accountingService
     * @param CostService $cost
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(CreateArticle $request, AccountingService $accountingService, CostService $cost)
    {
        $user = auth()->user();

        if (! $this->checkBalance($user ,$accountingService, $cost->articleCost($user->id))){
            return response()->json(["message"=>"insufficient balance"])->setStatusCode(406);
        }
        $article = DB::transaction(function () use ($accountingService, $cost, $user, $request){
            $article = $user->articles()->create([
                'title' => $request->input('article.title'),
                'description' => $request->input('article.description'),
                'body' => $request->input('article.body'),
            ]);
            $accountingService->deductFromWallet($user->id, $cost->articleCost($user->id));
            return $article;
        });


        $inputTags = $request->input('article.tagList');

        if ($inputTags && ! empty($inputTags)) {

            $tags = array_map(function($name) {
                return Tag::firstOrCreate(['name' => $name])->id;
            }, $inputTags);

            $article->tags()->attach($tags);
        }

        return $this->respondWithTransformer($article);
    }

    /**
     * Get the article given by its slug.
     *
     * @param Article $article
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Article $article)
    {
        return $this->respondWithTransformer($article);
    }

    /**
     * Update the article given by its slug and return the article if successful.
     *
     * @param UpdateArticle $request
     * @param Article $article
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateArticle $request, Article $article)
    {
        if ($request->has('article')) {
            $article->update($request->get('article'));
        }

        return $this->respondWithTransformer($article);
    }

    /**
     * Delete the article given by its slug.
     *
     * @param DeleteArticle $request
     * @param Article $article
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(DeleteArticle $request, Article $article)
    {
        $article->delete();

        return $this->respondSuccess();
    }

    protected function checkBalance(Authenticatable $user, AccountingService $accountingService, $cost): bool
    {
        return $accountingService->userBalance($user->id) >= $cost;
    }
}
