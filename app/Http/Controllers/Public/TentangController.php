<?php
namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Support\RingkasanData;

class TentangController extends Controller
{
    public function index()
    {
        // Ringkasan satu baris sama persis dengan Beranda & Semua Brand
        $stats = RingkasanData::publik();

        return view('public.tentang', compact('stats'));
    }
}
