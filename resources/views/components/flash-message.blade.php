@if(session('success'))
    <div class="mb-4 p-4 text-sm text-green-800 bg-green-100 rounded-lg">
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="mb-4 p-4 text-sm text-red-800 bg-red-100 rounded-lg">
        {{ session('error') }}
    </div>
@endif
