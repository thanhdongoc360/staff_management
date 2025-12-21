<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Models\Employee;
use Illuminate\Http\Request;

class EmployeeDashboardController extends Controller
{
    /**
     * Get employee dashboard statistics
     */
    public function stats()
    {
        $employee = auth()->user()->employee;
        
        if (!$employee) {
            return response()->json(['message' => 'Employee profile not found'], 404);
        }

        $pendingLeaves = LeaveRequest::where('employee_id', $employee->id)
            ->where('status', 'Chờ duyệt')
            ->count();
        
        $approvedLeaves = LeaveRequest::where('employee_id', $employee->id)
            ->where('status', 'Đã duyệt')
            ->count();

        $totalNotifications = auth()->user()->notifications()->count();
        $unreadNotifications = auth()->user()->notifications()->where('is_read', false)->count();

        return response()->json([
            'pending_leaves' => $pendingLeaves,
            'approved_leaves' => $approvedLeaves,
            'position' => $employee->position,
            'department' => $employee->department,
            'status' => $employee->status,
            'employee_code' => $employee->employee_code,
            'total_notifications' => $totalNotifications,
            'unread_notifications' => $unreadNotifications,
        ], 200);
    }

    /**
     * Get employee's leave requests
     */
    public function myLeaves()
    {
        $employee = auth()->user()->employee;
        
        if (!$employee) {
            return response()->json(['message' => 'Employee profile not found'], 404);
        }

        $leaves = LeaveRequest::where('employee_id', $employee->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($leave) {
                return [
                    'id' => $leave->id,
                    'start_date' => $leave->start_date->format('d/m/Y'),
                    'end_date' => $leave->end_date->format('d/m/Y'),
                    'reason' => $leave->reason,
                    'type' => $leave->type,
                    'status' => $leave->status,
                    'days' => $leave->start_date->diffInDays($leave->end_date) + 1,
                    'created_at' => $leave->created_at->format('d/m/Y H:i'),
                ];
            });

        return response()->json([
            'data' => $leaves,
            'count' => $leaves->count(),
        ], 200);
    }

    /**
     * Get employee's leave statistics
     */
    public function leaveStats()
    {
        $employee = auth()->user()->employee;
        
        if (!$employee) {
            return response()->json(['message' => 'Employee profile not found'], 404);
        }

        $byStatus = LeaveRequest::where('employee_id', $employee->id)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->get()
            ->keyBy('status')
            ->map(fn($item) => $item->count);

        $byType = LeaveRequest::where('employee_id', $employee->id)
            ->selectRaw('type, count(*) as count')
            ->groupBy('type')
            ->get()
            ->keyBy('type')
            ->map(fn($item) => $item->count);

        return response()->json([
            'by_status' => $byStatus,
            'by_type' => $byType,
        ], 200);
    }
}
