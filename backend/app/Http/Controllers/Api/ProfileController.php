<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    /**
     * Get current user's profile
     */
    public function show(Request $request)
    {
        $user = $request->user()->load('employee');

        return response()->json([
            'data' => $this->transform($user),
        ]);
    }

    /**
     * Update profile info (name, email, phone, position, department)
     */
    public function update(Request $request)
    {
        $user = $request->user()->load('employee');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:50'],
            'position' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        if ($user->employee) {
            $user->employee->update([
                'phone' => $validated['phone'] ?? $user->employee->phone,
                'position' => $validated['position'] ?? $user->employee->position,
                'department' => $validated['department'] ?? $user->employee->department,
            ]);
        } else {
            // Create minimal employee profile if missing
            $user->employee()->create([
                'employee_code' => null,
                'position' => $validated['position'] ?? null,
                'department' => $validated['department'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'status' => 'Đang làm việc',
            ]);
        }

        $user->refresh()->load('employee');

        return response()->json([
            'message' => 'Cập nhật thông tin cá nhân thành công',
            'data' => $this->transform($user),
        ]);
    }

    /**
     * Change password
     */
    public function changePassword(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => ['required'],
            'new_password' => ['required', 'min:6', 'confirmed'],
        ]);

        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'message' => 'Mật khẩu hiện tại không đúng',
            ], 422);
        }

        $user->update([
            'password' => bcrypt($validated['new_password']),
        ]);

        return response()->json([
            'message' => 'Đổi mật khẩu thành công',
        ]);
    }

    private function transform($user): array
    {
        $employee = $user->employee;

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'employee' => $employee ? [
                'id' => $employee->id,
                'employee_code' => $employee->employee_code,
                'position' => $employee->position,
                'department' => $employee->department,
                'phone' => $employee->phone,
                'status' => $employee->status,
            ] : null,
        ];
    }
}
