<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeLocation;
use App\Services\TrackingService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TrackingController extends Controller
{
    protected TrackingService $trackingService;

    public function __construct(TrackingService $trackingService)
    {
        $this->trackingService = $trackingService;
    }

    /**
     * Start live tracking session
     */
    public function startSession(Request $request)
    {
        $employee = $request->user()->employee;
        if (!$employee) {
            return response()->json(['message' => 'Employee profile not found.'], 404);
        }

        $session = $this->trackingService->startSession($employee, $request->attendance_record_id);

        return response()->json([
            'status' => 'success',
            'message' => 'Tracking session started.',
            'data' => $session,
        ]);
    }

    /**
     * Record employee location update
     */
    public function recordLocation(Request $request)
    {
        $employee = $request->user()->employee;
        if (!$employee) {
            return response()->json(['message' => 'Employee profile not found.'], 404);
        }

        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'accuracy' => 'nullable|numeric',
            'speed' => 'nullable|numeric',
            'battery_level' => 'nullable|integer',
            'is_mock' => 'nullable|boolean',
        ]);

        $location = $this->trackingService->recordLocation($employee, $request->all());

        return response()->json([
            'status' => 'success',
            'data' => $location,
        ]);
    }

    /**
     * Stop tracking session
     */
    public function stopSession(Request $request)
    {
        $employee = $request->user()->employee;
        if (!$employee) {
            return response()->json(['message' => 'Employee profile not found.'], 404);
        }

        $session = $this->trackingService->stopSession($employee);

        return response()->json([
            'status' => 'success',
            'message' => 'Tracking session ended.',
            'data' => $session,
        ]);
    }

    /**
     * Admin view: list active on-duty employees for live tracking map
     */
    public function activeList()
    {
        $list = $this->trackingService->getActiveTrackingList();

        return response()->json([
            'status' => 'success',
            'count' => count($list),
            'data' => $list,
        ]);
    }

    /**
     * Admin view: employee location breadcrumb history
     */
    public function employeeHistory(Request $request, $employeeId)
    {
        $date = $request->date ?? Carbon::today()->format('Y-m-d');

        $locations = EmployeeLocation::where('employee_id', $employeeId)
            ->whereDate('recorded_at', $date)
            ->orderBy('recorded_at', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'date' => $date,
            'count' => $locations->count(),
            'data' => $locations,
        ]);
    }
}
