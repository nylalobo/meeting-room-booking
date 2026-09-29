<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
$userGreeting = (string) (session()->get('first_name') ?? '');
if (trim($userGreeting) === '') {
    $userGreeting = 'User';
}
?>

<!-- Hero Banner -->
<div class="dashboard-hero glass-panel animate-fade-up">
    <div class="hero-meta-row">
        <div class="hero-greeting-pill" id="heroTimeGreeting">
            <span class="pulsing-dot pulsing-dot-green"></span>
            <span id="heroGreetingText">Good day</span> · MeetSpace Active
        </div>
        <div class="hero-status-pill">
            <i class="bi bi-shield-check"></i> System Operational
        </div>
        <div class="hero-date-chip">
            <i class="bi bi-calendar3"></i> <?= date('l, F j, Y') ?>
        </div>
    </div>
    <h1 class="welcome-heading">Welcome back, <?= esc($userGreeting) ?>! 👋</h1>
    <p class="welcome-subtitle" id="welcomeTimeSubtitle">Here's what's happening across MeetSpace today.</p>
</div>

<!-- Statistics Cards -->
<div class="row g-3 stats-row stagger-children">

    <!-- Total Rooms -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card glass-card hover-lift">
            <div class="stat-icon-wrapper stat-icon-cyan">
                <i class="bi bi-door-open"></i>
            </div>
            <div class="stat-meta">
                <div class="stat-label">TOTAL ROOMS</div>
                <div class="stat-value" id="total-rooms">0</div>
            </div>
        </div>
    </div>

    <!-- Total Bookings -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card glass-card hover-lift">
            <div class="stat-icon-wrapper stat-icon-blue">
                <i class="bi bi-calendar-check"></i>
            </div>
            <div class="stat-meta">
                <div class="stat-label">TOTAL BOOKINGS</div>
                <div class="stat-value" id="total-bookings">0</div>
            </div>
        </div>
    </div>

    <!-- Pending Requests -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card glass-card hover-lift">
            <div class="stat-icon-wrapper stat-icon-amber">
                <i class="bi bi-clipboard-data"></i>
            </div>
            <div class="stat-meta">
                <div class="stat-label">PENDING REQUESTS</div>
                <div class="stat-value" id="pending-requests">0</div>
            </div>
        </div>
    </div>

    <!-- Active Users -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card glass-card hover-lift">
            <div class="stat-icon-wrapper stat-icon-purple">
                <i class="bi bi-people"></i>
            </div>
            <div class="stat-meta">
                <div class="stat-label">ACTIVE USERS</div>
                <div class="stat-value" id="active-users">0</div>
            </div>
        </div>
    </div>

</div>

