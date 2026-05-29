@extends('layouts.app')
@section('title', 'Data Ujian')
@section('page-title', 'Data Ujian')
@section('content')
<div class="page-header d-flex justify-between align-center flex-wrap gap-2">
    <div><h1>Data Ujian</h1><p>Kelola ujian, DP/TK, dan laporan nilai</p></div>
    @if(!auth()->user()->isKepalaSekolah())
    <a href="{{ route('ujian.create') }}" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Tambah Ujian</a>
    @endif
</div>
<div class="card mb-3"><div class="card-body">
    <form action="{{ route('ujian.index') }}" method="GET" class="d-flex gap-1 flex-wrap">
        <input type="text" name="search" class="form-control" placeholder="Cari nama ujian..." value="{{ request('search') }}" style="max-width:260px">
        <select name="status" class="form-control" style="max-width:160px"><option value="">Semua Status</option>
            @foreach(['draft','kunci_lengkap','data_mentah','olah_data','dianalisis','selesai'] as $s)<option value="{{ $s }}" {{ request('status')==$s?'selected':'' }}>{{ ucwords(str_replace('_',' ',$s)) }}</option>@endforeach</select>
        <select name="mapel_id" class="form-control" style="max-width:200px"><option value="">Semua Mapel</option>@foreach($mapelList as $m)<option value="{{ $m->id }}" {{ request('mapel_id')==$m->id?'selected':'' }}>{{ $m->nama_mapel }}</option>@endforeach</select>
        <button type="submit" class="btn btn-outline"><i class="bi bi-search"></i></button>
        @if(request()->hasAny(['search','status','mapel_id']))<a href="{{ route('ujian.index') }}" class="btn btn-outline">Reset</a>@endif
    </form>
</div></div>
<div class="card"><div class="table-responsive"><table class="table">
    <thead><tr><th>No</th><th>Nama Ujian</th><th>Mapel</th><th>Guru</th><th>Kelas</th><th>Soal</th><th>Status</th><th>Tanggal</th><th>Aksi</th></tr></thead>
    <tbody>
        @forelse($ujian as $i => $u)
        <tr>
            <td>{{ $ujian->firstItem() + $i }}</td>
            <td><a href="{{ route('ujian.show', $u) }}" class="fw-semibold">{{ $u->nama_ujian }}</a></td>
            <td>{{ $u->mapel->nama_mapel ?? '-' }}</td>
            <td>{{ $u->guru->nama_guru ?? '-' }}</td>
            <td>{{ $u->kelas->pluck('nama_kelas')->join(', ') ?: '-' }}</td>
            <td>{{ $u->jumlah_soal }}</td>
            <td>@include('components.badge', ['type' => $u->status])</td>
            <td>{{ $u->tanggal_ujian?->format('d/m/Y') ?? '-' }}</td>
            <td><div class="btn-group">
                <a href="{{ route('ujian.show', $u) }}" class="btn btn-sm btn-outline" title="Detail"><i class="bi bi-eye"></i></a>
                @if(!auth()->user()->isKepalaSekolah())
                <a href="{{ route('ujian.edit', $u) }}" class="btn btn-sm btn-outline"><i class="bi bi-pencil"></i></a>
                <form id="delete-ujian-{{ $u->id }}" action="{{ route('ujian.destroy', $u) }}" method="POST" style="display:inline">@csrf @method('DELETE')</form>
                <button class="btn btn-sm btn-danger" onclick="confirmDelete('delete-ujian-{{ $u->id }}', '{{ $u->nama_ujian }}')"><i class="bi bi-trash"></i></button>
                @endif
            </div></td>
        </tr>
        @empty<tr><td colspan="9" class="text-center text-muted" style="padding:24px">Belum ada data ujian.</td></tr>@endforelse
    </tbody>
</table></div>
@if($ujian->hasPages())<div class="pagination-wrapper">{!! $ujian->links('components.pagination') !!}</div>@endif</div>
@endsection
