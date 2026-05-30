<div class="table-responsive">
    <table class="table">
        <thead>
            <tr>
                <th>Waktu</th>
                @if($showUser ?? true)<th>User</th>@endif
                <th>Modul</th>
                <th>Aksi</th>
                <th>Deskripsi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs as $log)
                <tr>
                    <td>{{ $log->created_at?->format('d/m/Y H:i') }}</td>
                    @if($showUser ?? true)<td>{{ $log->nama_user ?? $log->user->name ?? '-' }}</td>@endif
                    <td>{{ $log->modul ?? '-' }}</td>
                    <td>{{ $log->aksi ?? $log->aktivitas }}</td>
                    <td style="white-space:normal;min-width:220px">{{ $log->deskripsi ?? $log->aktivitas }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ ($showUser ?? true) ? 5 : 4 }}" class="text-center text-muted" style="padding:24px">
                        Belum ada aktivitas.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
