@extends('layouts.app')

@section('title', 'User Management')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1><i class="fas fa-users"></i> User Management</h1>
                <div>
                    <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add User
                    </a>
                    <a href="{{ route('admin.users.import') }}" class="btn btn-info">
                        <i class="fas fa-file-import"></i> Import CSV
                    </a>
                    <a href="{{ route('admin.users.export', request()->all()) }}" class="btn btn-success">
                        <i class="fas fa-file-export"></i> Export
                    </a>
                    <a href="{{ route('admin.users.audit-logs') }}" class="btn btn-secondary">
                        <i class="fas fa-history"></i> Audit Logs
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5>Total Users</h5>
                    <h2>{{ $stats['total'] }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5>Active</h5>
                    <h2>{{ $stats['active'] }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h5>Suspended</h5>
                    <h2>{{ $stats['suspended'] }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5>Students</h5>
                    <h2>{{ $stats['students'] }}</h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-header">
            <h5><i class="fas fa-filter"></i> Filters</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.users.index') }}">
                <div class="row">
                    <div class="col-md-3">
                        <label for="role">Role</label>
                        <select name="role" id="role" class="form-control">
                            <option value="">All Roles</option>
                            <option value="student" {{ request('role') == 'student' ? 'selected' : '' }}>Student</option>
                            <option value="instructor" {{ request('role') == 'instructor' ? 'selected' : '' }}>Instructor</option>
                            <option value="admin" {{ request('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                            <option value="parent" {{ request('role') == 'parent' ? 'selected' : '' }}>Parent</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="status">Status</label>
                        <select name="status" id="status" class="form-control">
                            <option value="">All Statuses</option>
                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="suspended" {{ request('status') == 'suspended' ? 'selected' : '' }}>Suspended</option>
                            <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="search">Search (Name/Email)</label>
                        <input type="text" name="search" id="search" class="form-control" 
                               value="{{ request('search') }}" placeholder="Search...">
                    </div>
                    <div class="col-md-3">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i> Filter
                        </button>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-3">
                        <label for="start_date">Start Date</label>
                        <input type="date" name="start_date" id="start_date" class="form-control" 
                               value="{{ request('start_date') }}">
                    </div>
                    <div class="col-md-3">
                        <label for="end_date">End Date</label>
                        <input type="date" name="end_date" id="end_date" class="form-control" 
                               value="{{ request('end_date') }}">
                    </div>
                    <div class="col-md-3">
                        <label for="sort_field">Sort By</label>
                        <select name="sort_field" id="sort_field" class="form-control">
                            <option value="created_at" {{ request('sort_field') == 'created_at' ? 'selected' : '' }}>Created At</option>
                            <option value="full_name" {{ request('sort_field') == 'full_name' ? 'selected' : '' }}>Name</option>
                            <option value="email" {{ request('sort_field') == 'email' ? 'selected' : '' }}>Email</option>
                            <option value="role" {{ request('sort_field') == 'role' ? 'selected' : '' }}>Role</option>
                            <option value="status" {{ request('sort_field') == 'status' ? 'selected' : '' }}>Status</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="sort_direction">Direction</label>
                        <select name="sort_direction" id="sort_direction" class="form-control">
                            <option value="desc" {{ request('sort_direction') == 'desc' ? 'selected' : '' }}>Descending</option>
                            <option value="asc" {{ request('sort_direction') == 'asc' ? 'selected' : '' }}>Ascending</option>
                        </select>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Users Table -->
    <div class="card">
        <div class="card-header">
            <h5><i class="fas fa-table"></i> Users List</h5>
        </div>
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle"></i> {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            @endif

            <form action="{{ route('admin.users.bulk-action') }}" method="POST" id="bulk-form">
                @csrf
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th width="50">
                                    <input type="checkbox" id="select-all">
                                </th>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Created At</th>
                                <th width="200">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($users as $user)
                                <tr>
                                    <td>
                                        <input type="checkbox" name="user_ids[]" value="{{ $user->id }}" class="user-checkbox">
                                    </td>
                                    <td>{{ $user->id }}</td>
                                    <td>{{ $user->full_name }}</td>
                                    <td>{{ $user->email }}</td>
                                    <td>
                                        <span class="badge badge-{{ $user->role === 'admin' ? 'danger' : ($user->role === 'instructor' ? 'info' : 'secondary') }}">
                                            {{ ucfirst($user->role) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($user->status === 'active')
                                            <span class="badge badge-success">Active</span>
                                        @elseif($user->status === 'suspended')
                                            <span class="badge badge-danger">Suspended</span>
                                            @if($user->suspension_reason)
                                                <small class="text-muted d-block">{{ Str::limit($user->suspension_reason, 30) }}</small>
                                            @endif
                                        @else
                                            <span class="badge badge-warning">{{ ucfirst($user->status) }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $user->created_at->format('Y-m-d') }}</td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('admin.users.show', $user) }}" class="btn btn-info" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-warning" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            @if($user->status === 'active')
                                                <button type="button" class="btn btn-danger" 
                                                        onclick="showSuspendModal({{ $user->id }}, '{{ $user->full_name }}')" 
                                                        title="Suspend">
                                                    <i class="fas fa-ban"></i>
                                                </button>
                                            @else
                                                <form action="{{ route('admin.users.unsuspend', $user) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-success" title="Unsuspend">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                        <p>No users found.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Bulk Actions -->
                <div class="mt-3">
                    <div class="row align-items-end">
                        <div class="col-md-3">
                            <label>Bulk Action</label>
                            <select name="action" id="bulk-action" class="form-control" onchange="toggleBulkFields()">
                                <option value="">Select Action...</option>
                                <option value="suspend">Suspend Selected</option>
                                <option value="delete">Delete Selected</option>
                                <option value="change_role">Change Role</option>
                            </select>
                        </div>
                        <div class="col-md-3" id="reason-field" style="display:none;">
                            <label>Reason</label>
                            <textarea name="reason" id="bulk-reason" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-md-3" id="role-field" style="display:none;">
                            <label>New Role</label>
                            <select name="new_role" id="bulk-new-role" class="form-control">
                                <option value="student">Student</option>
                                <option value="instructor">Instructor</option>
                                <option value="admin">Admin</option>
                                <option value="parent">Parent</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary w-100" id="bulk-submit" disabled>
                                <i class="fas fa-cog"></i> Execute
                            </button>
                        </div>
                    </div>
                </div>
            </form>

            <!-- Pagination -->
            <div class="mt-4">
                {{ $users->links() }}
            </div>
        </div>
    </div>
</div>

<!-- Suspend Modal -->
<div class="modal fade" id="suspendModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="suspend-form">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Suspend User</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <p>Suspend user: <strong id="suspend-user-name"></strong></p>
                    <input type="hidden" id="suspend-user-id">
                    <div class="form-group">
                        <label for="suspend-reason">Reason <span class="text-danger">*</span></label>
                        <textarea name="reason" id="suspend-reason" class="form-control" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Suspend User</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// Select all checkbox
document.getElementById('select-all').addEventListener('change', function() {
    document.querySelectorAll('.user-checkbox').forEach(cb => {
        cb.checked = this.checked;
    });
    toggleBulkSubmit();
});

// Toggle bulk submit button
document.querySelectorAll('.user-checkbox').forEach(cb => {
    cb.addEventListener('change', toggleBulkSubmit);
});

function toggleBulkSubmit() {
    const checked = document.querySelectorAll('.user-checkbox:checked').length;
    document.getElementById('bulk-submit').disabled = checked === 0;
}

// Toggle bulk action fields
function toggleBulkFields() {
    const action = document.getElementById('bulk-action').value;
    document.getElementById('reason-field').style.display = action === 'suspend' ? 'block' : 'none';
    document.getElementById('role-field').style.display = action === 'change_role' ? 'block' : 'none';
}

// Show suspend modal
function showSuspendModal(userId, userName) {
    document.getElementById('suspend-user-id').value = userId;
    document.getElementById('suspend-user-name').textContent = userName;
    document.getElementById('suspend-form').action = '/admin/users/' + userId + '/suspend';
    document.getElementById('suspendModal').classList.add('show');
    document.getElementById('suspendModal').style.display = 'block';
    document.querySelector('.modal-backdrop').classList.add('show');
}

// Close modal helper
document.querySelector('#suspendModal .close').addEventListener('click', function() {
    document.getElementById('suspendModal').classList.remove('show');
    document.getElementById('suspendModal').style.display = 'none';
    document.querySelector('.modal-backdrop').classList.remove('show');
});
</script>
@endpush
