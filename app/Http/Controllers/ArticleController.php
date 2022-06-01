<?php

namespace App\Http\Controllers;

use App\Http\Requests\Article\DestroyRequest;
use App\Http\Requests\Article\FeedRequest;
use App\Http\Requests\Article\IndexRequest;
use App\Http\Requests\Article\StoreRequest;
use App\Http\Requests\Article\UpdateRequest;
use App\Http\Resources\ArticleCollection;
use App\Http\Resources\ArticleResource;
use App\Models\Article;
use App\Models\User;
use App\Services\ArticleService;

class Result
{
  public $articles;
  public $articlesCount;

  public function __construct($articles, $articlesCount)
  {
    $this->articles = $articles;
    $this->articlesCount = $articlesCount;
  }
}

class ArticleController extends Controller
{
  protected Article $article;
  protected ArticleService $articleService;
  protected User $user;

  public function __construct(Article $article, ArticleService $articleService, User $user)
  {
    $this->article = $article;
    $this->articleService = $articleService;
    $this->user = $user;
  }

  public function index(IndexRequest $request)
  {
    $result = [];
    $total = $this->article->getFilteredTotal($request->validated());
    $filterd = $this->article->getFiltered($request->validated());
    $result['articles'] = $filterd;
    $result['articlesCount'] = count($total);
    return $result;
  }

  public function feed(FeedRequest $request): ArticleCollection
  {
    return new ArticleCollection($this->article->getFiltered($request->validated()));
  }

  public function show(Article $article): ArticleResource
  {
    return $this->articleResponse($article);
  }

  public function store(StoreRequest $request): ArticleResource
  {
    $article = auth()->user()->articles()->create($request->validated()['article']);

    $this->articleService->syncTags($article, $request->validated()['article']['tagList'] ?? []);

    return $this->articleResponse($article);
  }

  public function update(Article $article, UpdateRequest $request): ArticleResource
  {
    $article->update($request->validated()['article']);

    $this->articleService->syncTags($article, $request->validated()['article']['tagList'] ?? []);

    return $this->articleResponse($article);
  }

  public function destroy(Article $article, DestroyRequest $request): void
  {
    $article->delete();
  }

  public function favorite(Article $article): ArticleResource
  {
    $article->users()->attach(auth()->id());

    return $this->articleResponse($article);
  }

  public function unfavorite(Article $article): ArticleResource
  {
    $article->users()->detach(auth()->id());

    return $this->articleResponse($article);
  }

  protected function articleResponse(Article $article): ArticleResource
  {
    return new ArticleResource($article->load('user', 'users', 'tags', 'user.followers'));
  }
}
