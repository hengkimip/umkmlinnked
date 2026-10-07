{{-- $b: App\Models\Berita --}}
<article class="ib-berita-card">
    <a href="{{ route('berita.show', $b->slug) }}" class="ib-berita-card__link">
        <div class="ib-berita-card__foto">
            @if ($b->gambarUrl())
                <img src="{{ $b->gambarUrl() }}" alt="" width="480" height="270" loading="lazy" decoding="async">
            @else
                <span aria-hidden="true">UMKMLinked<b>.ID</b></span>
            @endif
        </div>
        <div class="ib-berita-card__isi">
            <time class="ib-berita-card__tanggal" datetime="{{ $b->terbit_pada->toIso8601String() }}">
                {{ $b->terbit_pada->translatedFormat('d F Y') }}
            </time>
            <h3 class="ib-berita-card__judul">{{ $b->judul }}</h3>
            <p class="ib-berita-card__ringkas">{{ $b->cuplikan() }}</p>
            <span class="ib-berita-card__baca">Baca selengkapnya <x-public.icon name="arrow-right" :size="16" :stroke="2" /></span>
        </div>
    </a>
</article>
