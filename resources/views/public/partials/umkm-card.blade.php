{{--
    Kartu UMKM. Hanya data yang boleh publik (NFR-02): nama, sektor,
    kabupaten, foto produk, kanal pemasaran, sertifikasi, tautan WhatsApp usaha.
    $item  : App\Models\Umkm
    $badge : 'digital' (marketplace) | 'global' (sertifikasi)
    $showKlasifikasi : tampilkan label klasifikasi bila ada (default true)
--}}
@php
    $badge           = $badge ?? 'digital';
    $showKlasifikasi = $showKlasifikasi ?? true;

    $produk = $item->produkUnggulan->first() ?? $item->produk->first();
    $foto   = $produk?->foto_kecil ?: ($item->foto_usaha ? Storage::url($item->foto_usaha) : null);

    $inisial = collect(preg_split('/\s+/', trim($item->nama_usaha)))
        ->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');

    $lokasi = $item->kabupaten && $item->kabupaten !== \App\Models\Umkm::KABUPATEN_KOSONG ? $item->kabupaten : null;

    $badges = $badge === 'global'
        ? array_filter([
            $item->legalitas?->nomor_halal ? ['Halal', 'halal'] : null,
            $item->legalitas?->nomor_bpom  ? ['BPOM', 'bpom']   : null,
            $item->legalitas?->nomor_sni   ? ['SNI', 'sni']     : null,
        ])
        : array_filter([
            $item->tokopedia ? ['Tokopedia', 'tokped'] : null,
            $item->shopee    ? ['Shopee', 'shopee']    : null,
            $item->instagram ? ['IG', 'ig']            : null,
        ]);

    $klasifikasi = $showKlasifikasi && in_array($item->klasifikasi, ['unggulan', 'berkembang'], true)
        ? $item->klasifikasi : null;
@endphp
<a href="{{ route('direktori.show', $item->slug) }}" class="ib-card">
    <div class="ib-card__photo">
        <div class="ib-card__photo-inner">
            @if ($foto)
                <img src="{{ $foto }}" alt="Produk {{ $item->nama_usaha }}" width="400" height="400"
                     loading="lazy" decoding="async" referrerpolicy="no-referrer">
            @else
                <div class="ib-card__placeholder" aria-hidden="true">{{ $inisial ?: 'U' }}</div>
            @endif

            @if ($klasifikasi)
                <span class="ib-card__klasifikasi ib-card__klasifikasi--{{ $klasifikasi }}">{{ ucfirst($klasifikasi) }}</span>
            @elseif ($badges)
                <div class="ib-card__badges">
                    @foreach ($badges as [$label, $mod])
                        <span class="ib-badge ib-badge--{{ $mod }}">{{ $label }}</span>
                    @endforeach
                </div>
            @endif

            @if ($item->wa_link)
                <span class="ib-card__wa" data-wa-link="{{ $item->wa_link }}" title="Hubungi via WhatsApp">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="#fff" aria-hidden="true"><path d="M17.47 14.38c-.3-.15-1.76-.87-2.03-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.94 1.16-.17.2-.35.22-.64.07-.3-.15-1.26-.46-2.39-1.47-.88-.79-1.48-1.76-1.65-2.06-.17-.3-.02-.46.13-.6.13-.14.3-.35.45-.52.15-.18.2-.3.3-.5.1-.2.05-.37-.03-.52-.07-.15-.67-1.61-.92-2.2-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.79.37-.27.3-1.04 1.02-1.04 2.48s1.07 2.88 1.21 3.07c.15.2 2.1 3.2 5.08 4.49.71.31 1.26.49 1.7.63.71.22 1.36.19 1.87.12.57-.09 1.76-.72 2-1.41.25-.7.25-1.29.18-1.41-.08-.13-.28-.2-.57-.35zM12 0C5.37 0 0 5.37 0 12c0 2.12.55 4.1 1.51 5.83L0 24l6.34-1.48A11.95 11.95 0 0 0 12 24c6.63 0 12-5.37 12-12S18.63 0 12 0zm0 21.82a9.82 9.82 0 0 1-5-1.37l-.36-.21-3.73.87.94-3.62-.24-.37A9.82 9.82 0 1 1 12 21.82z"/></svg>
                </span>
            @endif
        </div>
    </div>
    <div class="ib-card__info">
        <h3 class="ib-card__name">{{ $item->nama_usaha }}</h3>
        <p class="ib-card__sektor">{{ $item->sektor_label }}@if ($lokasi) · {{ $lokasi }}@endif</p>
    </div>
</a>
