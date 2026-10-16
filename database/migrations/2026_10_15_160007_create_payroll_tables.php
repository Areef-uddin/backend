<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->string('payroll_month'); // e.g. "2026-10"
            $table->integer('year')->default(2026);
            $table->integer('month')->default(10);
            $table->enum('status', ['draft', 'calculated', 'submitted', 'approved', 'finalized', 'published'])->default('draft');
            $table->integer('total_employees')->default(0);
            $table->decimal('total_gross', 14, 2)->default(0.00);
            $table->decimal('total_deductions', 14, 2)->default(0.00);
            $table->decimal('total_net_pay', 14, 2)->default(0.00);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['payroll_month']);
        });

        Schema::create('payroll_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->integer('total_days_in_month')->default(31);
            $table->decimal('payable_days', 4, 1)->default(31);
            $table->decimal('present_days', 4, 1)->default(0);
            $table->decimal('leave_days', 4, 1)->default(0);
            $table->decimal('lop_days', 4, 1)->default(0); // Loss Of Pay days
            $table->decimal('basic_pay', 12, 2)->default(0.00);
            $table->decimal('hra', 12, 2)->default(0.00);
            $table->decimal('special_allowance', 12, 2)->default(0.00);
            $table->decimal('overtime_pay', 12, 2)->default(0.00);
            $table->decimal('gross_earnings', 12, 2)->default(0.00);
            $table->decimal('lop_deduction', 12, 2)->default(0.00);
            $table->decimal('pf_deduction', 12, 2)->default(0.00);
            $table->decimal('esic_deduction', 12, 2)->default(0.00);
            $table->decimal('pt_deduction', 12, 2)->default(0.00);
            $table->decimal('tds_deduction', 12, 2)->default(0.00);
            $table->decimal('other_deductions', 12, 2)->default(0.00);
            $table->decimal('total_deductions', 12, 2)->default(0.00);
            $table->decimal('net_pay', 12, 2)->default(0.00);
            $table->enum('payment_status', ['pending', 'processing', 'paid', 'failed'])->default('pending');
            $table->string('payment_reference')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->unique(['payroll_run_id', 'employee_id']);
        });

        Schema::create('payroll_item_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_item_id')->constrained()->cascadeOnDelete();
            $table->string('component_name');
            $table->string('component_code');
            $table->enum('component_type', ['earning', 'deduction']);
            $table->decimal('amount', 12, 2);
            $table->timestamps();
        });

        Schema::create('payslips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('payslip_number')->unique();
            $table->string('payroll_month');
            $table->string('file_path')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->boolean('is_viewed')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payslips');
        Schema::dropIfExists('payroll_item_components');
        Schema::dropIfExists('payroll_items');
        Schema::dropIfExists('payroll_runs');
    }
};
