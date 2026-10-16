<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Designation;
use App\Models\JoiningRequest;
use App\Models\Role;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class JoiningRequestSeeder extends Seeder
{
    public function run(): void
    {
        $employeeRole = Role::where('name', 'Employee')->first();
        $hrUser = User::where('email', 'hr@zivbiyt.com')->first();
        $opsDept = Department::where('code', 'OPS')->first() ?? Department::first();
        $techDesig = Designation::where('department_id', $opsDept?->id)->first() ?? Designation::first();
        $shift = Shift::first();

        // 1. Pending Worker 1: Vikram Singh
        $worker1 = User::firstOrCreate(
            ['email' => 'worker@starforce.com'],
            [
                'name' => 'Vikram Singh',
                'password' => Hash::make('password123'),
                'phone' => '+91 98765 43220',
                'status' => 'active',
            ]
        );
        if ($employeeRole) {
            $worker1->roles()->syncWithoutDetaching([$employeeRole->id]);
        }

        JoiningRequest::updateOrCreate(
            ['user_id' => $worker1->id],
            [
                'status' => 'pending',
                'proposed_employee_code' => 'SF-P089',
                'date_of_joining' => Carbon::today()->addDays(2)->format('Y-m-d'),
                'proposed_designation' => 'Field Facility Technician',
                'proposed_department' => 'Operations & Field Services',
                'branch_location' => 'Sector 62, Noida Hub',
                'reporting_officer' => 'Anurag Sharma (Area Manager)',
                // Personal
                'full_name' => 'Vikram Singh',
                'father_spouse_name' => 'Harpal Singh',
                'dob' => '1996-08-14',
                'gender' => 'Male',
                'marital_status' => 'Married',
                'blood_group' => 'B+',
                'phone' => '+91 98765 43220',
                'alternate_phone' => '+91 98112 23344',
                'email' => 'worker@starforce.com',
                'aadhaar_number' => '4521-8899-1234',
                'pan_number' => 'BKAPS9921E',
                'emergency_contact_name' => 'Harpal Singh (Father)',
                // Address
                'permanent_address' => 'H.No 45, Village Rampur, Greater Noida, UP - 201310',
                'current_address' => 'Room 12, Sharma Niwas, Sector 62, Noida, UP - 201301',
                // Emergency
                'emergency_relationship' => 'Father',
                'emergency_phone' => '+91 98112 23344',
                'emergency_address' => 'H.No 45, Village Rampur, Greater Noida, UP',
                // Bank
                'account_holder_name' => 'Vikram Singh',
                'bank_name' => 'State Bank of India',
                'branch_name' => 'Sector 62 Noida',
                'account_number' => '389211002938',
                'ifsc_code' => 'SBIN0004521',
                'upi_id' => 'vikram.singh@oksbi',
                // Education
                'qualification' => 'ITI Electrical & Maintenance',
                'institution_board' => 'UP State Technical Board, Lucknow',
                'passing_year' => '2017',
                'percentage_grade' => '74% (First Class)',
                // Previous Employment
                'previous_company_name' => 'Apex Facility Management Pvt Ltd',
                'previous_designation' => 'Junior Electrician',
                'period_of_employment' => '2 Years (2021 - 2023)',
                'reason_for_leaving' => 'Career Growth & Better Opportunities',
                'previous_salary' => 19500.00,
                // Docs
                'documents_submitted' => [
                    'Aadhaar Card (Front & Back)',
                    'PAN Card',
                    'Bank Passbook / Cancelled Cheque',
                    'Passport Size Photograph',
                    'Educational Marksheets / Certificates',
                ],
                // Declaration
                'declaration_accepted' => true,
                'signature_name' => 'Vikram Singh',
                'declaration_date' => Carbon::today()->format('Y-m-d'),
                'declaration_place' => 'Noida',
            ]
        );

        // 2. Pending Worker 2: Sunita Sharma
        $worker2 = User::firstOrCreate(
            ['email' => 'sunita.sharma@starforce.com'],
            [
                'name' => 'Sunita Sharma',
                'password' => Hash::make('password123'),
                'phone' => '+91 98119 87654',
                'status' => 'active',
            ]
        );
        if ($employeeRole) {
            $worker2->roles()->syncWithoutDetaching([$employeeRole->id]);
        }

        JoiningRequest::updateOrCreate(
            ['user_id' => $worker2->id],
            [
                'status' => 'pending',
                'proposed_employee_code' => 'SF-P092',
                'date_of_joining' => Carbon::today()->addDays(4)->format('Y-m-d'),
                'proposed_designation' => 'Facility Supervisor',
                'proposed_department' => 'Operations & Field Services',
                'branch_location' => 'Gurugram Cybercity Cluster',
                'reporting_officer' => 'Deepak Verma',
                'full_name' => 'Sunita Sharma',
                'father_spouse_name' => 'Rajendra Sharma',
                'dob' => '1994-03-22',
                'gender' => 'Female',
                'marital_status' => 'Single',
                'blood_group' => 'O+',
                'phone' => '+91 98119 87654',
                'alternate_phone' => '+91 98777 11223',
                'email' => 'sunita.sharma@starforce.com',
                'aadhaar_number' => '8892-1133-7766',
                'pan_number' => 'CPKSS8821M',
                'emergency_contact_name' => 'Rajendra Sharma',
                'permanent_address' => 'Flat 204, Sector 14, Old Railway Road, Gurugram - 122001',
                'current_address' => 'Flat 204, Sector 14, Old Railway Road, Gurugram - 122001',
                'emergency_relationship' => 'Father',
                'emergency_phone' => '+91 98777 11223',
                'emergency_address' => 'Sector 14 Gurugram',
                'account_holder_name' => 'Sunita Sharma',
                'bank_name' => 'HDFC Bank',
                'branch_name' => 'Cyber City Gurugram',
                'account_number' => '50100234918231',
                'ifsc_code' => 'HDFC0001243',
                'upi_id' => 'sunita@okhdfcbank',
                'qualification' => 'Diploma in Hotel & Hospitality Management',
                'institution_board' => 'National Council of Hotel Management',
                'passing_year' => '2016',
                'percentage_grade' => '78%',
                'previous_company_name' => 'Premier Corporate Services Ltd',
                'previous_designation' => 'Shift Supervisor',
                'period_of_employment' => '3 Years (2020 - 2023)',
                'reason_for_leaving' => 'Relocation',
                'previous_salary' => 26000.00,
                'documents_submitted' => [
                    'Aadhaar Card (Front & Back)',
                    'PAN Card',
                    'Bank Passbook / Cancelled Cheque',
                    'Passport Size Photograph',
                    'Educational Marksheets / Certificates',
                    'Previous Relieving / Experience Letter',
                    'Police Verification / Clearance Certificate',
                ],
                'declaration_accepted' => true,
                'signature_name' => 'Sunita Sharma',
                'declaration_date' => Carbon::today()->format('Y-m-d'),
                'declaration_place' => 'Gurugram',
            ]
        );

        // 3. Approved Worker: Rajesh Kumar
        $rajesh = User::where('email', 'rajesh.kumar@zivbiyt.com')->first();
        if ($rajesh) {
            JoiningRequest::updateOrCreate(
                ['user_id' => $rajesh->id],
                [
                    'status' => 'approved',
                    'proposed_employee_code' => 'SF-1001',
                    'date_of_joining' => '2023-01-15',
                    'proposed_designation' => 'Senior Field Engineer',
                    'proposed_department' => 'Operations & Field Services',
                    'branch_location' => 'South Bangalore Cluster',
                    'reporting_officer' => 'Pooja Verma',
                    'full_name' => 'Rajesh Kumar',
                    'father_spouse_name' => 'Ramesh Kumar',
                    'dob' => '1992-05-10',
                    'gender' => 'Male',
                    'marital_status' => 'Married',
                    'blood_group' => 'O+',
                    'phone' => '+91 98111 22334',
                    'email' => 'rajesh.kumar@zivbiyt.com',
                    'aadhaar_number' => '3344-5566-7788',
                    'pan_number' => 'ABCDE1234F',
                    'emergency_contact_name' => 'Ramesh Kumar',
                    'permanent_address' => '#42, 5th Cross, Koramangala 4th Block, Bangalore 560034',
                    'current_address' => '#42, 5th Cross, Koramangala 4th Block, Bangalore 560034',
                    'emergency_relationship' => 'Father',
                    'emergency_phone' => '+91 98111 22335',
                    'account_holder_name' => 'Rajesh Kumar',
                    'bank_name' => 'HDFC Bank',
                    'branch_name' => 'Koramangala',
                    'account_number' => '50100456789123',
                    'ifsc_code' => 'HDFC0000123',
                    'qualification' => 'B.Tech Electrical & Electronics',
                    'institution_board' => 'VTU Belgaum',
                    'passing_year' => '2014',
                    'percentage_grade' => '72%',
                    'declaration_accepted' => true,
                    'signature_name' => 'Rajesh Kumar',
                    'declaration_date' => '2023-01-10',
                    'declaration_place' => 'Bangalore',
                    // Office Use Only
                    'approved_employee_code' => 'SF-1001',
                    'official_joining_date' => '2023-01-15',
                    'department_id' => $opsDept?->id,
                    'designation_id' => $techDesig?->id,
                    'salary_wages' => 42000.00,
                    'duty_location' => 'South Bangalore Cluster',
                    'shift_id' => $shift?->id,
                    'documents_verified_by' => 'Pooja Verma (HR)',
                    'background_verification' => 'completed',
                    'police_verification' => 'completed',
                    'uniform_issued' => true,
                    'id_card_issued' => true,
                    'admin_remarks' => 'All original documents verified. Background verification clean. Enrolled to Starforce facilities operations.',
                    'reviewed_by' => $hrUser?->id,
                    'reviewed_at' => Carbon::parse('2023-01-12 11:30:00'),
                ]
            );
        }
    }
}
