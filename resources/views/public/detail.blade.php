@extends('layouts.public')
@section('title', $umkm->nama_usaha . ' — UMKMLinked.ID')

@section('content')

{{-- HERO --}}
<div style="position:relative; height:280px; overflow:hidden; background:linear-gradient(135deg,#065f46 0%,#0d9488 50%,#0891b2 100%)">
    <div style="position:relative; z-index:10; display:flex; flex-direction:column; justify-content:flex-end; height:100%; padding:0 32px 32px; max-width:1280px; margin:0 auto">
        <a href="{{ route('direktori.index') }}" style="color:rgba(255,255,255,0.8); font-size:13px; text-decoration:none; margin-bottom:12px; display:inline-block">
            ← Kembali ke Direktori
        </a>
        <span style="background:rgba(255,255,255,0.2); padding:6px 14px; border-radius:20px; color:white; font-size:12px; font-weight:600; border:1px solid rgba(255,255,255,0.3); width:fit-content; margin-bottom:8px">
            {{ ucfirst($umkm->klasifikasi) }}
        </span>
        <h1 style="color:white; font-size:28px; font-weight:700; margin:0 0 6px">{{ $umkm->nama_usaha }}</h1>
        <p style="color:rgba(255,255,255,0.8); font-size:14px; margin:0">
            {{ ucfirst($umkm->sektor) }} · 📍 {{ $umkm->kabupaten }}
        </p>
    </div>
</div>

