@if ($paginator->hasPages() || $paginator->total() > 0)
    <nav class="pg-pager" role="navigation" aria-label="Sayfalama">
        <p class="pg-pager-info">
            {{ $paginator->firstItem() ?? 0 }}–{{ $paginator->lastItem() ?? 0 }} / {{ $paginator->total() }} kayıt
            · sayfa başına {{ $paginator->perPage() }}
        </p>

        @if ($paginator->hasPages())
            <ul class="pg-pager-list">
                @if ($paginator->onFirstPage())
                    <li><span class="pg-pager-btn is-disabled" aria-disabled="true">‹</span></li>
                @else
                    <li><a class="pg-pager-btn" href="{{ $paginator->previousPageUrl() }}" rel="prev">‹</a></li>
                @endif

                @foreach ($elements as $element)
                    @if (is_string($element))
                        <li><span class="pg-pager-btn is-disabled">{{ $element }}</span></li>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <li><span class="pg-pager-btn is-active" aria-current="page">{{ $page }}</span></li>
                            @else
                                <li><a class="pg-pager-btn" href="{{ $url }}">{{ $page }}</a></li>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                @if ($paginator->hasMorePages())
                    <li><a class="pg-pager-btn" href="{{ $paginator->nextPageUrl() }}" rel="next">›</a></li>
                @else
                    <li><span class="pg-pager-btn is-disabled" aria-disabled="true">›</span></li>
                @endif
            </ul>
        @endif
    </nav>
@endif
