@extends('layouts.app')

@section('title', 'Riwayat Aktivitas')
@section('page-title', 'Riwayat Aktivitas')

@section('content')
<div class="page-header">
    <h1>Riwayat Aktivitas</h1>
    <p>Catatan aktivitas pengguna untuk perubahan data, proses analisis, import, export, dan login.</p>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('audit-log.index') }}" class="form-row">
            <div class="form-group">
                <label class="form-label">Tanggal</label>
                <input type="date" name="tanggal" class="form-control" value="{{ request('tanggal') }}">
            </div>
            <div class="form-group">
                <label class="form-label">User</label>
                <select name="user_id" class="form-control">
                    <option value="">Semua User</option>
                    @foreach($filterOptions['users'] as $user)
                        <option value="{{ $user->id }}" @selected((string) request('user_id') === (string) $user->id)>
                            {{ $user->name }} ({{ $user->username }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Role</label>
                <select name="role" class="form-control">
                    <option value="">Semua Role</option>
                    @foreach($filterOptions['roles'] as $role)
                        <option value="{{ $role }}" @selected(request('role') === $role)>{{ $role }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Modul</label>
                <select name="modul" class="form-control">
                    <option value="">Semua Modul</option>
                    @foreach($filterOptions['moduls'] as $modul)
                        <option value="{{ $modul }}" @selected(request('modul') === $modul)>{{ $modul }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Aksi</label>
                <select name="aksi" class="form-control">
                    <option value="">Semua Aksi</option>
                    @foreach($filterOptions['aksis'] as $aksi)
                        <option value="{{ $aksi }}" @selected(request('aksi') === $aksi)>{{ $aksi }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Pencarian</label>
                <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Cari aktivitas, user, IP">
            </div>
            <div class="form-group d-flex align-center gap-1" style="align-self:end">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search"></i> Filter
                </button>
                <a href="{{ route('audit-log.index') }}" class="btn btn-outline">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="btn-group mb-3">
    <a href="{{ route('audit-log.export.excel', request()->query()) }}" class="btn btn-success" data-loading data-loading-text="Mengexport...">
        <i class="bi bi-file-earmark-excel"></i> Export Excel
    </a>
    <a href="{{ route('audit-log.export.pdf', request()->query()) }}" class="btn btn-danger" data-loading data-loading-text="Mengexport...">
        <i class="bi bi-file-earmark-pdf"></i> Export PDF
    </a>
</div>

<div class="card">
    <div class="card-header"><i class="bi bi-clock-history"></i> Tabel Riwayat Aktivitas</div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>Nama User</th>
                    <th>Role</th>
                    <th>Modul</th>
                    <th>Aksi</th>
                    <th>Deskripsi</th>
                    <th>IP</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td>{{ $log->created_at?->format('d/m/Y H:i') }}</td>
                        <td>{{ $log->nama_user ?? $log->user->name ?? '-' }}</td>
                        <td>{{ $log->role ?? $log->user->role->nama_role ?? '-' }}</td>
                        <td>{{ $log->modul ?? '-' }}</td>
                        <td>{{ $log->aksi ?? $log->aktivitas }}</td>
                        <td style="white-space:normal;min-width:260px">{{ $log->deskripsi ?? $log->aktivitas }}</td>
                        <td>{{ $log->ip_address ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <i class="bi bi-clock-history empty-icon"></i>
                                <h3>Belum ada aktivitas</h3>
                                <p>Aktivitas pengguna akan muncul setelah login, import, export, atau perubahan data.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">
        {{ $logs->links() }}
    </div>
</div>
@endsection
