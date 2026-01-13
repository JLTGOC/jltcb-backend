<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DummyController extends Controller
{
    public function dummyReels(Request $request) {
        $isGuest = $request->attributes->get('is_guest', false);
        $user = $request->user();
        
        if ($isGuest) {
            return $this->success('Hello guest! This is reels (limited access)', [
                'is_guest' => true,
                'data' => 'Limited guest content'
            ]);
        }
        
        return $this->success('Hello ' . $user->email . '! This is reels (full access)', [
            'is_guest' => false,
            'user' => $user,
            'data' => 'Full content for authenticated users'
        ]);
    }

    public function dummyArticles(Request $request) {
        $isGuest = $request->attributes->get('is_guest', false);
        $user = $request->user();
        
        if ($isGuest) {
            return $this->success('Hello guest! This is articles (limited access)', [
                'is_guest' => true,
                'data' => 'Limited guest content'
            ]);
        }
        
        return $this->success('Hello ' . $user->email . '! This is articles (full access)', [
            'is_guest' => false,
            'user' => $user,
            'data' => 'Full content for authenticated users'
        ]);
    }
}