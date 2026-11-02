<?php

namespace App\Controllers;

class Home extends BaseController
{
    /** GET / — 서비스 소개 페이지. */
    public function index(): string
    {
        return view('home/landing', ['loggedIn' => session()->has('authUser')]);
    }
}
