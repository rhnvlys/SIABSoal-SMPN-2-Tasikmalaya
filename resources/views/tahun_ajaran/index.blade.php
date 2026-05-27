@extends('layouts.app')
@section('title', 'Tahun Ajaran')
@section('page-title', 'Tahun Ajaran')
@section('content')
<div class="page-header d-flex justify-between align-center flex-wrap gap-2">
    <div><h1>Tahun Ajaran</h1><p>Kelola tahun ajaran dan semester</p></div>
    <a href="{{ route('tahun-ajaran.create') }}" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Tambah</a>
</div>
<div class="card"><div class="table-responsive"><table class="table">
    <thead><tr><th>No</th><th>Tahun Ajaran</th><th>Semester</th><th>Status</th><th>Aksi</th></tr></thead>
    <tbody>
        @forelse($tahunAjaran as $i => $ta)
        <tr><td>{{ $tahunAjaran->firstItem() + $i }}</td><td class="fw-semibold">{{ $ta->tahun_ajaran }}</td><td>{{ $ta->semester }}</td><td>@include('components.badge', ['type' => $ta->status])</td>
            <td><div class="btn-group"><a href="{{ route('tahun-ajaran.edit', $ta) }}" class="btn btn-sm btn-outline"><i class="bi bi-pencil"></i></a><form id="delete-ta-{{ $ta->id }}" action="{{ route('tahun-ajaran.destroy', $ta) }}" method="POST" style="display:inline">@csrf @method('DELETE')</form><button class="btn btn-sm btn-danger" onclick="confirmDelete('delete-ta-{{ $ta->id }}', '{{ $ta->label }}')"><i class="bi bi-trash"></i></button></div></td></tr>
        @empty<tr><td colspan="5" class="text-center text-muted" style="padding:24px">Belum ada data.</td></tr>@endforelse
    </tbody>
</table></div>@if($tahunAjaran->hasPages())<div class="pagination-wrapper">{!! $tahunAjaran->links('components.pagination') !!}</div>@endif</div>
@endsection
