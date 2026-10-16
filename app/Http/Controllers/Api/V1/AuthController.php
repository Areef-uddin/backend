<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Role;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * User registration for Employee mobile app
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'phone' => 'nullable|string|max:20',
            'department_id' => 'nullable|integer',
            'designation_id' => 'nullable|integer',
            'device_name' => 'nullable|string',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'status' => 'active',
        ]);

        // Assign Employee role
        $role = Role::where('name', 'Employee')->first();
        if ($role) {
            $user->roles()->attach($role->id);
        }

        $deviceName = $request->device_name ?? 'flutter_mobile_app';
        $token = $user->createToken($deviceName)->plainTextToken;

        AuditLog::log('auth.register', 'User', $user->id, "Candidate {$user->name} ({$user->email}) registered account. Starforce Joining Form required.");

        $user->load(['roles.permissions']);

        return response()->json([
            'status' => 'success',
            'message' => 'Account registered successfully. Please submit your Starforce Employee Joining Form.',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'status' => $user->status,
                'avatar' => $user->avatar,
                'roles' => $user->roles->pluck('name')->toArray(),
                'permissions' => $user->roles->flatMap->permissions->pluck('name')->unique()->values()->toArray(),
                'employee' => null,
            ],
        ], 201);
    }
    /**
     * User login for Admin & Mobile apps
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'device_name' => 'nullable|string',
        ]);

        $user = User::with(['roles.permissions', 'employee.department', 'employee.designation'])
            ->where('email', $request->email)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials do not match our records.'],
            ]);
        }

        if ($user->status !== 'active') {
            return response()->json([
                'message' => 'Your account has been deactivated. Please contact HR.',
            ], 403);
        }

        $user->update(['last_login_at' => now()]);

        $deviceName = $request->device_name ?? 'web-or-mobile';
        $token = $user->createToken($deviceName)->plainTextToken;

        $roleNames = $user->roles->pluck('name')->toArray();
        $permissions = $user->roles->flatMap->permissions->pluck('name')->unique()->values()->toArray();

        AuditLog::log('auth.login', 'User', $user->id, "User {$user->name} ({$user->email}) logged in successfully.");

        return response()->json([
            'status' => 'success',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'status' => $user->status,
                'avatar' => $user->avatar,
                'roles' => $roleNames,
                'permissions' => $permissions,
                'employee' => $user->employee ? [
                    'id' => $user->employee->id,
                    'employee_code' => $user->employee->employee_code,
                    'department' => $user->employee->department?->name,
                    'designation' => $user->employee->designation?->name,
                    'work_location' => $user->employee->work_location,
                    'joining_date' => $user->employee->joining_date?->format('Y-m-d'),
                ] : null,
            ],
        ]);
    }

    /**
     * Get authenticated user profile
     */
    public function me(Request $request)
    {
        $user = $request->user()->load(['roles.permissions', 'employee.department', 'employee.designation', 'employee.bankAccount']);

        $roleNames = $user->roles->pluck('name')->toArray();
        $permissions = $user->roles->flatMap->permissions->pluck('name')->unique()->values()->toArray();

        return response()->json([
            'status' => 'success',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'status' => $user->status,
                'avatar' => $user->avatar,
                'roles' => $roleNames,
                'permissions' => $permissions,
                'employee' => $user->employee,
            ],
        ]);
    }

    /**
     * Logout
     */
    public function logout(Request $request)
    {
        $user = $request->user();
        if ($user) {
            $user->currentAccessToken()->delete();
            AuditLog::log('auth.logout', 'User', $user->id, "User {$user->name} logged out.");
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Logged out successfully.',
        ]);
    }
}
