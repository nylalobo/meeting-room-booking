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
$isLoggedIn = session()->get('isLoggedIn') === true && !empty(session()->get('user_id'));
$userId     = $isLoggedIn ? (int) session()->get('user_id') : null;

$userFirstName = '';
$userLastName  = '';
$userFullName  = '';
$userRole      = '';
$userInitials  = '';
$dbUser        = null;

if ($isLoggedIn && $userId) {
    // Authoritatively resolve user from database to ensure fresh identity on profile/role update
    $userModel = model('App\Models\UserModel');
    $dbUser    = $userModel ? $userModel->find($userId) : null;

    if ($dbUser) {
        $userFirstName = (string) ($dbUser['first_name'] ?? '');
        $userLastName  = (string) ($dbUser['last_name'] ?? '');
        $userFullName  = trim($userFirstName . ' ' . $userLastName);

        if (!empty($dbUser['role_id'])) {
            $roleModel = model('App\Models\RoleModel');
            $role      = $roleModel ? $roleModel->find($dbUser['role_id']) : null;
            if ($role && !empty($role['name'])) {
                $userRole = (string) $role['name'];
            }
        }
    }

    // Fallbacks to session data if DB record is temporarily unavailable
    if (empty($userFullName)) {
        $userFirstName = (string) (session()->get('first_name') ?? '');
        $userLastName  = (string) (session()->get('last_name') ?? '');
        $userFullName  = trim($userFirstName . ' ' . $userLastName);
    }
    if (empty($userFullName)) {
        $userFullName = 'User';
    }
    if (empty($userRole)) {
        $userRole = (string) (session()->get('role_name') ?? 'Member');
    }

    // Dynamic initials: First letter of first name + first letter of last name (e.g. Nyla Lobo -> NL)
    $parts = preg_split('/\s+/', trim($userFullName));
    if (count($parts) >= 2) {
        $userInitials = strtoupper(mb_substr($parts[0], 0, 1) . mb_substr(end($parts), 0, 1));
    } elseif (!empty($parts[0])) {
        $userInitials = strtoupper(mb_substr($parts[0], 0, 2));
    } else {
        $userInitials = 'U';
    }
}
?>

