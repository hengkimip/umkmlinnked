{{-- 401 Unauthorized --}}
@include('errors.tampilan', [
    'kode' => 401, 'teknis' => 'Unauthorized', 'ilustrasi' => 'kunci', 'tombol' => 'masuk',
    'pesan' => 'Silakan masuk terlebih dahulu untuk mengakses halaman ini.',
])
