@extends('layouts.app')
@section('title', 'Manajemen User')
@section('page-title', 'Manajemen User')

@section('content')
<div class="page-header d-flex justify-between align-center flex-wrap gap-2">
    <div>
        <h1>Manajemen User</h1>
        <p>Kelola akun pengguna sistem</p>
    </div>
    <a href="{{ route('users.create') }}" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Tambah User</a>
</div>

{{-- Search --}}
<div class="card mb-3">
    <div class="card-body">
        <form action="{{ route('users.index') }}" method="GET" class="d-flex gap-1">
            <input type="text" name="search" class="form-control" placeholder="Cari nama atau username..." value="{{ request('search') }}" style="max-width:320px">
            <button type="submit" class="btn btn-outline"><i class="bi bi-search"></i></button>
            @if(request('search'))
                <a href="{{ route('users.index') }}" class="btn btn-outline">Reset</a>
            @endif
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $i => $user)
                    <tr>
                        <td>{{ $users->firstItem() + $i }}</td>
                        <td class="fw-semibold">{{ $user->name }}</td>
                        <td>{{ $user->username }}</td>
                        <td>{{ $user->email ?? '-' }}</td>
                        <td>{{ $user->role->nama_role ?? '-' }}</td>
                        <td>@include('components.badge', ['type' => $user->status])</td>
                        <td>
                            <div class="btn-group">
                                <a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-outline"><i class="bi bi-pencil"></i></a>
                                @if($user->id !== auth()->id())
                                    <form id="delete-user-{{ $user->id }}" action="{{ route('users.destroy', $user) }}" method="POST" style="display:inline">
                                        @csrf @method('DELETE')
                                    </form>
                                    <button class="btn btn-sm btn-danger" onclick="confirmDelete('delete-user-{{ $user->id }}', '{{ $user->name }}')"><i class="bi bi-trash"></i></button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted" style="padding:24px">Belum ada data user.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($users->hasPages())
        <div class="pagination-wrapper">{!! $users->links('components.pagination') !!}</div>
    @endif
</div>
@endsection
