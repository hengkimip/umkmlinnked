{{--
    Kartu produk beranda (dipakai untuk kartu yang tampil & cadangan rotasi).
    $u       : App\Models\Umkm (relasi produk, legalitas, pemasaran sudah dimuat)
    $lencana : 'klasifikasi' | 'digital' | 'global'
    $eager   : muat gambar segera (baris pertama di atas lipatan)
--}}
@php
    // URL gambar hanya dipakai bila bentuknya aman untuk atribut src
    $amanUrl = fn (?string $url) => $url && preg_match('#^(https?://|/)[^\s\'"()<>\\\\]+$#', $url) ? $url : null;

    $produkFoto = $u->produk->first(fn ($p) => $amanUrl($p->foto_kecil));
    $produk     = $produkFoto ?? $u->produk->first();
    $foto       = $produkFoto ? $amanUrl($produkFoto->foto_kecil)
                : $amanUrl($u->foto_usaha ? Storage::url($u->foto_usaha) : null);
    $lokasi     = $u->kabupaten !== \App\Models\Umkm::KABUPATEN_KOSONG ? $u->kabupaten : null;
    $inisial    = collect(preg_split('/\s+/', trim($u->nama_usaha)))->filter()->take(2)
                    ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');

    $daftarLencana = match ($lencana) {
        'digital' => array_values(array_filter([
            $u->marketplaceUrl('tokopedia') ? 'Tokopedia' : null,
            $u->marketplaceUrl('shopee') ? 'Shopee' : null,
            $u->instagramUrl() ? 'Instagram' : null,
            $u->wa_link ? 'WhatsApp' : null,
        ])),
        'global' => array_values(array_filter([
            ['ekspor' => 'Ekspor', 'nasional' => 'Nasional', 'regional' => 'Regional'][$u->pemasaran?->jangkauan_pasar] ?? null,
            $u->legalitas?->nomor_halal ? 'Halal' : null,
            $u->legalitas?->nomor_bpom ? 'BPOM' : null,
            $u->legalitas?->nomor_sni ? 'SNI' : null,
        ])),
        default => in_array($u->klasifikasi, ['unggulan', 'berkembang'], true) ? [ucfirst($u->klasifikasi)] : [],
    };
@endphp
<li class="ib-rail__item">
    <a href="{{ route('direktori.show', $u->slug) }}" class="ib-produk">
        <div class="ib-produk__foto">
            @if ($foto)
                <img src="{{ $foto }}" alt="{{ $produk?->nama_produk ?? $u->nama_usaha }}" width="320" height="400"
                     loading="{{ ($eager ?? false) ? 'eager' : 'lazy' }}" decoding="async" referrerpolicy="no-referrer">
            @else
                <div class="ib-produk__placeholder" aria-hidden="true">
                    <span>{{ $inisial ?: 'U' }}</span>
                </div>
            @endif
            @if ($daftarLencana)
                <div class="ib-produk__lencana">
                    @foreach (array_slice($daftarLencana, 0, 2) as $l)
                        <span>{{ $l }}</span>
                    @endforeach
                    @if (count($daftarLencana) > 2)<span>+{{ count($daftarLencana) - 2 }}</span>@endif
                </div>
            @endif
        </div>
        <div class="ib-produk__info">
            <p class="ib-produk__brand">{{ $u->nama_usaha }}</p>
            <h3 class="ib-produk__nama">{{ $produk?->nama_produk ?? $u->sektor_label }}</h3>
            <p class="ib-produk__meta">
                <x-public.icon name="map-pin" :size="14" />
                {{ $lokasi ?? 'Kalimantan Barat' }} · {{ $u->sektor_label }}
            </p>
            @if ($produk?->harga > 0)
                <p class="ib-produk__harga">Rp {{ number_format($produk->harga, 0, ',', '.') }}</p>
            @endif
        </div>
    </a>
</li>
