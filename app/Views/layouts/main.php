<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= $title ?? 'Dashboard' ?> - MeetSpace Enterprise Suite</title>

    <!-- Anti-Flash Theme Script -->
    <script>
        (function() {
            try {
                var savedTheme = localStorage.getItem('meetspace-theme');
                if (savedTheme === 'light' || savedTheme === 'dark') {
                    document.documentElement.setAttribute('data-theme', savedTheme);
                } else {
                    document.documentElement.setAttribute('data-theme', 'dark');
                }
            } catch (e) {
                document.documentElement.setAttribute('data-theme', 'dark');
            }
        })();
    </script>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= base_url('css/app.css') ?>">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= base_url('assets/images/meetspace-icon.png') ?>">
</head>

<body>

<?php
$isLoggedIn    = session()->get('isLoggedIn') === true && !empty(session()->get('user_id'));
$userFirstName = (string) (session()->get('first_name') ?? '');
$userLastName  = (string) (session()->get('last_name') ?? '');
$userFullName  = trim($userFirstName . ' ' . $userLastName);
if (empty($userFullName)) {
    $userFullName = 'User';
}
$userRole    = (string) (session()->get('role_name') ?? 'Member');
$userInitial = strtoupper(substr($userFirstName ?: 'U', 0, 1));
?>

