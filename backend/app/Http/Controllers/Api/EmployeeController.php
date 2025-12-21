<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    /**
     * List employees with filters and pagination
     */
    public function index(Request $request)
    {
        $query = Employee::with('user')->orderByDesc('created_at');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('employee_code', 'like', "%{$search}%")
                    ->orWhere('department', 'like', "%{$search}%")
                    ->orWhere('position', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $perPage = (int) $request->query('per_page', 10);
        $employees = $query->paginate($perPage);

        return response()->json([
            'data' => $employees->getCollection()->transform(fn ($emp) => $this->transformEmployee($emp)),
            'meta' => [
                'current_page' => $employees->currentPage(),
                'last_page' => $employees->lastPage(),
                'per_page' => $employees->perPage(),
                'total' => $employees->total(),
            ],
        ]);
    }

    /**
     * Create new employee and user
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['nullable', 'string', 'min:6'],
            'role' => ['nullable', Rule::in(['admin', 'employee'])],
            'employee_code' => ['nullable', 'string', 'max:50', 'unique:employees,employee_code'],
            'position' => ['required', 'string', 'max:255'],
            'department' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', Rule::in(['Đang làm việc', 'Nghỉ việc'])],
        ]);

        $password = $validated['password'] ?? 'password123';
        $role = $validated['role'] ?? 'employee';
        $employeeCode = $validated['employee_code'] ?? $this->generateEmployeeCode();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($password),
            'role' => $role,
        ]);

        $employee = Employee::create([
            'user_id' => $user->id,
            'employee_code' => $employeeCode,
            'position' => $validated['position'],
            'department' => $validated['department'],
            'phone' => $validated['phone'] ?? null,
            'status' => $validated['status'] ?? 'Đang làm việc',
        ]);

        return response()->json([
            'message' => 'Employee created successfully',
            'data' => $this->transformEmployee($employee->load('user')),
        ], 201);
    }

    /**
     * Show employee detail
     */
    public function show($id)
    {
        $employee = Employee::with('user')->findOrFail($id);
        return response()->json([
            'data' => $this->transformEmployee($employee),
        ]);
    }

    /**
     * Update employee and user
     */
    public function update(Request $request, $id)
    {
        $employee = Employee::with('user')->findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($employee->user_id)],
            'password' => ['nullable', 'string', 'min:6'],
            'role' => ['nullable', Rule::in(['admin', 'employee'])],
            'employee_code' => ['required', 'string', 'max:50', Rule::unique('employees', 'employee_code')->ignore($employee->id)],
            'position' => ['required', 'string', 'max:255'],
            'department' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'status' => ['required', Rule::in(['Đang làm việc', 'Nghỉ việc'])],
        ]);

        $employee->user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'] ?? $employee->user->role,
            'password' => $validated['password'] ? Hash::make($validated['password']) : $employee->user->password,
        ]);

        $employee->update([
            'employee_code' => $validated['employee_code'],
            'position' => $validated['position'],
            'department' => $validated['department'],
            'phone' => $validated['phone'] ?? null,
            'status' => $validated['status'],
        ]);

        return response()->json([
            'message' => 'Employee updated successfully',
            'data' => $this->transformEmployee($employee->fresh('user')),
        ]);
    }

    /**
     * Delete employee and user
     */
    public function destroy($id)
    {
        $employee = Employee::with('user')->findOrFail($id);

        if (auth()->id() === $employee->user_id) {
            return response()->json(['message' => 'Cannot delete your own account'], 403);
        }

        $employee->user->delete();

        return response()->json([
            'message' => 'Employee deleted successfully',
        ]);
    }

    private function transformEmployee(Employee $employee)
    {
        return [
            'id' => $employee->id,
            'user_id' => $employee->user_id,
            'name' => $employee->user?->name,
            'email' => $employee->user?->email,
            'role' => $employee->user?->role,
            'employee_code' => $employee->employee_code,
            'position' => $employee->position,
            'department' => $employee->department,
            'phone' => $employee->phone,
            'status' => $employee->status,
            'created_at' => optional($employee->created_at)->format('d/m/Y H:i'),
        ];
    }

    private function generateEmployeeCode(): string
    {
        $nextNumber = (Employee::max('id') ?? 0) + 1;
        return 'EMP-' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
    }
}
