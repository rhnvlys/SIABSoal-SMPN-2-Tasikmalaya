{{-- Alert Component — auto-shows session flash messages --}}

@if (session('success'))
    <div class="alert alert-success" role="alert">
        <i class="bi bi-check-circle-fill"></i>
        <span>{{ session('success') }}</span>
        <button class="alert-close" type="button" aria-label="Tutup">&times;</button>
    </div>
@endif

@if (session('error'))
    <div class="alert alert-danger" role="alert">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <span>{{ session('error') }}</span>
        <button class="alert-close" type="button" aria-label="Tutup">&times;</button>
    </div>
@endif

@if (session('warning'))
    <div class="alert alert-warning" role="alert">
        <i class="bi bi-exclamation-circle-fill"></i>
        <span>{{ session('warning') }}</span>
        <button class="alert-close" type="button" aria-label="Tutup">&times;</button>
    </div>
@endif

@if (session('info'))
    <div class="alert alert-info" role="alert">
        <i class="bi bi-info-circle-fill"></i>
        <span>{{ session('info') }}</span>
        <button class="alert-close" type="button" aria-label="Tutup">&times;</button>
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger" role="alert">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <div>
            <strong>Terdapat kesalahan:</strong>
            <ul style="margin:6px 0 0;padding-left:18px">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        <button class="alert-close" type="button" aria-label="Tutup">&times;</button>
    </div>
@endif
