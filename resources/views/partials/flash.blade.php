@if (session('status'))
    <div class="alert-success" role="status">
        {{ session('status') }}
    </div>
@endif

@if (session('success'))
    <div class="alert-success" role="status">
        {{ session('success') }}
    </div>
@endif

@if (session('error'))
    <div class="alert-danger" role="alert">
        {{ session('error') }}
    </div>
@endif
