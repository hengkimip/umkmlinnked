{{-- Galat 4xx lain (mis. 419 sesi kedaluwarsa, 429 terlalu banyak permintaan) --}}
@php
    $kode = $exception->getStatusCode();
    [$teknis, $pesan, $tombol] = match ($kode) {
        419     => ['Page Expired', 'Sesi halaman sudah berakhir. Silakan muat ulang halaman lalu coba lagi.', 'muat-ulang'],
        429     => ['Too Many Requests', 'Terlalu banyak permintaan dalam waktu singkat. Silakan tunggu sebentar lalu coba lagi.', 'muat-ulang'],
        default => [\Symfony\Component\HttpFoundation\Response::$statusTexts[$kode] ?? 'Error', 'Maaf, permintaan Anda tidak dapat diproses.', null],
    };
@endphp
@include('errors.tampilan', ['kode' => $kode, 'teknis' => $teknis, 'pesan' => $pesan, 'ilustrasi' => 'umum', 'tombol' => $tombol])
