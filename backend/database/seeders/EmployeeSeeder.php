<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Notification;
use App\Models\Salary;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // Admin user
            $admin = User::create([
                'name' => 'Nguyễn Quản Trị',
                'email' => 'admin@staffhub.com',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'email_verified_at' => now(),
                'remember_token' => Str::random(10),
            ]);

            // Employees data
            $employeesData = [
                [
                    'user' => [
                        'name' => 'Nguyễn Văn An',
                        'email' => 'an.nguyen@staffhub.com',
                        'password' => Hash::make('employee123'),
                        'role' => 'employee',
                    ],
                    'employee' => [
                        'employee_code' => 'EMP-00001',
                        'position' => 'Senior Developer',
                        'department' => 'IT',
                        'phone' => '0901000001',
                        'status' => 'Đang làm việc',
                    ],
                ],
                [
                    'user' => [
                        'name' => 'Trần Thị Bình',
                        'email' => 'binh.tran@staffhub.com',
                        'password' => Hash::make('employee123'),
                        'role' => 'employee',
                    ],
                    'employee' => [
                        'employee_code' => 'EMP-00002',
                        'position' => 'HR Manager',
                        'department' => 'Nhân sự',
                        'phone' => '0901000002',
                        'status' => 'Đang làm việc',
                    ],
                ],
                [
                    'user' => [
                        'name' => 'Lê Minh Châu',
                        'email' => 'chau.le@staffhub.com',
                        'password' => Hash::make('employee123'),
                        'role' => 'employee',
                    ],
                    'employee' => [
                        'employee_code' => 'EMP-00003',
                        'position' => 'Designer',
                        'department' => 'Design',
                        'phone' => '0901000003',
                        'status' => 'Đang làm việc',
                    ],
                ],
                [
                    'user' => [
                        'name' => 'Phạm Quốc Đạt',
                        'email' => 'dat.pham@staffhub.com',
                        'password' => Hash::make('employee123'),
                        'role' => 'employee',
                    ],
                    'employee' => [
                        'employee_code' => 'EMP-00004',
                        'position' => 'Marketing Lead',
                        'department' => 'Marketing',
                        'phone' => '0901000004',
                        'status' => 'Đang làm việc',
                    ],
                ],
                [
                    'user' => [
                        'name' => 'Hoàng Thu Hiền',
                        'email' => 'hien.hoang@staffhub.com',
                        'password' => Hash::make('employee123'),
                        'role' => 'employee',
                    ],
                    'employee' => [
                        'employee_code' => 'EMP-00005',
                        'position' => 'Accountant',
                        'department' => 'Kế toán',
                        'phone' => '0901000005',
                        'status' => 'Nghỉ việc',
                    ],
                ],
                [
                    'user' => [
                        'name' => 'John Doe',
                        'email' => 'john@staffhub.com',
                        'password' => Hash::make('employee123'),
                        'role' => 'employee',
                    ],
                    'employee' => [
                        'employee_code' => 'EMP-00006',
                        'position' => 'Senior Developer',
                        'department' => 'IT',
                        'phone' => '0901000006',
                        'status' => 'Đang làm việc',
                    ],
                ],
            ];

            $employees = collect();

            foreach ($employeesData as $data) {
                $user = User::create(array_merge($data['user'], [
                    'email_verified_at' => now(),
                    'remember_token' => Str::random(10),
                ]));

                $employee = Employee::create(array_merge($data['employee'], [
                    'user_id' => $user->id,
                ]));

                $employees->push($employee);
            }

            // Leave requests samples
            $leaveSamples = [
                [
                    'employee_code' => 'EMP-00001',
                    'start' => '2025-12-20',
                    'end' => '2025-12-22',
                    'reason' => 'Nghỉ phép năm',
                    'status' => 'Chờ duyệt',
                    'type' => 'Nghỉ phép năm',
                ],
                [
                    'employee_code' => 'EMP-00003',
                    'start' => '2025-12-15',
                    'end' => '2025-12-16',
                    'reason' => 'Việc gia đình',
                    'status' => 'Đã duyệt',
                    'type' => 'Việc gia đình',
                ],
                [
                    'employee_code' => 'EMP-00004',
                    'start' => '2025-12-10',
                    'end' => '2025-12-10',
                    'reason' => 'Khám bệnh',
                    'status' => 'Từ chối',
                    'type' => 'Khám bệnh',
                ],
            ];

            foreach ($leaveSamples as $item) {
                $employee = $employees->firstWhere('employee_code', $item['employee_code']);
                if ($employee) {
                    LeaveRequest::create([
                        'employee_id' => $employee->id,
                        'start_date' => Carbon::parse($item['start']),
                        'end_date' => Carbon::parse($item['end']),
                        'reason' => $item['reason'],
                        'status' => $item['status'],
                        'type' => $item['type'],
                    ]);
                }
            }

            // Salary samples for John Doe (EMP-00006)
            $john = $employees->firstWhere('employee_code', 'EMP-00006');
            if ($john) {
                $salaries = [
                    ['month' => 11, 'year' => 2025, 'base' => 15000000, 'bonus' => 2000000],
                    ['month' => 10, 'year' => 2025, 'base' => 15000000, 'bonus' => 1500000],
                    ['month' => 9,  'year' => 2025, 'base' => 15000000, 'bonus' => 2500000],
                ];

                foreach ($salaries as $salary) {
                    Salary::create([
                        'employee_id' => $john->id,
                        'base_salary' => $salary['base'],
                        'bonus' => $salary['bonus'],
                        'total' => $salary['base'] + $salary['bonus'],
                        'month' => $salary['month'],
                        'year' => $salary['year'],
                    ]);
                }
            }

            // Notifications for John Doe
            $johnUser = User::where('email', 'john@staffhub.com')->first();
            if ($johnUser) {
                $notifications = [
                    ['title' => 'Đơn nghỉ phép từ ngày 20/12 đã được phê duyệt', 'date' => '2025-12-08', 'is_read' => true],
                    ['title' => 'Nhắc nhở: Nộp báo cáo tháng 12 trước ngày 15/12', 'date' => '2025-12-05', 'is_read' => true],
                    ['title' => 'Thông báo: Họp team vào thứ 5 tuần này lúc 2PM', 'date' => '2025-12-03', 'is_read' => false],
                ];

                foreach ($notifications as $note) {
                    Notification::create([
                        'user_id' => $johnUser->id,
                        'title' => $note['title'],
                        'content' => $note['title'],
                        'date' => Carbon::parse($note['date']),
                        'is_read' => $note['is_read'],
                    ]);
                }
            }

            // Schedules (work calendar)
            $schedules = [
                ['title' => 'Họp team IT - 9:00 AM', 'date' => '2025-12-09', 'time' => '09:00:00', 'description' => 'Họp tuần IT'],
                ['title' => 'Deadline dự án Website - 5:00 PM', 'date' => '2025-12-10', 'time' => '17:00:00', 'description' => 'Nộp deliverables'],
                ['title' => 'Training React Advanced - 2:00 PM', 'date' => '2025-12-12', 'time' => '14:00:00', 'description' => 'Đào tạo nội bộ'],
                ['title' => 'Nộp báo cáo tháng', 'date' => '2025-12-15', 'time' => '09:00:00', 'description' => 'Báo cáo tháng 12'],
            ];

            foreach ($schedules as $schedule) {
                Schedule::create([
                    'title' => $schedule['title'],
                    'date' => Carbon::parse($schedule['date']),
                    'time' => $schedule['time'],
                    'description' => $schedule['description'],
                ]);
            }
        });
    }
}
