@forelse($umkmUnggulan as $item)
    @if(is_object($item) && isset($item->slug))
    <a href="{{ route('direktori.show', $item->slug) }}"
       class="bg-white rounded-2xl border hover:shadow-md transition group overflow-hidden">
        <div class="aspect-video overflow-hidden bg-gray-100 flex items-center justify-center text-4xl">
            @if($item->foto_usaha)
                <img src="{{ Storage::url($item->foto_usaha) }}"
                     alt="{{ $item->nama_usaha }}"
                     class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
            @else
                🏪
            @endif
        </div>
        <div class="p-4">
            <span class="text-xs font-medium px-2 py-1 rounded-full bg-yellow-100 text-yellow-700">
                Unggulan
            </span>
            <h3 class="font-semibold text-gray-800 mt-2 line-clamp-1">
                {{ $item->nama_usaha }}
            </h3>
            <p class="text-xs text-gray-500 mt-1">📍 {{ $item->kabupaten ?? '-' }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ ucfirst($item->sektor ?? '-') }}</p>
        </div>
    </a>
    @endif
@empty
    <div class="col-span-4 text-center py-16 bg-gray-50 rounded-2xl border border-dashed">
        <p class="text-4xl mb-3">🏪</p>
        <p class="text-gray-500">Belum ada UMKM unggulan</p>
    </div>
@endforelse