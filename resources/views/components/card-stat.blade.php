{{--
    Stat Card Component
    Usage: @include('components.card-stat', ['value' => 120, 'label' => 'Jumlah Siswa', 'icon' => 'bi-mortarboard-fill', 'color' => 'blue'])
    Colors: blue, green, amber, red, purple, teal
--}}

<div class="stat-card">
    <div class="stat-icon {{ $color ?? 'blue' }}">
        <i class="bi {{ $icon ?? 'bi-hash' }}"></i>
    </div>
    <div class="stat-info">
        <h3>{{ number_format($value ?? 0) }}</h3>
        <p>{{ $label ?? 'Label' }}</p>
    </div>
</div>
