<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Foto Produk — Admin</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 font-sans">
<div class="flex min-h-screen">

    {{-- Sidebar --}}
    <aside style="width:224px; background:#166534; color:white; flex-shrink:0; position:relative">
        <div style="padding:20px; border-bottom:1px solid rgba(255,255,255,0.2)">
            <span style="font-weight:700; font-size:18px">UMKMLinked.ID</span>
            <p style="font-size:12px; color:rgba(255,255,255,0.6); margin:4px 0 0">Panel Admin</p>
        </div>
        <nav style="padding:16px; font-size:14px">
            <a href="/admin/dashboard" style="display:block; padding:8px 12px; border-radius:8px; color:white; text-decoration:none; margin-bottom:4px; opacity:0.8">Dashboard</a>
            <a href="/admin/import" style="display:block; padding:8px 12px; border-radius:8px; color:white; text-decoration:none; margin-bottom:4px; opacity:0.8">Import Data</a>
            <a href="/admin/produk/upload-foto" style="display:block; padding:8px 12px; border-radius:8px; background:rgba(255,255,255,0.2); color:white; text-decoration:none; margin-bottom:4px">Upload Foto Produk</a>
        </nav>
        <div style="position:absolute; bottom:0; width:224px; padding:16px; border-top:1px solid rgba(255,255,255,0.2)">
            <form method="POST" action="/logout">
                @csrf
                <button type="submit" style="background:none; border:none; color:rgba(255,255,255,0.6); cursor:pointer; font-size:14px">Keluar</button>
            </form>
        </div>
    </aside>

    <main style="flex:1; overflow:auto">
        <div style="background:white; border-bottom:1px solid #e5e7eb; padding:12px 24px">
            <p style="font-size:14px; color:#6b7280; margin:0">Upload Foto Produk UMKM</p>
        </div>

        <div style="max-width:700px; margin:32px auto; padding:0 16px">

            <h1 style="font-size:20px; font-weight:700; color:#1f2937; margin-bottom:4px">Upload Foto Produk</h1>
            <p style="font-size:14px; color:#6b7280; margin-bottom:24px">
                Upload 1–10 foto produk untuk setiap UMKM. Foto pertama (urutan 1) akan tampil di halaman listing.
            </p>

            @if(session('success'))
            <div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px; padding:12px 16px; margin-bottom:20px; color:#15803d; font-size:14px">
                ✅ {{ session('success') }}
            </div>
            @endif

            @if(session('error'))
            <div style="background:#fef2f2; border:1px solid #fecaca; border-radius:8px; padding:12px 16px; margin-bottom:20px; color:#dc2626; font-size:14px">
                ❌ {{ session('error') }}
            </div>
            @endif

            {{-- Form Upload --}}
            <div style="background:white; border:1px solid #e5e7eb; border-radius:12px; padding:24px; margin-bottom:20px">
                <form method="POST" action="/admin/produk/upload-foto" enctype="multipart/form-data" id="upload-form">
                    @csrf

                    {{-- Pilih UMKM --}}
                    <div style="margin-bottom:16px">
                        <label style="display:block; font-size:13px; font-weight:600; color:#374151; margin-bottom:6px">
                            Pilih UMKM *
                        </label>
                        <select name="umkm_id" required id="umkm-select"
                            style="width:100%; border:1px solid #d1d5db; border-radius:8px; padding:10px 12px; font-size:14px; color:#1f2937; background:white"
                            onchange="loadProduk(this.value)">
                            <option value="">-- Pilih UMKM --</option>
                            @foreach(\App\Models\Umkm::orderBy('nama_usaha')->get(['id','nama_usaha','kabupaten']) as $umkm)
                            <option value="{{ $umkm->id }}">{{ $umkm->nama_usaha }} — {{ $umkm->kabupaten }}</option>
                            @endforeach
                        </select>
                        @error('umkm_id')
                        <p style="color:#dc2626; font-size:12px; margin-top:4px">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Nama Produk --}}
                    <div style="margin-bottom:16px">
                        <label style="display:block; font-size:13px; font-weight:600; color:#374151; margin-bottom:6px">
                            Nama Produk *
                        </label>
                        <input type="text" name="nama_produk" value="{{ old('nama_produk') }}"
                            placeholder="Contoh: Kopi Arabika Kalbar"
                            style="width:100%; border:1px solid #d1d5db; border-radius:8px; padding:10px 12px; font-size:14px; color:#1f2937; box-sizing:border-box"
                            required>
                        @error('nama_produk')
                        <p style="color:#dc2626; font-size:12px; margin-top:4px">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Upload Foto (1-10) --}}
                    <div style="margin-bottom:16px">
                        <label style="display:block; font-size:13px; font-weight:600; color:#374151; margin-bottom:6px">
                            Foto Produk (maks. 10 foto) *
                        </label>
                        <p style="font-size:12px; color:#6b7280; margin-bottom:8px">
                            📌 Foto pertama yang dipilih akan tampil di halaman listing. Format: JPG, PNG, WEBP. Maks. 2MB per foto.
                        </p>

                        {{-- Drop zone --}}
                        <div id="drop-zone"
                            style="border:2px dashed #d1d5db; border-radius:10px; padding:32px 20px; text-align:center; cursor:pointer; transition:border-color 0.2s; background:#fafafa"
                            onclick="document.getElementById('foto-input').click()"
                            ondragover="handleDragOver(event)"
                            ondrop="handleDrop(event)">
                            <div style="font-size:36px; margin-bottom:8px">📸</div>
                            <p style="font-size:14px; color:#374151; font-weight:500; margin:0 0 4px">
                                Klik atau drag foto ke sini
                            </p>
                            <p style="font-size:12px; color:#9ca3af; margin:0">
                                Pilih 1–10 foto sekaligus
                            </p>
                        </div>

                        <input type="file" id="foto-input" name="foto[]"
                            accept="image/jpeg,image/png,image/webp"
                            multiple style="display:none"
                            onchange="previewFoto(this)">

                        @error('foto')
                        <p style="color:#dc2626; font-size:12px; margin-top:4px">{{ $message }}</p>
                        @enderror
                        @error('foto.*')
                        <p style="color:#dc2626; font-size:12px; margin-top:4px">{{ $message }}</p>
                        @enderror

                        {{-- Preview grid --}}
                        <div id="preview-grid" style="display:grid; grid-template-columns:repeat(5,1fr); gap:8px; margin-top:12px"></div>
                        <p id="foto-count" style="font-size:12px; color:#6b7280; margin-top:8px; display:none"></p>
                    </div>

                    {{-- Harga (opsional) --}}
                    <div style="margin-bottom:20px">
                        <label style="display:block; font-size:13px; font-weight:600; color:#374151; margin-bottom:6px">
                            Harga (opsional)
                        </label>
                        <input type="number" name="harga" value="{{ old('harga') }}"
                            placeholder="Contoh: 85000"
                            style="width:100%; border:1px solid #d1d5db; border-radius:8px; padding:10px 12px; font-size:14px; color:#1f2937; box-sizing:border-box">
                    </div>

                    <button type="submit" id="submit-btn"
                        style="width:100%; background:#166534; color:white; border:none; border-radius:10px; padding:14px; font-size:15px; font-weight:600; cursor:pointer">
                        💾 Simpan Foto Produk
                    </button>
                </form>
            </div>

            {{-- Daftar produk UMKM terpilih --}}
            <div id="produk-list" style="display:none; background:white; border:1px solid #e5e7eb; border-radius:12px; padding:20px">
                <h2 style="font-size:16px; font-weight:600; color:#1f2937; margin-bottom:12px">
                    Produk yang sudah ada
                </h2>
                <div id="produk-items"></div>
            </div>

        </div>
    </main>
