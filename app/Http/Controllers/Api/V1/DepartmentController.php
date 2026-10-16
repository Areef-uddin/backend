<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Designation;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index()
    {
        $departments = Department::withCount('employees')
            ->where('is_active', true)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $departments,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:departments,name',
            'code' => 'required|string|unique:departments,code',
            'description' => 'nullable|string',
        ]);

        $dept = Department::create($request->all());

        return response()->json([
            'status' => 'success',
            'data' => $dept,
        ], 201);
    }
}
