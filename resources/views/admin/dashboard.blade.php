<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — UMKMLinked.ID</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 font-sans antialiased">
<div class="flex min-h-screen">

    <aside class="w-56 bg-green-800 text-white flex-shrink-0 relative">
        <div class="p-5 border-b border-green-700">
            <span class="font-bold text-lg">UMKMLinked<span class="text-green-300">.ID</span></span>
            <p class="text-green-400 text-xs mt-1">Panel Admin</p>
        </div>
        <nav class="p-4 space-y-1 text-sm">
            <a href="/admin/dashboard" class="block px-3 py-2 rounded-lg bg-green-700">Dashboard</a>
            <a href="/admin/import" class="block px-3 py-2 rounded-lg hover:bg-green-700 transition">Import Data</a>
            <a href="/" target="_blank" class="block px-3 py-2 rounded-lg hover:bg-green-700 transition text-green-300">Lihat Website</a>
        </nav>
        <div class="absolute bottom-0 w-56 p-4 border-t border-green-700">
            <form method="POST" action="/logout">
                @csrf
                <button type="submit" class="w-full text-left text-sm text-green-300 hover:text-white px-3 py-2">
                    Keluar
                </button>
            </form>
        </div>
    </aside>

    <main class="flex-1 overflow-auto">
        <div class="bg-white border-b px-6 py-3">
            <p class="text-sm text-gray-600">Selamat datang, <strong>{{ auth()->user()->name }}</strong></p>
        </div>

        <div class="p-6">

            @if(session('success'))
            <div class="bg-green-50 border border-green-200 rounded-xl px-4 py-3 text-green-700 text-sm mb-6">
                {{ session('success') }}
            </div>
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
                <div class="bg-white rounded-2xl border p-5">
                    <p class="text-xs text-gray-500 mb-1">Total UMKM</p>
                    <p class="text-3xl font-bold text-green-700">{{ \App\Models\Umkm::count() }}</p>
                </div>
                <div class="bg-white rounded-2xl border p-5">
                    <p class="text-xs text-gray-500 mb-1">UMKM Aktif</p>
                    <p class="text-3xl font-bold text-blue-600">{{ \App\Models\Umkm::where('status','aktif')->count() }}</p>
                </div>
                <div class="bg-white rounded-2xl border p-5">
                    <p class="text-xs text-gray-500 mb-1">UMKM Unggulan</p>
                    <p class="text-3xl font-bold text-yellow-600">{{ \App\Models\Umkm::where('klasifikasi','unggulan')->count() }}</p>
                </div>
                <div class="bg-white rounded-2xl border p-5">
                    <p class="text-xs text-gray-500 mb-1">Total Produk</p>
                    <p class="text-3xl font-bold text-purple-600">{{ \App\Models\Produk::count() }}</p>
                </div>
            </div>

            <div class="bg-white rounded-2xl border p-6 mb-6">
                <h2 class="font-semibold text-gray-800 mb-4">Aksi Cepat</h2>
                <div class="flex flex-wrap gap-3">
                    <a href="/admin/import" class="bg-green-700 text-white px-5 py-2 rounded-xl text-sm font-medium hover:bg-green-800 transition">
                        Import Data CSV
                    </a>
                    <a href="/" target="_blank" class="bg-gray-100 text-gray-700 px-5 py-2 rounded-xl text-sm font-medium hover:bg-gray-200 transition">
                        Lihat Website Publik
                    </a>
                </div>
            </div>

            <div class="bg-white rounded-2xl border p-6">
                <h2 class="font-semibold text-gray-800 mb-4">UMKM Terbaru</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-left text-gray-500">
                                <th class="pb-3 font-medium">Nama UMKM</th>
                                <th class="pb-3 font-medium">Sektor</th>
                                <th class="pb-3 font-medium">Kabupaten</th>
                                <th class="pb-3 font-medium">Klasifikasi</th>
                                <th class="pb-3 font-medium">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse(\App\Models\Umkm::latest()->limit(10)->get() as $umkm)
                            <tr class="hover:bg-gray-50">
                                <td class="py-3 font-medium text-gray-800">{{ $umkm->nama_usaha }}</td>
                                <td class="py-3 text-gray-500">{{ ucfirst($umkm->sektor) }}</td>
                                <td class="py-3 text-gray-500">{{ $umkm->kabupaten }}</td>
                                <td class="py-3">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium
                                        @if($umkm->klasifikasi==='unggulan') bg-yellow-100 text-yellow-700
                                        @elseif($umkm->klasifikasi==='berkembang') bg-blue-100 text-blue-700
                                        @else bg-gray-100 text-gray-600 @endif">
                                        {{ ucfirst($umkm->klasifikasi) }}
                                    </span>
                                </td>
                                <td class="py-3">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium
                                        @if($umkm->status==='aktif') bg-green-100 text-green-700
                                        @elseif($umkm->status==='draft') bg-gray-100 text-gray-600
                                        @else bg-red-100 text-red-600 @endif">
                                        {{ ucfirst($umkm->status) }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="py-10 text-center text-gray-400">
                                    Belum ada data. Silakan import CSV terlebih dahulu.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>
</div>
</body>
</html>