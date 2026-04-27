@extends('layouts.panel', ['title' => $post->title, 'heading' => $post->title])

@section('content')
    @include('admin.blog.partials.form', ['action' => route('admin.blog.update', $post), 'method' => 'PUT', 'post' => $post])
@endsection
