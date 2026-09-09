<?php

namespace App\Http\Controllers;

use App\Services\GtkMonitoringService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GtkMonitoringController extends Controller
{
    public function __construct(private GtkMonitoringService $monitoring) {}

    public function index(Request $request): View
    {
        return view('gtk.monitoring', $this->monitoring->halaman($request));
    }
}
