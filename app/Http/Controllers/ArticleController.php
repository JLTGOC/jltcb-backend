<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateArticleRequest;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\ArticleResource;
use App\Http\Requests\StoreArticleRequest;
use Illuminate\Support\Facades\Storage; // Import Storage facade

class ArticleController extends Controller
{
    /**
     * Index Articles
     * 
     * Display a listing of the resource.
     */
    public function index()
    {
        // Use pagination for performance if the list grows huge
        $articles = Article::latest('updated_at')->get();

        if ($articles->isEmpty()) {
            return $this->success('No articles found.', [], 200);
        }

        return $this->success('Articles retrieved successfully.', ArticleResource::collection($articles), 200);
    }

    /**
     * Store Article
     * 
     * Store a newly created resource in storage.
     */
    public function store(StoreArticleRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $validated = $request->validated();

            $imagePath = upload_image($request, 'image', 'articles/images');

            $article = Article::create([
                'title' => $validated['title'],
                'content' => $validated['content'],
                'image_url' => $imagePath,
            ]);

            return $this->success('Article created successfully.', new ArticleResource($article), 201);
        });
    }

    /**
     * Show Article
     * 
     * Display the specified resource.
     */
    public function show(Article $article)
    {
        return $this->success('Article retrieved successfully.', new ArticleResource($article));
    }

    /**
     * Update Article
     * 
     * Update the specified resource in storage.
     */
    public function update(UpdateArticleRequest $request, Article $article)
    {
        return DB::transaction(function () use ($request, $article) {
            $validated = $request->validated();

            if ($request->hasFile('image')) {
                if ($article->image_url && Storage::disk('public')->exists($article->image_url)) {
                    Storage::disk('public')->delete($article->image_url);
                }

                $validated['image_url'] = upload_image($request, 'image', 'articles/images');
            }

            $article->update($validated);

            $article->refresh();

            return $this->success('Article updated successfully.', new ArticleResource($article), 200);
        });
    }

    /**
     * Destroy Article
     * 
     * Remove the specified resource from storage.
     */
    public function destroy(Article $article)
    {
        return DB::transaction(function () use ($article) {  
            if ($article->image_url && Storage::disk('public')->exists($article->image_url)) {
                Storage::disk('public')->delete($article->image_url);
            }

            $article->delete();

            return $this->success('Article deleted successfully.', [], 200);
        });
    }
}