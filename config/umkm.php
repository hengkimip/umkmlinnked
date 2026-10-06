<?php

return [

    /*
    | Deteksi duplikasi UMKM (import file & tambah manual).
    | Skor kemiripan gabungan 0–100; data dengan skor ≥ ambang tidak langsung disimpan,
    | melainkan masuk antrean review manual. Sesuaikan setelah uji coba terhadap data riil:
    | terlalu rendah → beban review admin naik; terlalu tinggi → duplikasi lebih mudah lolos.
    */
    'ambang_duplikat' => (int) env('UMKM_AMBANG_DUPLIKAT', 60),

    /*
    | Super Admin dikelola Bank Indonesia Wilayah Kalimantan Barat. Jumlah akun Super Admin aktif
    | dibatasi sesuai permintaan BI (1–3 orang); akun baru/aktivasi ditolak bila kuota penuh.
    */
    'instansi_super_admin' => env('UMKM_INSTANSI_SUPER_ADMIN', 'Bank Indonesia Wilayah Kalimantan Barat'),
    'maks_super_admin'     => max(1, min(3, (int) env('UMKM_MAKS_SUPER_ADMIN', 3))),

];