<!-- MeetSpace Today - Interactive Flash Cards -->
<section class="meetspace-today-section mt-4 mb-3 animate-fade-up">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h2 class="panel-title d-flex align-items-center gap-2 mb-1" style="font-size: 16px;">
                <i class="bi bi-stars text-cyan"></i>
                <span>MeetSpace Today</span>
            </h2>
            <p class="text-muted small mb-0" style="font-size: 12px;">Interactive operational cards · Click any card to flip for details</p>
        </div>
        <span class="badge bg-secondary-subtle text-secondary border px-2 py-1 small" style="font-size: 11px;">
            <i class="bi bi-arrow-repeat me-1"></i>Live Sync
        </span>
    </div>

    <div class="flash-card-grid stagger-children">

        <!-- Card 1: Next Meeting -->
        <div class="flash-card-container">
            <div class="flash-card glass-card" tabindex="0" role="button" aria-label="Next meeting card. Press Enter to flip for details." id="flashCardMeeting">
                <!-- Front -->
                <div class="flash-card-front">
                    <div class="flash-card-header">
                        <span><i class="bi bi-calendar-event me-1"></i>Next Meeting</span>
                        <div class="flash-card-icon flash-card-icon-blue">
                            <i class="bi bi-clock-history"></i>
                        </div>
                    </div>
                    <div class="flash-card-body">
                        <h3 class="flash-card-title" id="fcMeetingTitle">Checking schedule...</h3>
                        <p class="flash-card-detail" id="fcMeetingTime">
                            <i class="bi bi-calendar3"></i>
                            <span>Today</span>
                        </p>
                        <p class="flash-card-detail" id="fcMeetingRoom">
                            <i class="bi bi-geo-alt"></i>
                            <span>Conference Room</span>
                        </p>
                    </div>
                    <div class="flash-card-footer">
                        <span class="badge-status badge-upcoming" id="fcMeetingStatus">Upcoming</span>
                        <span class="flash-card-flip-btn">Details <i class="bi bi-arrow-repeat"></i></span>
                    </div>
                </div>
                <!-- Back -->
                <div class="flash-card-back">
                    <div class="flash-card-header">
                        <span>Meeting Overview</span>
                        <span class="badge bg-info-subtle text-info small">Active</span>
                    </div>
                    <div class="flash-card-body">
                        <div class="small text-muted mb-1" id="fcBackDetails">Full booking schedule and attendee list available in Bookings.</div>
                        <div class="small fw-semibold text-white mb-2" id="fcBackRoomInfo">Select to open calendar.</div>
                    </div>
                    <div class="flash-card-footer">
                        <a href="<?= base_url('bookings') ?>" class="flash-card-action-btn" onclick="event.stopPropagation();">
                            <span>Open Bookings</span>
                            <i class="bi bi-arrow-right"></i>
                        </a>
                        <span class="flash-card-flip-btn">Flip back <i class="bi bi-arrow-counterclockwise"></i></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 2: Quick Room Availability -->
        <div class="flash-card-container">
            <div class="flash-card glass-card" tabindex="0" role="button" aria-label="Room availability card. Press Enter to flip for details." id="flashCardRooms">
                <!-- Front -->
                <div class="flash-card-front">
                    <div class="flash-card-header">
                        <span><i class="bi bi-door-open me-1"></i>Room Availability</span>
                        <div class="flash-card-icon flash-card-icon-green">
                            <i class="bi bi-lightning-charge-fill"></i>
                        </div>
                    </div>
                    <div class="flash-card-body">
                        <h3 class="flash-card-title d-flex align-items-center gap-2" id="fcAvailTitle">
                            <span class="pulsing-dot pulsing-dot-green"></span>
                            <span id="fcAvailCount">Available Now</span>
                        </h3>
                        <p class="flash-card-detail" id="fcTopRoom">
                            <i class="bi bi-building"></i>
                            <span>Spaces ready for instant booking</span>
                        </p>
                        <p class="flash-card-detail" id="fcAvailSeats">
                            <i class="bi bi-people"></i>
                            <span>Full capacity available</span>
                        </p>
                    </div>
                    <div class="flash-card-footer">
                        <span class="text-success small fw-semibold">Instant Access</span>
                        <span class="flash-card-flip-btn">Details <i class="bi bi-arrow-repeat"></i></span>
                    </div>
                </div>
                <!-- Back -->
                <div class="flash-card-back">
                    <div class="flash-card-header">
                        <span>Rooms Overview</span>
                        <span class="badge bg-success-subtle text-success small">Live</span>
                    </div>
                    <div class="flash-card-body">
                        <div class="small text-muted mb-1">Check availability finder or browse conference rooms across all locations.</div>
                        <div class="small fw-semibold text-white mb-2">QR check-in placards equipped in all active rooms.</div>
                    </div>
                    <div class="flash-card-footer">
                        <a href="<?= base_url('rooms') ?>" class="flash-card-action-btn" onclick="event.stopPropagation();">
                            <span>Browse Rooms</span>
                            <i class="bi bi-arrow-right"></i>
                        </a>
                        <span class="flash-card-flip-btn">Flip back <i class="bi bi-arrow-counterclockwise"></i></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 3: Pending Approvals Queue -->
        <div class="flash-card-container">
            <div class="flash-card glass-card" tabindex="0" role="button" aria-label="Approvals card. Press Enter to flip for details." id="flashCardApprovals">
                <!-- Front -->
                <div class="flash-card-front">
                    <div class="flash-card-header">
                        <span><i class="bi bi-clipboard-check me-1"></i>Approval Queue</span>
                        <div class="flash-card-icon flash-card-icon-amber">
                            <i class="bi bi-inbox-fill"></i>
                        </div>
                    </div>
                    <div class="flash-card-body">
                        <h3 class="flash-card-title" id="fcPendingTitle">Review Requests</h3>
                        <p class="flash-card-detail" id="fcPendingDetail">
                            <i class="bi bi-hourglass-split"></i>
                            <span>0 requests pending review</span>
                        </p>
                        <p class="flash-card-detail">
                            <i class="bi bi-person-badge"></i>
                            <span>Requires Manager / Admin action</span>
                        </p>
                    </div>
                    <div class="flash-card-footer">
                        <span class="badge-status badge-pending" id="fcPendingBadge">Pending</span>
                        <span class="flash-card-flip-btn">Details <i class="bi bi-arrow-repeat"></i></span>
                    </div>
                </div>
                <!-- Back -->
                <div class="flash-card-back">
                    <div class="flash-card-header">
                        <span>Workflow Actions</span>
                        <span class="badge bg-warning-subtle text-warning small">Review</span>
                    </div>
                    <div class="flash-card-body">
                        <div class="small text-muted mb-1">Approve or reject meeting reservation requests with instant attendee notifications.</div>
                        <div class="small fw-semibold text-white mb-2">One-click approval workflow.</div>
                    </div>
                    <div class="flash-card-footer">
                        <a href="<?= base_url('bookings') ?>" class="flash-card-action-btn" onclick="event.stopPropagation();">
                            <span>Open Approvals</span>
                            <i class="bi bi-arrow-right"></i>
                        </a>
                        <span class="flash-card-flip-btn">Flip back <i class="bi bi-arrow-counterclockwise"></i></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 4: Equipment & Resources -->
        <div class="flash-card-container">
            <div class="flash-card glass-card" tabindex="0" role="button" aria-label="Equipment and resources card. Press Enter to flip for details." id="flashCardOperations">
                <!-- Front -->
                <div class="flash-card-front">
                    <div class="flash-card-header">
                        <span><i class="bi bi-projector me-1"></i>Equipment & Assets</span>
                        <div class="flash-card-icon flash-card-icon-purple">
                            <i class="bi bi-layers-fill"></i>
                        </div>
                    </div>
                    <div class="flash-card-body">
                        <h3 class="flash-card-title">Equipment & Resources</h3>
                        <p class="flash-card-detail">
                            <i class="bi bi-cpu"></i>
                            <span>Hardware & AV assets active</span>
                        </p>
                        <p class="flash-card-detail">
                            <i class="bi bi-projector"></i>
                            <span>Equipment checkout & return</span>
                        </p>
                    </div>
                    <div class="flash-card-footer">
                        <span class="text-info small fw-semibold">Equipped</span>
                        <span class="flash-card-flip-btn">Details <i class="bi bi-arrow-repeat"></i></span>
                    </div>
                </div>
                <!-- Back -->
                <div class="flash-card-back">
                    <div class="flash-card-header">
                        <span>Resource Services</span>
                        <span class="badge bg-primary-subtle text-primary small">Enterprise</span>
                    </div>
                    <div class="flash-card-body">
                        <div class="small text-muted mb-1">Manage hardware inventory, meeting room projectors, and equipment assets.</div>
                        <div class="small fw-semibold text-white mb-2">Integrated into booking workflows.</div>
                    </div>
                    <div class="flash-card-footer">
                        <a href="<?= base_url('equipment') ?>" class="flash-card-action-btn" onclick="event.stopPropagation();">
                            <span>Equipment Catalog</span>
                            <i class="bi bi-arrow-right"></i>
                        </a>
                        <span class="flash-card-flip-btn">Flip back <i class="bi bi-arrow-counterclockwise"></i></span>
                    </div>
                </div>
            </div>
        </div>

    </div>
