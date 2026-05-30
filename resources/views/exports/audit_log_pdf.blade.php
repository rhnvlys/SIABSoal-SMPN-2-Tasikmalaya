<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Riwayat Aktivitas</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 9pt; color: #1F2937; }
        .header { text-align: center; margin-bottom: 14px; border-bottom: 2px solid #1F2937; padding-bottom: 8px; }
        .header h1 { font-size: 13pt; margin: 0; }
        .header p { margin: 2px 0; color: #4B5563; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #9CA3AF; padding: 4px 6px; vertical-align: top; }
        th { background: #E7F0FA; font-weight: bold; text-align: center; }
        td { font-size: 8pt; }
        .footer { margin-top: 12px; text-align: right; font-size: 8pt; color: #6B7280; }
    </style>
</head>
<body>
    <div class="header">
        <h1>SMP NEGERI 2 TASIKMALAYA</h1>
        <p>SIABSoal SMPN 2 Tasikmalaya</p>
        <p>RIWAYAT AKTIVITAS / AUDIT LOG</p>
    </div>

    <table>
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
            @foreach($logs as $log)
                <tr>
                    <td>{{ $log->created_at?->format('d/m/Y H:i:s') }}</td>
                    <td>{{ $log->nama_user ?? $log->user->name ?? '-' }}</td>
                    <td>{{ $log->role ?? $log->user->role->nama_role ?? '-' }}</td>
                    <td>{{ $log->modul ?? '-' }}</td>
                    <td>{{ $log->aksi ?? $log->aktivitas }}</td>
                    <td>{{ $log->deskripsi ?? $log->aktivitas }}</td>
                    <td>{{ $log->ip_address ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Dicetak {{ $tanggalCetak->format('d/m/Y H:i') }}
    </div>
</body>
</html>
