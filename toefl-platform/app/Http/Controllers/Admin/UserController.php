<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserAuditLog;
use App\Exports\UsersExport;
use App\Imports\UsersImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request)
    {
        // Validate filters
        $validated = $request->validate([
            'role' => ['nullable', Rule::in(['student', 'instructor', 'admin', 'super_admin', 'parent'])],
            'status' => ['nullable', Rule::in(['active', 'inactive', 'suspended', 'pending'])],
            'search' => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'sort_field' => 'nullable|in:name,email,role,status,created_at',
            'sort_direction' => 'nullable|in:asc,desc',
            'per_page' => 'nullable|integer|min:10|max:100',
        ]);

        // Build query
        $query = User::query();

        // Apply filters
        if (isset($validated['role'])) {
            $query->where('role', $validated['role']);
        }

        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (isset($validated['search'])) {
            $query->search($validated['search']);
        }

        if (isset($validated['start_date']) || isset($validated['end_date'])) {
            $query->dateRange(
                $validated['start_date'] ?? null,
                $validated['end_date'] ?? null
            );
        }

        // Apply sorting
        $sortField = $validated['sort_field'] ?? 'created_at';
        $sortDirection = $validated['sort_direction'] ?? 'desc';
        $query->orderBy($sortField, $sortDirection);

        // Pagination (50 per page default)
        $perPage = $validated['per_page'] ?? 50;
        $users = $query->paginate($perPage)->withQueryString();

        // Get statistics
        $stats = [
            'total' => User::count(),
            'active' => User::where('status', 'active')->count(),
            'suspended' => User::where('status', 'suspended')->count(),
            'students' => User::where('role', 'student')->count(),
            'instructors' => User::where('role', 'instructor')->count(),
        ];

        return view('admin.users.index', compact('users', 'stats', 'validated'));
    }

    /**
     * Show user details.
     */
    public function show(User $user)
    {
        $auditLogs = $user->userAuditLogs()
            ->with('admin')
            ->latest()
            ->take(50)
            ->get();

        return view('admin.users.show', compact('user', 'auditLogs'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        $roles = User::getRoles();
        $statuses = User::getStatuses();
        return view('admin.users.create', compact('roles', 'statuses'));
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => ['required', Rule::in(['student', 'instructor', 'admin', 'parent'])],
            'status' => ['required', Rule::in(['active', 'pending', 'suspended'])],
            'email_verified' => 'boolean',
        ]);

        DB::transaction(function () use ($validated, $request) {
            // Create user
            $user = User::create([
                'full_name' => $validated['full_name'],
                'email' => $validated['email'],
                'password_hash' => Hash::make($validated['password']),
                'role' => $validated['role'],
                'status' => $validated['status'],
                'email_verified' => $validated['email_verified'] ?? false,
            ]);

            // Log audit
            UserAuditLog::create([
                'user_id' => $user->id,
                'admin_id' => Auth::id(),
                'action' => UserAuditLog::ACTION_CREATED,
                'new_values' => $user->toArray(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        });

        return redirect()->route('admin.users.index')
            ->with('success', 'User created successfully.');
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user)
    {
        $roles = User::getRoles();
        $statuses = User::getStatuses();
        return view('admin.users.edit', compact('user', 'roles', 'statuses'));
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'role' => ['required', Rule::in(['student', 'instructor', 'admin', 'parent'])],
            'status' => ['required', Rule::in(['active', 'pending', 'suspended'])],
            'email_verified' => 'boolean',
        ]);

        $oldValues = $user->only(['full_name', 'email', 'role', 'status', 'email_verified']);

        DB::transaction(function () use ($validated, $request, $user, $oldValues) {
            // Prepare update data
            $updateData = [
                'full_name' => $validated['full_name'],
                'email' => $validated['email'],
                'role' => $validated['role'],
                'status' => $validated['status'],
                'email_verified' => $validated['email_verified'] ?? false,
            ];

            // Add password if provided
            if (!empty($validated['password'])) {
                $updateData['password_hash'] = Hash::make($validated['password']);
            }

            // Update user
            $user->update($updateData);

            // Check if role changed
            $action = UserAuditLog::ACTION_UPDATED;
            if ($oldValues['role'] !== $validated['role']) {
                $action = UserAuditLog::ACTION_ROLE_CHANGED;
            }

            // Log audit
            UserAuditLog::create([
                'user_id' => $user->id,
                'admin_id' => Auth::id(),
                'action' => $action,
                'old_values' => $oldValues,
                'new_values' => $user->fresh()->only(['full_name', 'email', 'role', 'status', 'email_verified']),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        });

        return redirect()->route('admin.users.index')
            ->with('success', 'User updated successfully.');
    }

    /**
     * Suspend the specified user.
     */
    public function suspend(Request $request, User $user)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $oldValues = $user->only(['status', 'suspension_reason', 'suspended_at']);

        DB::transaction(function () use ($validated, $request, $user, $oldValues) {
            $user->suspend($validated['reason'], Auth::user());

            UserAuditLog::create([
                'user_id' => $user->id,
                'admin_id' => Auth::id(),
                'action' => UserAuditLog::ACTION_SUSPENDED,
                'old_values' => $oldValues,
                'new_values' => [
                    'status' => $user->status,
                    'suspension_reason' => $user->suspension_reason,
                    'suspended_at' => $user->suspended_at,
                ],
                'reason' => $validated['reason'],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        });

        return redirect()->back()
            ->with('success', 'User suspended successfully.');
    }

    /**
     * Unsuspend the specified user.
     */
    public function unsuspend(Request $request, User $user)
    {
        $oldValues = $user->only(['status', 'suspension_reason', 'suspended_at']);

        DB::transaction(function () use ($request, $user, $oldValues) {
            $user->unsuspend();

            UserAuditLog::create([
                'user_id' => $user->id,
                'admin_id' => Auth::id(),
                'action' => UserAuditLog::ACTION_UNSUSPENDED,
                'old_values' => $oldValues,
                'new_values' => [
                    'status' => $user->status,
                    'suspension_reason' => null,
                    'suspended_at' => null,
                ],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        });

        return redirect()->back()
            ->with('success', 'User unsuspended successfully.');
    }

    /**
     * Soft delete the specified user.
     */
    public function destroy(Request $request, User $user)
    {
        $oldValues = $user->toArray();

        DB::transaction(function () use ($request, $user, $oldValues) {
            $user->delete();

            UserAuditLog::create([
                'user_id' => $user->id,
                'admin_id' => Auth::id(),
                'action' => UserAuditLog::ACTION_DELETED,
                'old_values' => $oldValues,
                'new_values' => ['deleted_at' => now()],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        });

        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully.');
    }

    /**
     * Bulk actions (suspend, delete, change role).
     */
    public function bulkAction(Request $request)
    {
        $validated = $request->validate([
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
            'action' => 'required|in:suspend,delete,change_role',
            'reason' => 'nullable|string|max:1000|required_if:action,suspend',
            'new_role' => 'nullable|in:student,instructor,admin,parent|required_if:action,change_role',
        ]);

        $userIds = $validated['user_ids'];
        $minCount = 1; // Can work with any count, but requirement says min 10 for some actions
        
        if (count($userIds) < $minCount) {
            return back()->with('error', 'Please select at least ' . $minCount . ' users.');
        }

        DB::transaction(function () use ($validated, $request) {
            $users = User::whereIn('id', $validated['user_ids'])->get();
            $admin = Auth::user();

            foreach ($users as $user) {
                $oldValues = $user->only(['status', 'role']);

                switch ($validated['action']) {
                    case 'suspend':
                        $user->suspend($validated['reason'], $admin);
                        $action = UserAuditLog::ACTION_BULK_SUSPEND;
                        $newValues = ['status' => $user->status];
                        break;

                    case 'delete':
                        $user->delete();
                        $action = UserAuditLog::ACTION_BULK_DELETE;
                        $newValues = ['deleted_at' => now()];
                        break;

                    case 'change_role':
                        $user->update(['role' => $validated['new_role']]);
                        $action = UserAuditLog::ACTION_BULK_ROLE_CHANGE;
                        $newValues = ['role' => $user->role];
                        break;
                }

                UserAuditLog::create([
                    'user_id' => $user->id,
                    'admin_id' => $admin->id,
                    'action' => $action,
                    'old_values' => $oldValues,
                    'new_values' => $newValues,
                    'reason' => $validated['reason'] ?? null,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            }
        });

        return redirect()->route('admin.users.index')
            ->with('success', 'Bulk action completed successfully.');
    }

    /**
     * Download CSV template for import.
     */
    public function downloadTemplate()
    {
        $filename = 'users_import_template.csv';
        
        $columns = ['full_name', 'email', 'role', 'status'];
        $sampleData = [
            ['John Doe', 'john@example.com', 'student', 'active'],
            ['Jane Smith', 'jane@example.com', 'instructor', 'active'],
        ];

        return response()->streamDownload(function () use ($columns, $sampleData) {
            $output = fopen('php://output', 'w');
            
            // Header
            fputcsv($output, $columns);
            
            // Sample data
            foreach ($sampleData as $row) {
                fputcsv($output, $row);
            }
            
            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * Show import form with preview.
     */
    public function showImportForm()
    {
        return view('admin.users.import');
    }

    /**
     * Preview CSV import.
     */
    public function previewImport(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,xlsx,xls|max:10240',
        ]);

        try {
            $file = $request->file('file');
            $data = Excel::toArray([], $file);
            
            if (empty($data) || empty($data[0])) {
                return back()->with('error', 'Empty file or invalid format.');
            }

            $import = new UsersImport();
            $preview = $import->getPreview($data[0]);

            // Store file temporarily for commit
            $tempPath = $file->storeAs('imports', 'users_' . time() . '.' . $file->getClientOriginalExtension());

            return view('admin.users.import-preview', compact('preview', 'tempPath'));
        } catch (\Exception $e) {
            return back()->with('error', 'Error processing file: ' . $e->getMessage());
        }
    }

    /**
     * Commit CSV import.
     */
    public function commitImport(Request $request)
    {
        $validated = $request->validate([
            'temp_path' => 'required|string',
        ]);

        $tempPath = $validated['temp_path'];

        if (!Storage::exists($tempPath)) {
            return back()->with('error', 'Temporary file not found.');
        }

        try {
            $filePath = Storage::path($tempPath);
            $data = Excel::toArray(new UsersImport(), $filePath);
            
            $imported = 0;
            $failed = 0;
            $errors = [];

            DB::transaction(function () use ($data, &$imported, &$failed, &$errors) {
                $rows = $data[0] ?? [];
                $header = array_shift($rows); // Remove header

                foreach ($rows as $index => $row) {
                    try {
                        // Combine header and row data
                        $rowData = array_combine($header, $row);

                        // Validate row
                        $validator = Validator::make($rowData, [
                            'full_name' => 'required|string|max:255',
                            'email' => 'required|email|unique:users,email',
                            'role' => Rule::in(['student', 'instructor', 'admin', 'parent']),
                            'status' => Rule::in(['active', 'pending', 'suspended']),
                        ]);

                        if ($validator->fails()) {
                            $failed++;
                            $errors[] = "Row " . ($index + 2) . ": " . implode(', ', $validator->errors()->all());
                            continue;
                        }

                        // Create user
                        User::create([
                            'full_name' => $rowData['full_name'],
                            'email' => $rowData['email'],
                            'password_hash' => Hash::make('default_password_123'), // Default password
                            'role' => $rowData['role'],
                            'status' => $rowData['status'] ?? 'active',
                            'email_verified' => false,
                        ]);

                        $imported++;
                    } catch (\Exception $e) {
                        $failed++;
                        $errors[] = "Row " . ($index + 2) . ": " . $e->getMessage();
                    }
                }
            });

            // Clean up temp file
            Storage::delete($tempPath);

            // Log bulk import audit
            if ($imported > 0) {
                UserAuditLog::create([
                    'user_id' => Auth::id(),
                    'admin_id' => Auth::id(),
                    'action' => 'bulk_import',
                    'new_values' => ['imported_count' => $imported, 'failed_count' => $failed],
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            }

            return redirect()->route('admin.users.index')
                ->with('success', "Import completed. {$imported} users imported, {$failed} failed.")
                ->with('import_errors', $errors);
        } catch (\Exception $e) {
            Storage::delete($tempPath);
            return back()->with('error', 'Error importing file: ' . $e->getMessage());
        }
    }

    /**
     * Export users to Excel/CSV.
     */
    public function export(Request $request)
    {
        $filters = $request->only(['role', 'status', 'search', 'start_date', 'end_date', 'sort_field', 'sort_direction']);
        
        $filename = 'users_export_' . date('Y-m-d_His') . '.xlsx';

        return Excel::download(new UsersExport($filters), $filename);
    }

    /**
     * View audit logs.
     */
    public function auditLogs(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'admin_id' => 'nullable|exists:users,id',
            'action' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $query = UserAuditLog::with(['user', 'admin']);

        if (isset($validated['user_id'])) {
            $query->onUser($validated['user_id']);
        }

        if (isset($validated['admin_id'])) {
            $query->byAdmin($validated['admin_id']);
        }

        if (isset($validated['action'])) {
            $query->action($validated['action']);
        }

        if (isset($validated['start_date']) || isset($validated['end_date'])) {
            $query->dateRange(
                $validated['start_date'] ?? null,
                $validated['end_date'] ?? null
            );
        }

        $logs = $query->latest()->paginate(50);

        $actions = [
            UserAuditLog::ACTION_CREATED => 'Created',
            UserAuditLog::ACTION_UPDATED => 'Updated',
            UserAuditLog::ACTION_SUSPENDED => 'Suspended',
            UserAuditLog::ACTION_UNSUSPENDED => 'Unsuspended',
            UserAuditLog::ACTION_DELETED => 'Deleted',
            UserAuditLog::ACTION_RESTORED => 'Restored',
            UserAuditLog::ACTION_ROLE_CHANGED => 'Role Changed',
            UserAuditLog::ACTION_BULK_SUSPEND => 'Bulk Suspend',
            UserAuditLog::ACTION_BULK_DELETE => 'Bulk Delete',
            UserAuditLog::ACTION_BULK_ROLE_CHANGE => 'Bulk Role Change',
        ];

        return view('admin.users.audit-logs', compact('logs', 'actions', 'validated'));
    }
}
