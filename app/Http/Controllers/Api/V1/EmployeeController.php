<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\EmployeeBankAccount;
use App\Models\EmployeeSalaryAssignment;
use App\Models\Role;
use App\Models\SalaryStructure;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class EmployeeController extends Controller
{
    /**
     * List all employees with filters and search
     */
    public function index(Request $request)
    {
        $query = Employee::with([
            'user.joiningRequest',
            'department',
            'designation',
            'manager',
            'bankAccount',
            'currentSalaryAssignment',
        ]);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('employee_code', 'like', "%{$search}%")
                  ->orWhere('work_location', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->filled('employment_status')) {
            $query->where('employment_status', $request->employment_status);
        }

        $employees = $query->latest('id')->paginate($request->per_page ?? 25);

        return response()->json([
            'status' => 'success',
            'data' => $employees,
        ]);
    }

    /**
     * Show single employee details
     */
    public function show($id)
    {
        $employee = Employee::with([
            'user.joiningRequest',
            'department',
            'designation',
            'manager',
            'bankAccounts',
            'documents',
            'shifts',
            'leaveBalances.leaveType',
            'salaryAssignments.structure',
            'latestLocation',
        ])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $employee,
        ]);
    }

    /**
     * Store new employee & user
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'nullable|string|min:6',
            'phone' => 'nullable|string',
            'employee_code' => 'required|string|unique:employees,employee_code',
            'department_id' => 'required|exists:departments,id',
            'designation_id' => 'required|exists:designations,id',
            'joining_date' => 'required|date',
            'employment_type' => 'required|in:full_time,part_time,contract,intern',
            'work_location' => 'nullable|string',
            'base_ctc' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();

        try {
            // 1. Create User
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password ?? 'Zivbiyt@123'),
                'phone' => $request->phone,
                'status' => 'active',
            ]);

            // Assign Employee Role
            $empRole = Role::where('name', 'Employee')->first();
            if ($empRole) {
                $user->roles()->attach($empRole->id);
            }

            // 2. Create Employee Profile
            $employee = Employee::create([
                'user_id' => $user->id,
                'employee_code' => $request->employee_code,
                'department_id' => $request->department_id,
                'designation_id' => $request->designation_id,
                'manager_id' => $request->manager_id,
                'joining_date' => $request->joining_date,
                'employment_status' => 'active',
                'employment_type' => $request->employment_type,
                'work_location' => $request->work_location ?? 'Head Office',
                'emergency_contact' => $request->emergency_contact,
                'emergency_phone' => $request->emergency_phone,
                'dob' => $request->dob,
                'gender' => $request->gender,
                'address' => $request->address,
                'city' => $request->city,
                'state' => $request->state,
                'postal_code' => $request->postal_code,
                'pan_number' => $request->pan_number,
                'aadhaar_number' => $request->aadhaar_number,
            ]);

            // 3. Bank Account
            if ($request->filled('bank_name') && $request->filled('account_number')) {
                EmployeeBankAccount::create([
                    'employee_id' => $employee->id,
                    'account_holder_name' => $request->account_holder_name ?? $user->name,
                    'bank_name' => $request->bank_name,
                    'account_number' => $request->account_number,
                    'ifsc_code' => $request->ifsc_code ?? 'SBIN0001234',
                    'branch_name' => $request->branch_name,
                    'is_primary' => true,
                    'is_verified' => true,
                ]);
            }

            // 4. Default Salary Assignment Calculation
            $ctc = (float) $request->base_ctc;
            $basic = round($ctc * 0.50, 2); // 50% Basic
            $hra = round($basic * 0.40, 2);   // 40% of Basic
            $splAllowance = max(0, round($ctc - ($basic + $hra), 2));
            $pfEmployee = min(1800, round($basic * 0.12, 2));
            $pt = 200.00;
            $gross = $basic + $hra + $splAllowance;
            $net = max(0, round($gross - ($pfEmployee + $pt), 2));

            $structure = SalaryStructure::where('is_active', true)->first();

            if ($structure) {
                EmployeeSalaryAssignment::create([
                    'employee_id' => $employee->id,
                    'salary_structure_id' => $structure->id,
                    'base_monthly_ctc' => $ctc,
                    'basic_salary' => $basic,
                    'hra' => $hra,
                    'special_allowance' => $splAllowance,
                    'pf_employee' => $pfEmployee,
                    'pf_employer' => $pfEmployee,
                    'professional_tax' => $pt,
                    'gross_salary' => $gross,
                    'net_salary' => $net,
                    'effective_from' => $request->joining_date,
                    'is_active' => true,
                ]);
            }

            AuditLog::log('employee.created', 'Employee', $employee->id, "Employee {$user->name} ({$employee->employee_code}) was onboarded.");

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Employee created successfully.',
                'data' => $employee->load(['user', 'department', 'designation', 'currentSalaryAssignment']),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create employee: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update employee details
     */
    public function update(Request $request, $id)
    {
        $employee = Employee::with('user')->findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => 'nullable|string',
            'department_id' => 'sometimes|exists:departments,id',
            'designation_id' => 'sometimes|exists:designations,id',
            'employment_status' => 'sometimes|in:active,probation,notice,terminated,resigned',
            'work_location' => 'nullable|string',
        ]);

        if ($request->filled('name') || $request->filled('phone')) {
            $employee->user->update($request->only(['name', 'phone']));
        }

        $employee->update($request->except(['name', 'phone', 'email']));

        AuditLog::log('employee.updated', 'Employee', $employee->id, "Employee {$employee->employee_code} details were updated.");

        return response()->json([
            'status' => 'success',
            'message' => 'Employee updated successfully.',
            'data' => $employee->fresh(['user', 'department', 'designation']),
        ]);
    }

    /**
     * Deactivate employee
     */
    public function deactivate(Request $request, $id)
    {
        $employee = Employee::with('user')->findOrFail($id);
        $employee->update(['employment_status' => 'terminated']);
        $employee->user->update(['status' => 'inactive']);

        AuditLog::log('employee.deactivated', 'Employee', $employee->id, "Employee {$employee->employee_code} was deactivated.");

        return response()->json([
            'status' => 'success',
            'message' => 'Employee deactivated successfully.',
        ]);
    }

    /**
     * Delete employee permanently from database
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $employee = Employee::findOrFail($id);
            $userId = $employee->user_id;

            // Delete associated records
            EmployeeBankAccount::where('employee_id', $employee->id)->delete();
            EmployeeSalaryAssignment::where('employee_id', $employee->id)->delete();
            \App\Models\AttendanceRecord::where('employee_id', $employee->id)->delete();
            \App\Models\LeaveRequest::where('employee_id', $employee->id)->delete();
            \App\Models\PayrollItem::where('employee_id', $employee->id)->delete();
            \App\Models\EmployeeDocument::where('employee_id', $employee->id)->delete();
            \App\Models\LiveTrackingSession::where('employee_id', $employee->id)->delete();

            if ($userId) {
                \App\Models\JoiningRequest::where('user_id', $userId)->delete();
            }

            $employee->delete();

            if ($userId) {
                $user = User::find($userId);
                if ($user && !$user->hasRole(['Super Admin', 'Admin', 'HR Manager', 'Finance Manager'])) {
                    $user->tokens()->delete();
                    $user->roles()->detach();
                    $user->delete();
                }
            }

            AuditLog::log('employee.deleted', 'Employee', $id, "Employee record #{$id} and associated data were permanently deleted.");

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Employee permanently deleted from database.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete employee: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Purge all test employees from database
     */
    public function purgeAll()
    {
        DB::beginTransaction();
        try {
            $employees = Employee::all();
            $deletedCount = 0;

            foreach ($employees as $employee) {
                $userId = $employee->user_id;

                EmployeeBankAccount::where('employee_id', $employee->id)->delete();
                EmployeeSalaryAssignment::where('employee_id', $employee->id)->delete();
                \App\Models\AttendanceRecord::where('employee_id', $employee->id)->delete();
                \App\Models\LeaveRequest::where('employee_id', $employee->id)->delete();
                \App\Models\PayrollItem::where('employee_id', $employee->id)->delete();
                \App\Models\EmployeeDocument::where('employee_id', $employee->id)->delete();
                \App\Models\LiveTrackingSession::where('employee_id', $employee->id)->delete();

                if ($userId) {
                    \App\Models\JoiningRequest::where('user_id', $userId)->delete();
                }

                $employee->delete();

                if ($userId) {
                    $user = User::find($userId);
                    if ($user && !$user->hasRole(['Super Admin', 'Admin', 'HR Manager', 'Finance Manager'])) {
                        $user->tokens()->delete();
                        $user->roles()->detach();
                        $user->delete();
                    }
                }

                $deletedCount++;
            }

            AuditLog::log('employee.purged_all', 'Employee', null, "All test employees ({$deletedCount}) were purged from database.");

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => "Successfully removed {$deletedCount} employee(s) from database.",
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to purge employees: ' . $e->getMessage(),
            ], 500);
        }
    }
}
