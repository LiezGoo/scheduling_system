<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('page-title', 'Dashboard') | SorSU Scheduling System</title>
    <link rel="icon" href="{{ asset('images/logo.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="{{ asset('css/app-layout.css') }}" rel="stylesheet">
</head>

<body class="app-shell">
    @php
        $role = auth()->user()->role ?? '';
        $rolePaths = [
            'admin' => 'admin/dashboard',
            'department_head' => 'department-head/dashboard',
            'program_head' => 'program-head/dashboard',
            'instructor' => 'instructor/dashboard',
            'student' => 'student/dashboard',
        ];
        $dashboardRoutes = [
            'admin' => route('admin.dashboard'),
            'department_head' => route('department-head.dashboard'),
            'program_head' => route('program-head.dashboard'),
            'instructor' => route('instructor.dashboard'),
            'student' => route('student.dashboard'),
        ];
        $currentPattern = $rolePaths[$role] ?? 'dashboard';
        $baseDashboardRoute = $dashboardRoutes[$role] ?? url('/');
        $menuItems = [
            [
                'label' => 'Dashboard',
                'icon' => 'fa-solid fa-gauge',
                'anchor' => 'overview',
                'roles' => ['admin', 'department_head', 'program_head', 'instructor', 'student'],
                'pattern' => $currentPattern,
            ],
            [
                'label' => 'User & Role Management',
                'icon' => 'fa-solid fa-users-gear',
                'anchor' => 'user-management',
                'roles' => ['admin'],
                'pattern' => 'admin/dashboard*',
            ],
            [
                'label' => 'Faculty Load Management',
                'icon' => 'fa-solid fa-clipboard-list',
                'anchor' => 'faculty-load',
                'roles' => ['admin', 'department_head', 'program_head', 'instructor'],
                'pattern' => $currentPattern,
            ],
            [
                'label' => 'Program Management',
                'icon' => 'fa-solid fa-diagram-project',
                'anchor' => 'program-management',
                'roles' => ['admin', 'department_head', 'program_head'],
                'pattern' => $currentPattern,
            ],
            [
                'label' => 'Curriculum Management',
                'icon' => 'fa-solid fa-book-open',
                'anchor' => 'curriculum-management',
                'roles' => ['admin', 'department_head', 'program_head'],
                'pattern' => $currentPattern,
            ],
            [
                'label' => 'Room Management',
                'icon' => 'fa-solid fa-door-open',
                'anchor' => 'room-management',
                'roles' => ['admin', 'department_head'],
                'pattern' => $currentPattern,
            ],
            [
                'label' => 'Schedule Generation',
                'icon' => 'fa-solid fa-calendar-check',
                'anchor' => 'schedule-generation',
                'roles' => ['admin', 'department_head', 'program_head', 'instructor', 'student'],
                'pattern' => $currentPattern,
            ],
        ];
    @endphp

    <nav id="appHeader" class="navbar navbar-dark navbar-expand-lg app-navbar fixed-top shadow-sm">
        <div class="container-fluid">
            <div class="d-flex align-items-center">
                <button class="btn btn-link text-white p-0 me-3" id="sidebarToggle" type="button"
                    aria-label="Toggle sidebar">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <span class="navbar-brand d-flex align-items-center mb-0 fw-semibold">
                    <img src="{{ asset('images/logo.png') }}" alt="SorSU Logo" class="brand-logo me-2">
                    SorSU Scheduling System
                </span>
            </div>

            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-link text-white position-relative nav-icon" type="button"
                    aria-label="Notifications">
                    <i class="fa-regular fa-bell"></i>
                </button>

                <button class="btn btn-link text-white d-flex align-items-center gap-2" type="button"
                    data-bs-toggle="modal" data-bs-target="#userProfileModal" aria-label="User profile">
                    <i class="fa-regular fa-circle-user"></i>
                    {{-- <span class="d-none d-sm-inline">{{ auth()->user()->name ?? 'User' }}</span> --}}
                </button>
            </div>
        </div>
    </nav>

    <div class="app-wrapper">
        <aside id="appSidebar" class="app-sidebar">
            <div class="sidebar-inner">
                <nav class="nav flex-column nav-pills">
                    @foreach ($menuItems as $item)
                        @if (in_array($role, $item['roles'], true))
                            @php
                                $href = $baseDashboardRoute . '#' . $item['anchor'];
                                $isActive = request()->is($item['pattern']);
                            @endphp
                            <a class="nav-link d-flex align-items-center gap-2 {{ $isActive ? 'active' : '' }}"
                                href="{{ $href }}" data-bs-toggle="tooltip" data-bs-placement="right"
                                data-bs-title="{{ $item['label'] }}">
                                <i class="{{ $item['icon'] }}"></i>
                                <span class="link-label">{{ $item['label'] }}</span>
                            </a>
                        @endif
                    @endforeach
                </nav>
            </div>
        </aside>

        <main id="appMain" class="app-main flex-grow-1">
            <div class="page-header d-flex align-items-center justify-content-between mb-4">
                <div>
                    <p class="text-muted text-uppercase small mb-1">Dashboard</p>
                    <h1 class="h4 mb-0">@yield('page-title', 'Dashboard')</h1>
                </div>
            </div>
            @yield('content')
        </main>
    </div>

    @php
        $user = auth()->user();
        $profileEditUrl = Route::has('profile.edit') ? route('profile.edit') : '#';
    @endphp
    <div class="modal fade" id="userProfileModal" tabindex="-1" aria-labelledby="userProfileModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content rounded-3 overflow-hidden">
                <div class="modal-header user-profile-header">
                    <div class="d-flex align-items-center gap-2 text-white">
                        <i class="fa-regular fa-user"></i>
                        <h5 class="modal-title" id="userProfileModalLabel">User Profile</h5>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex justify-content-center mb-3">
                        <div class="profile-avatar overflow-hidden">
                            @if (!empty($user?->profile_photo_url))
                                <img src="{{ $user->profile_photo_url }}" alt="{{ $user->name ?? 'User' }} profile"
                                    class="img-fluid h-100 w-100 object-fit-cover">
                            @else
                                <i class="fa-regular fa-user"></i>
                            @endif
                        </div>
                    </div>
                    <div class="container-fluid">
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <p class="text-muted small mb-1">Full Name</p>
                                <p class="fw-semibold mb-0">{{ $user->name ?? 'N/A' }}</p>
                            </div>
                            <div class="col-12 col-md-6">
                                <p class="text-muted small mb-1">Email</p>
                                <p class="fw-semibold mb-0">{{ $user->email ?? 'N/A' }}</p>
                            </div>
                            <div class="col-12 col-md-6">
                                <p class="text-muted small mb-1">Role</p>
                                <p class="fw-semibold mb-0">{{ $user->role ?? 'N/A' }}</p>
                            </div>
                            <div class="col-12 col-md-6">
                                <p class="text-muted small mb-1">Status</p>
                                @php
                                    $isActive = ($user->status ?? '') === 'active';
                                @endphp
                                <span
                                    class="badge {{ $isActive ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">
                                    {{ $isActive ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-between align-items-center">
                    <form method="POST" action="{{ route('logout') }}" class="m-0">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-primary-theme d-flex align-items-center gap-2">
                            <i class="fa-solid fa-right-from-bracket"></i>
                            <span>Logout</span>
                        </button>
                    </form>
                    <a href="{{ $profileEditUrl }}"
                        class="btn btn-sm btn-primary-theme d-flex align-items-center gap-2">
                        <i class="fa-solid fa-user-pen"></i>
                        <span>Edit Profile</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (() => {
            const body = document.body;
            const sidebar = document.getElementById('appSidebar');
            const main = document.getElementById('appMain');
            const toggle = document.getElementById('sidebarToggle');
            const navLinks = Array.from(document.querySelectorAll('#appSidebar [data-bs-toggle="tooltip"]'));
            const lgBreakpoint = 992; // Bootstrap lg breakpoint
            let tooltipInstances = [];

            const enableTooltips = () => {
                if (tooltipInstances.length) return;
                tooltipInstances = navLinks.map((el) => new bootstrap.Tooltip(el));
            };

            const disableTooltips = () => {
                tooltipInstances.forEach((instance) => instance.dispose());
                tooltipInstances = [];
            };

            const setLayout = () => {
                const isMobile = window.innerWidth < lgBreakpoint;
                const isCollapsed = body.classList.contains('sidebar-collapsed');
                const isOpen = body.classList.contains('sidebar-open');
                const rootStyles = getComputedStyle(document.documentElement);
                const expandedWidth = rootStyles.getPropertyValue('--sidebar-expanded-width').trim();
                const collapsedWidth = rootStyles.getPropertyValue('--sidebar-collapsed-width').trim();

                if (isMobile) {
                    body.classList.remove('sidebar-collapsed');
                    main.style.marginLeft = '0';
                    sidebar.style.left = isOpen ? '0' : '-100%';
                    disableTooltips();
                } else {
                    body.classList.remove('sidebar-open');
                    sidebar.style.left = '0';
                    const width = isCollapsed ? collapsedWidth : expandedWidth;
                    main.style.marginLeft = width;
                    if (isCollapsed) {
                        enableTooltips();
                    } else {
                        disableTooltips();
                    }
                }
            };

            const toggleSidebar = () => {
                if (window.innerWidth < lgBreakpoint) {
                    body.classList.toggle('sidebar-open');
                } else {
                    body.classList.toggle('sidebar-collapsed');
                }
                setLayout();
            };

            toggle?.addEventListener('click', toggleSidebar);
            window.addEventListener('resize', setLayout);
            document.addEventListener('DOMContentLoaded', setLayout);
        })();
    </script>
    @stack('scripts')
</body>

</html>
