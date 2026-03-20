<?php

namespace App\Http\Controllers;

use App\Services\MenuService;

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        return redirect(
            app(MenuService::class)->getFirstUrlForUser(auth()->user())
        );
    }
}
