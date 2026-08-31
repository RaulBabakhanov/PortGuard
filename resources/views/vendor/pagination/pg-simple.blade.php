@if ($paginator->hasPages())
    <nav class="pg-pager" role="navigation" aria-label="Sayfalama">
        <p class="pg-pager-info">
            Sayfa {{ $paginator->currentPage() }} · sayfa başına {{ $paginator->perPage() }}
        </p>

        <ul class="pg-pager-list">
            @if ($paginator->onFirstPage())
                <li><span class="pg-pager-btn is-disabled" aria-disabled="true">‹</span></li>
            @else
                <li><a class="pg-pager-btn" href="{{ $paginator->previousPageUrl() }}" rel="prev">‹</a></li>
            @endif

            @if ($paginator->hasMorePages())
                <li><a class="pg-pager-btn" href="{{ $paginator->nextPageUrl() }}" rel="next">›</a></li>
            @else
                <li><span class="pg-pager-btn is-disabled" aria-disabled="true">›</span></li>
            @endif
        </ul>
    </nav>
@endif
