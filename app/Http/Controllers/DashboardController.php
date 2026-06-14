<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Student;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'groups'   => Group::count(),
            'students' => Student::count(),
        ];

        return view('dashboard', compact('stats'));
    }
}
