<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BlogPostTranslationRequest;
use App\Models\BlogPost;
use App\Services\Seo\PublicTranslationWorkflow;
use Illuminate\Http\RedirectResponse;

class BlogPostTranslationController extends Controller
{
    public function update(BlogPostTranslationRequest $request, BlogPost $post, PublicTranslationWorkflow $workflow): RedirectResponse
    {
        $workflow->saveBlogEnglish($post, $request->validated());

        return back()->with('success', __('site.translation_saved_for_review'));
    }

    public function approve(BlogPost $post, PublicTranslationWorkflow $workflow): RedirectResponse
    {
        $workflow->approveBlogEnglish($post, request()->user());

        return back()->with('success', __('site.translation_approved'));
    }
}
