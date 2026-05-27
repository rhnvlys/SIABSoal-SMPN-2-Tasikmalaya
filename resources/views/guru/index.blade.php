@extends('layouts.app')
@section('title', 'Data Guru')
@section('page-title', 'Data Guru')
@section('content')
<div class="page-header d-flex justify-between align-center flex-wrap gap-2">
    <div><h1>Data Guru</h1><p>Kelola data guru</p></div>
    <a href="{{ route('guru.create') }}" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Tambah Guru</a>
</div>
<div class="card mb-3"><div class="card-body">
    <form action="{{ route('guru.index') }}" method="GET" class="d-flex gap-1">
        <input type="text" name="search" class="form-control" placeholder="Cari nama/NIP..." value="{{ request('search') }}" style="max-width:320px">
        <button type="submit" class="btn btn-outline"><i class="bi bi-search"></i></button>
        @if(request('search'))<a href="{{ route('guru.index') }}" class="btn btn-outline">Reset</a>@endif
    </form>
</div></div>
<div class="card"><div class="table-responsive"><table class="table">
    <thead><tr><th>No</th><th>NIP</th><th>Nama Guru</th><th>L/P</th><th>No HP</th><th>Status</th><th>Akun</th><th>Aksi</th></tr></thead>
    <tbody>
        @forelse($guru as $i => $g)
        <tr>
            <td>{{ $guru->firstItem() + $i }}</td>
            <td>{{ $g->nip ?? '-' }}</td>
            <td class="fw-semibold">{{ $g->nama_guru }}</td>
            <td>@if($g->jenis_kelamin)@include('components.badge', ['type' => $g->jenis_kelamin])@else - @endif</td>
            <td>{{ $g->no_hp ?? '-' }}</td>
            <td>@include('components.badge', ['type' => $g->status])</td>
            <td>{{ $g->user->username ?? '-' }}</td>
            <td><div class="btn-group">
                <a href="{{ route('guru.edit', $g) }}" class="btn btn-sm btn-outline"><i class="bi bi-pencil"></i></a>
                <form id="delete-guru-{{ $g->id }}" action="{{ route('guru.destroy', $g) }}" method="POST" style="display:inline">@csrf @method('DELETE')</form>
                <button class="btn btn-sm btn-danger" onclick="confirmDelete('delete-guru-{{ $g->id }}', '{{ $g->nama_guru }}')"><i class="bi bi-trash"></i></button>
            </div></td>
        </tr>
        @empty
        <tr><td colspan="8" class="text-center text-muted" style="padding:24px">Belum ada data guru.</td></tr>
        @endforelse
    </tbody>
</table></div>
@if($guru->hasPages())<div class="pagination-wrapper">{!! $guru->links('components.pagination') !!}</div>@endif
</div>
@endsection
