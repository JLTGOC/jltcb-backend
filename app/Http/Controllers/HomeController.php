<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{
    Reel,
    Article
};
use App\Http\Resources\{
    ArticleResource,
    ReelResource
};

class HomeController extends Controller
{
    /**
     * Home
     * 
     * Fetch dashboard content
     */
    public function home() {
        $articles = Article::latest('updated_at')->get();
        $reels = Reel::orderBy('created_at', 'desc')
            ->get();
        // $careers

        $articleCollection = ArticleResource::collection($articles);
        $reelCollection = ReelResource::collection($reels);

        return $this->success('Dashboard content fetched successfully', [
            'reels' => $reelCollection,
            'articles' => $articleCollection,
        ], 200);
    }
}
