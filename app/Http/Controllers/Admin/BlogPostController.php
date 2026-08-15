<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BlogPostRequest;
use App\Models\BlogPost;
use App\Services\Blog\BlogHeaderImageManager;
use App\Support\Html\HtmlSanitizer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class BlogPostController extends Controller
{
    public function index(): View
    {
        return view('admin.blog.index', [
            'posts' => BlogPost::query()->latest()->paginate(12),
        ]);
    }

    public function create(): View
    {
        return view('admin.blog.create', [
            'post' => new BlogPost,
        ]);
    }

    public function store(BlogPostRequest $request): RedirectResponse
    {
        $post = BlogPost::query()->create([
            ...$this->payload($request),
            'created_by_user_id' => $request->user()->id,
            'updated_by_user_id' => $request->user()->id,
        ]);
        $this->storeHeaderImage($request, $post);

        return redirect()->route('admin.blog.index')->with('success', __('site.blog_created'));
    }

    public function edit(BlogPost $post): View
    {
        return view('admin.blog.edit', [
            'post' => $post,
        ]);
    }

    public function update(BlogPostRequest $request, BlogPost $post): RedirectResponse
    {
        $post->update([
            ...$this->payload($request),
            'updated_by_user_id' => $request->user()->id,
        ]);
        $this->storeHeaderImage($request, $post);

        return redirect()->route('admin.blog.edit', $post)->with('success', __('site.blog_updated'));
    }

    public function destroy(BlogPost $post): RedirectResponse
    {
        $post->delete();

        return redirect()->route('admin.blog.index')->with('success', __('site.blog_deleted'));
    }

    private function payload(BlogPostRequest $request): array
    {
        $status = $request->validated('status');
        $publishedAt = $request->validated('published_at');

        return [
            'title' => $request->validated('title'),
            'slug' => $request->validated('slug'),
            'summary' => $request->validated('summary'),
            'body_html' => app(HtmlSanitizer::class)->clean($request->validated('body_html')),
            'status' => $status,
            'published_at' => $status === 'published' ? ($publishedAt ?: now()) : $publishedAt,
            'seo_keywords' => collect(explode(',', (string) $request->validated('seo_keywords')))
                ->map(fn (string $keyword) => trim($keyword))
                ->filter()
                ->values()
                ->all(),
        ];
    }

    private function storeHeaderImage(BlogPostRequest $request, BlogPost $post): void
    {
        if (! $request->hasFile('header_image')) {
            return;
        }

        $images = app(BlogHeaderImageManager::class);
        $previousPath = $post->header_image_path;

        $path = $images->store($request->file('header_image'));

        if ($post->header_image_path) {
            $images->delete($previousPath);
        }

        $post->forceFill([
            'header_image_path' => $path,
        ])->save();
    }
}
