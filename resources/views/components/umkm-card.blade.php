<a href="{{ route('direktori.show', $item->slug) }}" class="umkm-card">
    <div class="umkm-card__photo-wrap">
        <div class="umkm-card__photo">
            @if($fotoTampil)
                <img src="{{ $fotoTampil }}" alt="{{ $item->nama_usaha }}" loading="lazy">
            @else
                <div class="umkm-card__placeholder">{{ $theme === 'global' ? '🌍' : '🏪' }}</div>
            @endif

            @if(count($badges))
            <div class="umkm-card__badges">
                @foreach($badges as $badge)
                <span class="umkm-badge" style="background:{{ $badge['color'] }}">{{ $badge['label'] }}</span>
                @endforeach
            </div>
            @endif

            @if($showKlasifikasi && $item->klasifikasi === 'unggulan')
            <span class="umkm-card__klasifikasi umkm-card__klasifikasi--unggulan">⭐ Unggulan</span>
            @elseif($showKlasifikasi && $item->klasifikasi === 'berkembang')
            <span class="umkm-card__klasifikasi umkm-card__klasifikasi--berkembang">📈 Berkembang</span>
            @endif

            @if($item->whatsapp)
            <span class="umkm-card__whatsapp" data-wa-link="{{ $item->wa_link }}">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="white">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                    <path d="M12 0C5.373 0 0 5.373 0 12c0 2.115.549 4.099 1.508 5.826L0 24l6.335-1.484A11.945 11.945 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.818a9.818 9.818 0 01-5.006-1.366l-.36-.214-3.727.872.936-3.619-.235-.372A9.818 9.818 0 1112 21.818z"/>
                </svg>
            </span>
            @endif
        </div>
    </div>

    <div class="umkm-card__info umkm-card__info--{{ $theme }}">
        <p class="umkm-card__nama">{{ $item->nama_usaha }}</p>
        <p class="umkm-card__sektor">{{ ucfirst($item->sektor) }}</p>
    </div>
</a>