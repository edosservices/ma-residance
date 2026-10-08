@if (session('status'))
    <div class="mb-4 rounded-2xl bg-emerald-50 px-4 py-3 text-sm text-emerald-950">{{ session('status') }}</div>
@endif
@if (session('error'))
    <div class="mb-4 rounded-2xl bg-red-50 px-4 py-3 text-sm text-danger">{{ session('error') }}</div>
@endif
@if ($errors->any())
    <div class="mb-4 rounded-2xl bg-red-50 px-4 py-3 text-sm text-danger">{{ $errors->first() }}</div>
@endif
