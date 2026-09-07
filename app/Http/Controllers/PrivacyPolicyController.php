<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class PrivacyPolicyController extends Controller
{
    public function __invoke(): View
    {
        return view('legal.privacy-policy', [
            'updatedAt' => '7 September 2026',
        ]);
    }
}
