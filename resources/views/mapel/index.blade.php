@extends('layouts.app')
@section('title', 'Mata Pelajaran')
@section('page-title', 'Mata Pelajaran')
@section('content')
<div class="page-header d-flex justify-between align-center flex-wrap gap-2">
    <div><h1>Mata Pelajaran</h1><p>Kelola data mata pelajaran</p></div>
    <a href="{{ route('mapel.create') }}" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Tambah Mapel</a>
</div>
<div class="card mb-3"><div class="card-body"><form action="{{ route('mapel.index') }}" method="GET" class="d-flex gap-1"><input type="text" name="search" class="form-control" placeholder="Cari kode/nama..." value="{{ request('search') }}" style="max-width:320px"><button type="submit" class="btn btn-outline"><i class="bi bi-search"></i></button>@if(request('search'))<a href="{{ route('mapel.index') }}" class="btn btn-outline">Reset</a>@endif</form></div></div>
<div class="card"><div class="table-responsive"><table class="table">
    <thead><tr><th>No</th><th>Kode</th><th>Nama Mata Pelajaran</th><th>Aksi</th></tr></thead>
    <tbody>
        @forelse($mapel as $i => $m)
        <tr><td>{{ $mapel->firstItem() + $i }}</td><td class="fw-semibold">{{ $m->kode_mapel }}</td><td>{{ $m->nama_mapel }}</td>
            <td><div class="btn-group"><a href="{{ route('mapel.edit', $m) }}" class="btn btn-sm btn-outline"><i class="bi bi-pencil"></i></a><form id="delete-mapel-{{ $m->id }}" action="{{ route('mapel.destroy', $m) }}" method="POST" style="display:inline">@csrf @method('DELETE')</form><button class="btn btn-sm btn-danger" onclick="confirmDelete('delete-mapel-{{ $m->id }}', '{{ $m->nama_mapel }}')"><i class="bi bi-trash"></i></button></div></td></tr>
        @empty<tr><td colspan="4" class="text-center text-muted" style="padding:24px">Belum ada data mapel.</td></tr>@endforelse
    </tbody>
</table></div>@if($mapel->hasPages())<div class="pagination-wrapper">{!! $mapel->links('components.pagination') !!}</div>@endif</div>
@endsection
