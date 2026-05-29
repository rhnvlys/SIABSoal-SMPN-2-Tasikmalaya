@extends('layouts.app')
@section('title', 'Data Kelas')
@section('page-title', 'Data Kelas')
@section('content')
<div class="page-header d-flex justify-between align-center flex-wrap gap-2">
    <div><h1>{{ auth()->user()->isGuru() ? 'Data Kelas Saya' : 'Data Kelas' }}</h1><p>{{ auth()->user()->isGuru() ? 'Kelas yang Anda pegang sebagai wali kelas' : 'Kelola kelas dan assignment siswa' }}</p></div>
    @if(auth()->user()->isAdmin())
    <a href="{{ route('kelas.create') }}" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Tambah Kelas</a>
    @endif
</div>
<div class="card mb-3"><div class="card-body">
    <form action="{{ route('kelas.index') }}" method="GET" class="d-flex gap-1">
        <select name="tahun_ajaran_id" class="form-control" style="max-width:240px"><option value="">Semua Tahun Ajaran</option>@foreach($tahunAjaranList as $ta)<option value="{{ $ta->id }}" {{ request('tahun_ajaran_id')==$ta->id?'selected':'' }}>{{ $ta->label }}</option>@endforeach</select>
        <button type="submit" class="btn btn-outline"><i class="bi bi-funnel"></i> Filter</button>
    </form>
</div></div>
<div class="card"><div class="table-responsive"><table class="table">
    <thead><tr><th>No</th><th>Kelas</th><th>Tingkat</th><th>Tahun Ajaran</th><th>Wali Kelas</th><th>Jumlah Siswa</th><th>Aksi</th></tr></thead>
    <tbody>
        @forelse($kelas as $i => $k)
        <tr>
            <td>{{ $kelas->firstItem() + $i }}</td>
            <td class="fw-semibold">{{ $k->nama_kelas }}</td>
            <td>{{ $k->tingkat }}</td>
            <td>{{ $k->tahunAjaran->label ?? '-' }}</td>
            <td>{{ $k->waliKelas->nama_guru ?? '-' }}</td>
            <td>{{ $k->siswa_kelas_count }}</td>
            <td><div class="btn-group">
                <a href="{{ route('kelas.show', $k) }}" class="btn btn-sm btn-outline" title="Detail"><i class="bi bi-eye"></i></a>
                @if(auth()->user()->isAdmin())
                <a href="{{ route('kelas.edit', $k) }}" class="btn btn-sm btn-outline"><i class="bi bi-pencil"></i></a>
                <form id="delete-kelas-{{ $k->id }}" action="{{ route('kelas.destroy', $k) }}" method="POST" style="display:inline">@csrf @method('DELETE')</form>
                <button class="btn btn-sm btn-danger" onclick="confirmDelete('delete-kelas-{{ $k->id }}', '{{ $k->nama_kelas }}')"><i class="bi bi-trash"></i></button>
                @endif
            </div></td>
        </tr>
        @empty
        <tr><td colspan="7" class="text-center text-muted" style="padding:24px">Belum ada data kelas.</td></tr>
        @endforelse
    </tbody>
</table></div>
@if($kelas->hasPages())<div class="pagination-wrapper">{!! $kelas->links('components.pagination') !!}</div>@endif
</div>
@endsection
