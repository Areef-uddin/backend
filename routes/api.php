<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\EmployeeController;
use App\Http\Controllers\Api\V1\DepartmentController;
use App\Http\Controllers\Api\V1\DesignationController;
use App\Http\Controllers\Api\V1\AttendanceController;
use App\Http\Controllers\Api\V1\TrackingController;
use App\Http\Controllers\Api\V1\LeaveController;
use App\Http\Controllers\Api\V1\SalaryStructureController;
use App\Http\Controllers\Api\V1\PayrollController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\AuditLogController;
use App\Http\Controllers\Api\V1\JoiningRequestController;

/*
|--------------------------------------------------------------------------
| API Routes - V1
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // Public authentication & candidate onboarding routes
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/joining/register-and-submit', [JoiningRequestController::class, 'registerAndSubmit']);
    Route::get('/joining/options', [JoiningRequestController::class, 'options']);
    Route::post('/joining/upload-doc', [JoiningRequestController::class, 'uploadDocument']);

    // Authenticated routes
    Route::middleware('auth:sanctum')->group(function () {

        // Auth & Profile
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        // Dashboard & Metrics
        Route::get('/admin/dashboard', [DashboardController::class, 'kpi']);
        Route::get('/admin/audit-logs', [AuditLogController::class, 'index']);

        // Employees & Organization
        Route::get('/employees', [EmployeeController::class, 'index']);
        Route::post('/employees', [EmployeeController::class, 'store']);
        Route::get('/employees/{id}', [EmployeeController::class, 'show']);
        Route::patch('/employees/{id}', [EmployeeController::class, 'update']);
        Route::post('/employees/{id}/deactivate', [EmployeeController::class, 'deactivate']);

        Route::get('/departments', [DepartmentController::class, 'index']);
        Route::post('/departments', [DepartmentController::class, 'store']);
        Route::get('/designations', [DesignationController::class, 'index']);
        Route::post('/designations', [DesignationController::class, 'store']);

        // Attendance (Mobile & Web)
        Route::get('/attendance/today', [AttendanceController::class, 'today']);
        Route::post('/attendance/check-in', [AttendanceController::class, 'checkIn']);
        Route::post('/attendance/check-out', [AttendanceController::class, 'checkOut']);
        Route::post('/attendance/reset-today', [AttendanceController::class, 'resetToday']);
        Route::get('/attendance/history', [AttendanceController::class, 'history']);
        Route::get('/attendance/summary', [AttendanceController::class, 'monthlySummary']);
        Route::get('/admin/attendance', [AttendanceController::class, 'adminList']);
        Route::post('/attendance/corrections', [AttendanceController::class, 'submitCorrection']);
        Route::post('/admin/attendance/corrections/{id}/review', [AttendanceController::class, 'reviewCorrection']);

        // Live Tracking (Mobile pings & Admin map)
        Route::post('/tracking/sessions/start', [TrackingController::class, 'startSession']);
        Route::post('/tracking/locations', [TrackingController::class, 'recordLocation']);
        Route::post('/tracking/sessions/stop', [TrackingController::class, 'stopSession']);
        Route::get('/admin/tracking/active', [TrackingController::class, 'activeList']);
        Route::get('/admin/tracking/{employeeId}/history', [TrackingController::class, 'employeeHistory']);

        // Leave Management
        Route::get('/leave/types', [LeaveController::class, 'types']);
        Route::get('/leave/balances', [LeaveController::class, 'balances']);
        Route::get('/leave/requests', [LeaveController::class, 'myRequests']);
        Route::post('/leave/requests', [LeaveController::class, 'apply']);
        Route::get('/admin/leave/requests', [LeaveController::class, 'adminList']);
        Route::post('/leave/requests/{id}/approve', [LeaveController::class, 'approve']);
        Route::post('/leave/requests/{id}/reject', [LeaveController::class, 'reject']);

        // Salary Structures
        Route::get('/salary/structures', [SalaryStructureController::class, 'index']);
        Route::get('/salary/my-structure', [SalaryStructureController::class, 'myStructure']);

        // Payroll Administration & Engine
        Route::get('/admin/payroll-runs', [PayrollController::class, 'index']);
        Route::post('/admin/payroll-runs', [PayrollController::class, 'create']);
        Route::post('/admin/payroll-runs/{id}/calculate', [PayrollController::class, 'calculate']);
        Route::get('/admin/payroll-runs/{id}/preview', [PayrollController::class, 'preview']);
        Route::post('/admin/payroll-runs/{id}/approve', [PayrollController::class, 'approve']);
        Route::post('/admin/payroll-runs/{id}/finalize', [PayrollController::class, 'finalize']);
        Route::post('/admin/payroll-runs/{id}/publish-payslips', [PayrollController::class, 'publishPayslips']);

        // Payslips
        Route::get('/payslips', [PayrollController::class, 'myPayslips']);
        Route::get('/payslips/{id}/download', [PayrollController::class, 'downloadPayslip']);

        // Starforce Employee Joining Form (Mobile & Admin)
        Route::get('/joining/options', [JoiningRequestController::class, 'options']);
        Route::get('/joining/my-status', [JoiningRequestController::class, 'myStatus']);
        Route::post('/joining/submit', [JoiningRequestController::class, 'store']);
        Route::get('/admin/joining-requests', [JoiningRequestController::class, 'adminIndex']);
        Route::get('/admin/joining-requests/{id}', [JoiningRequestController::class, 'adminShow']);
        Route::post('/admin/joining-requests/{id}/approve', [JoiningRequestController::class, 'adminApprove']);
        Route::post('/admin/joining-requests/{id}/reject', [JoiningRequestController::class, 'adminReject']);
    });
});

