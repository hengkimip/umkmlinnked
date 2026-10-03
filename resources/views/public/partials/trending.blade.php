@if ($trending->isNotEmpty())
<section aria-labelledby="trending-title">
    <div class="ib-section-head">
        <div>
            <p class="ib-section-eyebrow">Pilihan teratas</p>
            <h2 id="trending-title" class="ib-section-heading">Trending</h2>
        </div>
        <div class="ib-slider-controls">
            <button type="button" data-slider-prev class="ib-slider-btn" aria-label="Geser ke kiri" aria-controls="trending-track">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            </button>
            <button type="button" data-slider-next class="ib-slider-btn" aria-label="Geser ke kanan" aria-controls="trending-track">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </button>
        </div>
    </div>
    <div class="ib-trending-viewport">
        <div class="ib-trending-track" id="trending-track">
            @foreach ($trending as $item)
                <div class="ib-trending-item">
                    @include('public.partials.umkm-card', ['item' => $item, 'badge' => $badge ?? 'digital', 'showKlasifikasi' => false])
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif
