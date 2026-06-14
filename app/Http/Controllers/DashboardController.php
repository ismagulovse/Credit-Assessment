<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Student;
use App\Models\Subject;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'groups'   => Group::count(),
            'students' => Student::count(),
            'subjects' => Subject::count(),
        ];

        return view('dashboard', compact('stats'));
    }
}
