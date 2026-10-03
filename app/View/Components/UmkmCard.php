<?php
namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\Support\Facades\Storage;

class UmkmCard extends Component
{
    public $fotoTampil;
    public $badges;

    public function __construct(
        public $item,
        public string $theme = 'digital', // 'digital' atau 'global'
        public bool $showKlasifikasi = true,
    ) {
        // Hitung foto sekali di sini, bukan berulang di setiap Blade
        $produkUtama = $item->produkUnggulan->first() ?? $item->produk->first();
        $this->fotoTampil = $produkUtama?->foto_final;

        if (!$this->fotoTampil && $item->foto_usaha) {
            $this->fotoTampil = Storage::url($item->foto_usaha);
        }

        // Kumpulkan badge platform/sertifikasi jadi array, bukan if-else berulang di Blade
        $this->badges = $theme === 'global'
            ? $this->badgeSertifikasi()
            : $this->badgePlatform();
    }

    private function badgePlatform(): array
    {
        $badges = [];
        if ($this->item->tokopedia) $badges[] = ['label' => 'Tokped', 'color' => '#03a500'];
        if ($this->item->shopee)    $badges[] = ['label' => 'Shopee', 'color' => '#ee4d2d'];
        if ($this->item->instagram) $badges[] = ['label' => 'IG',     'color' => '#c13584'];
        return $badges;
    }

    private function badgeSertifikasi(): array
    {
        $badges = [];
        $legalitas = $this->item->legalitas;
        if ($legalitas?->nomor_halal) $badges[] = ['label' => '🌙 Halal', 'color' => '#15803d'];
        if ($legalitas?->nomor_bpom)  $badges[] = ['label' => 'BPOM',     'color' => '#dc2626'];
        if ($legalitas?->nomor_pirt)  $badges[] = ['label' => 'PIRT',     'color' => '#0891b2'];
        if ($legalitas?->nomor_sni)   $badges[] = ['label' => 'SNI',      'color' => '#7c3aed'];
        return $badges;
    }

    public function render()
    {
        return view('components.umkm-card');
    }
}