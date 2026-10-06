{{-- 403 Forbidden --}}
@include('errors.tampilan', [
    'kode' => 403, 'teknis' => 'Forbidden', 'ilustrasi' => 'stop',
    'pesan' => 'Maaf, Anda tidak memiliki izin untuk membuka halaman ini.',
])
