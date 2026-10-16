<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Designation;
use Illuminate\Http\Request;

class DesignationController extends Controller
{
    public function index(Request $request)
    {
        $query = Designation::with('department')->where('is_active', true);

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        return response()->json([
            'status' => 'success',
            'data' => $query->get(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'department_id' => 'nullable|exists:departments,id',
            'min_salary' => 'nullable|numeric',
            'max_salary' => 'nullable|numeric',
        ]);

        $designation = Designation::create($request->all());

        return response()->json([
            'status' => 'success',
            'data' => $designation,
        ], 201);
    }
}