</div>

<script>
function previewFoto(input) {
    const grid = document.getElementById('preview-grid');
    const count = document.getElementById('foto-count');
    grid.innerHTML = '';

    const files = Array.from(input.files).slice(0, 10);

    if (files.length === 0) {
        count.style.display = 'none';
        return;
    }

    files.forEach((file, i) => {
        const reader = new FileReader();
        reader.onload = (e) => {
            const div = document.createElement('div');
            div.style.cssText = 'position:relative; aspect-ratio:1; border-radius:8px; overflow:hidden; border:2px solid ' + (i === 0 ? '#16a34a' : '#e5e7eb');
            div.innerHTML = `
                <img src="${e.target.result}" style="width:100%;height:100%;object-fit:cover">
                ${i === 0 ? '<span style="position:absolute;top:4px;left:4px;background:#16a34a;color:white;font-size:9px;padding:2px 6px;border-radius:4px;font-weight:700">UTAMA</span>' : ''}
                <span style="position:absolute;bottom:4px;right:4px;background:rgba(0,0,0,0.5);color:white;font-size:9px;padding:1px 5px;border-radius:3px">${i+1}</span>
            `;
            grid.appendChild(div);
        };
        reader.readAsDataURL(file);
    });

    count.style.display = 'block';
    count.textContent = `${files.length} foto dipilih. Foto #1 akan tampil di halaman listing.`;

    document.getElementById('drop-zone').style.borderColor = '#16a34a';
}

function handleDragOver(e) {
    e.preventDefault();
    document.getElementById('drop-zone').style.borderColor = '#16a34a';
}

function handleDrop(e) {
    e.preventDefault();
    const input = document.getElementById('foto-input');
    const dt = new DataTransfer();
    Array.from(e.dataTransfer.files).slice(0, 10).forEach(f => dt.items.add(f));
    input.files = dt.files;
    previewFoto(input);
}

function loadProduk(umkmId) {
    if (!umkmId) {
        document.getElementById('produk-list').style.display = 'none';
        return;
    }
    fetch(`/admin/produk/list/${umkmId}`)
        .then(r => r.json())
        .then(data => {
            const list = document.getElementById('produk-list');
            const items = document.getElementById('produk-items');
            if (data.length === 0) {
                list.style.display = 'none';
                return;
            }
            items.innerHTML = data.map(p => `
                <div style="display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid #f3f4f6">
                    <div style="width:48px;height:48px;border-radius:6px;overflow:hidden;background:#f3f4f6;flex-shrink:0">
                        ${p.foto ? `<img src="${p.foto_url}" style="width:100%;height:100%;object-fit:cover">` : '<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:20px">🏪</div>'}
                    </div>
                    <div>
                        <p style="font-size:13px;font-weight:600;color:#1f2937;margin:0">${p.nama_produk}</p>
                        <p style="font-size:11px;color:#6b7280;margin:2px 0 0">Urutan: ${p.urutan} ${p.is_unggulan ? '⭐' : ''}</p>
                    </div>
                </div>
            `).join('');
            list.style.display = 'block';
        });
}

document.getElementById('upload-form').addEventListener('submit', function() {
    const btn = document.getElementById('submit-btn');
    btn.disabled = true;
    btn.textContent = 'Menyimpan...';
});
</script>

</body>
</html>