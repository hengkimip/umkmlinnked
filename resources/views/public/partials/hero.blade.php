{{-- Hero halaman publik: motif & siluet Kalimantan Barat dari CSS (.ib-hero::before/::after) --}}
<section class="{{ $variant ?? 'ib-hero' }}" aria-labelledby="page-title">
    <div class="{{ $variant ?? 'ib-hero' }}__content">
        <span class="{{ $variant ?? 'ib-hero' }}__badge">{{ $badge }}</span>
        <h1 id="page-title" class="{{ $variant ?? 'ib-hero' }}__title">{{ $title }}</h1>
        <p class="{{ $variant ?? 'ib-hero' }}__desc">{{ $desc }}</p>
    </div>
</section>