<div class="app-wrapper" id="appWrapper">

    <!-- Mobile Sidebar Backdrop Overlay -->
    <div class="sidebar-backdrop" id="sidebarBackdrop" aria-hidden="true"></div>

    <!-- Sidebar -->
    <aside class="sidebar" id="appSidebar" aria-label="Main Navigation">

        <!-- Logo & Header -->
        <div class="sidebar-header">
            <a href="<?= base_url('/') ?>" class="sidebar-brand" aria-label="MeetSpace Enterprise Suite">
                <div class="brand-logo-box">
                    <img src="<?= base_url('assets/images/meetspace-logo.png') ?>" alt="MeetSpace M Symbol" class="brand-logo-crop">
                </div>
                <div class="brand-info">
                    <div class="brand-title">Meet<span class="brand-title-accent">Space</span></div>
                    <div class="brand-subtitle">Enterprise Suite</div>
                </div>
            </a>
            <button type="button" class="sidebar-close-btn" id="sidebarCloseBtn" aria-label="Close navigation menu" title="Close Menu">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <!-- Primary Action Button -->
        <div class="sidebar-action">
            <a href="<?= base_url('bookings') ?>" class="btn-new-booking" title="New Booking">
                <i class="bi bi-plus-lg new-booking-icon"></i>
                <span class="new-booking-text">New Booking</span>
            </a>
        </div>

        <!-- Navigation Menu -->
        <nav class="sidebar-nav">

            <a href="<?= base_url('/') ?>" class="nav-item <?= (uri_string() === '' || uri_string() === '/') ? 'active' : '' ?>" title="Dashboard">
                <i class="bi bi-grid-fill nav-icon"></i>
                <span class="nav-text">Dashboard</span>
            </a>

            <a href="<?= base_url('locations') ?>" class="nav-item <?= str_starts_with(uri_string(), 'locations') ? 'active' : '' ?>" title="Locations">
                <i class="bi bi-geo-alt nav-icon"></i>
                <span class="nav-text">Locations</span>
            </a>

            <a href="<?= base_url('rooms') ?>" class="nav-item <?= str_starts_with(uri_string(), 'rooms') ? 'active' : '' ?>" title="Rooms">
                <i class="bi bi-door-open nav-icon"></i>
                <span class="nav-text">Rooms</span>
            </a>

            <a href="<?= base_url('bookings') ?>" class="nav-item <?= str_starts_with(uri_string(), 'bookings') ? 'active' : '' ?>" title="Bookings">
                <i class="bi bi-calendar-check nav-icon"></i>
                <span class="nav-text">Bookings</span>
            </a>

            <a href="<?= base_url('participants') ?>" class="nav-item <?= str_starts_with(uri_string(), 'participants') ? 'active' : '' ?>" title="Participants">
                <i class="bi bi-people nav-icon"></i>
                <span class="nav-text">Participants</span>
            </a>

            <a href="<?= base_url('facilities') ?>" class="nav-item <?= str_starts_with(uri_string(), 'facilities') ? 'active' : '' ?>" title="Facilities">
                <i class="bi bi-building-gear nav-icon"></i>
                <span class="nav-text">Facilities</span>
            </a>

            <a href="<?= base_url('users') ?>" class="nav-item <?= str_starts_with(uri_string(), 'users') ? 'active' : '' ?>" title="Users">
                <i class="bi bi-person nav-icon"></i>
                <span class="nav-text">Users</span>
            </a>

            <a href="<?= base_url('departments') ?>" class="nav-item <?= str_starts_with(uri_string(), 'departments') ? 'active' : '' ?>" title="Departments">
                <i class="bi bi-diagram-3 nav-icon"></i>
                <span class="nav-text">Departments</span>
            </a>

        </nav>

        <!-- Bottom Account Section (Authenticated Users) -->
        <?php if ($isLoggedIn): ?>
        <div class="sidebar-footer">
            <div class="sidebar-account">
                <div class="account-profile" title="<?= esc($userFullName) ?> (<?= esc($userRole) ?>)">
                    <div class="account-avatar" aria-hidden="true">
                        <?= esc($userInitial) ?>
                    </div>
                    <div class="account-info">
                        <span class="account-name"><?= esc($userFullName) ?></span>
                        <span class="account-role"><?= esc($userRole) ?></span>
                    </div>
                </div>
                <button type="button" class="btn-sidebar-logout" id="sidebarLogoutBtn" aria-label="Log out of MeetSpace" title="Logout">
                    <i class="bi bi-box-arrow-right logout-icon"></i>
                    <span class="logout-text">Logout</span>
                </button>
            </div>
        </div>
        <?php endif; ?>

    </aside>

    <!-- Main Content Area -->
    <div class="main-area">

        <!-- Top Navigation / Search Bar -->
        <header class="topbar">
            <div class="topbar-left">
                <button type="button" class="sidebar-toggle-btn" id="sidebarToggleBtn" aria-label="Toggle navigation sidebar" aria-expanded="false" title="Toggle Sidebar">
                    <i class="bi bi-list"></i>
                </button>
                <div class="topbar-search">
                    <i class="bi bi-search search-icon"></i>
                    <input type="text" class="search-input" placeholder="Search..." aria-label="Search">
                </div>
            </div>

            <div class="topbar-actions">
                <button type="button" class="theme-toggle-btn" id="themeToggleBtn" aria-label="Toggle theme mode" title="Switch Theme">
                    <span class="theme-icon-box">
                        <i class="bi bi-moon-stars theme-icon-dark"></i>
                        <i class="bi bi-sun theme-icon-light"></i>
                    </span>
                    <span class="theme-label" id="themeLabel">Dark</span>
                </button>
            </div>
        </header>

        <!-- Page Content Body -->
        <main class="content">
            <?= $this->renderSection('content') ?>
        </main>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    window.MeetSpaceUser = <?= json_encode([
        'id'            => session()->get('user_id') ? (int) session()->get('user_id') : null,
        'email'         => session()->get('email'),
        'first_name'    => session()->get('first_name'),
        'last_name'     => session()->get('last_name'),
        'role_id'       => session()->get('role_id') ? (int) session()->get('role_id') : null,
        'role_name'     => session()->get('role_name'),
        'department_id' => session()->get('department_id') ? (int) session()->get('department_id') : null,
        'isLoggedIn'    => session()->get('isLoggedIn') === true,
    ]) ?>;
</script>
<script src="<?= base_url('js/app.js') ?>"></script>
<script src="<?= base_url('js/dashboard.js') ?>"></script>
<script src="<?= base_url('js/locations.js') ?>"></script>
<script src="<?= base_url('js/rooms.js') ?>"></script>
<script src="<?= base_url('js/bookings.js') ?>"></script>
<script src="<?= base_url('js/participants.js') ?>"></script>
<script src="<?= base_url('js/facilities.js') ?>"></script>
<script src="<?= base_url('js/users.js') ?>"></script>
<script src="<?= base_url('js/departments.js') ?>"></script>

</body>
</html>