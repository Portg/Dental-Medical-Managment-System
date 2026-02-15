<?php

namespace App\Http\Controllers;

use App\Services\HomeService;
use Illuminate\Support\Facades\Gate;

class HomeController extends Controller
{
    private HomeService $homeService;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(HomeService $homeService)
    {
        $this->middleware('auth');
        $this->homeService = $homeService;
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $data = $this->homeService->getDashboardData();

        if (Gate::allows('Super-Administrator-Dashboard', auth()->user())) {
            return redirect('superadmin');
        } elseif (Gate::allows('Admin-Dashboard', auth()->user())) {
            return view('home')->with($data);
        } else if (Gate::allows('Receptionist-Dashboard', auth()->user())) {
            return redirect('receptionist');
        } else if (Gate::allows('Doctor-Dashboard', auth()->user())) {
            return redirect('doctor');
        } else if (Gate::allows('Nurse-Dashboard', auth()->user())) {
            return redirect('nurse');
        } else {
            return __('auth.invalid_user');
        }
    }
}
