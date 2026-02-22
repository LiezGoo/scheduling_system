<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="user-id" content="{{ auth()->id() }}">
    <title>@yield('page-title', 'Dashboard') | SorSU Scheduling System</title>
    <link rel="icon" href="{{ asset('images/logo.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="{{ asset('css/app-layout.css') }}" rel="stylesheet">
    @stack('styles')
</head>

<body class="app-shell sidebar-collapsed">
    <div id="globalToastContainer" class="position-fixed end-0 p-3" style="z-index: 1080; top: 72px; max-width: 420px;">
        @if (session('success'))
            <div class="toast align-items-center text-bg-success border-0 shadow-sm" role="alert" aria-live="assertive"
                aria-atomic="true" data-auto-show="true">
                <div class="d-flex">
                    <div class="toast-body">
                        {{ session('success') }}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"
                        aria-label="Close"></button>
                </div>
            </div>
        @endif
        @if (session('error'))
            <div class="toast align-items-center text-bg-danger border-0 shadow-sm" role="alert" aria-live="assertive"
                aria-atomic="true" data-auto-show="true">
                <div class="d-flex">
                    <div class="toast-body">
                        {{ session('error') }}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"
                        aria-label="Close"></button>
                </div>
            </div>
        @endif
        @if ($errors->any())
            <div class="toast align-items-center text-bg-danger border-0 shadow-sm" role="alert" aria-live="assertive"
                aria-atomic="true" data-auto-show="true">
                <div class="d-flex">
                    <div class="toast-body">
                        {{ $errors->first() }}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"
                        aria-label="Close"></button>
                </div>
            </div>
        @endif
    </div>
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
                'href' => route('admin.users.index'),
                'roles' => ['admin'],
                'pattern' => 'admin/users*',
            ],
            [
                'label' => 'Academic Year Management & Semester Management',
                'icon' => 'fa-solid fa-calendar-alt',
                'href' => route('admin.academic-years.index'),
                'roles' => ['admin'],
                'pattern' => 'admin/academic-years*',
            ],
            [
                'label' => 'Department Management',
                'icon' => 'fa-solid fa-building',
                'href' => route('admin.departments.index'),
                'roles' => ['admin'],
                'pattern' => 'admin/departments',
            ],
            [
                'label' => 'Program Management',
                'icon' => 'fa-solid fa-diagram-project',
                'href' => route('admin.programs.index'),
                'roles' => ['admin'],
                'pattern' => 'admin/programs*',
            ],
            [
                'label' => 'Room Management',
                'icon' => 'fa-solid fa-door-open',
                'href' => route('admin.rooms.index'),
                'roles' => ['admin'],
                'pattern' => 'admin/rooms*',
            ],
            // Program Head Menu Items
            [
                'label' => 'Curriculum Management',
                'icon' => 'fa-solid fa-layer-group',
                'href' => route('program-head.curriculum.index'),
                'roles' => ['program_head'],
                'pattern' => 'program-head/curriculum*',
            ],
            [
                'label' => 'Faculty Load Management',
                'icon' => 'fa-solid fa-clipboard-list',
                'href' => route('program-head.faculty-load.index'),
                'roles' => ['program_head'],
                'pattern' => 'program-head/faculty-load*',
            ],
            [
                'label' => 'Schedule Management',
                'icon' => 'fa-solid fa-calendar-check',
                'href' => route('program-head.schedules.index'),
                'roles' => ['program_head'],
                'pattern' => 'program-head/schedules*',
            ],
            [
                'label' => 'Subject Management',
                'icon' => 'fa-solid fa-book',
                'href' => route('department-head.subjects.index'),
                'roles' => ['department_head'],
                'pattern' => 'department-head/subjects*',
            ],
            [
                'label' => 'Schedule Approval',
                'icon' => 'fa-solid fa-calendar-check',
                'href' => route('department-head.schedules.index'),
                'roles' => ['department_head'],
                'pattern' => 'department-head/schedules*',
            ],
        ];
    @endphp

    <nav id="appHeader" class="navbar navbar-dark navbar-expand-lg app-navbar fixed-top shadow-sm">
        <div class="container-fluid">
            <div class="d-flex align-items-center">
                <!-- Sidebar Toggle: Hidden on mobile -->
                <button class="btn btn-link text-white p-0 me-3 d-none d-md-inline-block" id="sidebarToggle"
                    type="button" aria-label="Toggle sidebar">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <!-- Brand: Logo always visible, text hidden on mobile -->
                <span class="navbar-brand d-flex align-items-center mb-0 fw-semibold">
                    <img src="{{ asset('images/logo.png') }}" alt="SorSU Logo" class="brand-logo me-2">
                    <span class="d-none d-md-inline">SorSU Scheduling System</span>
                </span>
            </div>

            <div class="d-flex align-items-center gap-2 gap-md-3">
                <!-- Notification Bell -->
                <div class="dropdown position-static position-md-relative">
                    <button class="btn btn-link text-white position-relative nav-icon p-2" type="button"
                        id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false"
                        aria-label="Notifications">
                        <i class="fa-regular fa-bell" id="notificationBellIcon"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                            id="notificationBadge" style="display: none; font-size: 0.65rem;">
                            0
                        </span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end notification-dropdown shadow-lg"
                        aria-labelledby="notificationDropdown">
                        <li class="dropdown-header d-flex justify-content-between align-items-center px-3 py-2">
                            <span class="fw-bold">Notifications</span>
                            <button class="btn btn-link btn-sm text-decoration-none p-0" id="markAllReadBtn"
                                style="font-size: 0.8rem;">
                                Mark all as read
                            </button>
                        </li>
                        <li>
                            <hr class="dropdown-divider m-0">
                        </li>
                        <div id="notificationList" class="notification-list-container">
                            <li class="px-3 py-4 text-center text-muted">
                                <i class="fa-regular fa-bell-slash mb-2" style="font-size: 2rem; opacity: 0.5;"></i>
                                <p class="mb-0 small">No notifications</p>
                            </li>
                        </div>
                    </ul>
                </div>

                <button class="btn btn-link text-white d-flex align-items-center gap-2 p-2" type="button"
                    data-bs-toggle="modal" data-bs-target="#userProfileModal" aria-label="User profile">
                    <i class="fa-regular fa-circle-user"></i>
                    {{-- <span class="d-none d-sm-inline">{{ auth()->user()->name ?? 'User' }}</span> --}}
                </button>
            </div>
        </div>
    </nav>

    <div class="app-wrapper">
        <!-- Sidebar: Hidden on mobile (max-width: 767px) -->
        <aside id="appSidebar" class="app-sidebar d-none d-md-block">
            <div class="sidebar-inner">
                <nav class="nav flex-column nav-pills">
                    @foreach ($menuItems as $item)
                        @if (in_array($role, $item['roles'], true))
                            @php
                                $href = isset($item['href'])
                                    ? $item['href']
                                    : $baseDashboardRoute . '#' . $item['anchor'];
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
                                <p class="fw-semibold mb-0">{{ $user->full_name ?? 'N/A' }}</p>
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
                    <form id="logoutForm" method="POST" action="{{ route('logout') }}" class="m-0">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-primary-theme d-flex align-items-center gap-2">
                            <i class="fa-solid fa-right-from-bracket"></i>
                            <span>Logout</span>
                        </button>
                    </form>
                    <button type="button" class="btn btn-sm btn-primary-theme d-flex align-items-center gap-2"
                        data-edit-profile-btn data-user-id="">
                        <i class="fa-solid fa-user-pen"></i>
                        <span>Edit Profile</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit User Profile Modal -->
    @include('modals.edit-user-profile')

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (() => {
            const body = document.body;
            const sidebar = document.getElementById('appSidebar');
            const main = document.getElementById('appMain');
            const toggle = document.getElementById('sidebarToggle');
            const navLinks = Array.from(document.querySelectorAll('#appSidebar [data-bs-toggle="tooltip"]'));
            const lgBreakpoint = 992; // Bootstrap lg breakpoint
            const SIDEBAR_STATE_KEY = 'sidebar-state';
            let tooltipInstances = [];

            const applyStoredSidebarState = () => {
                const stored = localStorage.getItem(SIDEBAR_STATE_KEY);
                body.classList.remove('sidebar-open', 'sidebar-collapsed');
                if (stored === 'open') {
                    body.classList.add('sidebar-open');
                } else {
                    body.classList.add('sidebar-collapsed');
                }
            };

            const persistSidebarState = () => {
                const state = window.innerWidth < lgBreakpoint ?
                    (body.classList.contains('sidebar-open') ? 'open' : 'collapsed') :
                    (body.classList.contains('sidebar-collapsed') ? 'collapsed' : 'open');
                localStorage.setItem(SIDEBAR_STATE_KEY, state);
            };

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
                persistSidebarState();
                setLayout();
            };

            toggle?.addEventListener('click', toggleSidebar);
            toggle?.addEventListener('keydown', (event) => {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                }
            });

            window.addEventListener('resize', setLayout);

            document.addEventListener('DOMContentLoaded', () => {
                applyStoredSidebarState();
                setLayout();
            });
        })();
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const toasts = Array.from(document.querySelectorAll('.toast[data-auto-show="true"]'));
            toasts.forEach((toastEl) => {
                const toast = new bootstrap.Toast(toastEl, { autohide: true, delay: 4000 });
                toast.show();
                toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
            });
        });
    </script>

    <!-- Notification System -->
    <script src="{{ asset('js/notifications.js') }}"></script>

    <!-- Edit User Profile Modal -->
    <script src="{{ asset('js/edit-user-profile.js') }}"></script>

    <!-- Logout Handler -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const logoutForm = document.getElementById('logoutForm');
            if (logoutForm) {
                logoutForm.addEventListener('submit', function(e) {
                    // Remove the e.preventDefault() and this.submit() lines
                    // Let the form submit naturally with CSRF token
                });
            }
        });
    </script>

    @stack('scripts')
</body>

</html>
