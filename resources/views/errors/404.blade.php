{{-- 404 Not Found --}}
@include('errors.tampilan', [
    'kode' => 404, 'teknis' => 'Not Found', 'ilustrasi' => 'cari',
    'pesan' => 'Halaman yang Anda cari tidak ditemukan.',
])