</section>

<!-- Main Dashboard Grid -->
<div class="row g-4 dashboard-grid mt-1">

    <!-- Left Column: Upcoming Meetings -->
    <div class="col-12 col-lg-8">
        <div class="panel-card glass-panel upcoming-meetings-card">

            <div class="panel-header">
                <h2 class="panel-title">Upcoming Meetings</h2>
                <a href="<?= base_url('bookings') ?>" class="link-view-all">View All →</a>
            </div>

            <div class="table-responsive">
                <table class="table custom-dark-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col" style="width: 32%;">MEETING NAME</th>
                            <th scope="col" style="width: 28%;">ROOM</th>
                            <th scope="col" style="width: 22%;">TIME</th>
                            <th scope="col" style="width: 18%;" class="text-end">STATUS</th>
                        </tr>
                    </thead>
                    <tbody id="upcomingMeetingsBody">
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted-blue">
                                <div class="meetspace-loader py-2" role="status" aria-live="polite">
                                    <div class="meetspace-loader-track">
                                        <div class="meetspace-loader-bar">
                                            <div class="meetspace-loader-highlights"></div>
                                        </div>
                                    </div>
                                    <div class="meetspace-loader-text">
                                        <span class="loader-label">Loading upcoming meetings</span><span class="loader-dots"><span>.</span><span>.</span><span>.</span></span>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>
    </div>

    <!-- Right Column: Quick Availability & Performance -->
    <div class="col-12 col-lg-4 d-flex flex-column gap-3">

        <!-- Quick Availability Panel -->
        <div class="panel-card glass-panel quick-availability-card">

            <div class="panel-header mb-3">
                <h2 class="panel-title d-flex align-items-center gap-2">
                    <i class="bi bi-lightning-charge-fill text-cyan"></i>
                    <span>Quick Availability</span>
                </h2>
            </div>

            <div class="room-availability-list" id="quickAvailabilityList">
                <div class="meetspace-loader py-2" role="status" aria-live="polite">
                    <div class="meetspace-loader-track" style="width: 140px; height: 8px;">
                        <div class="meetspace-loader-bar">
                            <div class="meetspace-loader-highlights"></div>
                        </div>
                    </div>
                    <div class="meetspace-loader-text" style="font-size: 12.5px;">
                        <span class="loader-label">Loading room availability</span><span class="loader-dots"><span>.</span><span>.</span><span>.</span></span>
                    </div>
                </div>
            </div>

        </div>

        <!-- Performance Panel -->
        <div class="panel-card glass-panel performance-card">
            <div class="perf-icon-glow">
                <svg width="48" height="32" viewBox="0 0 60 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M6 32L18 20L30 26L44 10L54 18" stroke="#818cf8" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"/>
                    <circle cx="6" cy="32" r="4.5" fill="#a5b4fc" />
                    <circle cx="18" cy="20" r="4.5" fill="#a5b4fc" />
                    <circle cx="30" cy="26" r="4.5" fill="#a5b4fc" />
                    <circle cx="44" cy="10" r="5" fill="#c7d2fe" />
                    <circle cx="54" cy="18" r="4.5" fill="#a5b4fc" />
                </svg>
            </div>
            <p class="perf-text">System performing optimally</p>
        </div>

    </div>

</div>

<?= $this->endSection() ?>