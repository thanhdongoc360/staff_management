<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Models\Notification;
use App\Models\User;
use App\Mail\LeaveRequestStatusMail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class LeaveRequestController extends Controller
{
    /**
     * Admin: list leave requests with filters
     */
    public function index(Request $request)
    {
        $query = LeaveRequest::with('employee.user')->orderByDesc('created_at');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($type = $request->query('type')) {
            $query->where('type', $type);
        }

        $perPage = (int) $request->query('per_page', 10);
        $leaves = $query->paginate($perPage);

        return response()->json([
            'data' => $leaves->getCollection()->transform(fn ($leave) => $this->transformLeave($leave)),
            'meta' => [
                'current_page' => $leaves->currentPage(),
                'last_page' => $leaves->lastPage(),
                'per_page' => $leaves->perPage(),
                'total' => $leaves->total(),
            ],
        ]);
    }

    /**
     * Employee: list own leave requests
     */
    public function myLeaves(Request $request)
    {
        $employeeId = auth()->user()->employee?->id;
        if (!$employeeId) {
            return response()->json(['message' => 'Employee profile not found'], 404);
        }

        $leaves = LeaveRequest::where('employee_id', $employeeId)
            ->orderByDesc('created_at')
            ->paginate((int) $request->query('per_page', 10));

        return response()->json([
            'data' => $leaves->getCollection()->transform(fn ($leave) => $this->transformLeave($leave)),
            'meta' => [
                'current_page' => $leaves->currentPage(),
                'last_page' => $leaves->lastPage(),
                'per_page' => $leaves->perPage(),
                'total' => $leaves->total(),
            ],
        ]);
    }

    /**
     * Employee: create leave request
     */
    public function store(Request $request)
    {
        $employeeId = auth()->user()->employee?->id;
        if (!$employeeId) {
            return response()->json(['message' => 'Employee profile not found'], 404);
        }

        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['required', 'string', 'max:500'],
            'type' => ['required', 'string', 'max:100'],
        ]);

        $leave = LeaveRequest::create([
            'employee_id' => $employeeId,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'reason' => $validated['reason'],
            'type' => $validated['type'],
            'status' => 'Chờ duyệt',
        ]);

        $leave->load('employee.user');

        // Notify admins about new leave request
        $admins = User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            Notification::create([
                'user_id' => $admin->id,
                'title' => 'Don nghi moi',
                'content' => 'Nhan vien ' . ($leave->employee?->user?->name ?? 'khong ro') . ' da gui don tu ' . optional($leave->start_date)->format('d/m/Y') . ' den ' . optional($leave->end_date)->format('d/m/Y'),
                'date' => Carbon::now()->toDateString(),
                'is_read' => false,
            ]);
        }

        if ($admins->count() > 0) {
            $emails = $admins->pluck('email')->filter()->all();
            if (!empty($emails)) {
                try {
                    Mail::to($emails)->send(new LeaveRequestStatusMail($leave, 'new_request_admin'));
                } catch (\Throwable $e) {
                    Log::warning('Cannot send leave request email to admins', ['error' => $e->getMessage()]);
                }
            }
        }

        return response()->json([
            'message' => 'Đã nộp đơn nghỉ phép',
            'data' => $this->transformLeave($leave->load('employee.user')),
        ], 201);
    }

    /**
     * Admin: approve or reject
     */
    public function updateStatus(Request $request, $id)
    {
        $leave = LeaveRequest::with('employee.user')->findOrFail($id);

        $validated = $request->validate([
            'status' => ['required', Rule::in(['Đã duyệt', 'Từ chối'])],
        ]);

        $leave->update(['status' => $validated['status']]);

        // Notify employee about status change
        if ($leave->employee && $leave->employee->user) {
            Notification::create([
                'user_id' => $leave->employee->user->id,
                'title' => 'Cap nhat don nghi',
                'content' => 'Don nghi tu ' . optional($leave->start_date)->format('d/m/Y') . ' den ' . optional($leave->end_date)->format('d/m/Y') . ' da duoc ' . $leave->status,
                'date' => Carbon::now()->toDateString(),
                'is_read' => false,
            ]);

            if ($leave->employee->user->email) {
                try {
                    Mail::to($leave->employee->user->email)->send(new LeaveRequestStatusMail($leave, 'status_update'));
                } catch (\Throwable $e) {
                    Log::warning('Cannot send leave status email to employee', ['error' => $e->getMessage()]);
                }
            }
        }

        return response()->json([
            'message' => 'Cập nhật trạng thái thành công',
            'data' => $this->transformLeave($leave),
        ]);
    }

    /**
     * Admin: delete leave request
     */
    public function destroy($id)
    {
        $leave = LeaveRequest::findOrFail($id);
        $leave->delete();

        return response()->json([
            'message' => 'Đã xóa đơn nghỉ',
        ]);
    }

    private function transformLeave(LeaveRequest $leave): array
    {
        $days = $leave->start_date && $leave->end_date
            ? $leave->start_date->diffInDays($leave->end_date) + 1
            : null;

        return [
            'id' => $leave->id,
            'employee_id' => $leave->employee_id,
            'employee_name' => $leave->employee?->user?->name,
            'employee_code' => $leave->employee?->employee_code,
            'start_date' => optional($leave->start_date)->format('d/m/Y'),
            'end_date' => optional($leave->end_date)->format('d/m/Y'),
            'reason' => $leave->reason,
            'status' => $leave->status,
            'type' => $leave->type,
            'days' => $days,
            'created_at' => optional($leave->created_at)->format('d/m/Y H:i'),
        ];
    }
}
