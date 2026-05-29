@extends('layouts.app')
@section('title', 'Detail Kelas')
@section('page-title', 'Detail Kelas')
@section('content')
<div class="page-header"><h1>{{ $kelas->nama_kelas }}</h1><p>{{ $kelas->tahunAjaran->label ?? '' }} &mdash; Wali Kelas: {{ $kelas->waliKelas->nama_guru ?? '-' }}</p></div>
@if($canManageStudents)
<div class="card mb-3">
    <div class="card-header"><i class="bi bi-person-plus-fill"></i> Tambah Siswa ke Kelas</div>
    <div class="card-body">
        <form action="{{ route('kelas.siswa.store', $kelas) }}" method="POST" class="d-flex gap-1 flex-wrap" data-loading data-loading-text="Memproses...">
            @csrf
            <select name="siswa_id" class="form-control" style="max-width:360px" required>
                <option value="">-- Pilih Siswa --</option>
                @foreach($siswaList as $siswa)
                    <option value="{{ $siswa->id }}">{{ $siswa->nis }} - {{ $siswa->nama_siswa }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Tambahkan</button>
        </form>
    </div>
</div>
@endif

<div class="card"><div class="card-header d-flex justify-between align-center">
    <span>Daftar Siswa ({{ $kelas->siswaKelas->count() }} siswa)</span>
</div><div class="table-responsive"><table class="table">
    <thead><tr><th>No</th><th>NIS</th><th>NISN</th><th>Nama Siswa</th><th>L/P</th><th>Status</th>@if($canManageStudents)<th>Aksi</th>@endif</tr></thead>
    <tbody>
        @forelse($kelas->siswaKelas as $i => $sk)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ $sk->siswa->nis ?? '-' }}</td>
            <td>{{ $sk->siswa->nisn ?? '-' }}</td>
            <td class="fw-semibold">{{ $sk->siswa->nama_siswa ?? '-' }}</td>
            <td>@include('components.badge', ['type' => $sk->siswa->jenis_kelamin ?? '-'])</td>
            <td>@include('components.badge', ['type' => $sk->status])</td>
            @if($canManageStudents)
            <td>
                <form action="{{ route('kelas.siswa.destroy', [$kelas, $sk->siswa]) }}" method="POST" data-confirm data-confirm-title="Hapus Siswa dari Kelas" data-confirm-message="Siswa akan dihapus dari daftar kelas ini. Lanjutkan?" data-confirm-button="Ya, hapus" style="display:inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                </form>
            </td>
            @endif
        </tr>
        @empty
        <tr><td colspan="{{ $canManageStudents ? 7 : 6 }}" class="text-center text-muted" style="padding:24px">Belum ada siswa di kelas ini.</td></tr>
        @endforelse
    </tbody>
</table></div></div>
<div class="btn-group mt-2"><a href="{{ route('kelas.index') }}" class="btn btn-outline"><i class="bi bi-arrow-left"></i> Kembali</a></div>
@endsection
