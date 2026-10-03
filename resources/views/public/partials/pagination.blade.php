{{-- $paginator: LengthAwarePaginator --}}
<div class="ib-pagination">
    <p class="ib-pagination__info">
        Menampilkan <strong>{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}</strong>
        dari <strong>{{ number_format($paginator->total(), 0, ',', '.') }}</strong> UMKM
    </p>

    @if ($paginator->hasPages())
    <nav class="ib-pagination__nav" aria-label="Navigasi halaman">
        @if ($paginator->onFirstPage())
            <span class="ib-page-btn ib-page-btn--disabled" aria-hidden="true">‹</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="ib-page-btn" rel="prev" aria-label="Halaman sebelumnya">‹</a>
        @endif

        @foreach ($paginator->getUrlRange(1, $paginator->lastPage()) as $page => $url)
            @if ($page == $paginator->currentPage())
                <span class="ib-page-btn ib-page-btn--active" aria-current="page">{{ $page }}</span>
            @elseif ($page == 1 || $page == $paginator->lastPage() || abs($page - $paginator->currentPage()) <= 1)
                <a href="{{ $url }}" class="ib-page-btn" aria-label="Halaman {{ $page }}">{{ $page }}</a>
            @elseif (abs($page - $paginator->currentPage()) == 2)
                <span class="ib-page-btn ib-page-btn--dots" aria-hidden="true">…</span>
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="ib-page-btn" rel="next" aria-label="Halaman berikutnya">›</a>
        @else
            <span class="ib-page-btn ib-page-btn--disabled" aria-hidden="true">›</span>
        @endif
    </nav>
    @endif
</div>
