<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\TrackingSession;
use App\Models\EmployeeLocation;
use Carbon\Carbon;
use Exception;

class TrackingService
{
    /**
     * Start a tracking session for an employee
     */
    public function startSession(Employee $employee, ?int $attendanceRecordId = null): TrackingSession
    {
        // Close any dangling sessions
        TrackingSession::where('employee_id', $employee->id)
            ->where('status', 'active')
            ->update([
                'status' => 'completed',
                'ended_at' => Carbon::now(),
            ]);

        return TrackingSession::create([
            'employee_id' => $employee->id,
            'attendance_record_id' => $attendanceRecordId,
            'started_at' => Carbon::now(),
            'status' => 'active',
            'total_distance_km' => 0.00,
        ]);
    }

    /**
     * Record a location ping
     */
    public function recordLocation(Employee $employee, array $data): EmployeeLocation
    {
        $activeSession = TrackingSession::where('employee_id', $employee->id)
            ->where('status', 'active')
            ->latest()
            ->first();

        if (!$activeSession) {
            $activeSession = $this->startSession($employee);
        }

        return EmployeeLocation::create([
            'employee_id' => $employee->id,
            'session_id' => $activeSession->id,
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'accuracy' => $data['accuracy'] ?? null,
            'speed' => $data['speed'] ?? null,
            'battery_level' => $data['battery_level'] ?? null,
            'is_mock' => $data['is_mock'] ?? false,
            'recorded_at' => isset($data['recorded_at']) ? Carbon::parse($data['recorded_at']) : Carbon::now(),
            'received_at' => Carbon::now(),
        ]);
    }

    /**
     * Stop a tracking session
     */
    public function stopSession(Employee $employee): ?TrackingSession
    {
        $activeSession = TrackingSession::where('employee_id', $employee->id)
            ->where('status', 'active')
            ->latest()
            ->first();

        if ($activeSession) {
            $activeSession->update([
                'status' => 'completed',
                'ended_at' => Carbon::now(),
            ]);
        }

        return $activeSession;
    }

    /**
     * Get all active on-duty employees with latest location & status freshness
     */
    public function getActiveTrackingList(): array
    {
        $today = Carbon::today()->toDateString();

        // Strict query: Only employees who are actively checked in and on duty today
        $employees = Employee::with(['user', 'department', 'designation'])
            ->whereHas('user', function ($q) {
                $q->where('status', 'active');
            })
            ->whereHas('attendanceRecords', function ($q) use ($today) {
                $q->whereDate('work_date', $today)
                  ->whereNotNull('check_in_at')
                  ->whereNull('check_out_at');
            })
            ->get();

        $results = [];

        foreach ($employees as $emp) {
            $attendance = \App\Models\AttendanceRecord::where('employee_id', $emp->id)
                ->whereDate('work_date', $today)
                ->whereNotNull('check_in_at')
                ->whereNull('check_out_at')
                ->first();

            if (!$attendance) {
                continue;
            }

            $activeSession = TrackingSession::where('employee_id', $emp->id)
                ->where('status', 'active')
                ->latest()
                ->first();

            $latestLoc = EmployeeLocation::where('employee_id', $emp->id)
                ->latest('recorded_at')
                ->first();

            $lat = $latestLoc?->latitude ?? $attendance->check_in_lat;
            $lng = $latestLoc?->longitude ?? $attendance->check_in_lng;

            if ($lat === null || $lng === null) {
                continue;
            }

            $recordedTime = $latestLoc?->recorded_at ?? $attendance->check_in_at;
            $minutesAgo = $recordedTime ? max(0, (int) round(Carbon::parse($recordedTime)->diffInMinutes(Carbon::now()))) : 0;

            $results[] = [
                'session_id' => $activeSession?->id ?? 0,
                'employee_id' => $emp->id,
                'employee_code' => $emp->employee_code,
                'name' => $emp->user->name,
                'department' => $emp->department?->name ?? 'Field Services',
                'designation' => $emp->designation?->name ?? 'Field Engineer',
                'phone' => $emp->user->phone ?? $emp->emergency_phone ?? '',
                'latitude' => (float) $lat,
                'longitude' => (float) $lng,
                'accuracy' => (float) ($latestLoc?->accuracy ?? 10.0),
                'battery_level' => (int) ($latestLoc?->battery_level ?? 90),
                'speed' => (float) ($latestLoc?->speed ?? 0.0),
                'recorded_at' => $recordedTime ? Carbon::parse($recordedTime)->toIso8601String() : null,
                'minutes_ago' => $minutesAgo,
                'status' => 'online',
                'is_checked_in' => true,
            ];
        }

        return $results;
    }
}
