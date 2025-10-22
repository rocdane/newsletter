<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Email;

class HomeController extends Controller
{
    public function welcome()
    {
        return view('welcome');
    }
}
