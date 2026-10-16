<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_structures', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('salary_components', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->enum('type', ['earning', 'deduction']);
            $table->enum('calculation_type', ['fixed', 'percentage'])->default('fixed');
            $table->string('percentage_of')->nullable(); // e.g. 'basic'
            $table->boolean('is_taxable')->default(true);
            $table->boolean('is_statutory')->default(false);
            $table->timestamps();
        });

        Schema::create('salary_structure_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salary_structure_id')->constrained()->cascadeOnDelete();
            $table->foreignId('salary_component_id')->constrained()->cascadeOnDelete();
            $table->decimal('default_amount', 12, 2)->default(0.00);
            $table->decimal('default_percentage', 5, 2)->default(0.00);
            $table->timestamps();
        });

        Schema::create('employee_salary_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('salary_structure_id')->constrained()->cascadeOnDelete();
            $table->decimal('base_monthly_ctc', 12, 2);
            $table->decimal('basic_salary', 12, 2);
            $table->decimal('hra', 12, 2)->default(0.00);
            $table->decimal('special_allowance', 12, 2)->default(0.00);
            $table->decimal('pf_employee', 12, 2)->default(0.00);
            $table->decimal('pf_employer', 12, 2)->default(0.00);
            $table->decimal('esic_employee', 12, 2)->default(0.00);
            $table->decimal('esic_employer', 12, 2)->default(0.00);
            $table->decimal('professional_tax', 12, 2)->default(200.00);
            $table->decimal('tds_estimate', 12, 2)->default(0.00);
            $table->decimal('gross_salary', 12, 2);
            $table->decimal('net_salary', 12, 2);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_salary_assignments');
        Schema::dropIfExists('salary_structure_components');
        Schema::dropIfExists('salary_components');
        Schema::dropIfExists('salary_structures');
    }
};
