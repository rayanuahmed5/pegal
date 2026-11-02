<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

// the page everybody lands on after signing in
class HomeController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('home', ['user' => $request->user()]);
    }
}
