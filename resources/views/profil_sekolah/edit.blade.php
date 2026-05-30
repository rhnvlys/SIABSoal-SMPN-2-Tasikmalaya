@extends('layouts.app')
@section('title', 'Profil Sekolah')
@section('page-title', 'Profil Sekolah')
@section('content')
<div class="page-header"><h1>Profil Sekolah</h1><p>Atur informasi sekolah untuk laporan dan export</p></div>
<div class="card" style="max-width:780px"><div class="card-body">
    <form action="{{ route('profil-sekolah.update') }}" method="POST" enctype="multipart/form-data" data-loading data-loading-text="Menyimpan...">@csrf @method('PUT')
        <div class="form-group"><label class="form-label">Nama Sekolah <span class="required">*</span></label><input type="text" name="nama_sekolah" class="form-control" value="{{ old('nama_sekolah', $pengaturan->nama_sekolah) }}" required></div>
        <div class="form-group"><label class="form-label">Nama Sistem <span class="required">*</span></label><input type="text" name="nama_sistem" class="form-control" value="{{ old('nama_sistem', $pengaturan->nama_sistem) }}" required></div>
        <div class="form-group"><label class="form-label">Nama Lengkap Sistem <span class="required">*</span></label><input type="text" name="nama_lengkap_sistem" class="form-control" value="{{ old('nama_lengkap_sistem', $pengaturan->nama_lengkap_sistem) }}" required></div>
        <div class="form-group"><label class="form-label">Alamat</label><textarea name="alamat" class="form-control" rows="2">{{ old('alamat', $pengaturan->alamat) }}</textarea></div>
        <div class="form-row">
            <div class="form-group"><label class="form-label">Kepala Sekolah</label><input type="text" name="kepala_sekolah" class="form-control" value="{{ old('kepala_sekolah', $pengaturan->kepala_sekolah) }}"></div>
            <div class="form-group"><label class="form-label">NIP Kepala Sekolah</label><input type="text" name="nip_kepala_sekolah" class="form-control" value="{{ old('nip_kepala_sekolah', $pengaturan->nip_kepala_sekolah) }}"></div>
        </div>
        <div class="form-group">
            <label class="form-label">Logo Sekolah</label>
            <div class="logo-upload-preview">
                <div class="logo-preview-box">
                    @if($pengaturan->logoUrl())
                        <img id="logo-preview" src="{{ $pengaturan->logoUrl() }}" alt="Logo sekolah saat ini">
                    @else
                        <div id="logo-preview-fallback" class="logo-preview-fallback">
                            <i class="bi bi-building-fill"></i>
                            <span>SIABSoal</span>
                        </div>
                        <img id="logo-preview" src="" alt="Preview logo sekolah" style="display:none">
                    @endif
                </div>
                <div style="flex:1">
                    <input type="file" name="logo" id="logo-input" class="form-control" accept="image/png,image/jpeg,image/webp">
                    <p class="text-sm text-muted mt-1">Format png, jpg, jpeg, atau webp. Maksimal 2MB.</p>
                </div>
            </div>
        </div>
        <div class="btn-group mt-2"><button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Simpan</button></div>
    </form>
</div></div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('logo-input');
    const preview = document.getElementById('logo-preview');
    const fallback = document.getElementById('logo-preview-fallback');

    if (!input || !preview) return;

    input.addEventListener('change', function () {
        const file = input.files && input.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = function (event) {
            preview.src = event.target.result;
            preview.style.display = 'block';
            if (fallback) fallback.style.display = 'none';
        };
        reader.readAsDataURL(file);
    });
});
</script>
@endpush
