{{-- 500 Internal Server Error --}}
@include('errors.tampilan', [
    'kode' => 500, 'teknis' => 'Internal Server Error', 'ilustrasi' => 'rusak', 'tombol' => 'muat-ulang',
    'pesan' => 'Terjadi gangguan pada sistem. Silakan coba beberapa saat lagi.',
])
