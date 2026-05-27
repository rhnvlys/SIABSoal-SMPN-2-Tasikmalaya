{{-- Badge Component — Sesuai Arahan Sekolah --}}
@php
    $badgeClasses = [
        // Status ujian
        'draft' => ['class' => 'badge-gray', 'label' => 'Draft'],
        'kunci_lengkap' => ['class' => 'badge-blue', 'label' => 'Kunci Lengkap'],
        'data_mentah' => ['class' => 'badge-amber', 'label' => 'Data Mentah T1'],
        'olah_data' => ['class' => 'badge-purple', 'label' => 'Olah Data T2'],
        'dianalisis' => ['class' => 'badge-teal', 'label' => 'Dianalisis T3'],
        'selesai' => ['class' => 'badge-green', 'label' => 'Selesai'],

        // Kategori Daya Pembeda (DP)
        'dp_baik' => ['class' => 'badge-green', 'label' => 'Baik'],
        'dp_revisi' => ['class' => 'badge-amber', 'label' => 'Revisi'],
        'dp_buang' => ['class' => 'badge-red', 'label' => 'Buang'],

        // Kategori Tingkat Kesukaran (TK)
        'tk_mudah' => ['class' => 'badge-green', 'label' => 'Mudah'],
        'tk_sedang' => ['class' => 'badge-blue', 'label' => 'Sedang'],
        'tk_sukar' => ['class' => 'badge-red', 'label' => 'Sukar'],

        // Keputusan soal
        'keputusan_dipakai' => ['class' => 'badge-green', 'label' => 'Dipakai'],
        'keputusan_dipakai_dengan_catatan' => ['class' => 'badge-teal', 'label' => 'Dipakai dengan catatan'],
        'keputusan_revisi' => ['class' => 'badge-amber', 'label' => 'Revisi'],
        'keputusan_buang' => ['class' => 'badge-red', 'label' => 'Buang'],

        // Kelompok
        'atas' => ['class' => 'badge-green', 'label' => 'Atas'],
        'bawah' => ['class' => 'badge-amber', 'label' => 'Bawah'],
        'tengah' => ['class' => 'badge-purple', 'label' => 'Tengah'],
        'tidak_hadir' => ['class' => 'badge-red', 'label' => 'Tidak Hadir'],

        // Keterangan
        'tercapai' => ['class' => 'badge-green', 'label' => 'TERCAPAI'],
        'perlu_peningkatan' => ['class' => 'badge-amber', 'label' => 'PERLU PENINGKATAN'],

        // Kehadiran
        'hadir' => ['class' => 'badge-green', 'label' => 'Hadir'],

        // Status guru/siswa
        'aktif' => ['class' => 'badge-green', 'label' => 'Aktif'],
        'nonaktif' => ['class' => 'badge-gray', 'label' => 'Nonaktif'],
    ];

    $badge = $badgeClasses[$type ?? ''] ?? ['class' => 'badge-gray', 'label' => $type ?? '-'];
@endphp

<span class="badge {{ $badge['class'] }}">{{ $label ?? $badge['label'] }}</span>
