<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

// the front door: guests see what Pegel is about, signed-in users land on their own page
class HomeController extends Controller
{
    public function __invoke(Request $request): View
    {
        if ($request->user() === null) {
            return view('landing');
        }

        return view('home', ['user' => $request->user()]);
    }
}
