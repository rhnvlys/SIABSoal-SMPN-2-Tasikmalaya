@extends('layouts.app')
@section('title', 'Data Siswa')
@section('page-title', 'Data Siswa')
@section('content')
<div class="page-header d-flex justify-between align-center flex-wrap gap-2">
    <div><h1>Data Siswa</h1><p>Kelola data peserta didik</p></div>
    <a href="{{ route('siswa.create') }}" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Tambah Siswa</a>
</div>
<div class="card mb-3"><div class="card-body">
    <form action="{{ route('siswa.index') }}" method="GET" class="d-flex gap-1 flex-wrap">
        <input type="text" name="search" class="form-control" placeholder="Cari NIS, NISN, atau nama..." value="{{ request('search') }}" style="max-width:320px">
        <select name="status" class="form-control" style="max-width:160px"><option value="">Semua Status</option><option value="aktif" {{ request('status')=='aktif'?'selected':'' }}>Aktif</option><option value="nonaktif" {{ request('status')=='nonaktif'?'selected':'' }}>Nonaktif</option><option value="lulus" {{ request('status')=='lulus'?'selected':'' }}>Lulus</option></select>
        <button type="submit" class="btn btn-outline"><i class="bi bi-search"></i></button>
        @if(request('search') || request('status'))<a href="{{ route('siswa.index') }}" class="btn btn-outline">Reset</a>@endif
    </form>
</div></div>
<div class="card"><div class="table-responsive"><table class="table">
    <thead><tr><th>No</th><th>NIS</th><th>NISN</th><th>Nama Siswa</th><th>L/P</th><th>Status</th><th>Aksi</th></tr></thead>
    <tbody>
        @forelse($siswa as $i => $s)
        <tr>
            <td>{{ $siswa->firstItem() + $i }}</td>
            <td>{{ $s->nis }}</td>
            <td>{{ $s->nisn ?? '-' }}</td>
            <td class="fw-semibold">{{ $s->nama_siswa }}</td>
            <td>@include('components.badge', ['type' => $s->jenis_kelamin])</td>
            <td>@include('components.badge', ['type' => $s->status])</td>
            <td><div class="btn-group">
                <a href="{{ route('siswa.edit', $s) }}" class="btn btn-sm btn-outline"><i class="bi bi-pencil"></i></a>
                <form id="delete-siswa-{{ $s->id }}" action="{{ route('siswa.destroy', $s) }}" method="POST" style="display:inline">@csrf @method('DELETE')</form>
                <button class="btn btn-sm btn-danger" onclick="confirmDelete('delete-siswa-{{ $s->id }}', '{{ $s->nama_siswa }}')"><i class="bi bi-trash"></i></button>
            </div></td>
        </tr>
        @empty
        <tr><td colspan="7" class="text-center text-muted" style="padding:24px">Belum ada data siswa.</td></tr>
        @endforelse
    </tbody>
</table></div>
@if($siswa->hasPages())<div class="pagination-wrapper">{!! $siswa->links('components.pagination') !!}</div>@endif
</div>
@endsection
