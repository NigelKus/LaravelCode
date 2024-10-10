@if ($paginator->hasPages())
    <nav>
        <ul class="pagination">
            {{-- Cek Halaman Pertama --}}
            @if ($paginator->onFirstPage())
                <li class="page-item disabled" aria-disabled="true" aria-label="@lang('pagination.previous')">
                    <span class="page-link" aria-hidden="true">&lsaquo;</span>
                </li>
            @else
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="@lang('pagination.previous')">&lsaquo;</a>
                </li>
            @endif

            {{-- Check page 2 double --}}
            @if ($paginator->currentPage() > 2)
                <li class="page-item"><a class="page-link" href="{{ $paginator->url(1) }}">1</a></li>
            @endif

            {{-- Beri ... --}}
            @if ($paginator->currentPage() > 4 && $paginator->lastPage() > 6)
                <li class="page-item disabled"><span class="page-link">...</span></li>
            @endif

            {{-- 1 halaman sebelum currect page --}}
            @if ($paginator->currentPage() > 1)
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->url($paginator->currentPage() - 1) }}">{{ $paginator->currentPage() - 1 }}</a>
                </li>
            @endif
            
            {{-- Current page --}}
            <li class="page-item active" aria-current="page"><span class="page-link">{{ $paginator->currentPage() }}</span></li>

            {{-- 1 halaman setelah currect page --}}
            @if ($paginator->currentPage() < $paginator->lastPage())
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->url($paginator->currentPage() + 1) }}">{{ $paginator->currentPage() + 1 }}</a>
                </li>
            @endif

            {{-- Beri ... --}}
            @if ($paginator->currentPage() < $paginator->lastPage() - 1)
                <li class="page-item disabled"><span class="page-link">...</span></li>
            @endif

            {{-- Cek halaman sebelum last page --}}
            @if ($paginator->currentPage() < $paginator->lastPage() - 1)
                <li class="page-item"><a class="page-link" href="{{ $paginator->url($paginator->lastPage()) }}">{{ $paginator->lastPage() }}</a></li>
            @endif

            {{-- Check halaman terakhir  --}}
            @if ($paginator->hasMorePages())
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="@lang('pagination.next')">&rsaquo;</a>
                </li>
            @else
                <li class="page-item disabled" aria-disabled="true" aria-label="@lang('pagination.next')">
                    <span class="page-link" aria-hidden="true">&rsaquo;</span>
                </li>
            @endif
        </ul>
    </nav>
@endif
