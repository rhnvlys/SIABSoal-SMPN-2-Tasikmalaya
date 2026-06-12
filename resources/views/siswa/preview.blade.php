@extends('layouts.app')
@section('title', 'Preview Import Data Siswa')
@section('page-title', 'Preview Import')
@section('content')
@include('components.breadcrumb', ['items' => [
    ['label' => 'Data Siswa', 'route' => 'siswa.index'],
    ['label' => 'Preview Import'],
]])

<div class="page-header">
    <h1>Preview Import Data Siswa</h1>
    <p>Pratinjau validasi data siswa sebelum disimpan ke database</p>
</div>

{{-- Ringkasan --}}
<div class="stats-grid mb-3">
    @include('components.card-stat', ['value' => $result['summary']['total'], 'label' => 'Total Baris', 'icon' => 'bi-list-ol', 'color' => 'blue'])
    @include('components.card-stat', ['value' => $result['summary']['valid_count'], 'label' => 'Data Valid', 'icon' => 'bi-check-circle-fill', 'color' => 'green'])
    @include('components.card-stat', ['value' => $result['summary']['error_count'], 'label' => 'Data Error', 'icon' => 'bi-x-circle-fill', 'color' => 'red'])
</div>

{{-- Error Detail --}}
@if(count($result['errors']) > 0)
<div class="card mb-3">
    <div class="card-header" style="color:var(--danger)"><i class="bi bi-exclamation-triangle-fill"></i> Detail Error ({{ count($result['errors']) }} baris)</div>
    <div class="table-responsive"><table class="table" style="font-size:var(--font-size-sm)">
        <thead><tr><th>Baris</th><th>NIS</th><th>Nama</th><th>Error</th></tr></thead>
        <tbody>
            @foreach($result['errors'] as $err)
            <tr>
                <td>{{ $err['baris'] }}</td>
                <td>{{ $err['nis'] }}</td>
                <td>{{ $err['nama'] }}</td>
                <td style="color:var(--danger)">
                    <ul style="margin:0;padding-left:16px">
                        @foreach($err['errors'] as $e)
                        <li>{{ $e }}</li>
                        @endforeach
                    </ul>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table></div>
</div>
@endif

{{-- Valid Data Preview --}}
@if(count($result['valid']) > 0)
<div class="card mb-3">
    <div class="card-header" style="color:var(--success)"><i class="bi bi-check-circle-fill"></i> Data Valid ({{ count($result['valid']) }} siswa)</div>
    <div class="table-responsive"><table class="table" style="font-size:var(--font-size-sm)">
        <thead><tr><th>No</th><th>NIS</th><th>NISN</th><th>Nama Siswa</th><th>L/P</th><th>Kelas</th><th>Tahun Ajaran</th><th>Status</th></tr></thead>
        <tbody>
            @foreach($result['valid'] as $i => $v)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $v['nis'] }}</td>
                <td>{{ $v['nisn'] ?? '-' }}</td>
                <td>{{ $v['nama_siswa'] }}</td>
                <td>@include('components.badge', ['type' => $v['jenis_kelamin']])</td>
                <td><span class="fw-semibold text-primary">{{ $v['nama_kelas'] }}</span></td>
                <td>{{ $v['nama_tahun_ajaran'] }}</td>
                <td>@include('components.badge', ['type' => $v['status']])</td>
            </tr>
            @endforeach
        </tbody>
    </table></div>
</div>
@endif

{{-- Action Buttons --}}
<div class="btn-group mt-2">
    <a href="{{ route('siswa.index') }}" class="btn btn-outline"><i class="bi bi-arrow-left"></i> Batal</a>

    @if(count($result['valid']) > 0 && count($result['errors']) === 0)
    <form action="{{ route('siswa.import.confirm') }}" method="POST" data-loading data-loading-text="Mengimport...">
        @csrf
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Konfirmasi Import ({{ count($result['valid']) }} siswa)</button>
    </form>
    @elseif(count($result['errors']) > 0)
        <span class="alert alert-warning" style="margin:0;padding:10px 14px">
            Perbaiki seluruh error sebelum import dikonfirmasi. Data tidak akan disimpan sebagian.
        </span>
    @endif
</div>
@endsection
