<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\EmployeeBankAccount;
use App\Models\EmployeeDocument;
use App\Models\EmployeeSalaryAssignment;
use App\Models\JoiningRequest;
use App\Models\SalaryStructure;
use App\Models\Shift;
use App\Models\User;
use App\Models\Role;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class JoiningRequestController extends Controller
{
    /**
     * Provide configuration options & lookups for the form
     */
    public function options()
    {
        return response()->json([
            'status' => 'success',
            'data' => [
                'departments' => Department::where('is_active', true)->select('id', 'name', 'code')->get(),
                'designations' => Designation::where('is_active', true)->select('id', 'name', 'department_id')->get(),
                'shifts' => Shift::where('is_active', true)->select('id', 'name', 'start_time', 'end_time')->get(),
                'document_types' => [
                    'Aadhaar Card (Front & Back)',
                    'PAN Card',
                    'Bank Passbook / Cancelled Cheque',
                    'Educational Marksheets / Certificates',
                    'Previous Relieving / Experience Letter',
                    'Passport Size Photograph',
                    'Police Verification / Clearance Certificate',
                    'Address Proof (Electricity / Rent Agreement)',
                ],
                'blood_groups' => ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'],
                'genders' => ['Male', 'Female', 'Other'],
                'marital_statuses' => ['Single', 'Married', 'Divorced', 'Widowed'],
                'referral_sources' => [
                    'Employee Referral',
                    'Direct Application / Walk-in',
                    'Recruitment Partner / Consultant',
                    'Job Portal (Naukri / Indeed / LinkedIn)',
                    'Friend / Relative Referral',
                    'Social Media / Website',
                    'Other Referral',
                ],
            ],
        ]);
    }

    /**
     * Unified candidate registration & joining form submission
     */
    public function registerAndSubmit(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'full_name' => 'required|string|max:255',
                'phone' => 'required|string|max:20',
                'email' => 'required|email|max:255',
                'password' => 'required|string|min:6',
                'declaration_accepted' => 'required|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()->first(),
                    'errors' => $validator->errors(),
                ], 422);
            }

            $email = strtolower(trim($request->email));
            $phone = trim($request->phone);
            $name = trim($request->full_name);

            // Resolve candidate photograph to a safe HTTPS file URL if provided as base64/data URI
            $resolvedPhotoUrl = $this->resolvePhotoUrl($request->input('photo_url'), $request);
            $safeAvatar = ($resolvedPhotoUrl && strlen($resolvedPhotoUrl) <= 255) ? $resolvedPhotoUrl : null;

            $user = User::where('email', $email)->first();

            if ($user) {
                if (!Hash::check($request->password, $user->password)) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'An account with this email already exists. Please verify your password.',
                    ], 422);
                }
                if ($safeAvatar) {
                    $user->update(['avatar' => $safeAvatar]);
                }
            } else {
                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'password' => Hash::make($request->password),
                    'avatar' => $safeAvatar,
                    'status' => 'active',
                ]);
            }

            $empRole = Role::firstOrCreate(
                ['name' => 'Employee'],
                ['display_name' => 'Employee', 'description' => 'Employee role']
            );
            $user->roles()->syncWithoutDetaching([$empRole->id]);

            $token = $user->createToken('employee-mobile')->plainTextToken;

            $data = $request->only([
                'proposed_employee_code',
                'date_of_joining',
                'proposed_designation',
                'proposed_department',
                'branch_location',
                'reporting_officer',
                'full_name',
                'father_spouse_name',
                'dob',
                'gender',
                'marital_status',
                'blood_group',
                'phone',
                'alternate_phone',
                'email',
                'aadhaar_number',
                'pan_number',
                'emergency_contact_name',
                'permanent_address',
                'current_address',
                'emergency_relationship',
                'emergency_phone',
                'emergency_address',
                'account_holder_name',
                'bank_name',
                'branch_name',
                'account_number',
                'ifsc_code',
                'upi_id',
                'qualification',
                'institution_board',
                'passing_year',
                'percentage_grade',
                'previous_company_name',
                'previous_designation',
                'period_of_employment',
                'reason_for_leaving',
                'previous_salary',
                'documents_submitted',
                'uploaded_documents',
                'declaration_accepted',
                'signature_name',
                'declaration_date',
                'declaration_place',
                'photo_url',
                // Referral Option
                'referral_source',
                'referred_by_name',
                'referral_employee_code',
                'referral_phone',
                'referral_notes',
            ]);

            $data['user_id'] = $user->id;
            $data['status'] = 'pending';
            $data = $this->cleanJoiningData($data, $resolvedPhotoUrl);

            $joining = JoiningRequest::where('user_id', $user->id)->first();
            if ($joining) {
                $joining->update($data);
            } else {
                $joining = JoiningRequest::create($data);
            }

            try {
                AuditLog::log('joining.submitted', 'JoiningRequest', $joining->id, "Candidate {$user->name} ({$user->email}) submitted Starforce Joining Form for verification.");
            } catch (\Throwable $auditEx) {
                \Log::warning('Failed to write audit log for joining submit: ' . $auditEx->getMessage());
            }

            $user->load(['roles.permissions']);

            return response()->json([
                'status' => 'success',
                'message' => 'Joining form submitted successfully. Your application is under HR review.',
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'roles' => $user->roles->pluck('name')->toArray(),
                ],
                'data' => $joining,
            ], 201);
        } catch (\Throwable $e) {
            \Log::error('Error in registerAndSubmit: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to process application: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Worker submits or updates their joining form
     */
    public function store(Request $request)
    {
        try {
            $user = $request->user();

            $validator = Validator::make($request->all(), [
                'full_name' => 'required|string|max:255',
                'phone' => 'required|string|max:20',
                'email' => 'nullable|email|max:255',
                'dob' => 'nullable|date',
                'gender' => 'nullable|string',
                'marital_status' => 'nullable|string',
                'blood_group' => 'nullable|string',
                'father_spouse_name' => 'nullable|string|max:255',
                'permanent_address' => 'nullable|string',
                'current_address' => 'nullable|string',
                'emergency_contact_name' => 'nullable|string|max:255',
                'emergency_relationship' => 'nullable|string|max:100',
                'emergency_phone' => 'nullable|string|max:20',
                'aadhaar_number' => 'nullable|string|max:30',
                'pan_number' => 'nullable|string|max:30',
                'account_holder_name' => 'nullable|string|max:255',
                'bank_name' => 'nullable|string|max:255',
                'account_number' => 'nullable|string|max:50',
                'ifsc_code' => 'nullable|string|max:20',
                'declaration_accepted' => 'required|boolean',
                'signature_name' => 'nullable|string|max:255',
                'declaration_date' => 'nullable|date',
                'declaration_place' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation error: ' . $validator->errors()->first(),
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Check if existing
            $joining = JoiningRequest::where('user_id', $user->id)->first();

            if ($joining && $joining->status === 'approved') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Your joining application has already been approved. Modifications must be requested via HR.',
                    'data' => $joining,
                ], 400);
            }

            // Resolve candidate photograph to a safe HTTPS file URL if provided as base64/data URI
            $resolvedPhotoUrl = $this->resolvePhotoUrl($request->input('photo_url'), $request);
            $safeAvatar = ($resolvedPhotoUrl && strlen($resolvedPhotoUrl) <= 255) ? $resolvedPhotoUrl : null;

            $data = $request->only([
                'proposed_employee_code',
                'date_of_joining',
                'proposed_designation',
                'proposed_department',
                'branch_location',
                'reporting_officer',
                'full_name',
                'father_spouse_name',
                'dob',
                'gender',
                'marital_status',
                'blood_group',
                'phone',
                'alternate_phone',
                'email',
                'aadhaar_number',
                'pan_number',
                'emergency_contact_name',
                'permanent_address',
                'current_address',
                'emergency_relationship',
                'emergency_phone',
                'emergency_address',
                'account_holder_name',
                'bank_name',
                'branch_name',
                'account_number',
                'ifsc_code',
                'upi_id',
                'qualification',
                'institution_board',
                'passing_year',
                'percentage_grade',
                'previous_company_name',
                'previous_designation',
                'period_of_employment',
                'reason_for_leaving',
                'previous_salary',
                'documents_submitted',
                'uploaded_documents',
                'photo_url',
                // Referral Option
                'referral_source',
                'referred_by_name',
                'referral_employee_code',
                'referral_phone',
                'referral_notes',
                'declaration_accepted',
                'signature_name',
                'declaration_date',
                'declaration_place',
            ]);

            $data['user_id'] = $user->id;
            $data['status'] = 'pending'; // Reset to pending if resubmitting
            $data = $this->cleanJoiningData($data, $resolvedPhotoUrl);

            if ($joining) {
                $joining->update($data);
            } else {
                $joining = JoiningRequest::create($data);
            }

            // Keep user name/phone/password in sync
            if ($request->filled('full_name')) {
                $user->update(['name' => $request->full_name]);
            }
            if ($request->filled('phone')) {
                $user->update(['phone' => $request->phone]);
            }
            if ($request->filled('password') && strlen($request->password) >= 6) {
                $user->update(['password' => Hash::make($request->password)]);
            }
            if ($safeAvatar) {
                $user->update(['avatar' => $safeAvatar]);
            }

            try {
                AuditLog::log('joining_request.submitted', 'JoiningRequest', $joining->id, "User {$user->name} ({$user->id}) submitted employee joining form.");
            } catch (\Throwable $auditEx) {
                \Log::warning('Failed to write audit log for joining update: ' . $auditEx->getMessage());
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Employee joining application submitted successfully. Pending HR Admin verification.',
                'data' => $joining->fresh(['department', 'designation', 'shift']),
            ], 201);
        } catch (\Throwable $e) {
            \Log::error('Error in store joining request: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to save application: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get current user's joining request status & details
     */
    public function myStatus(Request $request)
    {
        $user = $request->user();
        $joining = JoiningRequest::where('user_id', $user->id)
            ->with(['department', 'designation', 'shift', 'reviewer'])
            ->first();

        $employee = $user->employee;

        if ($joining) {
            $status = $joining->status;
            $isApproved = ($status === 'approved');
        } else {
            // Only legacy seeded employees with active status are approved without joining request
            if ($employee && $employee->employment_status === 'active' && $user->id <= 11) {
                $status = 'approved';
                $isApproved = true;
            } else {
                $status = 'not_submitted';
                $isApproved = false;
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'has_submitted' => !is_null($joining),
                'joining_request' => $joining,
                'status' => $status,
                'is_approved' => $isApproved,
                'can_check_in' => $isApproved,
                'employee' => $isApproved && $employee ? $employee->load(['department', 'designation']) : null,
            ],
        ]);
    }

    /**
     * Admin: List joining requests with filters
     */
    public function adminIndex(Request $request)
    {
        $query = JoiningRequest::with(['user', 'department', 'designation', 'shift', 'reviewer']);

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('proposed_employee_code', 'like', "%{$search}%")
                  ->orWhere('approved_employee_code', 'like', "%{$search}%")
                  ->orWhere('referred_by_name', 'like', "%{$search}%")
                  ->orWhere('referral_employee_code', 'like', "%{$search}%")
                  ->orWhere('aadhaar_number', 'like', "%{$search}%");
            });
        }

        $requests = $query->latest()->get();

        $counts = [
            'total' => JoiningRequest::count(),
            'pending' => JoiningRequest::where('status', 'pending')->count(),
            'approved' => JoiningRequest::where('status', 'approved')->count(),
            'rejected' => JoiningRequest::where('status', 'rejected')->count(),
        ];

        return response()->json([
            'status' => 'success',
            'data' => $requests,
            'counts' => $counts,
        ]);
    }

    /**
     * Admin: Get single joining request details
     */
    public function adminShow($id)
    {
        $joining = JoiningRequest::with(['user', 'department', 'designation', 'shift', 'reviewer'])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $joining,
        ]);
    }

    /**
     * Admin: Approve joining request and provision Employee profile
     */
    public function adminApprove(Request $request, $id)
    {
        $joining = JoiningRequest::with('user')->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'official_joining_date' => 'required|date',
            'department_id' => 'required|exists:departments,id',
            'designation_id' => 'required|exists:designations,id',
            'approved_employee_code' => 'nullable|string|max:50',
            'salary_wages' => 'nullable|numeric|min:0',
            'duty_location' => 'nullable|string|max:255',
            'shift_id' => 'nullable|exists:shifts,id',
            'documents_verified_by' => 'nullable|string|max:255',
            'background_verification' => 'nullable|in:completed,pending',
            'police_verification' => 'nullable|in:completed,pending,na',
            'uniform_issued' => 'nullable|boolean',
            'id_card_issued' => 'nullable|boolean',
            'admin_remarks' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Please provide all required office verification fields.',
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Determine code
            $employeeCode = $request->approved_employee_code ?: ($joining->proposed_employee_code ?: 'SF-' . str_pad($joining->user_id, 4, '0', STR_PAD_LEFT));

            // 1. Update joining request Section 10 "For Office Use Only"
            $joining->update([
                'status' => 'approved',
                'approved_employee_code' => $employeeCode,
                'official_joining_date' => $request->official_joining_date,
                'department_id' => $request->department_id,
                'designation_id' => $request->designation_id,
                'salary_wages' => $request->salary_wages,
                'duty_location' => $request->duty_location ?: $joining->branch_location,
                'shift_id' => $request->shift_id,
                'documents_verified_by' => $request->documents_verified_by ?: $request->user()->name,
                'background_verification' => $request->background_verification ?? 'completed',
                'police_verification' => $request->police_verification ?? 'completed',
                'uniform_issued' => (bool) $request->uniform_issued,
                'id_card_issued' => (bool) $request->id_card_issued,
                'admin_remarks' => $request->admin_remarks,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => Carbon::now(),
            ]);

            // 2. Create or Update Employee record
            $employee = Employee::where('user_id', $joining->user_id)->first();
            $employeeData = [
                'user_id' => $joining->user_id,
                'employee_code' => $employeeCode,
                'department_id' => $request->department_id,
                'designation_id' => $request->designation_id,
                'joining_date' => $request->official_joining_date,
                'employment_status' => 'active',
                'employment_type' => 'full_time',
                'work_location' => $request->duty_location ?: ($joining->branch_location ?: 'Headquarters'),
                'emergency_contact' => $joining->emergency_contact_name,
                'emergency_phone' => $joining->emergency_phone,
                'dob' => $joining->dob,
                'gender' => $joining->gender ? strtolower($joining->gender) : 'other',
                'marital_status' => $joining->marital_status ? strtolower($joining->marital_status) : null,
                'blood_group' => $joining->blood_group,
                'address' => $joining->permanent_address ?: $joining->current_address,
                'pan_number' => $joining->pan_number,
                'aadhaar_number' => $joining->aadhaar_number,
            ];

            if ($employee) {
                $employee->update($employeeData);
            } else {
                $employee = Employee::create($employeeData);
            }

            // 3. Bank Account
            if ($joining->account_number && $joining->ifsc_code) {
                EmployeeBankAccount::updateOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'is_primary' => true,
                    ],
                    [
                        'account_holder_name' => $joining->account_holder_name ?: $joining->full_name,
                        'bank_name' => $joining->bank_name ?: 'Bank',
                        'branch_name' => $joining->branch_name,
                        'account_number' => $joining->account_number,
                        'ifsc_code' => $joining->ifsc_code,
                        'is_verified' => true,
                    ]
                );
            }

            // 4. Shift assignment
            if ($request->filled('shift_id')) {
                DB::table('employee_shifts')->updateOrInsert(
                    [
                        'employee_id' => $employee->id,
                        'shift_id' => $request->shift_id,
                    ],
                    [
                        'effective_from' => $request->official_joining_date,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }

            // 5. Salary Structure Assignment if salary provided
            if ($request->filled('salary_wages') && $request->salary_wages > 0) {
                $salary = (float) $request->salary_wages;
                $defaultStructure = SalaryStructure::where('is_active', true)->first();
                $structureId = $defaultStructure ? $defaultStructure->id : 1;

                $basic = round($salary * 0.50, 2);
                $hra = round($salary * 0.20, 2);
                $special = round($salary - $basic - $hra, 2);
                $pf = round($basic * 0.12, 2);
                $pt = 200.00;
                $net = round($salary - $pf - $pt, 2);

                EmployeeSalaryAssignment::updateOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'is_active' => true,
                    ],
                    [
                        'salary_structure_id' => $structureId,
                        'base_monthly_ctc' => $salary,
                        'basic_salary' => $basic,
                        'hra' => $hra,
                        'special_allowance' => max(0, $special),
                        'pf_employee' => $pf,
                        'pf_employer' => $pf,
                        'esic_employee' => 0,
                        'esic_employer' => 0,
                        'professional_tax' => $pt,
                        'tds_estimate' => 0,
                        'gross_salary' => $salary,
                        'net_salary' => max(0, $net),
                        'effective_from' => $request->official_joining_date,
                    ]
                );
            }

            // 6. Sync User Profile details
            if ($joining->user) {
                $joining->user->update([
                    'name' => $joining->full_name,
                    'phone' => $joining->phone ?: $joining->user->phone,
                ]);
            }

            // 7. Sync KYC Documents to Employee Document Vault
            if (!empty($joining->uploaded_documents) && is_array($joining->uploaded_documents)) {
                foreach ($joining->uploaded_documents as $doc) {
                    EmployeeDocument::updateOrCreate(
                        [
                            'employee_id' => $employee->id,
                            'title' => $doc['title'] ?? 'KYC Document',
                        ],
                        [
                            'document_type' => 'kyc',
                            'file_path' => $doc['file_url'] ?? ($doc['relative_url'] ?? ''),
                            'status' => 'verified',
                        ]
                    );
                }
            }

            AuditLog::log(
                'joining_request.approved',
                'JoiningRequest',
                $joining->id,
                "Approved joining request for {$joining->full_name}. Provisioned Employee ID {$employee->id} (Code: {$employeeCode}). Attendance unlocked."
            );

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => "Joining form approved successfully! Employee profile active. Check-in & check-out is now unlocked for {$joining->full_name}.",
                'data' => [
                    'joining_request' => $joining->fresh(['department', 'designation', 'shift', 'reviewer']),
                    'employee' => $employee->fresh(['department', 'designation', 'bankAccount']),
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to approve joining form: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Admin: Reject joining request
     */
    public function adminReject(Request $request, $id)
    {
        $joining = JoiningRequest::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'admin_remarks' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Please provide the reason for rejection in admin remarks.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $joining->update([
            'status' => 'rejected',
            'admin_remarks' => $request->admin_remarks,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => Carbon::now(),
        ]);

        // If employee profile exists, deactivate
        if ($joining->user && $joining->user->employee) {
            $joining->user->employee->update(['employment_status' => 'inactive']);
        }

        AuditLog::log(
            'joining_request.rejected',
            'JoiningRequest',
            $joining->id,
            "Rejected joining request for {$joining->full_name}. Reason: {$request->admin_remarks}."
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Joining application marked as rejected.',
            'data' => $joining->fresh(['reviewer']),
        ]);
    }

    /**
     * Upload an onboarding KYC document or candidate photo
     */
    public function uploadDocument(Request $request)
    {
        $mimeMap = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ];

        // Support multipart file upload (accept 'file', 'image', 'photo', 'document', 'avatar')
        $file = $request->file('file')
            ?? $request->file('image')
            ?? $request->file('photo')
            ?? $request->file('document')
            ?? $request->file('avatar');

        if ($file) {
            $sizeInBytes = $file->getSize();
            if ($sizeInBytes > 15 * 1024 * 1024) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Uploaded file exceeds the maximum 15MB limit (HTTP 413 Payload Too Large).',
                ], 413);
            }

            $originalName = $file->getClientOriginalName() ?: 'upload_' . time() . '.jpg';
            $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
            $mimeType = $file->getMimeType();
            if (empty($mimeType) || $mimeType === 'application/octet-stream') {
                $mimeType = $mimeMap[$extension] ?? 'application/octet-stream';
            }

            $filename = 'doc_' . time() . '_' . substr(md5(uniqid()), 0, 8) . '.' . $extension;

            // Ensure destination directories exist
            $pubStorageDir = public_path('storage/joining_docs');
            $appStorageDir = storage_path('app/public/joining_docs');
            \Illuminate\Support\Facades\File::ensureDirectoryExists($pubStorageDir);
            \Illuminate\Support\Facades\File::ensureDirectoryExists($appStorageDir);

            $file->storeAs('public/joining_docs', $filename);
            @copy($appStorageDir . '/' . $filename, $pubStorageDir . '/' . $filename);

            $content = @file_get_contents($file->getRealPath());
            $dataUri = null;
            if ($content && $sizeInBytes < 400000) {
                $dataUri = 'data:' . $mimeType . ';base64,' . base64_encode($content);
            }

            $baseUrl = $this->resolveBaseUrl($request);
            $streamingUrl = $baseUrl . '/api/v1/joining/documents/' . $filename;
            $storageUrl = $baseUrl . '/storage/joining_docs/' . $filename;

            $sizeStr = $sizeInBytes > 1048576
                ? round($sizeInBytes / 1048576, 1) . ' MB'
                : max(1, round($sizeInBytes / 1024, 0)) . ' KB';

            return response()->json([
                'status' => 'success',
                'file_name' => $originalName,
                'file_url' => $streamingUrl,
                'storage_url' => $storageUrl,
                'data_uri' => $dataUri,
                'relative_url' => '/api/v1/joining/documents/' . $filename,
                'file_size' => $sizeStr,
                'mime_type' => $mimeType,
                'uploaded_at' => Carbon::now()->toIso8601String(),
            ]);
        }

        // Support base64 upload
        if ($request->filled('base64_data')) {
            $base64 = $request->input('base64_data');
            $originalName = $request->input('file_name', 'kyc_document_' . time() . '.jpg');
            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION) ?: 'jpg');

            if (preg_match('/^data:([a-zA-Z0-9\/\+\-\.]+);base64,/', $base64, $matches)) {
                $mimeType = $matches[1];
                $base64 = substr($base64, strpos($base64, ',') + 1);
            } else {
                $mimeType = $mimeMap[$extension] ?? 'application/octet-stream';
            }

            $decoded = base64_decode($base64);
            $filename = 'doc_' . time() . '_' . substr(md5(uniqid()), 0, 8) . '.' . $extension;

            // Ensure destination directories exist and save to both storage and public
            $pubStorageDir = public_path('storage/joining_docs');
            $appStorageDir = storage_path('app/public/joining_docs');
            \Illuminate\Support\Facades\File::ensureDirectoryExists($pubStorageDir);
            \Illuminate\Support\Facades\File::ensureDirectoryExists($appStorageDir);

            \Illuminate\Support\Facades\Storage::disk('public')->put('joining_docs/' . $filename, $decoded);
            @file_put_contents($pubStorageDir . '/' . $filename, $decoded);

            $sizeInBytes = strlen($decoded);
            $sizeStr = $sizeInBytes > 1048576
                ? round($sizeInBytes / 1048576, 1) . ' MB'
                : max(1, round($sizeInBytes / 1024, 0)) . ' KB';

            $dataUri = null;
            if ($sizeInBytes < 400000) {
                $dataUri = 'data:' . $mimeType . ';base64,' . base64_encode($decoded);
            }

            $baseUrl = $this->resolveBaseUrl($request);
            $streamingUrl = $baseUrl . '/api/v1/joining/documents/' . $filename;
            $storageUrl = $baseUrl . '/storage/joining_docs/' . $filename;

            return response()->json([
                'status' => 'success',
                'file_name' => $originalName,
                'file_url' => $streamingUrl,
                'storage_url' => $storageUrl,
                'data_uri' => $dataUri,
                'relative_url' => '/api/v1/joining/documents/' . $filename,
                'file_size' => $sizeStr,
                'mime_type' => $mimeType,
                'uploaded_at' => Carbon::now()->toIso8601String(),
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'No file or base64 data received. Supported multipart field names: file, image, photo, document, avatar, or JSON base64_data.',
        ], 400);
    }

    /**
     * Resolve the public accessible HTTPS base URL (avoids localhost on Render)
     */
    protected function resolveBaseUrl(Request $request): string
    {
        $appUrl = config('app.url');
        if (!empty($appUrl) && !str_contains($appUrl, 'localhost') && !str_contains($appUrl, '127.0.0.1')) {
            $baseUrl = rtrim($appUrl, '/');
        } else {
            $baseUrl = $request->getSchemeAndHttpHost() ?: 'https://backend-gi0y.onrender.com';
            if (str_starts_with($baseUrl, 'http://') && str_contains($baseUrl, 'onrender.com')) {
                $baseUrl = 'https://' . substr($baseUrl, 7);
            }
        }
        return $baseUrl;
    }

    /**
     * Store candidate photo if provided as data URI or base64 and return clean HTTPS file URL
     */
    protected function resolvePhotoUrl(?string $photoUrl, Request $request): ?string
    {
        if (empty($photoUrl)) {
            return null;
        }

        $photoUrl = trim($photoUrl);

        // If it's already a valid HTTP/HTTPS URL and not a localhost URL, return it directly
        if (filter_var($photoUrl, FILTER_VALIDATE_URL)) {
            if (str_contains($photoUrl, 'localhost') || str_contains($photoUrl, '127.0.0.1')) {
                // Replace localhost with live host
                $path = parse_url($photoUrl, PHP_URL_PATH);
                return $this->resolveBaseUrl($request) . $path;
            }
            return $photoUrl;
        }

        // If it's a data URI or base64 string
        if (str_starts_with($photoUrl, 'data:image') || str_starts_with($photoUrl, 'data:application') || strlen($photoUrl) > 255) {
            try {
                $base64 = $photoUrl;
                $extension = 'jpg';
                if (preg_match('/^data:image\/([a-zA-Z0-9]+);base64,/', $base64, $matches)) {
                    $ext = strtolower($matches[1]);
                    $extension = $ext === 'jpeg' ? 'jpg' : $ext;
                    $base64 = substr($base64, strpos($base64, ',') + 1);
                } elseif (str_contains($base64, ';base64,')) {
                    $base64 = substr($base64, strpos($base64, ',') + 1);
                }

                $decoded = base64_decode($base64);
                if ($decoded !== false && strlen($decoded) > 0) {
                    $filename = 'photo_' . time() . '_' . substr(md5(uniqid()), 0, 8) . '.' . $extension;

                    $pubStorageDir = public_path('storage/joining_docs');
                    $appStorageDir = storage_path('app/public/joining_docs');
                    \Illuminate\Support\Facades\File::ensureDirectoryExists($pubStorageDir);
                    \Illuminate\Support\Facades\File::ensureDirectoryExists($appStorageDir);

                    \Illuminate\Support\Facades\Storage::disk('public')->put('joining_docs/' . $filename, $decoded);
                    @file_put_contents($pubStorageDir . '/' . $filename, $decoded);

                    $baseUrl = $this->resolveBaseUrl($request);
                    return $baseUrl . '/api/v1/joining/documents/' . $filename;
                }
            } catch (\Throwable $e) {
                \Log::warning('Failed to decode candidate photo data URI: ' . $e->getMessage());
            }
        }

        return null;
    }

    /**
     * Clean and sanitize candidate payload before database persistence
     */
    protected function cleanJoiningData(array $data, ?string $resolvedPhotoUrl = null): array
    {
        if (!empty($resolvedPhotoUrl)) {
            $data['photo_url'] = $resolvedPhotoUrl;
        }

        // Clean dates: empty strings to null
        foreach (['dob', 'date_of_joining', 'declaration_date', 'official_joining_date'] as $dateField) {
            if (isset($data[$dateField]) && empty(trim((string)$data[$dateField]))) {
                $data[$dateField] = null;
            }
        }
        if (empty($data['declaration_date'])) {
            $data['declaration_date'] = Carbon::today()->format('Y-m-d');
        }

        // Clean numeric
        if (isset($data['previous_salary'])) {
            $cleanSalary = preg_replace('/[^0-9.]/', '', (string)$data['previous_salary']);
            $data['previous_salary'] = is_numeric($cleanSalary) ? (float)$cleanSalary : null;
        }

        // Clean long strings to prevent VARCHAR length overflows in DB
        $stringLimits = [
            'proposed_employee_code' => 50,
            'proposed_designation' => 100,
            'proposed_department' => 100,
            'branch_location' => 150,
            'reporting_officer' => 150,
            'father_spouse_name' => 150,
            'emergency_contact_name' => 150,
            'emergency_relationship' => 100,
            'emergency_phone' => 20,
            'account_holder_name' => 150,
            'bank_name' => 150,
            'branch_name' => 150,
            'account_number' => 50,
            'ifsc_code' => 20,
            'upi_id' => 100,
            'passing_year' => 20,
            'percentage_grade' => 20,
            'previous_company_name' => 150,
            'previous_designation' => 100,
            'period_of_employment' => 100,
            'signature_name' => 150,
            'declaration_place' => 100,
            'referral_source' => 100,
            'referred_by_name' => 150,
            'referral_employee_code' => 50,
            'referral_phone' => 20,
        ];
        foreach ($stringLimits as $field => $maxLen) {
            if (isset($data[$field]) && is_string($data[$field]) && strlen($data[$field]) > $maxLen) {
                $data[$field] = substr($data[$field], 0, $maxLen);
            }
        }

        // Sanitize uploaded_documents JSON array (strip large data_uri to keep payload compact and fast)
        if (isset($data['uploaded_documents']) && is_array($data['uploaded_documents'])) {
            $cleanDocs = [];
            foreach ($data['uploaded_documents'] as $doc) {
                if (is_array($doc)) {
                    unset($doc['data_uri']);
                    $cleanDocs[] = $doc;
                }
            }
            $data['uploaded_documents'] = $cleanDocs;
        }

        return $data;
    }

    /**
     * Public secure streaming route for joining documents & candidate photographs
     */
    public function serveDocument($filename)
    {
        $cleanFilename = basename($filename);
        $candidates = [
            public_path('storage/joining_docs/' . $cleanFilename),
            storage_path('app/public/joining_docs/' . $cleanFilename),
            storage_path('app/joining_docs/' . $cleanFilename),
        ];

        $targetPath = null;
        foreach ($candidates as $cand) {
            if (file_exists($cand) && is_file($cand)) {
                $targetPath = $cand;
                break;
            }
        }

        if (!$targetPath) {
            return response()->json([
                'status' => 'error',
                'message' => 'Document not found or has expired on server.',
            ], 404);
        }

        $ext = strtolower(pathinfo($cleanFilename, PATHINFO_EXTENSION));
        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ];
        $contentType = $mimeTypes[$ext] ?? 'application/octet-stream';

        return response()->file($targetPath, [
            'Content-Type' => $contentType,
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, OPTIONS',
            'Access-Control-Allow-Headers' => '*',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
