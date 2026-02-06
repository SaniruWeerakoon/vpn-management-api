<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController
{
    public function index()
    {
        return AuditLog::all();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'actor_id' => ['nullable', 'exists:users'],
            'action' => ['required'],
            'target_type' => ['required'],
            'target_id' => ['required', 'integer'],
            'metadata' => ['nullable'],
        ]);

        return AuditLog::create($data);
    }

    public function show(AuditLog $auditLog)
    {
        return $auditLog;
    }

    public function update(Request $request, AuditLog $auditLog)
    {
        $data = $request->validate([
            'actor_id' => ['nullable', 'exists:users'],
            'action' => ['required'],
            'target_type' => ['required'],
            'target_id' => ['required', 'integer'],
            'metadata' => ['nullable'],
        ]);

        $auditLog->update($data);

        return $auditLog;
    }

    public function destroy(AuditLog $auditLog)
    {
        $auditLog->delete();

        return response()->json();
    }
}
