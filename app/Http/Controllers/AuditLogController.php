<?php

namespace App\Http\Controllers;

use App\Models\LogAktivitas;
use App\Models\User;
use App\Services\ExportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function __construct(private readonly ExportService $exportService)
    {
    }

    public function index(Request $request)
    {
        $logs = $this->filteredQuery($request)
            ->with('user')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $filterOptions = $this->filterOptions();

        return view('audit_log.index', compact('logs', 'filterOptions'));
    }

    public function exportExcel(Request $request, ?string $filename = null)
    {
        $logs = $this->filteredQuery($request)->latest()->limit(1000)->get();

        LogAktivitas::catat('Export Excel audit log', 'Audit Log', 'Mengekspor Riwayat Aktivitas ke Excel');

        $rows = $logs->map(fn (LogAktivitas $log) => [
            optional($log->created_at)->format('d/m/Y H:i:s'),
            $log->nama_user ?? $log->user?->name ?? '-',
            $log->role ?? $log->user?->role?->nama_role ?? '-',
            $log->modul ?? '-',
            $log->aksi ?? $log->aktivitas,
            $log->deskripsi ?? $log->aktivitas,
            $log->ip_address ?? '-',
        ])->all();

        return $this->exportService->downloadExcel(
            ['Waktu', 'Nama User', 'Role', 'Modul', 'Aksi', 'Deskripsi', 'IP'],
            $rows,
            'audit_log_' . now()->format('Ymd_His'),
            'AUDIT_LOG',
            $this->auditMetaRows('RIWAYAT AKTIVITAS')
        );
    }

    public function exportPdf(Request $request, ?string $filename = null)
    {
        $logs = $this->filteredQuery($request)->latest()->limit(500)->get();

        LogAktivitas::catat('Export PDF audit log', 'Audit Log', 'Mengekspor Riwayat Aktivitas ke PDF');

        $pdf = Pdf::loadView('exports.audit_log_pdf', [
            'logs' => $logs,
            'tanggalCetak' => now(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('audit_log_' . now()->format('Ymd_His') . '.pdf');
    }

    private function filteredQuery(Request $request): Builder
    {
        return LogAktivitas::query()
            ->when($request->filled('tanggal'), fn (Builder $query) => $query->whereDate('created_at', $request->tanggal))
            ->when($request->filled('user_id'), fn (Builder $query) => $query->where('user_id', $request->user_id))
            ->when($request->filled('role'), fn (Builder $query) => $query->where('role', $request->role))
            ->when($request->filled('modul'), fn (Builder $query) => $query->where('modul', $request->modul))
            ->when($request->filled('aksi'), fn (Builder $query) => $query->where('aksi', $request->aksi))
            ->when($request->filled('search'), function (Builder $query) use ($request) {
                $search = $request->search;
                $query->where(function (Builder $subQuery) use ($search) {
                    $subQuery->where('nama_user', 'like', "%{$search}%")
                        ->orWhere('role', 'like', "%{$search}%")
                        ->orWhere('aksi', 'like', "%{$search}%")
                        ->orWhere('aktivitas', 'like', "%{$search}%")
                        ->orWhere('deskripsi', 'like', "%{$search}%")
                        ->orWhere('modul', 'like', "%{$search}%")
                        ->orWhere('ip_address', 'like', "%{$search}%");
                });
            });
    }

    private function filterOptions(): array
    {
        return [
            'users' => User::orderBy('name')->get(['id', 'name', 'username']),
            'roles' => LogAktivitas::query()->whereNotNull('role')->distinct()->orderBy('role')->pluck('role'),
            'moduls' => LogAktivitas::query()->whereNotNull('modul')->distinct()->orderBy('modul')->pluck('modul'),
            'aksis' => LogAktivitas::query()->whereNotNull('aksi')->distinct()->orderBy('aksi')->limit(100)->pluck('aksi'),
        ];
    }

    private function auditMetaRows(string $namaLaporan): array
    {
        return [
            ['SMP NEGERI 2 TASIKMALAYA'],
            ['SIABSoal SMPN 2 Tasikmalaya'],
            ['Nama Laporan', $namaLaporan],
            ['Tanggal Export', now()->format('d/m/Y H:i')],
        ];
    }
}
