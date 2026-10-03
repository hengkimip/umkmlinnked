<?php
namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;

class KemitraanController extends Controller
{
    public function index()
    {
        return view('public.kemitraan');
    }
}