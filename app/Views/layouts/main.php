<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= $title ?? 'Dashboard' ?> - MeetSpace Enterprise Suite</title>

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

<div class="app-wrapper">

    <!-- Dark Charcoal Sidebar -->
    <aside class="sidebar">

        <!-- Logo & Branding -->
        <a href="<?= base_url('/') ?>" class="sidebar-brand" aria-label="MeetSpace Enterprise Suite">
            <div class="brand-logo-box">
                <img src="<?= base_url('assets/images/meetspace-logo.png') ?>" alt="MeetSpace M Symbol" class="brand-logo-crop">
            </div>
            <div class="brand-info">
                <div class="brand-title">Meet<span class="brand-title-accent">Space</span></div>
                <div class="brand-subtitle">Enterprise Suite</div>
            </div>
        </a>

        <!-- Primary Action Button -->
        <div class="sidebar-action">
            <a href="<?= base_url('bookings') ?>" class="btn-new-booking">
                New Booking
            </a>
        </div>

        <!-- Navigation Menu -->
        <nav class="sidebar-nav">

            <a href="<?= base_url('/') ?>" class="nav-item <?= (uri_string() === '' || uri_string() === '/') ? 'active' : '' ?>">
                <i class="bi bi-grid-fill nav-icon"></i>
                <span>Dashboard</span>
            </a>

            <a href="<?= base_url('locations') ?>" class="nav-item <?= str_starts_with(uri_string(), 'locations') ? 'active' : '' ?>">
                <i class="bi bi-geo-alt nav-icon"></i>
                <span>Locations</span>
            </a>

            <a href="<?= base_url('rooms') ?>" class="nav-item <?= str_starts_with(uri_string(), 'rooms') ? 'active' : '' ?>">
                <i class="bi bi-door-open nav-icon"></i>
                <span>Rooms</span>
            </a>

            <a href="<?= base_url('bookings') ?>" class="nav-item <?= str_starts_with(uri_string(), 'bookings') ? 'active' : '' ?>">
                <i class="bi bi-calendar-check nav-icon"></i>
                <span>Bookings</span>
            </a>

            <a href="<?= base_url('participants') ?>" class="nav-item <?= str_starts_with(uri_string(), 'participants') ? 'active' : '' ?>">
                <i class="bi bi-people nav-icon"></i>
                <span>Participants</span>
            </a>

            <a href="<?= base_url('facilities') ?>" class="nav-item <?= str_starts_with(uri_string(), 'facilities') ? 'active' : '' ?>">
                <i class="bi bi-building-gear nav-icon"></i>
                <span>Facilities</span>
            </a>

            <a href="<?= base_url('users') ?>" class="nav-item <?= str_starts_with(uri_string(), 'users') ? 'active' : '' ?>">
                <i class="bi bi-person nav-icon"></i>
                <span>Users</span>
            </a>

            <a href="<?= base_url('departments') ?>" class="nav-item <?= str_starts_with(uri_string(), 'departments') ? 'active' : '' ?>">
                <i class="bi bi-diagram-3 nav-icon"></i>
                <span>Departments</span>
            </a>

        </nav>

    </aside>

    <!-- Main Content Area -->
    <div class="main-area">

        <!-- Top Search Area -->
        <header class="topbar">
            <div class="topbar-search">
                <i class="bi bi-search search-icon"></i>
                <input type="text" class="search-input" placeholder="Search..." aria-label="Search">
            </div>
        </header>

        <!-- Page Content Body -->
        <main class="content">
            <?= $this->renderSection('content') ?>
        </main>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
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