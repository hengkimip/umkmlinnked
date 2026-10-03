<?php
namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;

class TentangController extends Controller
{
    public function index()
    {
        return view('public.tentang');
    }
}