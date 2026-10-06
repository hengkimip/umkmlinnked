{{-- Galat 5xx lain (mis. 503 sedang pemeliharaan) --}}
@php
    $kode = $exception->getStatusCode();
    $teknis = \Symfony\Component\HttpFoundation\Response::$statusTexts[$kode] ?? 'Server Error';
    $pesan = $kode === 503
        ? 'Sistem sedang dalam pemeliharaan. Silakan coba beberapa saat lagi.'
        : 'Terjadi gangguan pada sistem. Silakan coba beberapa saat lagi.';
@endphp
@include('errors.tampilan', ['kode' => $kode, 'teknis' => $teknis, 'pesan' => $pesan, 'ilustrasi' => 'rusak', 'tombol' => 'muat-ulang'])
