@php
    $title = $title ?? '';
    $subtitle = $subtitle ?? null;
    $showSearch = ! empty($show_search);
    $searchQuery = $search_query ?? '';
    $searchPlaceholder = $search_placeholder ?? 'Search products, stores, or categories';
@endphp

{{-- Reusable page header (used by customer-facing pages) --}}
<section class="page-hero{{ $showSearch ? ' page-hero--with-search' : '' }}">
    <div class="container">
        <h1>{{ $title }}</h1>
        @if (!empty($subtitle))
            <p class="page-hero-subtitle">{{ $subtitle }}</p>
        @endif

        @if ($showSearch)
            <form class="hero-search page-hero-search" action="{{ route('categories') }}" method="get" role="search">
                <input type="search"
                       name="q"
                       value="{{ $searchQuery }}"
                       placeholder="{{ $searchPlaceholder }}"
                       autocomplete="off"
                       aria-label="Search products, stores, or categories">
                <button type="submit" class="btn btn-search">Search</button>
            </form>
        @endif

        <div class="divider"></div>
    </div>
</section>
