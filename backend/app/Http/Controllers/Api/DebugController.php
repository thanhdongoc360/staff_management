<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Employee;

class DebugController extends Controller
{
    public function check()
    {
        return response()->json([
            'total_users' => User::count(),
            'total_employees' => Employee::count(),
            'auth_user' => auth()->user(),
            'auth_user_role' => auth()->user()?->role,
        ]);
    }
}
