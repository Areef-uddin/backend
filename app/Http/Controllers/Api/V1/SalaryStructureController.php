<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\EmployeeSalaryAssignment;
use App\Models\SalaryComponent;
use App\Models\SalaryStructure;
use Illuminate\Http\Request;

class SalaryStructureController extends Controller
{
    /**
     * List all salary structures with components
     */
    public function index()
    {
        $structures = SalaryStructure::with('components')
            ->where('is_active', true)
            ->get();

        $components = SalaryComponent::all();

        return response()->json([
            'status' => 'success',
            'structures' => $structures,
            'components' => $components,
        ]);
    }

    /**
     * Get current employee's salary structure & assignment
     */
    public function myStructure(Request $request)
    {
        $employee = $request->user()->employee;
        if (!$employee) {
            return response()->json(['message' => 'Employee profile not found.'], 404);
        }

        $assignment = EmployeeSalaryAssignment::with('structure.components')
            ->where('employee_id', $employee->id)
            ->where('is_active', true)
            ->first();

        return response()->json([
            'status' => 'success',
            'data' => $assignment,
        ]);
    }
}
