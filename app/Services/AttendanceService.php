<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\OfficeLocation;
use Carbon\Carbon;
use Exception;

class AttendanceService
{
    /**
     * Check-in for an employee
     */
    public function checkIn(Employee $employee, array $data): AttendanceRecord
    {
        $today = Carbon::today()->format('Y-m-d');

        $record = AttendanceRecord::where('employee_id', $employee->id)
            ->whereDate('work_date', $today)
            ->first();

        $lat = $data['latitude'] ?? null;
        $lng = $data['longitude'] ?? null;
        $address = $data['address'] ?? 'Mobile GPS Check-in';
        $now = Carbon::now();

        // If employee is already checked in and currently on duty, update location and return gracefully
        if ($record && $record->check_in_at && !$record->check_out_at) {
            if ($lat !== null && $lng !== null) {
                $record->update([
                    'check_in_lat' => $lat,
                    'check_in_lng' => $lng,
                    'check_in_address' => $address,
                ]);
            }
            return $record;
        }

        $shift = $employee->currentShift();

        $lateMinutes = 0;
        $status = 'present';

        if ($shift) {
            $shiftStartTime = Carbon::parse($shift->start_time);
            $graceTime = $shiftStartTime->copy()->addMinutes($shift->grace_period_minutes);

            if ($now->greaterThan($graceTime)) {
                $lateMinutes = $now->diffInMinutes($shiftStartTime);
                $status = 'late';
            }
        }

        if (!$record) {
            $record = AttendanceRecord::create([
                'employee_id' => $employee->id,
                'shift_id' => $shift?->id,
                'work_date' => $today,
                'check_in_at' => $now,
                'check_in_lat' => $lat,
                'check_in_lng' => $lng,
                'check_in_address' => $address,
                'status' => $status,
                'late_minutes' => $lateMinutes,
                'is_verified' => true,
                'verification_method' => 'gps',
            ]);
        } else {
            // Re-starting shift (e.g. after testing or second check-in)
            $record->update([
                'check_in_at' => $now,
                'check_out_at' => null,
                'check_in_lat' => $lat,
                'check_in_lng' => $lng,
                'check_in_address' => $address,
                'status' => $status,
                'total_minutes' => 0,
                'late_minutes' => $lateMinutes,
                'is_verified' => true,
                'verification_method' => 'gps',
            ]);
        }

        return $record;
    }

    /**
     * Check-out for an employee
     */
    public function checkOut(Employee $employee, array $data): AttendanceRecord
    {
        $today = Carbon::today()->format('Y-m-d');

        $record = AttendanceRecord::where('employee_id', $employee->id)
            ->whereDate('work_date', $today)
            ->first();

        // If no check-in record exists, auto-create one from 1 hour ago so checkout works reliably
        $now = Carbon::now();
        if (!$record || !$record->check_in_at) {
            $record = AttendanceRecord::create([
                'employee_id' => $employee->id,
                'shift_id' => $employee->currentShift()?->id,
                'work_date' => $today,
                'check_in_at' => $now->copy()->subHours(1),
                'check_in_lat' => $data['latitude'] ?? null,
                'check_in_lng' => $data['longitude'] ?? null,
                'check_in_address' => 'Auto Check-in',
                'status' => 'present',
                'is_verified' => true,
                'verification_method' => 'gps',
            ]);
        }

        // If already checked out, update coordinates if given and return gracefully
        if ($record->check_out_at) {
            if (!empty($data['latitude']) && !empty($data['longitude'])) {
                $record->update([
                    'check_out_lat' => $data['latitude'],
                    'check_out_lng' => $data['longitude'],
                    'check_out_address' => $data['address'] ?? $record->check_out_address,
                ]);
            }
            return $record;
        }

        $checkInAt = Carbon::parse($record->check_in_at);
        $totalMinutes = $now->diffInMinutes($checkInAt);

        $shift = $employee->currentShift();
        $halfDayMinutes = ($shift?->half_day_hours ?? 4) * 60;
        $fullDayMinutes = ($shift?->full_day_hours ?? 8) * 60;

        $status = $record->status;
        if ($totalMinutes < $halfDayMinutes) {
            $status = 'half_day';
        }

        $overtimeMinutes = max(0, $totalMinutes - $fullDayMinutes);

        $lat = $data['latitude'] ?? null;
        $lng = $data['longitude'] ?? null;
        $address = $data['address'] ?? 'Mobile GPS Check-out';

        $record->update([
            'check_out_at' => $now,
            'check_out_lat' => $lat,
            'check_out_lng' => $lng,
            'check_out_address' => $address,
            'status' => $status,
            'total_minutes' => $totalMinutes,
            'overtime_minutes' => $overtimeMinutes,
        ]);

        return $record;
    }
}
