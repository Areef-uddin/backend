<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JoiningRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'status',
        // 1. Employee & Joining Details (Proposed)
        'proposed_employee_code',
        'date_of_joining',
        'proposed_designation',
        'proposed_department',
        'branch_location',
        'reporting_officer',
        // 2. Personal Details
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
        // 3. Address Details
        'permanent_address',
        'current_address',
        // 4. Emergency Contact
        'emergency_relationship',
        'emergency_phone',
        'emergency_address',
        // 5. Bank Details
        'account_holder_name',
        'bank_name',
        'branch_name',
        'account_number',
        'ifsc_code',
        'upi_id',
        // 6. Educational Qualification
        'qualification',
        'institution_board',
        'passing_year',
        'percentage_grade',
        // 7. Previous Employment
        'previous_company_name',
        'previous_designation',
        'period_of_employment',
        'reason_for_leaving',
        'previous_salary',
        // 8. Documents Submitted
        'documents_submitted',
        'uploaded_documents',
        'photo_url',
        // Referral Details
        'referral_source',
        'referred_by_name',
        'referral_employee_code',
        'referral_phone',
        'referral_notes',
        // 9. Employee Declaration
        'declaration_accepted',
        'signature_name',
        'declaration_date',
        'declaration_place',
        // 10. For Office Use Only
        'approved_employee_code',
        'official_joining_date',
        'department_id',
        'designation_id',
        'salary_wages',
        'duty_location',
        'shift_id',
        'documents_verified_by',
        'background_verification',
        'police_verification',
        'uniform_issued',
        'id_card_issued',
        'admin_remarks',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'date_of_joining' => 'date',
        'dob' => 'date',
        'declaration_date' => 'date',
        'official_joining_date' => 'date',
        'reviewed_at' => 'datetime',
        'documents_submitted' => 'array',
        'uploaded_documents' => 'array',
        'declaration_accepted' => 'boolean',
        'uniform_issued' => 'boolean',
        'id_card_issued' => 'boolean',
        'previous_salary' => 'decimal:2',
        'salary_wages' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function designation()
    {
        return $this->belongsTo(Designation::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
