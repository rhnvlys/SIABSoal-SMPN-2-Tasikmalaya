{{--
    Empty State Component
    Usage: @include('components.empty-state', ['icon' => 'bi-inbox', 'title' => 'Belum ada data', 'description' => 'Silakan tambahkan data terlebih dahulu.'])
--}}
<div class="empty-state">
    <i class="bi {{ $icon ?? 'bi-inbox' }} empty-icon"></i>
    <h3>{{ $title ?? 'Belum ada data' }}</h3>
    <p>{{ $description ?? 'Data yang Anda cari belum tersedia.' }}</p>
    @if(isset($actionRoute) && isset($actionLabel))
    <a href="{{ $actionRoute }}" class="btn btn-primary" style="margin-top:16px">
        <i class="bi {{ $actionIcon ?? 'bi-plus-circle' }}"></i> {{ $actionLabel }}
    </a>
    @endif
</div>
