<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class KelembagaanController extends Controller
{
    public function identitas(): View
    {
        return view('kelembagaan.identitas', [
            'madrasah' => config('madrasah'),
        ]);
    }
}
