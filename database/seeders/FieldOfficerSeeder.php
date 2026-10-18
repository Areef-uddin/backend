<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\JoiningRequest;
use App\Models\Role;
use App\Models\Shift;
use App\Models\User;
use App\Models\AttendanceRecord;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class FieldOfficerSeeder extends Seeder
{
    public function run(): void
    {
        $employeeRole = Role::firstOrCreate(['name' => 'Employee'], ['display_name' => 'Employee', 'description' => 'Employee']);
        $opsDept = Department::where('code', 'OPS')->first() ?? Department::first();
        $shift = Shift::first();

        // Ensure Designation exists
        $foDesig = Designation::firstOrCreate(
            ['name' => 'Field Operations Officer', 'department_id' => $opsDept->id],
            ['code' => 'FO', 'min_salary' => 28000, 'max_salary' => 55000, 'is_active' => true]
        );

        $officers = [
            [
                'name' => 'Rajesh Sharma',
                'email' => 'rajesh.fo@starforce.com',
                'phone' => '+91 98765 11001',
                'code' => 'SF-FO-101',
                'designation' => 'Senior Field Officer',
                'checked_in_today' => true,
                'check_in_time' => '08:30:00',
                'referrals' => [
                    [
                        'name' => 'Ramesh Kumar',
                        'phone' => '+91 98765 43210',
                        'email' => 'ramesh.guard@starforce.com',
                        'role' => 'Security Guard',
                        'status' => 'approved',
                        'code' => 'SF-1042',
                        'date' => Carbon::now()->subDays(4)->format('Y-m-d'),
                        'aadhaar' => '5421-9988-1122',
                    ],
                    [
                        'name' => 'Suresh Yadav',
                        'phone' => '+91 98234 56789',
                        'email' => 'suresh.hk@starforce.com',
                        'role' => 'Housekeeping Supervisor',
                        'status' => 'pending',
                        'code' => 'SF-P102',
                        'date' => Carbon::now()->subDays(2)->format('Y-m-d'),
                        'aadhaar' => '7823-1122-3344',
                    ],
                    [
                        'name' => 'Deepak Verma',
                        'phone' => '+91 97123 45678',
                        'email' => 'deepak.tech@starforce.com',
                        'role' => 'Appliance Technician',
                        'status' => 'pending',
                        'code' => 'SF-P103',
                        'date' => Carbon::now()->subDays(1)->format('Y-m-d'),
                        'aadhaar' => '3312-5544-9988',
                    ],
                ],
            ],
            [
                'name' => 'Vikram Singh',
                'email' => 'vikram.fo@starforce.com',
                'phone' => '+91 98765 11002',
                'code' => 'SF-FO-102',
                'designation' => 'Area Operations Field Officer',
                'checked_in_today' => false,
                'check_in_time' => null,
                'referrals' => [
                    [
                        'name' => 'Manoj Tiwari',
                        'phone' => '+91 99887 76655',
                        'email' => 'manoj.eng@starforce.com',
                        'role' => 'Senior Field Engineer',
                        'status' => 'approved',
                        'code' => 'SF-1035',
                        'date' => Carbon::now()->subDays(5)->format('Y-m-d'),
                        'aadhaar' => '9988-1234-5678',
                    ],
                    [
                        'name' => 'Ajay Rawat',
                        'phone' => '+91 98112 23344',
                        'email' => 'ajay.elec@starforce.com',
                        'role' => 'Field Electrician',
                        'status' => 'pending',
                        'code' => 'SF-P105',
                        'date' => Carbon::now()->subDays(1)->format('Y-m-d'),
                        'aadhaar' => '6655-4433-2211',
                    ],
                ],
            ],
            [
                'name' => 'Amit Patel',
                'email' => 'amit.fo@starforce.com',
                'phone' => '+91 98765 11003',
                'code' => 'SF-FO-103',
                'designation' => 'Site Inspection Officer',
                'checked_in_today' => true,
                'check_in_time' => '09:15:00',
                'referrals' => [
                    [
                        'name' => 'Sandeep Chauhan',
                        'phone' => '+91 91234 56780',
                        'email' => 'sandeep.dispatch@starforce.com',
                        'role' => 'Dispatch Coordinator',
                        'status' => 'pending',
                        'code' => 'SF-P106',
                        'date' => Carbon::now()->format('Y-m-d'),
                        'aadhaar' => '8877-3322-1144',
                    ],
                    [
                        'name' => 'Neha Gupta',
                        'phone' => '+91 98777 66554',
                        'email' => 'neha.exec@starforce.com',
                        'role' => 'Facility Executive',
                        'status' => 'approved',
                        'code' => 'SF-1050',
                        'date' => Carbon::now()->subDays(6)->format('Y-m-d'),
                        'aadhaar' => '4411-8899-7766',
                    ],
                ],
            ],
        ];

        foreach ($officers as $off) {
            $user = User::updateOrCreate(
                ['email' => $off['email']],
                [
                    'name' => $off['name'],
                    'phone' => $off['phone'],
                    'password' => Hash::make('password123'),
                    'status' => 'active',
                ]
            );
            $user->roles()->syncWithoutDetaching([$employeeRole->id]);

            $emp = Employee::updateOrCreate(
                ['employee_code' => $off['code']],
                [
                    'user_id' => $user->id,
                    'department_id' => $opsDept->id,
                    'designation_id' => $foDesig->id,
                    'joining_date' => '2024-01-15',
                    'employment_status' => 'active',
                    'phone' => $off['phone'],
                ]
            );

            // Attendance check-in for today if applicable
            if ($off['checked_in_today']) {
                AttendanceRecord::updateOrCreate(
                    [
                        'employee_id' => $emp->id,
                        'work_date' => Carbon::today()->format('Y-m-d'),
                    ],
                    [
                        'shift_id' => $shift?->id,
                        'check_in_at' => Carbon::today()->setTimeFromTimeString($off['check_in_time']),
                        'status' => 'present',
                        'check_in_address' => 'Outer Ring Road HQ, Bangalore',
                        'check_in_lat' => 12.926031,
                        'check_in_lng' => 77.676246,
                        'is_verified' => true,
                    ]
                );
            }

            // Seed Referred Candidates in JoiningRequest
            foreach ($off['referrals'] as $ref) {
                $refUser = User::firstOrCreate(
                    ['email' => $ref['email']],
                    [
                        'name' => $ref['name'],
                        'phone' => $ref['phone'],
                        'password' => Hash::make('password123'),
                        'status' => 'active',
                    ]
                );

                JoiningRequest::updateOrCreate(
                    ['user_id' => $refUser->id],
                    [
                        'status' => $ref['status'],
                        'proposed_employee_code' => $ref['code'],
                        'approved_employee_code' => $ref['status'] === 'approved' ? $ref['code'] : null,
                        'date_of_joining' => $ref['date'],
                        'proposed_designation' => $ref['role'],
                        'proposed_department' => 'Operations & Field Services',
                        'branch_location' => 'Bangalore Operations Zone',
                        'reporting_officer' => $off['name'] . ' (' . $off['code'] . ')',
                        'full_name' => $ref['name'],
                        'phone' => $ref['phone'],
                        'email' => $ref['email'],
                        'aadhaar_number' => $ref['aadhaar'],
                        'pan_number' => 'ABCDE' . rand(1000, 9999) . 'F',
                        // REFERRAL FIELDS
                        'referral_source' => 'Employee Referral',
                        'referred_by_name' => $off['name'],
                        'referral_employee_code' => $off['code'],
                        'referral_phone' => $off['phone'],
                        'referral_notes' => 'Direct referral from Field Officer ' . $off['name'],
                        'declaration_accepted' => true,
                        'signature_name' => $ref['name'],
                        'declaration_date' => $ref['date'],
                        'declaration_place' => 'Bangalore',
                    ]
                );
            }
        }
    }
}
