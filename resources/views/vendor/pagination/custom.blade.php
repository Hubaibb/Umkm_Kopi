@if ($paginator->hasPages())
<nav class="pagination" aria-label="Navigasi halaman">
    {{-- Previous --}}
    @if ($paginator->onFirstPage())
        <span class="disabled">← Sebelumnya</span>
    @else
        <a href="{{ $paginator->previousPageUrl() }}" rel="prev">← Sebelumnya</a>
    @endif

    {{-- Pages --}}
    @foreach ($elements as $element)
        @if (is_string($element))
            <span class="dots">{{ $element }}</span>
        @endif

        @if (is_array($element))
            @foreach ($element as $page => $url)
                @if ($page == $paginator->currentPage())
                    <span class="active">{{ $page }}</span>
                @else
                    <a href="{{ $url }}">{{ $page }}</a>
                @endif
            @endforeach
        @endif
    @endforeach

    {{-- Next --}}
    @if ($paginator->hasMorePages())
        <a href="{{ $paginator->nextPageUrl() }}" rel="next">Berikutnya →</a>
    @else
        <span class="disabled">Berikutnya →</span>
    @endif
</nav>
@endif
