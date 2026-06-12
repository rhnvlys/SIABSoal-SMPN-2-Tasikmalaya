@extends('layouts.app')
@section('title', 'Preview Upload Template Lengkap')
@section('page-title', 'Preview Template Lengkap')

@section('content')
@include('components.breadcrumb', ['items' => [
    ['label' => 'Template Excel', 'route' => 'template-excel.index'],
    ['label' => 'Preview Upload'],
]])

<div class="page-header">
    <h1>Preview Upload Template Lengkap</h1>
    <p>{{ $result['file_name'] }} - {{ $ujian->nama_ujian }}</p>
</div>

<div class="stats-grid mb-3">
    @include('components.card-stat', ['value' => count($result['found_sheets']), 'label' => 'Sheet Ditemukan', 'icon' => 'bi-file-earmark-spreadsheet', 'color' => 'blue'])
    @include('components.card-stat', ['value' => count($result['processable_sheets']), 'label' => 'Sheet Diproses', 'icon' => 'bi-check-circle-fill', 'color' => 'green'])
    @include('components.card-stat', ['value' => count($result['ignored_sheets']), 'label' => 'Sheet Diabaikan', 'icon' => 'bi-shield-lock-fill', 'color' => 'yellow'])
    @include('components.card-stat', ['value' => count($result['errors']), 'label' => 'Error Validasi', 'icon' => 'bi-x-circle-fill', 'color' => 'red'])
</div>

<div class="card mb-3">
    <div class="card-header"><i class="bi bi-list-check"></i> Ringkasan Sheet</div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Sheet</th>
                    <th>Jumlah Baris</th>
                    <th>Status</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($result['found_sheets'] as $sheet)
                    @php
                        $diproses = in_array($sheet, $result['processable_sheets'], true);
                        $ignored = array_key_exists($sheet, $result['ignored_sheets']);
                    @endphp
                    <tr>
                        <td><strong>{{ $sheet }}</strong></td>
                        <td>{{ $result['row_counts'][$sheet] ?? '-' }}</td>
                        <td>
                            @if($diproses)
                                @include('components.badge', ['type' => 'aktif', 'label' => 'diproses'])
                            @elseif($ignored)
                                @include('components.badge', ['type' => 'warning', 'label' => 'diabaikan'])
                            @else
                                <span class="text-muted">informasi</span>
                            @endif
                        </td>
                        <td>{{ $result['ignored_sheets'][$sheet] ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@if(count($result['warnings']) > 0)
<div class="card mb-3">
    <div class="card-header" style="color:var(--warning)"><i class="bi bi-exclamation-triangle-fill"></i> Warning Role dan Data</div>
    <div class="card-body">
        <ul style="margin:0;padding-left:18px">
            @foreach($result['warnings'] as $warning)
                <li>{{ $warning }}</li>
            @endforeach
        </ul>
    </div>
</div>
@endif

@if(count($result['errors']) > 0)
<div class="card mb-3">
    <div class="card-header" style="color:var(--danger)"><i class="bi bi-x-circle-fill"></i> Error Validasi</div>
    <div class="card-body">
        <ul style="margin:0;padding-left:18px">
            @foreach($result['errors'] as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
</div>
@endif

<div class="card mb-3">
    <div class="card-header"><i class="bi bi-database-check"></i> Data yang Akan Diproses</div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Sheet</th>
                    <th>Jumlah Data Valid</th>
                </tr>
            </thead>
            <tbody>
                @forelse($result['payload'] as $sheet => $rows)
                    <tr>
                        <td>{{ $sheet }}</td>
                        <td>{{ count($rows) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" class="text-muted">Tidak ada data yang dapat diproses.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="btn-group mt-2">
    <a href="{{ route('template-excel.index', ['ujian_id' => $ujian->id]) }}" class="btn btn-outline"><i class="bi bi-arrow-left"></i> Batal</a>

    @if(count($result['errors']) === 0 && count($result['payload']) > 0)
        <form action="{{ route('template-excel.upload.confirm') }}" method="POST" data-loading data-loading-text="Memproses...">
            @csrf
            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Proses Import</button>
        </form>
    @else
        <span class="alert alert-warning" style="margin:0;padding:10px 14px">
            Tombol Proses Import dinonaktifkan sampai error validasi diperbaiki.
        </span>
    @endif
</div>
@endsection