<div class="app-wrapper" id="appWrapper">

    <!-- Ambient Background Depth Orbs -->
    <div class="ambient-orb ambient-orb-1" aria-hidden="true"></div>
    <div class="ambient-orb ambient-orb-2" aria-hidden="true"></div>
    <div class="ambient-orb ambient-orb-3" aria-hidden="true"></div>

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

            <div class="sidebar-nav-section">
                <span class="nav-section-label">MAIN</span>
            </div>

            <a href="<?= base_url('/') ?>" class="nav-item <?= (uri_string() === '' || uri_string() === '/') ? 'active' : '' ?>" title="Dashboard">
                <i class="bi bi-grid-fill nav-icon"></i>
                <span class="nav-text">Dashboard</span>
            </a>

            <div class="sidebar-nav-section">
                <span class="nav-section-label">BOOKING &amp; MEETINGS</span>
            </div>

            <a href="<?= base_url('locations') ?>" class="nav-item <?= str_starts_with(uri_string(), 'locations') ? 'active' : '' ?>" title="Locations">
                <i class="bi bi-geo-alt nav-icon"></i>
                <span class="nav-text">Locations</span>
            </a>

            <a href="<?= base_url('rooms') ?>" class="nav-item <?= str_starts_with(uri_string(), 'rooms') ? 'active' : '' ?>" title="Rooms">
                <i class="bi bi-door-open nav-icon"></i>
                <span class="nav-text">Rooms</span>
            </a>

            <a href="<?= base_url('bookings') ?>" class="nav-item <?= (str_starts_with(uri_string(), 'bookings') && (!isset($_GET['view']) || $_GET['view'] !== 'calendar')) ? 'active' : '' ?>" title="Bookings">
                <i class="bi bi-calendar-check nav-icon"></i>
                <span class="nav-text">Bookings</span>
            </a>

            <a href="<?= base_url('bookings?view=calendar') ?>" class="nav-item <?= (str_starts_with(uri_string(), 'bookings') && (isset($_GET['view']) && $_GET['view'] === 'calendar')) ? 'active' : '' ?>" title="Calendar">
                <i class="bi bi-calendar3 nav-icon"></i>
                <span class="nav-text">Calendar</span>
            </a>

            <a href="<?= base_url('participants') ?>" class="nav-item <?= str_starts_with(uri_string(), 'participants') ? 'active' : '' ?>" title="Participants">
                <i class="bi bi-people nav-icon"></i>
                <span class="nav-text">Participants</span>
            </a>

            <div class="sidebar-nav-section">
                <span class="nav-section-label">RESOURCES</span>
            </div>

            <a href="<?= base_url('equipment') ?>" class="nav-item <?= str_starts_with(uri_string(), 'equipment') ? 'active' : '' ?>" title="Equipment">
                <i class="bi bi-tools nav-icon"></i>
                <span class="nav-text">Equipment</span>
            </a>

            <a href="<?= base_url('facilities') ?>" class="nav-item <?= str_starts_with(uri_string(), 'facilities') ? 'active' : '' ?>" title="Facilities">
                <i class="bi bi-building-gear nav-icon"></i>
                <span class="nav-text">Facilities</span>
            </a>

            <div class="sidebar-nav-section">
                <span class="nav-section-label">ADMINISTRATION</span>
            </div>

            <a href="<?= base_url('users') ?>" class="nav-item <?= str_starts_with(uri_string(), 'users') ? 'active' : '' ?>" title="Users">
                <i class="bi bi-person nav-icon"></i>
                <span class="nav-text">Users</span>
            </a>

            <?php if (strcasecmp((string) ($userRole ?? ''), 'Admin') === 0 || (int) (session()->get('role_id') ?? 0) === 1): ?>
            <a href="<?= base_url('roles') ?>" class="nav-item <?= (str_starts_with(uri_string(), 'roles') || str_starts_with(uri_string(), 'admin/roles')) ? 'active' : '' ?>" title="User Roles">
                <i class="bi bi-shield-lock nav-icon"></i>
                <span class="nav-text">User Roles</span>
            </a>
            <?php endif; ?>

            <a href="<?= base_url('departments') ?>" class="nav-item <?= str_starts_with(uri_string(), 'departments') ? 'active' : '' ?>" title="Departments">
                <i class="bi bi-diagram-3 nav-icon"></i>
                <span class="nav-text">Departments</span>
            </a>

            <?php if (strcasecmp((string) ($userRole ?? ''), 'Admin') === 0 || (int) (session()->get('role_id') ?? 0) === 1): ?>
            <a href="<?= base_url('settings') ?>" class="nav-item <?= (str_starts_with(uri_string(), 'settings') || str_starts_with(uri_string(), 'admin/settings')) ? 'active' : '' ?>" title="Settings & Configuration">
                <i class="bi bi-gear nav-icon"></i>
                <span class="nav-text">Settings</span>
            </a>
            <?php endif; ?>

        </nav>

        <!-- Bottom Account Section (Authenticated Users) -->
        <?php if ($isLoggedIn): ?>
        <div class="sidebar-footer">
            <div class="sidebar-account">
                <div class="account-profile" title="<?= esc($userFullName) ?> (<?= esc($userRole) ?>)">
                    <div class="account-avatar" aria-hidden="true">
                        <?= esc($userInitials) ?>
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
                <button type="button" class="theme-toggle-btn cosmic-toggle" id="themeToggleBtn" aria-label="Toggle theme mode" title="Switch Theme" role="switch" aria-checked="true">
                    <div class="cosmic-track" aria-hidden="true">
                        <span class="cosmic-star star-1"></span>
                        <span class="cosmic-star star-2"></span>
                        <span class="cosmic-star star-3"></span>
                        <span class="cosmic-star star-4"></span>
                        <div class="cosmic-orb">
                            <div class="cosmic-orb-inner">
                                <span class="cosmic-crater crater-1"></span>
                                <span class="cosmic-crater crater-2"></span>
                            </div>
                        </div>
                    </div>
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
        'id'            => $userId,
        'email'         => $dbUser['email'] ?? session()->get('email'),
        'first_name'    => $userFirstName ?: session()->get('first_name'),
        'last_name'     => $userLastName ?: session()->get('last_name'),
        'role_id'       => !empty($dbUser['role_id']) ? (int) $dbUser['role_id'] : (session()->get('role_id') ? (int) session()->get('role_id') : null),
        'role_name'     => $userRole,
        'department_id' => !empty($dbUser['department_id']) ? (int) $dbUser['department_id'] : (session()->get('department_id') ? (int) session()->get('department_id') : null),
        'isLoggedIn'    => $isLoggedIn,
    ]) ?>;
</script>
<script src="<?= base_url('js/app.js') ?>"></script>
<script src="<?= base_url('js/dashboard.js') ?>"></script>
<script src="<?= base_url('js/locations.js') ?>"></script>
<script src="<?= base_url('js/rooms.js') ?>"></script>
<script src="<?= base_url('js/bookings.js') ?>"></script>
<script src="<?= base_url('js/participants.js') ?>"></script>
<script src="<?= base_url('js/facilities.js') ?>"></script>
<script src="<?= base_url('js/equipment.js') ?>"></script>
<script src="<?= base_url('js/users.js') ?>"></script>
<script src="<?= base_url('js/roles.js') ?>"></script>
<script src="<?= base_url('js/settings.js') ?>"></script>
<script src="<?= base_url('js/departments.js') ?>"></script>

</body>
</html>