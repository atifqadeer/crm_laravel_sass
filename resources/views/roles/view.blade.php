@extends('layouts.vertical', ['title' => 'View Role', 'subTitle' => 'Home'])

@section('css')
@vite(['node_modules/choices.js/public/assets/styles/choices.min.css'])
@endsection

@section('content')
    <div class="row">
        <div class="col-xl-12 col-lg-12">
            <div class="card">
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Role Name</label>
                        <p class="form-control-static">{{ $role->name }}</p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Active Permissions</label>
                        <div class="border rounded py-3 px-2" style="max-height: 500px; overflow-y: auto;">
                            @forelse($groupedPermissions as $group => $groupPermissions)
                                @php
                                    $activePermissions = $groupPermissions->filter(function($permission) use ($rolePermissions) {
                                        return in_array($permission->id, $rolePermissions);
                                    });
                                @endphp
                                @if($activePermissions->isNotEmpty())
                                    <div class="mb-2">
                                        <div class="fw-bold text-uppercase mb-2">
                                            {{ ucfirst($group) }}
                                        </div>
                                        <div class="row ms-2">
                                            @foreach($activePermissions as $permission)
                                                <div class="col-md-2">
                                                    <div class="form-check mb-2">
                                                        <input class="form-check-input" type="checkbox" 
                                                            id="perm_{{ $permission->id }}" 
                                                            checked disabled>
                                                        <label class="form-check-label" for="perm_{{ $permission->id }}">
                                                            {{ ucfirst(Str::after($permission->name, '-')) }}
                                                        </label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                        <hr>
                                    </div>
                                @endif
                            @empty
                                <div class="text-muted">No permissions assigned to this role.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
                <div class="card-header d-flex align-items-end">
                    <a href="{{ route('roles.list') }}" class="btn btn-primary btn-lg ms-auto">
                        <i class="mdi mdi-arrow-left"></i> Back to Roles
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection