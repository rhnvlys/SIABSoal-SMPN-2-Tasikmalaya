@extends('layouts.app')
@section('title', 'Detail Kelas')
@section('page-title', 'Detail Kelas')
@section('content')
<div class="page-header"><h1>{{ $kelas->nama_kelas }}</h1><p>{{ $kelas->tahunAjaran->label ?? '' }} &mdash; Wali Kelas: {{ $kelas->waliKelas->nama_guru ?? '-' }}</p></div>
<div class="card"><div class="card-header d-flex justify-between align-center">
    <span>Daftar Siswa ({{ $kelas->siswaKelas->count() }} siswa)</span>
</div><div class="table-responsive"><table class="table">
    <thead><tr><th>No</th><th>NIS</th><th>NISN</th><th>Nama Siswa</th><th>L/P</th><th>Status</th></tr></thead>
    <tbody>
        @forelse($kelas->siswaKelas as $i => $sk)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ $sk->siswa->nis ?? '-' }}</td>
            <td>{{ $sk->siswa->nisn ?? '-' }}</td>
            <td class="fw-semibold">{{ $sk->siswa->nama_siswa ?? '-' }}</td>
            <td>@include('components.badge', ['type' => $sk->siswa->jenis_kelamin ?? '-'])</td>
            <td>@include('components.badge', ['type' => $sk->status])</td>
        </tr>
        @empty
        <tr><td colspan="6" class="text-center text-muted" style="padding:24px">Belum ada siswa di kelas ini.</td></tr>
        @endforelse
    </tbody>
</table></div></div>
<div class="btn-group mt-2"><a href="{{ route('kelas.index') }}" class="btn btn-outline"><i class="bi bi-arrow-left"></i> Kembali</a></div>
@endsection
