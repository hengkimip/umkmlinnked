<?php
namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;

class BeritaController extends Controller
{
    public function index()
    {
        return view('public.berita.index');
    }

    public function show($slug)
    {
        return view('public.berita.show', compact('slug'));
    }
}