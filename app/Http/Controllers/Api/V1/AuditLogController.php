<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    /**
     * List recent audit logs
     */
    public function index(Request $request)
    {
        $logs = AuditLog::with('user')
            ->latest()
            ->paginate($request->per_page ?? 30);

        return response()->json([
            'status' => 'success',
            'data' => $logs,
        ]);
    }
}
