<nav class="nextplay-pagination" role="navigation" aria-label="Pagination Navigation">
    <p class="nextplay-pagination__summary" aria-live="polite">Page {{ number_format($paginator->currentPage()) }}</p>

    <div class="nextplay-pagination__scroller">
        <div class="nextplay-pagination__controls">
            @if($paginator->onFirstPage())
                <span class="nextplay-pagination__item nextplay-pagination__item--disabled" aria-disabled="true" aria-label="Previous page">
                    <svg viewBox="0 0 20 20" aria-hidden="true"><path d="M12.5 15 7.5 10l5-5" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2"/></svg>
                </span>
            @else
                <a class="nextplay-pagination__item" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous page">
                    <svg viewBox="0 0 20 20" aria-hidden="true"><path d="M12.5 15 7.5 10l5-5" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2"/></svg>
                </a>
            @endif

            @if($paginator->hasMorePages())
                <a class="nextplay-pagination__item nextplay-pagination__item--wide" href="{{ $paginator->nextPageUrl() }}" rel="next">Next</a>
            @else
                <span class="nextplay-pagination__item nextplay-pagination__item--wide nextplay-pagination__item--disabled" aria-disabled="true">Next</span>
            @endif
        </div>
    </div>
</nav>
