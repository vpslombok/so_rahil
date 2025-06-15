<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PageController extends Controller
{
    /**
     * Display the application features page.
     *
     * @return \Illuminate\View\View
     */
    public function applicationFeatures()
    {
        return view('pages.features');
    }
}
