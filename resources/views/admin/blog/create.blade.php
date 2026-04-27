@extends('layouts.panel', ['title' => __('site.new_post'), 'heading' => __('site.new_post')])

@section('content')
    @include('admin.blog.partials.form', ['action' => route('admin.blog.store'), 'method' => 'POST', 'post' => $post])
@endsection
