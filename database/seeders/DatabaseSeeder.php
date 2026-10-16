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
    }
}
