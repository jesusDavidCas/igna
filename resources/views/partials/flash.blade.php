@if (session('success'))
    <div data-autohide class="mx-auto mt-4 max-w-6xl rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-800">
        {{ session('success') }}
    </div>
@endif

@if ($errors->any())
    <div class="mx-auto mt-4 max-w-6xl rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-800">
        <ul class="space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
