@extends('layouts.app')
@section('title', 'Data Siswa')
@section('page-title', 'Data Siswa')
@section('content')
<div class="page-header d-flex justify-between align-center flex-wrap gap-2">
    <div><h1>{{ auth()->user()->isGuru() ? 'Siswa Kelas Saya' : 'Data Siswa' }}</h1><p>{{ auth()->user()->isGuru() ? 'Data siswa pada kelas yang Anda pegang' : 'Kelola data peserta didik' }}</p></div>
    <div class="d-flex gap-1 flex-wrap">
        @if(!auth()->user()->isKepalaSekolah())
        <button type="button" class="btn btn-outline" onclick="openModal('importSiswaModal')">
            <i class="bi bi-file-earmark-excel"></i> Import Excel
        </button>
        @endif
        @if(auth()->user()->isAdmin())
        <a href="{{ route('siswa.create') }}" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Tambah Siswa</a>
        @endif
    </div>
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
                @if(!auth()->user()->isKepalaSekolah())
                <a href="{{ route('siswa.edit', $s) }}" class="btn btn-sm btn-outline"><i class="bi bi-pencil"></i></a>
                @if(auth()->user()->isAdmin())
                <form id="delete-siswa-{{ $s->id }}" action="{{ route('siswa.destroy', $s) }}" method="POST" style="display:inline">@csrf @method('DELETE')</form>
                <button class="btn btn-sm btn-danger" onclick="confirmDelete('delete-siswa-{{ $s->id }}', '{{ $s->nama_siswa }}')"><i class="bi bi-trash"></i></button>
                @endif
                @else
                <span class="text-muted">Read-only</span>
                @endif
            </div></td>
        </tr>
        @empty
        <tr><td colspan="7" class="text-center text-muted" style="padding:24px">Belum ada data siswa.</td></tr>
        @endforelse
    </tbody>
</table></div>
</div>
@if($siswa->hasPages())<div class="pagination-wrapper">{!! $siswa->links('components.pagination') !!}</div>@endif
</div>

{{-- Modal Import Siswa --}}
<div class="modal" id="importSiswaModal" style="display: none;">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Import Data Siswa</h5>
                <button type="button" class="close" onclick="closeModal('importSiswaModal')">&times;</button>
            </div>
            <form action="{{ route('siswa.import.preview') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info">
                        <strong>Petunjuk:</strong> Unduh template Excel yang sudah berformat rapi, lengkapi data siswa, lalu unggah berkas di bawah.
                    </div>
                    <div class="mb-3">
                        <a href="{{ route('siswa.template', ['filename' => 'template_data_siswa.xlsx']) }}" class="btn btn-sm btn-outline mb-2">
                            <i class="bi bi-download"></i> Unduh Template Excel Siswa
                        </a>
                    </div>
                    <div class="form-group">
                        <label for="file_siswa" class="form-label">Berkas Excel (xlsx, xls, csv)</label>
                        <input type="file" name="file" id="file_siswa" class="form-control" accept=".xlsx,.xls,.csv" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal('importSiswaModal')">Batal</button>
                    <button type="submit" class="btn btn-primary">Preview Import</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
