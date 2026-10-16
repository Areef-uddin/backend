<?php

namespace Database\Seeders;

use App\Models\AttendanceRecord;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\EmployeeBankAccount;
use App\Models\EmployeeLocation;
use App\Models\EmployeeSalaryAssignment;
use App\Models\EmployeeShift;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\OfficeLocation;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SalaryComponent;
use App\Models\SalaryStructure;
use App\Models\SalaryStructureComponent;
use App\Models\Shift;
use App\Models\TrackingSession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Roles & Permissions
        $roles = [
            'Super Admin' => 'Full access to all payroll, tracking, and HR modules',
            'HR Admin' => 'Manage employees, shifts, leaves, and attendance',
            'Payroll Manager' => 'Configure salary structures, run calculations, and export reports',
            'Payroll Approver' => 'Review and approve payroll runs',
            'Manager' => 'Approve team leaves and view attendance',
            'Employee' => 'Check in/out, view payslips, apply for leaves',
        ];

        $roleModels = [];
        foreach ($roles as $name => $desc) {
            $roleModels[$name] = Role::create([
                'name' => $name,
                'display_name' => $name,
                'description' => $desc,
            ]);
        }

        $permissions = [
            'employees.view', 'employees.create', 'employees.edit', 'employees.delete',
            'attendance.view', 'attendance.check_in', 'attendance.correct', 'attendance.lock',
            'tracking.view_active', 'tracking.view_history',
            'leave.apply', 'leave.approve',
            'payroll.calculate', 'payroll.approve', 'payroll.finalize', 'payroll.publish',
            'settings.view', 'settings.manage',
        ];

        foreach ($permissions as $perm) {
            $p = Permission::create([
                'name' => $perm,
                'display_name' => ucwords(str_replace(['.', '_'], ' ', $perm)),
                'module' => explode('.', $perm)[0],
            ]);
            $roleModels['Super Admin']->permissions()->attach($p->id);
        }

        // 2. Office Locations
        $office = OfficeLocation::create([
            'name' => 'Zivbiyt Tech & Operations HQ',
            'address' => 'Outer Ring Road, Bellandur, Bangalore, Karnataka 560103',
            'latitude' => 12.926031,
            'longitude' => 77.676246,
            'geofence_radius_meters' => 250,
            'is_active' => true,
        ]);

        // 3. Shifts
        $shiftGeneral = Shift::create([
            'name' => 'General Office Shift',
            'code' => 'GEN-09',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'grace_period_minutes' => 15,
            'half_day_hours' => 4,
            'full_day_hours' => 8,
            'is_night_shift' => false,
            'is_active' => true,
        ]);

        $shiftField = Shift::create([
            'name' => 'Field Morning Shift',
            'code' => 'FLD-08',
            'start_time' => '08:30:00',
            'end_time' => '17:30:00',
            'grace_period_minutes' => 20,
            'half_day_hours' => 4,
            'full_day_hours' => 8,
            'is_night_shift' => false,
            'is_active' => true,
        ]);

        // 4. Leave Types
        $leaveTypes = [
            ['name' => 'Casual Leave', 'code' => 'CL', 'days' => 12, 'is_paid' => true],
            ['name' => 'Sick Leave', 'code' => 'SL', 'days' => 10, 'is_paid' => true],
            ['name' => 'Privilege Leave', 'code' => 'PL', 'days' => 15, 'is_paid' => true],
            ['name' => 'Unpaid Leave (LOP)', 'code' => 'LOP', 'days' => 0, 'is_paid' => false],
        ];

        $leaveTypeModels = [];
        foreach ($leaveTypes as $lt) {
            $leaveTypeModels[$lt['code']] = LeaveType::create([
                'name' => $lt['name'],
                'code' => $lt['code'],
                'days_per_year' => $lt['days'],
                'is_paid' => $lt['is_paid'],
                'carry_forward' => $lt['code'] === 'PL',
            ]);
        }

        // 5. Departments & Designations
        $deptData = [
            'Operations & Field Services' => [
                'code' => 'OPS',
                'designations' => ['Senior Field Engineer', 'Appliance Technician', 'Electrician Specialist', 'Field Supervisor'],
            ],
            'Customer Support & Dispatch' => [
                'code' => 'CSD',
                'designations' => ['Support Executive', 'Dispatch Coordinator', 'Team Lead'],
            ],
            'Engineering & Product' => [
                'code' => 'ENG',
                'designations' => ['Full Stack Engineer', 'Mobile App Specialist', 'QA Analyst'],
            ],
            'HR & Administration' => [
                'code' => 'HRA',
                'designations' => ['HR Executive', 'Talent Acquisition Partner', 'HR Manager'],
            ],
            'Finance & Accounts' => [
                'code' => 'FIN',
                'designations' => ['Accounts Executive', 'Payroll Specialist', 'Finance Manager'],
            ],
        ];

        $deptModels = [];
        $desigModels = [];

        foreach ($deptData as $deptName => $info) {
            $d = Department::create([
                'name' => $deptName,
                'code' => $info['code'],
                'description' => "Zivbiyt {$deptName} Department",
                'is_active' => true,
            ]);
            $deptModels[$info['code']] = $d;

            foreach ($info['designations'] as $desName) {
                $des = Designation::create([
                    'name' => $desName,
                    'code' => strtoupper(substr(str_replace(' ', '', $desName), 0, 4)),
                    'department_id' => $d->id,
                    'min_salary' => 25000,
                    'max_salary' => 85000,
                    'is_active' => true,
                ]);
                $desigModels[$desName] = $des;
            }
        }

        // 6. Salary Structures & Components
        $structStandard = SalaryStructure::create([
            'name' => 'Standard Monthly Full-Time Structure',
            'code' => 'STD-M-2026',
            'description' => '50% Basic, 40% of Basic HRA, Statutory PF & PT',
            'is_active' => true,
        ]);

        $cmpBasic = SalaryComponent::create(['name' => 'Basic Salary', 'code' => 'BASIC', 'type' => 'earning', 'calculation_type' => 'percentage', 'percentage_of' => 'ctc', 'is_taxable' => true]);
        $cmpHra = SalaryComponent::create(['name' => 'House Rent Allowance', 'code' => 'HRA', 'type' => 'earning', 'calculation_type' => 'percentage', 'percentage_of' => 'basic', 'is_taxable' => true]);
        $cmpSpl = SalaryComponent::create(['name' => 'Special Allowance', 'code' => 'SPL_ALLOW', 'type' => 'earning', 'calculation_type' => 'fixed', 'is_taxable' => true]);
        $cmpPf = SalaryComponent::create(['name' => 'Provident Fund (Employee)', 'code' => 'PF_EMP', 'type' => 'deduction', 'calculation_type' => 'percentage', 'percentage_of' => 'basic', 'is_statutory' => true]);
        $cmpPt = SalaryComponent::create(['name' => 'Professional Tax', 'code' => 'PT', 'type' => 'deduction', 'calculation_type' => 'fixed', 'is_statutory' => true]);

        SalaryStructureComponent::create(['salary_structure_id' => $structStandard->id, 'salary_component_id' => $cmpBasic->id, 'default_percentage' => 50.00]);
        SalaryStructureComponent::create(['salary_structure_id' => $structStandard->id, 'salary_component_id' => $cmpHra->id, 'default_percentage' => 40.00]);
        SalaryStructureComponent::create(['salary_structure_id' => $structStandard->id, 'salary_component_id' => $cmpSpl->id, 'default_amount' => 5000.00]);
        SalaryStructureComponent::create(['salary_structure_id' => $structStandard->id, 'salary_component_id' => $cmpPf->id, 'default_percentage' => 12.00]);
        SalaryStructureComponent::create(['salary_structure_id' => $structStandard->id, 'salary_component_id' => $cmpPt->id, 'default_amount' => 200.00]);

        // 7. Seed Admin & HR Users
        $adminUser = User::create([
            'name' => 'Zivbiyt Super Admin',
            'email' => 'admin@zivbiyt.com',
            'password' => Hash::make('password123'),
            'phone' => '+91 98765 43210',
            'status' => 'active',
        ]);
        $adminUser->roles()->attach($roleModels['Super Admin']->id);

        $hrUser = User::create([
            'name' => 'Pooja Verma (HR)',
            'email' => 'hr@zivbiyt.com',
            'password' => Hash::make('password123'),
            'phone' => '+91 98765 43211',
            'status' => 'active',
        ]);
        $hrUser->roles()->attach($roleModels['HR Admin']->id);

        $payrollUser = User::create([
            'name' => 'Anil Mehta (Finance)',
            'email' => 'finance@zivbiyt.com',
            'password' => Hash::make('password123'),
            'phone' => '+91 98765 43212',
            'status' => 'active',
        ]);
        $payrollUser->roles()->attach($roleModels['Payroll Manager']->id);

        // 8. Seed Sample Employees (Field techs, Engineers, Support)
        $employeeData = [
            [
                'name' => 'Rajesh Kumar',
                'email' => 'rajesh.kumar@zivbiyt.com',
                'phone' => '+91 98111 22334',
                'code' => 'ZIV-1001',
                'dept' => 'OPS',
                'desig' => 'Senior Field Engineer',
                'ctc' => 42000,
                'location' => 'South Bangalore Cluster',
                'shift' => $shiftField,
                'lat' => 12.9342,
                'lng' => 77.6101,
            ],
            [
                'name' => 'Priya Sharma',
                'email' => 'priya.sharma@zivbiyt.com',
                'phone' => '+91 98222 33445',
                'code' => 'ZIV-1002',
                'dept' => 'CSD',
                'desig' => 'Dispatch Coordinator',
                'ctc' => 36000,
                'location' => 'Head Office',
                'shift' => $shiftGeneral,
                'lat' => 12.9260,
                'lng' => 77.6762,
            ],
            [
                'name' => 'Amit Sharma',
                'email' => 'amit.sharma@zivbiyt.com',
                'phone' => '+91 98333 44556',
                'code' => 'ZIV-1003',
                'dept' => 'OPS',
                'desig' => 'Appliance Technician',
                'ctc' => 38000,
                'location' => 'Indiranagar Hub',
                'shift' => $shiftField,
                'lat' => 12.9784,
                'lng' => 77.6408,
            ],
            [
                'name' => 'Sneha Patel',
                'email' => 'sneha.patel@zivbiyt.com',
                'phone' => '+91 98444 55667',
                'code' => 'ZIV-1004',
                'dept' => 'ENG',
                'desig' => 'Mobile App Specialist',
                'ctc' => 65000,
                'location' => 'Head Office',
                'shift' => $shiftGeneral,
                'lat' => 12.9260,
                'lng' => 77.6762,
            ],
            [
                'name' => 'Vikram Singh',
                'email' => 'vikram.singh@zivbiyt.com',
                'phone' => '+91 98555 66778',
                'code' => 'ZIV-1005',
                'dept' => 'OPS',
                'desig' => 'Field Supervisor',
                'ctc' => 48000,
                'location' => 'Koramangala Hub',
                'shift' => $shiftField,
                'lat' => 12.9352,
                'lng' => 77.6245,
            ],
            [
                'name' => 'Kavita Nair',
                'email' => 'kavita.nair@zivbiyt.com',
                'phone' => '+91 98666 77889',
                'code' => 'ZIV-1006',
                'dept' => 'HRA',
                'desig' => 'HR Executive',
                'ctc' => 35000,
                'location' => 'Head Office',
                'shift' => $shiftGeneral,
                'lat' => 12.9260,
                'lng' => 77.6762,
            ],
            [
                'name' => 'Sunil Joshi',
                'email' => 'sunil.joshi@zivbiyt.com',
                'phone' => '+91 98777 88990',
                'code' => 'ZIV-1007',
                'dept' => 'FIN',
                'desig' => 'Payroll Specialist',
                'ctc' => 45000,
                'location' => 'Head Office',
                'shift' => $shiftGeneral,
                'lat' => 12.9260,
                'lng' => 77.6762,
            ],
            [
                'name' => 'Deepak Verma',
                'email' => 'deepak.verma@zivbiyt.com',
                'phone' => '+91 98888 99001',
                'code' => 'ZIV-1008',
                'dept' => 'OPS',
                'desig' => 'Electrician Specialist',
                'ctc' => 34000,
                'location' => 'Whitefield Sector',
                'shift' => $shiftField,
                'lat' => 12.9698,
                'lng' => 77.7499,
            ],
        ];

        $today = Carbon::today()->format('Y-m-d');
        $currentMonth = Carbon::today()->format('Y-m');

        foreach ($employeeData as $idx => $ed) {
            $u = User::create([
                'name' => $ed['name'],
                'email' => $ed['email'],
                'password' => Hash::make('password123'),
                'phone' => $ed['phone'],
                'status' => 'active',
            ]);
            $u->roles()->attach($roleModels['Employee']->id);

            $emp = Employee::create([
                'user_id' => $u->id,
                'employee_code' => $ed['code'],
                'department_id' => $deptModels[$ed['dept']]->id,
                'designation_id' => $desigModels[$ed['desig']]->id,
                'manager_id' => $adminUser->id,
                'joining_date' => Carbon::now()->subMonths(6 + $idx)->format('Y-m-d'),
                'employment_status' => 'active',
                'employment_type' => 'full_time',
                'work_location' => $ed['location'],
                'emergency_contact' => 'Family Contact',
                'emergency_phone' => '+91 98000 0000' . $idx,
                'dob' => '1995-05-15',
                'gender' => ($idx % 2 == 0) ? 'male' : 'female',
                'city' => 'Bangalore',
                'state' => 'Karnataka',
                'pan_number' => 'ABCDE' . (1234 + $idx) . 'F',
                'aadhaar_number' => '4567 8901 ' . (2340 + $idx),
            ]);

            // Bank Account
            EmployeeBankAccount::create([
                'employee_id' => $emp->id,
                'account_holder_name' => $ed['name'],
                'bank_name' => ($idx % 2 == 0) ? 'HDFC Bank' : 'State Bank of India',
                'account_number' => '50100' . (34567890 + $idx),
                'ifsc_code' => ($idx % 2 == 0) ? 'HDFC0001234' : 'SBIN0004567',
                'branch_name' => 'Koramangala Branch',
                'is_primary' => true,
                'is_verified' => true,
            ]);

            // Shift Assignment
            EmployeeShift::create([
                'employee_id' => $emp->id,
                'shift_id' => $ed['shift']->id,
                'effective_from' => Carbon::now()->subMonths(6)->format('Y-m-d'),
            ]);

            // Leave Balances
            foreach ($leaveTypeModels as $code => $ltModel) {
                LeaveBalance::create([
                    'employee_id' => $emp->id,
                    'leave_type_id' => $ltModel->id,
                    'year' => Carbon::now()->year,
                    'opening_balance' => $ltModel->days_per_year,
                    'accrued' => $ltModel->days_per_year,
                    'used' => rand(0, 3),
                    'balance' => max(0, $ltModel->days_per_year - rand(0, 3)),
                ]);
            }

            // Salary Assignment
            $ctc = (float) $ed['ctc'];
            $basic = round($ctc * 0.50, 2);
            $hra = round($basic * 0.40, 2);
            $spl = max(0, round($ctc - ($basic + $hra), 2));
            $pf = min(1800, round($basic * 0.12, 2));
            $pt = 200.00;
            $gross = $basic + $hra + $spl;
            $net = max(0, round($gross - ($pf + $pt), 2));

            EmployeeSalaryAssignment::create([
                'employee_id' => $emp->id,
                'salary_structure_id' => $structStandard->id,
                'base_monthly_ctc' => $ctc,
                'basic_salary' => $basic,
                'hra' => $hra,
                'special_allowance' => $spl,
                'pf_employee' => $pf,
                'pf_employer' => $pf,
                'professional_tax' => $pt,
                'tds_estimate' => 0.00,
                'gross_salary' => $gross,
                'net_salary' => $net,
                'effective_from' => Carbon::now()->subMonths(6)->format('Y-m-d'),
                'is_active' => true,
            ]);

            // Attendance for Today
            $checkInTime = Carbon::today()->setHour(8)->setMinute(45)->addMinutes($idx * 5);
            $checkOutTime = ($idx < 4) ? Carbon::today()->setHour(17)->setMinute(45) : null;

            AttendanceRecord::create([
                'employee_id' => $emp->id,
                'shift_id' => $ed['shift']->id,
                'work_date' => $today,
                'check_in_at' => $checkInTime,
                'check_out_at' => $checkOutTime,
                'check_in_lat' => $ed['lat'],
                'check_in_lng' => $ed['lng'],
                'check_in_address' => $ed['location'] . ', Bangalore',
                'status' => 'present',
                'total_minutes' => $checkOutTime ? 540 : 360,
                'late_minutes' => 0,
                'is_verified' => true,
                'verification_method' => 'gps',
            ]);

            // Field tracking session for on-duty field techs
            if (in_array($ed['code'], ['ZIV-1001', 'ZIV-1003', 'ZIV-1005', 'ZIV-1008'])) {
                $session = TrackingSession::create([
                    'employee_id' => $emp->id,
                    'started_at' => $checkInTime,
                    'status' => 'active',
                    'total_distance_km' => round(12.5 + $idx * 3.2, 2),
                ]);

                // Record recent location pings
                EmployeeLocation::create([
                    'employee_id' => $emp->id,
                    'session_id' => $session->id,
                    'latitude' => $ed['lat'] + (rand(-10, 10) / 10000),
                    'longitude' => $ed['lng'] + (rand(-10, 10) / 10000),
                    'accuracy' => 12.5,
                    'speed' => rand(15, 35) / 3.6, // m/s
                    'battery_level' => 88 - ($idx * 6),
                    'recorded_at' => Carbon::now()->subMinutes($idx * 2 + 1),
                    'received_at' => Carbon::now(),
                ]);
            }
        }
    }
}
