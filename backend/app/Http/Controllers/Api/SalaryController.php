<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Salary;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SalaryController extends Controller
{
    /**
     * Admin: list salaries with filters
     */
    public function index(Request $request)
    {
        $query = Salary::with('employee.user')->orderByDesc('year')->orderByDesc('month');

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->query('employee_id'));
        }
        if ($request->filled('month')) {
            $query->where('month', $request->query('month'));
        }
        if ($request->filled('year')) {
            $query->where('year', $request->query('year'));
        }

        $perPage = (int) $request->query('per_page', 10);
        $salaries = $query->paginate($perPage);

        return response()->json([
            'data' => $salaries->getCollection()->transform(fn ($salary) => $this->transform($salary)),
            'meta' => [
                'current_page' => $salaries->currentPage(),
                'last_page' => $salaries->lastPage(),
                'per_page' => $salaries->perPage(),
                'total' => $salaries->total(),
            ],
        ]);
    }

    /**
     * Admin: create salary record
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'base_salary' => ['required', 'numeric', 'min:0'],
            'bonus' => ['nullable', 'numeric', 'min:0'],
            'month' => ['required', 'integer', 'between:1,12'],
            'year' => ['required', 'integer', 'between:2000,2100'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $total = $validated['base_salary'] + ($validated['bonus'] ?? 0);

        // prevent duplicate month-year per employee
        $exists = Salary::where('employee_id', $validated['employee_id'])
            ->where('month', $validated['month'])
            ->where('year', $validated['year'])
            ->exists();
        if ($exists) {
            return response()->json(['message' => 'Đã có bảng lương cho tháng/năm này'], 422);
        }

        $salary = Salary::create([
            'employee_id' => $validated['employee_id'],
            'base_salary' => $validated['base_salary'],
            'bonus' => $validated['bonus'] ?? 0,
            'total' => $total,
            'month' => $validated['month'],
            'year' => $validated['year'],
            'note' => $validated['note'] ?? null,
        ]);

        return response()->json([
            'message' => 'Tạo bảng lương thành công',
            'data' => $this->transform($salary->load('employee.user')),
        ], 201);
    }

    /**
     * Admin: update salary record
     */
    public function update(Request $request, $id)
    {
        $salary = Salary::with('employee.user')->findOrFail($id);

        $validated = $request->validate([
            'base_salary' => ['required', 'numeric', 'min:0'],
            'bonus' => ['nullable', 'numeric', 'min:0'],
            'month' => ['required', 'integer', 'between:1,12'],
            'year' => ['required', 'integer', 'between:2000,2100'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $total = $validated['base_salary'] + ($validated['bonus'] ?? 0);

        $salary->update([
            'base_salary' => $validated['base_salary'],
            'bonus' => $validated['bonus'] ?? 0,
            'total' => $total,
            'month' => $validated['month'],
            'year' => $validated['year'],
            'note' => $validated['note'] ?? null,
        ]);

        return response()->json([
            'message' => 'Cập nhật bảng lương thành công',
            'data' => $this->transform($salary),
        ]);
    }

    /**
     * Admin: delete salary record
     */
    public function destroy($id)
    {
        $salary = Salary::findOrFail($id);
        $salary->delete();

        return response()->json(['message' => 'Đã xóa bảng lương']);
    }

    /**
     * Employee: view own salaries
     */
    public function mySalaries(Request $request)
    {
        $employeeId = $request->user()->employee?->id;
        if (!$employeeId) {
            return response()->json(['message' => 'Employee profile not found'], 404);
        }

        $query = Salary::where('employee_id', $employeeId)->orderByDesc('year')->orderByDesc('month');
        if ($request->filled('month')) {
            $query->where('month', $request->query('month'));
        }
        if ($request->filled('year')) {
            $query->where('year', $request->query('year'));
        }

        $salaries = $query->paginate((int) $request->query('per_page', 10));

        return response()->json([
            'data' => $salaries->getCollection()->transform(fn ($salary) => $this->transform($salary)),
            'meta' => [
                'current_page' => $salaries->currentPage(),
                'last_page' => $salaries->lastPage(),
                'per_page' => $salaries->perPage(),
                'total' => $salaries->total(),
            ],
        ]);
    }

    private function transform(Salary $salary): array
    {
        return [
            'id' => $salary->id,
            'employee_id' => $salary->employee_id,
            'employee_name' => $salary->employee?->user?->name,
            'employee_code' => $salary->employee?->employee_code,
            'base_salary' => (float) $salary->base_salary,
            'bonus' => (float) $salary->bonus,
            'total' => (float) $salary->total,
            'month' => $salary->month,
            'year' => $salary->year,
            'note' => $salary->note,
            'created_at' => optional($salary->created_at)->format('d/m/Y H:i'),
        ];
    }
}
