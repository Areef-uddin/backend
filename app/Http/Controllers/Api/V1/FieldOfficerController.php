<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\JoiningRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;

class FieldOfficerController extends Controller
{
    /**
     * List all field officers with duty status & referral metrics
     */
    public function index()
    {
        $officerCodes = ['SF-FO-101', 'SF-FO-102', 'SF-FO-103'];
        $today = Carbon::today()->format('Y-m-d');

        $employees = Employee::with(['user', 'designation', 'department'])
            ->whereIn('employee_code', $officerCodes)
            ->get();

        if ($employees->isEmpty()) {
            try {
                (new \Database\Seeders\FieldOfficerSeeder())->run();
                $employees = Employee::with(['user', 'designation', 'department'])
                    ->whereIn('employee_code', $officerCodes)
                    ->get();
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('FieldOfficerSeeder auto-seed failed: ' . $e->getMessage());
            }
        }

        $officers = [];

        foreach ($employees as $emp) {
            $user = $emp->user;
            $code = $emp->employee_code;
            $name = $user ? $user->name : 'Field Officer';

            // Check today's attendance
            $att = AttendanceRecord::where('employee_id', $emp->id)
                ->where('work_date', $today)
                ->first();

            $isPunchedIn = $att && !empty($att->check_in_at) && empty($att->check_out_at);
            $checkInTime = $att && $att->check_in_at ? Carbon::parse($att->check_in_at)->format('h:i A') : null;
            $checkOutTime = $att && $att->check_out_at ? Carbon::parse($att->check_out_at)->format('h:i A') : null;

            // Referrals count
            $referralsQuery = JoiningRequest::where(function ($q) use ($code, $name) {
                $q->where('referral_employee_code', $code)
                  ->orWhere('referred_by_name', $name);
            });

            $totalReferrals = (clone $referralsQuery)->count();
            $approvedReferrals = (clone $referralsQuery)->where('status', 'approved')->count();
            $pendingReferrals = (clone $referralsQuery)->where('status', 'pending')->count();

            $officers[] = [
                'id' => $emp->id,
                'employee_code' => $code,
                'name' => $name,
                'email' => $user ? $user->email : '',
                'phone' => $emp->phone ?: ($user ? $user->phone : ''),
                'designation' => $emp->designation ? $emp->designation->name : 'Field Operations Officer',
                'department' => $emp->department ? $emp->department->name : 'Operations & Field Services',
                'is_punched_in' => $isPunchedIn,
                'check_in_time' => $checkInTime,
                'check_out_time' => $checkOutTime,
                'location' => $att ? ($att->check_in_address ?: 'Outer Ring Road HQ, Bangalore') : 'Outer Ring Road HQ, Bangalore',
                'total_referrals' => $totalReferrals,
                'approved_referrals' => $approvedReferrals,
                'pending_referrals' => $pendingReferrals,
            ];
        }

        return response()->json([
            'status' => 'success',
            'data' => $officers,
        ]);
    }

    /**
     * Get single officer details and referral history
     */
    public function referrals($code)
    {
        $emp = Employee::with(['user', 'designation'])->where('employee_code', $code)->first();
        if (!$emp) {
            try {
                (new \Database\Seeders\FieldOfficerSeeder())->run();
                $emp = Employee::with(['user', 'designation'])->where('employee_code', $code)->first();
            } catch (\Throwable $e) {}
        }
        if (!$emp) {
            return response()->json(['status' => 'error', 'message' => 'Field Officer not found'], 404);
        }

        $name = $emp->user ? $emp->user->name : '';

        $referrals = JoiningRequest::where(function ($q) use ($code, $name) {
            $q->where('referral_employee_code', $code)
              ->orWhere('referred_by_name', $name);
        })
        ->latest()
        ->get();

        $today = Carbon::today()->format('Y-m-d');
        $att = AttendanceRecord::where('employee_id', $emp->id)
            ->where('work_date', $today)
            ->first();

        $isPunchedIn = $att && !empty($att->check_in_at) && empty($att->check_out_at);

        return response()->json([
            'status' => 'success',
            'data' => [
                'officer' => [
                    'id' => $emp->id,
                    'employee_code' => $code,
                    'name' => $name,
                    'phone' => $emp->phone,
                    'designation' => $emp->designation ? $emp->designation->name : 'Field Operations Officer',
                    'is_punched_in' => $isPunchedIn,
                    'check_in_time' => $att && $att->check_in_at ? Carbon::parse($att->check_in_at)->format('h:i A') : null,
                    'check_out_time' => $att && $att->check_out_at ? Carbon::parse($att->check_out_at)->format('h:i A') : null,
                    'location' => $att ? ($att->check_in_address ?: 'Outer Ring Road HQ, Bangalore') : 'Outer Ring Road HQ, Bangalore',
                ],
                'referrals' => $referrals,
                'counts' => [
                    'total' => $referrals->count(),
                    'approved' => $referrals->where('status', 'approved')->count(),
                    'pending' => $referrals->where('status', 'pending')->count(),
                ],
            ],
        ]);
    }

    /**
     * Punch In / Punch Out for a Field Officer
     */
    public function punch(Request $request, $code)
    {
        $emp = Employee::where('employee_code', $code)->first();
        if (!$emp) {
            try {
                (new \Database\Seeders\FieldOfficerSeeder())->run();
                $emp = Employee::where('employee_code', $code)->first();
            } catch (\Throwable $e) {}
        }
        if (!$emp) {
            return response()->json(['status' => 'error', 'message' => 'Officer not found'], 404);
        }

        $today = Carbon::today()->format('Y-m-d');
        $now = Carbon::now();

        $att = AttendanceRecord::firstOrNew([
            'employee_id' => $emp->id,
            'work_date' => $today,
        ]);

        $action = $request->input('action'); // 'in', 'out', or 'toggle'

        if (!$action || $action === 'toggle') {
            $action = ($att->exists && !empty($att->check_in_at) && empty($att->check_out_at)) ? 'out' : 'in';
        }

        if ($action === 'in') {
            $att->check_in_at = $now;
            $att->check_out_at = null;
            $att->status = 'present';
            $att->check_in_address = $request->input('address', 'Outer Ring Road HQ, Bangalore');
            $att->check_in_lat = $request->input('latitude', 12.926031);
            $att->check_in_lng = $request->input('longitude', 77.676246);
            $att->is_verified = true;
            $att->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Punched IN successfully at ' . $now->format('h:i A'),
                'is_punched_in' => true,
                'check_in_time' => $now->format('h:i A'),
                'check_out_time' => null,
            ]);
        } else {
            $att->check_out_at = $now;
            if (!$att->check_in_at) {
                $att->check_in_at = $now->copy()->subHours(8);
            }
            $att->check_out_address = $request->input('address', 'Outer Ring Road HQ, Bangalore');
            $att->check_out_lat = $request->input('latitude', 12.926031);
            $att->check_out_lng = $request->input('longitude', 77.676246);
            $att->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Punched OUT successfully at ' . $now->format('h:i A'),
                'is_punched_in' => false,
                'check_in_time' => $att->check_in_at ? Carbon::parse($att->check_in_at)->format('h:i A') : null,
                'check_out_time' => $now->format('h:i A'),
            ]);
        }
    }
}
