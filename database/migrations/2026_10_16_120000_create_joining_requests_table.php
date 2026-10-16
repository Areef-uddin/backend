<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('joining_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');

            // 1. Employee & Joining Details (Proposed)
            $table->string('proposed_employee_code')->nullable();
            $table->date('date_of_joining')->nullable();
            $table->string('proposed_designation')->nullable();
            $table->string('proposed_department')->nullable();
            $table->string('branch_location')->nullable();
            $table->string('reporting_officer')->nullable();

            // 2. Personal Details
            $table->string('full_name');
            $table->string('father_spouse_name')->nullable();
            $table->date('dob')->nullable();
            $table->string('gender')->nullable(); // male, female, other
            $table->string('marital_status')->nullable();
            $table->string('blood_group')->nullable();
            $table->string('phone');
            $table->string('alternate_phone')->nullable();
            $table->string('email')->nullable();
            $table->string('aadhaar_number')->nullable();
            $table->string('pan_number')->nullable();
            $table->string('emergency_contact_name')->nullable();

            // 3. Address Details
            $table->text('permanent_address')->nullable();
            $table->text('current_address')->nullable();

            // 4. Emergency Contact Details
            $table->string('emergency_relationship')->nullable();
            $table->string('emergency_phone')->nullable();
            $table->text('emergency_address')->nullable();

            // 5. Bank Details
            $table->string('account_holder_name')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('branch_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('ifsc_code')->nullable();
            $table->string('upi_id')->nullable();

            // 6. Educational Qualification
            $table->string('qualification')->nullable();
            $table->string('institution_board')->nullable();
            $table->string('passing_year')->nullable();
            $table->string('percentage_grade')->nullable();

            // 7. Previous Employment
            $table->string('previous_company_name')->nullable();
            $table->string('previous_designation')->nullable();
            $table->string('period_of_employment')->nullable();
            $table->string('reason_for_leaving')->nullable();
            $table->decimal('previous_salary', 10, 2)->nullable();

            // 8. Documents Submitted (JSON list of submitted doc types)
            $table->json('documents_submitted')->nullable();

            // 9. Employee Declaration
            $table->boolean('declaration_accepted')->default(true);
            $table->string('signature_name')->nullable();
            $table->date('declaration_date')->nullable();
            $table->string('declaration_place')->nullable();

            // 10. For Office Use Only (HR Admin Approval Section)
            $table->string('approved_employee_code')->nullable();
            $table->date('official_joining_date')->nullable();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('designation_id')->nullable()->constrained('designations')->nullOnDelete();
            $table->decimal('salary_wages', 10, 2)->nullable();
            $table->string('duty_location')->nullable();
            $table->foreignId('shift_id')->nullable()->constrained('shifts')->nullOnDelete();
            $table->string('documents_verified_by')->nullable();
            $table->enum('background_verification', ['completed', 'pending'])->default('pending');
            $table->enum('police_verification', ['completed', 'pending', 'na'])->default('pending');
            $table->boolean('uniform_issued')->default(false);
            $table->boolean('id_card_issued')->default(false);
            $table->text('admin_remarks')->nullable();

            // Review meta
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('joining_requests');
    }
};
