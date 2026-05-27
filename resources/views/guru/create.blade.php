@extends('layouts.app')
@section('title', 'Tambah Guru')
@section('page-title', 'Tambah Guru')
@section('content')
<div class="page-header"><h1>Tambah Guru</h1></div>
<div class="card" style="max-width:680px"><div class="card-body">
    <form action="{{ route('guru.store') }}" method="POST">
        @csrf
        <div class="form-row">
            <div class="form-group"><label class="form-label">Nama Guru <span class="required">*</span></label>
                <input type="text" name="nama_guru" class="form-control @error('nama_guru') is-invalid @enderror" value="{{ old('nama_guru') }}" required>
                @error('nama_guru')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="form-group"><label class="form-label">NIP</label>
                <input type="text" name="nip" class="form-control @error('nip') is-invalid @enderror" value="{{ old('nip') }}">
                @error('nip')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        </div>
        <div class="form-row">
            <div class="form-group"><label class="form-label">Jenis Kelamin</label>
                <select name="jenis_kelamin" class="form-control"><option value="">-- Pilih --</option><option value="L" {{ old('jenis_kelamin')=='L'?'selected':'' }}>Laki-laki</option><option value="P" {{ old('jenis_kelamin')=='P'?'selected':'' }}>Perempuan</option></select></div>
            <div class="form-group"><label class="form-label">No HP</label>
                <input type="text" name="no_hp" class="form-control" value="{{ old('no_hp') }}"></div>
        </div>
        <div class="form-group"><label class="form-label">Alamat</label>
            <textarea name="alamat" class="form-control" rows="2">{{ old('alamat') }}</textarea></div>
        <div class="form-group"><label class="form-label">Status <span class="required">*</span></label>
            <select name="status" class="form-control" required><option value="aktif">Aktif</option><option value="nonaktif">Nonaktif</option></select></div>

        <hr style="border-color:var(--border-color);margin:20px 0">
        <div class="form-group">
            <label class="form-label"><input type="checkbox" name="buat_akun" value="1" id="cb-buat-akun" {{ old('buat_akun') ? 'checked' : '' }}> Buatkan akun login untuk guru ini</label>
        </div>
        <div id="akun-fields" style="display:{{ old('buat_akun') ? 'block' : 'none' }}">
            <div class="form-row">
                <div class="form-group"><label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control @error('username') is-invalid @enderror" value="{{ old('username') }}">
                    @error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="form-group"><label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control @error('password') is-invalid @enderror">
                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            </div>
        </div>

        <div class="btn-group mt-2">
            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Simpan</button>
            <a href="{{ route('guru.index') }}" class="btn btn-outline">Batal</a>
        </div>
    </form>
</div></div>
@push('scripts')
<script>
document.getElementById('cb-buat-akun').addEventListener('change',function(){document.getElementById('akun-fields').style.display=this.checked?'block':'none'});
</script>
@endpush
@endsection
