@if (session('status'))
    <div class="alert alert-success d-flex gap-2 align-items-start" role="status">
        <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
        <span>{{ session('status') }}</span>
    </div>
@endif
@if (session('error'))
    <div class="alert alert-danger d-flex gap-2 align-items-start" role="alert">
        <i class="bi bi-exclamation-octagon-fill" aria-hidden="true"></i>
        <span>{{ session('error') }}</span>
    </div>
@endif
@if ($errors->any())
    <div class="alert alert-danger" role="alert">
        <div class="fw-semibold mb-1"><i class="bi bi-exclamation-circle" aria-hidden="true"></i> Vérifiez les informations</div>
        <ul class="mb-0 ps-3">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
