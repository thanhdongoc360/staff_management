<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Employee;
use App\Models\LeaveRequest;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    /**
     * Get admin dashboard statistics
     */
    public function stats()
    {
        $totalEmployees = Employee::count();
        $pendingLeaves = LeaveRequest::where('status', 'Chờ duyệt')->count();
        $activeStaff = Employee::where('status', 'Đang làm việc')->count();
        $totalNotifications = auth()->user()->notifications()->count();
        $unreadNotifications = auth()->user()->notifications()->where('is_read', false)->count();

        return response()->json([
            'total_employees' => $totalEmployees,
            'pending_leaves' => $pendingLeaves,
            'active_staff' => $activeStaff,
            'total_notifications' => $totalNotifications,
            'unread_notifications' => $unreadNotifications,
        ], 200);
    }

    /**
     * Get recent employees (last 5)
     */
    public function recentEmployees()
    {
        $employees = Employee::with('user')
            ->latest('created_at')
            ->limit(5)
            ->get()
            ->map(function ($employee) {
                return [
                    'id' => $employee->id,
                    'name' => $employee->user->name,
                    'employee_code' => $employee->employee_code,
                    'position' => $employee->position,
                    'department' => $employee->department,
                    'status' => $employee->status,
                    'created_at' => $employee->created_at->format('d/m/Y'),
                ];
            });

        return response()->json([
            'data' => $employees,
            'count' => $employees->count(),
        ], 200);
    }

    /**
     * Get pending leave requests
     */
    public function pendingLeaves()
    {
        $leaves = LeaveRequest::with('employee.user')
            ->where('status', 'Chờ duyệt')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($leave) {
                return [
                    'id' => $leave->id,
                    'employee_name' => $leave->employee->user->name,
                    'employee_code' => $leave->employee->employee_code,
                    'start_date' => $leave->start_date->format('d/m/Y'),
                    'end_date' => $leave->end_date->format('d/m/Y'),
                    'reason' => $leave->reason,
                    'type' => $leave->type,
                    'days' => $leave->start_date->diffInDays($leave->end_date) + 1,
                ];
            });

        return response()->json([
            'data' => $leaves,
            'count' => $leaves->count(),
        ], 200);
    }

    /**
     * Get employee statistics breakdown
     */
    public function employeeStats()
    {
        $byStatus = Employee::selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->get()
            ->keyBy('status')
            ->map(fn($item) => $item->count);

        $byDepartment = Employee::selectRaw('department, count(*) as count')
            ->groupBy('department')
            ->get()
            ->keyBy('department')
            ->map(fn($item) => $item->count);

        return response()->json([
            'by_status' => $byStatus,
            'by_department' => $byDepartment,
        ], 200);
    }
}
