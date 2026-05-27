{{--
    Breadcrumb Component
    Usage: @include('components.breadcrumb', ['items' => [
        ['label' => 'Dashboard', 'route' => 'dashboard'],
        ['label' => 'Data Ujian', 'route' => 'ujian.index'],
        ['label' => 'Detail Ujian'],
    ]])
--}}
<nav class="breadcrumb" aria-label="Breadcrumb">
    <a href="{{ route('dashboard') }}"><i class="bi bi-house-fill"></i></a>
    @foreach($items ?? [] as $item)
        <span class="separator"><i class="bi bi-chevron-right"></i></span>
        @if(isset($item['route']) && !$loop->last)
            <a href="{{ route($item['route'], $item['params'] ?? []) }}">{{ $item['label'] }}</a>
        @else
            <span class="current">{{ $item['label'] }}</span>
        @endif
    @endforeach
</nav>
