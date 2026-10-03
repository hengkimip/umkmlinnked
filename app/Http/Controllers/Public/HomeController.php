<?php
namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Umkm;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    public function index()
    {
        $umkmUnggulan = Umkm::with(['produkUnggulan', 'legalitas'])
            ->aktif()
            ->where('klasifikasi', 'unggulan')
            ->orderByDesc('skor_total')
            ->limit(8)
            ->get();

        $totalUmkm = Umkm::aktif()->count();

        return view('public.home', compact('umkmUnggulan', 'totalUmkm'));
    }
}