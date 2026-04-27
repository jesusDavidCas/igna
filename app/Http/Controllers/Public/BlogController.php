<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use Illuminate\Contracts\View\View;

class BlogController extends Controller
{
    public function index(): View
    {
        return view('public.blog.index', [
            'posts' => BlogPost::query()
                ->where('status', 'published')
                ->whereNotNull('published_at')
                ->latest('published_at')
                ->paginate(9),
        ]);
    }

    public function show(BlogPost $post): View
    {
        abort_unless($post->status->value === 'published' && $post->published_at !== null, 404);

        return view('public.blog.show', [
            'post' => $post,
        ]);
    }
}