<div style="max-width:1280px; margin:0 auto; padding:24px 16px">
<div style="display:grid; grid-template-columns:2fr 1fr; gap:24px">

    {{-- KOLOM KIRI --}}
    <div>
        {{-- Foto Utama --}}
        <div style="border-radius:16px; overflow:hidden; background:#f0fdfa; aspect-ratio:16/9; margin-bottom:20px">
            @php
                $produkUtama = $umkm->produk->firstWhere('is_unggulan', true) ?? $umkm->produk->first();
                $fotoTampil  = $produkUtama?->foto_final;
                if (!$fotoTampil && $umkm->foto_usaha) {
                    $fotoTampil = Storage::url($umkm->foto_usaha);
                }
            @endphp
            @if($fotoTampil)
                <img src="{{ $fotoTampil }}" alt="{{ $umkm->nama_usaha }}" style="width:100%; height:100%; object-fit:cover">
            @else
                <div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; font-size:80px">🏪</div>
            @endif
        </div>

        {{-- Deskripsi --}}
        @if($umkm->deskripsi)
        <div style="background:white; border-radius:14px; padding:20px; margin-bottom:20px; border:1px solid #e5e7eb">
            <h2 style="font-size:16px; font-weight:700; color:#1f2937; margin:0 0 10px">Tentang Usaha</h2>
            <p style="font-size:14px; color:#4b5563; line-height:1.6; margin:0">{{ $umkm->deskripsi }}</p>
        </div>
        @endif

        {{-- Produk --}}
        @if($umkm->produk->isNotEmpty())
        <div style="background:white; border-radius:14px; padding:20px; margin-bottom:20px; border:1px solid #e5e7eb">
            <h2 style="font-size:16px; font-weight:700; color:#1f2937; margin:0 0 14px">Produk</h2>
            <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:12px">
                @foreach($umkm->produk as $produk)
                <div style="border-radius:10px; overflow:hidden; border:1px solid #f3f4f6">
                    <div style="aspect-ratio:1; background:#f9fafb">
                        @if($produk->foto_final)
                            <img src="{{ $produk->foto_final }}" alt="{{ $produk->nama_produk }}" style="width:100%; height:100%; object-fit:cover">
                        @else
                            <div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; font-size:32px">📦</div>
                        @endif
                    </div>
                    <div style="padding:8px">
                        <p style="font-size:12px; font-weight:600; color:#1f2937; margin:0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis">{{ $produk->nama_produk }}</p>
                        @if($produk->harga)
                        <p style="font-size:11px; color:#0d9488; margin:2px 0 0">Rp {{ number_format($produk->harga,0,',','.') }}</p>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Legalitas & Sertifikasi --}}
        @if($umkm->legalitas)
        <div style="background:white; border-radius:14px; padding:20px; margin-bottom:20px; border:1px solid #e5e7eb">
            <h2 style="font-size:16px; font-weight:700; color:#1f2937; margin:0 0 14px">Legalitas & Sertifikasi</h2>
            <div style="display:flex; gap:8px; flex-wrap:wrap">
                @if($umkm->legalitas->nomor_nib)
                <span style="background:#f0fdf4; color:#15803d; padding:6px 12px; border-radius:8px; font-size:12px; font-weight:600">✓ NIB</span>
                @endif
                @if($umkm->legalitas->nomor_siup)
                <span style="background:#f0fdf4; color:#15803d; padding:6px 12px; border-radius:8px; font-size:12px; font-weight:600">✓ SIUP</span>
                @endif
                @if($umkm->legalitas->nomor_halal)
                <span style="background:#f0fdf4; color:#15803d; padding:6px 12px; border-radius:8px; font-size:12px; font-weight:600">🌙 Halal MUI</span>
                @endif
                @if($umkm->legalitas->nomor_bpom)
                <span style="background:#fef2f2; color:#dc2626; padding:6px 12px; border-radius:8px; font-size:12px; font-weight:600">🏥 BPOM</span>
                @endif
                @if($umkm->legalitas->nomor_pirt)
                <span style="background:#eff6ff; color:#1d4ed8; padding:6px 12px; border-radius:8px; font-size:12px; font-weight:600">📋 PIRT</span>
                @endif
                @if(!$umkm->legalitas->nomor_nib && !$umkm->legalitas->nomor_siup && !$umkm->legalitas->nomor_halal && !$umkm->legalitas->nomor_bpom && !$umkm->legalitas->nomor_pirt)
                <p style="font-size:13px; color:#9ca3af; margin:0">Belum ada data legalitas.</p>
                @endif
            </div>
        </div>
        @endif
    </div>

    {{-- KOLOM KANAN --}}
    <div>
        {{-- Kontak --}}
        <div style="background:white; border-radius:14px; padding:20px; margin-bottom:20px; border:1px solid #e5e7eb; position:sticky; top:76px">
            <h2 style="font-size:16px; font-weight:700; color:#1f2937; margin:0 0 14px">Kontak & Informasi</h2>

            @if($umkm->whatsapp)
            <a href="{{ $umkm->wa_link }}" target="_blank"
               style="display:flex; align-items:center; justify-content:center; gap:8px; background:#22c55e; color:white; padding:12px; border-radius:10px; text-decoration:none; font-size:14px; font-weight:600; margin-bottom:12px">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="white">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                    <path d="M12 0C5.373 0 0 5.373 0 12c0 2.115.549 4.099 1.508 5.826L0 24l6.335-1.484A11.945 11.945 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.818a9.818 9.818 0 01-5.006-1.366l-.36-.214-3.727.872.936-3.619-.235-.372A9.818 9.818 0 1112 21.818z"/>
                </svg>
                Hubungi via WhatsApp
            </a>
            @endif

            <div style="display:flex; flex-direction:column; gap:10px; font-size:13px">
                @if($umkm->alamat_usaha)
                <div>
                    <p style="color:#9ca3af; margin:0 0 2px">📍 Alamat</p>
                    <p style="color:#1f2937; margin:0">{{ $umkm->alamat_usaha }}</p>
                </div>
                @endif
                @if($umkm->tahun_berdiri)
                <div>
                    <p style="color:#9ca3af; margin:0 0 2px">📅 Tahun Berdiri</p>
                    <p style="color:#1f2937; margin:0">{{ $umkm->tahun_berdiri }}</p>
                </div>
                @endif
                @if($umkm->jumlah_tenaga_kerja)
                <div>
                    <p style="color:#9ca3af; margin:0 0 2px">👥 Tenaga Kerja</p>
                    <p style="color:#1f2937; margin:0">{{ $umkm->jumlah_tenaga_kerja }} orang</p>
                </div>
                @endif
            </div>

            {{-- Social Links --}}
            @if($umkm->instagram || $umkm->website || $umkm->tokopedia || $umkm->shopee)
            <div style="margin-top:16px; padding-top:16px; border-top:1px solid #f3f4f6; display:flex; flex-direction:column; gap:8px">
                @if($umkm->instagram)
                <a href="{{ $umkm->instagram }}" target="_blank" style="color:#c13584; font-size:13px; text-decoration:none; font-weight:600">📷 Instagram</a>
                @endif
                @if($umkm->tokopedia)
                <a href="{{ $umkm->tokopedia }}" target="_blank" style="color:#03a500; font-size:13px; text-decoration:none; font-weight:600">🛒 Tokopedia</a>
                @endif
                @if($umkm->shopee)
                <a href="{{ $umkm->shopee }}" target="_blank" style="color:#ee4d2d; font-size:13px; text-decoration:none; font-weight:600">🛍️ Shopee</a>
                @endif
                @if($umkm->website)
                <a href="{{ $umkm->website }}" target="_blank" style="color:#2563eb; font-size:13px; text-decoration:none; font-weight:600">🌐 Website</a>
                @endif
            </div>
            @endif

            {{-- Skor --}}
            <div style="margin-top:16px; padding-top:16px; border-top:1px solid #f3f4f6">
                <p style="font-size:12px; color:#9ca3af; margin:0 0 6px">Skor Klasifikasi</p>
                <div style="display:flex; align-items:center; gap:8px">
                    <div style="flex:1; height:8px; background:#f3f4f6; border-radius:4px; overflow:hidden">
                        <div style="width:{{ $umkm->skor_total }}%; height:100%; background:#0d9488"></div>
                    </div>
                    <span style="font-size:13px; font-weight:700; color:#0d9488">{{ $umkm->skor_total }}/100</span>
                </div>
            </div>
        </div>
    </div>

</div>

{{-- UMKM Terkait --}}
@if($related->isNotEmpty())
<div style="margin-top:32px">
    <h2 style="font-size:16px; font-weight:700; color:#1f2937; margin:0 0 14px">UMKM Sejenis Lainnya</h2>
    <div style="display:grid; grid-template-columns:repeat(4,1fr); gap:14px">
        @foreach($related as $item)
            <x-umkm-card :item="$item" theme="digital" />
        @endforeach
    </div>
</div>
@endif

</div>
@endsection