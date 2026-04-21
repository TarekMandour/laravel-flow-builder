<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Flow Builder') — Laravel Flow Builder</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {--bs-link-color: #7808d0;
    --bs-link-hover-color: #411b60; --fb-sidebar: 260px; --fb-primary: #7808d0; --fb-primary-h: #411b60; }
        body { font-family: 'Segoe UI', system-ui, sans-serif; background: #f0f2f5; }
        a {
            text-decoration: none
        }
        .fb-sidebar {
            width: var(--fb-sidebar); min-height: 100vh;
            background: linear-gradient(180deg, #1e1b4b 0%, #000000 100%);
            position: fixed; left: 0; top: 0; z-index: 1040; transition: transform .3s;
        }
        .fb-sidebar .nav-link {
            color: rgba(255,255,255,.7); border-radius: .5rem;
            margin: 2px 12px; padding: .6rem 1rem; font-size: .9rem; transition: all .2s;
        }
        .fb-sidebar .nav-link:hover, .fb-sidebar .nav-link.active { color: #fff; background: rgba(255,255,255,.12); }
        .fb-sidebar .nav-link i { width: 24px; text-align: center; margin-right: 8px; }
        .fb-brand {
            padding: 1.5rem 1.25rem 1rem; color: #fff; font-weight: 700;
            font-size: 1.15rem; display: flex; align-items: center; gap: .6rem;
        }
        .fb-brand i { font-size: 1.4rem; color: #a5b4fc; }
        .fb-main { margin-left: var(--fb-sidebar); min-height: 100vh; }
        .fb-topbar {
            background: #fff; border-bottom: 1px solid #e5e7eb;
            padding: .75rem 1.5rem; position: sticky; top: 0; z-index: 1030;
        }
        .fb-content { padding: 1.5rem; }
        .card { border: none; border-radius: .75rem; box-shadow: 0 1px 3px rgba(0,0,0,.06); }
        .card-header { background: #fff; border-bottom: 1px solid #f3f4f6; font-weight: 600; }
        .stat-card { border-left: 4px solid; }
        .btn-fb-dark {
            width: 130px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: rgb(15, 15, 15);
            border: none;
            color: white;
            font-weight: 600;
            gap: 8px;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            transition-duration: .3s;
        }

        .btn-fb-dark:hover {
            background-color: var(--fb-primary);
            color: white;
        }

        .btn-fb-dark:active {
            background-color: var(--fb-primary);
            color: white;
        }

        .btn-fb-dark-outline {
            width: 130px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: rgb(255, 255, 255);
            border: 2px solid rgb(15, 15, 15);
            color: #000000;
            font-weight: 600;
            gap: 8px;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            transition-duration: .3s;
        }

        .btn-fb-dark-outline:hover {
            border: 2px solid rgb(15, 15, 15);
            background-color: rgb(15, 15, 15);
            color: white;
        }

        .btn-fb-dark-outline:active {
            border: 2px solid rgb(15, 15, 15);
            background-color: rgb(15, 15, 15);
            color: white;
        }
        .form-control.form-control-solid {
            background-color: var(--bs-gray-100);
            border-color: var(--bs-gray-100);
            color: var(--bs-gray-700);
            transition: color 0.2s ease;
        }
        .form-control {
            display: block;
            width: 100%;
            padding: 0.775rem 1rem;
            font-size: .9rem;
            font-weight: 500;
            line-height: 1.5;
            color: var(--bs-gray-700);
            appearance: none;
            background-color: var(--bs-body-bg);
            background-clip: padding-box;
            border: 1px solid var(--bs-gray-300);
            border-radius: 0.475rem;
            box-shadow: false;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }
        .form-select {
            background-color: var(--bs-gray-100);
            border-color: var(--bs-gray-100);
            padding: 0.5rem 2rem;
        }
        .form-select:focus, .form-control:focus {
            background-color: var(--bs-gray-100);
            border-color: var(--bs-gray-100);
            box-shadow: none;
        }
        .pagination {
            --bs-pagination-padding-x: 0.75rem;
            --bs-pagination-padding-y: 0.375rem;
            --bs-pagination-font-size: 1rem;
            --bs-pagination-color: var(--bs-link-color);
            --bs-pagination-bg: var(--bs-body-bg);
            --bs-pagination-border-width: var(--bs-border-width);
            --bs-pagination-border-color: var(--bs-border-color);
            --bs-pagination-border-radius: var(--bs-border-radius);
            --bs-pagination-hover-color: var(--bs-link-hover-color);
            --bs-pagination-hover-bg: var(--bs-tertiary-bg);
            --bs-pagination-hover-border-color: var(--bs-border-color);
            --bs-pagination-focus-color: var(--bs-link-hover-color);
            --bs-pagination-focus-bg: var(--bs-secondary-bg);
            --bs-pagination-focus-box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
            --bs-pagination-active-color: #fff;
            --bs-pagination-active-bg: #7808d0;
            --bs-pagination-active-border-color: #7808d0;
            --bs-pagination-disabled-color: var(--bs-secondary-color);
            --bs-pagination-disabled-bg: var(--bs-secondary-bg);
            --bs-pagination-disabled-border-color: var(--bs-border-color);
            display: flex;
            padding-left: 0;
            list-style: none;
        }
        .badge-trigger { background: #dbeafe; color: #1e40af; }
        .badge-condition { background: #fef3c7; color: #92400e; }
        .badge-action { background: #d1fae5; color: #065f46; }
        .badge-operation { background: #ede9fe; color: #5b21b6; }
        .badge-integration { background: #fce7f3; color: #9d174d; }
        .badge-success-soft { background: #d1fae5; color: #065f46; }
        .badge-danger-soft { background: #fee2e2; color: #991b1b; }
        .badge-warning-soft { background: #fef3c7; color: #92400e; }
        .badge-info-soft { background: #dbeafe; color: #1e40af; }
        .table th {
            padding: 15px; font-size: .8rem; 
        }
        .empty-state { padding: 3rem 1rem; text-align: center; color: #9ca3af; }
        .empty-state i { font-size: 3rem; margin-bottom: 1rem; display: block; }
        @media (max-width: 991.98px) {
            .fb-sidebar { transform: translateX(-100%); }
            .fb-sidebar.show { transform: translateX(0); }
            .fb-main { margin-left: 0; }
        }
    </style>
    @stack('styles')
</head>
<body>

{{-- Sidebar --}}
<nav class="fb-sidebar" id="fbSidebar">
    <div class="fb-brand"><i class="bi bi-diagram-3-fill"></i> Flow Builder</div>
    <hr class="mx-3 my-1" style="border-color:rgba(255,255,255,.15)">
    <ul class="nav flex-column mt-2">
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('flow-builder.dashboard') ? 'active' : '' }}"
               href="{{ route('flow-builder.dashboard') }}">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('flow-builder.flows.*') ? 'active' : '' }}"
               href="{{ route('flow-builder.flows.index') }}">
                <i class="bi bi-diagram-3"></i> Flows
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('flow-builder.executions.*') ? 'active' : '' }}"
               href="{{ route('flow-builder.executions.index') }}">
                <i class="bi bi-play-circle"></i> Executions
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('flow-builder.integrations.*') ? 'active' : '' }}"
               href="{{ route('flow-builder.integrations.index') }}">
                <i class="bi bi-plug"></i> Integrations
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('flow-builder.guide') ? 'active' : '' }}"
               href="{{ route('flow-builder.guide') }}">
                <i class="bi bi-book"></i> Package Guide
            </a>
        </li>
    </ul>
    <div class="position-absolute bottom-0 w-100 px-3 pb-3">
        <div class="small text-white-50 text-center">Laravel Flow Builder v1.0</div>
    </div>
</nav>

{{-- Main --}}
<div class="fb-main">
    <div class="fb-topbar d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-sm btn-light d-lg-none" onclick="document.getElementById('fbSidebar').classList.toggle('show')">
                <i class="bi bi-list"></i>
            </button>
            <nav aria-label="breadcrumb"><ol class="breadcrumb mb-0 small">@yield('breadcrumb')</ol></nav>
        </div>
        <div class="d-flex align-items-center gap-2">@yield('topbar-actions')</div>
    </div>
    <div class="fb-content">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show">
                <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @yield('content')
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
