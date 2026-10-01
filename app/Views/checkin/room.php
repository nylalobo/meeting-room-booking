<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'Room Check-in') ?> - MeetSpace Enterprise Suite</title>

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
<body class="checkin-body"
      data-room-id="<?= !empty($room['id']) ? (int) $room['id'] : '' ?>"
      data-room-code="<?= esc($roomCode ?? '') ?>"
      data-booking-id="<?= !empty($booking['id']) ? (int) $booking['id'] : '' ?>"
      data-is-logged-in="<?= $isLoggedIn ? '1' : '0' ?>"
      data-is-authorized="<?= $isAuthorized ? '1' : '0' ?>">

    <!-- Top Navigation Header -->
    <header class="checkin-topbar">
        <div class="checkin-topbar-inner">
            <a href="<?= base_url('/') ?>" class="checkin-brand" aria-label="MeetSpace Enterprise Suite">
                <div class="brand-logo-box">
                    <img src="<?= base_url('assets/images/meetspace-logo.png') ?>" alt="MeetSpace M Symbol" class="brand-logo-crop">
                </div>
                <div class="brand-info">
                    <div class="brand-title">Meet<span class="brand-title-accent">Space</span></div>
                    <div class="brand-subtitle">Room Check-in</div>
                </div>
            </a>

            <div class="checkin-topbar-actions">
                <!-- Theme Toggle Button -->
                <button type="button" class="btn btn-sm checkin-theme-toggle" id="themeToggleBtn" aria-label="Toggle theme" title="Toggle theme">
                    <i class="bi bi-moon-stars" id="themeIcon"></i>
                </button>

                <?php if ($isLoggedIn && !empty($user)): ?>
                    <div class="dropdown">
                        <button class="btn btn-sm checkin-user-pill dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="checkin-user-avatar"><?= esc(strtoupper(substr($user['first_name'] ?: 'U', 0, 1))) ?></span>
                            <span class="checkin-user-name d-none d-sm-inline"><?= esc($user['first_name']) ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                            <li class="dropdown-header">
                                <div class="fw-bold"><?= esc($user['first_name'] . ' ' . $user['last_name']) ?></div>
                                <div class="small text-muted"><?= esc($user['email']) ?></div>
                                <div class="badge bg-primary-subtle text-primary mt-1"><?= esc($user['role_name']) ?></div>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?= base_url('/') ?>"><i class="bi bi-grid me-2"></i> Dashboard</a></li>
                            <li><a class="dropdown-item" href="<?= base_url('bookings') ?>"><i class="bi bi-calendar-event me-2"></i> Bookings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?= base_url('logout') ?>"><i class="bi bi-box-arrow-right me-2"></i> Sign Out</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="<?= esc($loginUrl) ?>" class="btn btn-sm btn-outline-primary checkin-header-login-btn">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Main Content Page -->
    <main class="checkin-page-wrapper">
        <div class="checkin-container">

            <!-- Dynamic Alert Container -->
            <div id="checkinAlert" class="alert d-none mb-3" role="alert"></div>

            <?php if ($state === 'room_not_found'): ?>
                <!-- 404 Room Not Found Card -->
                <div class="card checkin-card text-center p-4">
                    <div class="checkin-state-icon-wrap text-warning mb-3">
                        <i class="bi bi-geo-alt-slash" style="font-size: 3rem;"></i>
                    </div>
                    <h2 class="checkin-card-title h4 mb-2">Room Not Found</h2>
                    <p class="text-muted mb-4">
                        The room code <strong>"<?= esc($roomCode) ?>"</strong> does not match any registered meeting room.
                        Please ensure you scanned an official MeetSpace room QR code.
                    </p>
                    <div>
                        <a href="<?= base_url('/') ?>" class="btn btn-outline-primary">
                            <i class="bi bi-arrow-left me-1"></i> Return to Dashboard
                        </a>
                    </div>
                </div>

            <?php elseif ($state === 'room_inactive'): ?>
                <!-- 422 Room Inactive Card -->
                <div class="card checkin-card text-center p-4">
                    <div class="checkin-state-icon-wrap text-danger mb-3">
                        <i class="bi bi-slash-circle" style="font-size: 3rem;"></i>
                    </div>
                    <h2 class="checkin-card-title h4 mb-2"><?= esc($room['name'] ?? 'Room') ?> Unavailable</h2>
                    <div class="mb-3">
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-1">
                            <?= esc($room['room_code'] ?? $roomCode) ?> &bull; Inactive
                        </span>
                    </div>
                    <p class="text-muted mb-4">
                        This meeting room is currently inactive or under maintenance. Check-ins are disabled for this room.
                    </p>
                    <div>
                        <a href="<?= base_url('/') ?>" class="btn btn-outline-primary">
                            <i class="bi bi-arrow-left me-1"></i> Return to Dashboard
                        </a>
                    </div>
                </div>

            <?php else: ?>
                <!-- Valid Room: Header Card -->
                <div class="card checkin-card checkin-room-header-card mb-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                            <div>
                                <div class="text-uppercase small text-muted fw-semibold mb-1">
                                    <i class="bi bi-door-open me-1"></i> Meeting Room
                                </div>
                                <h1 class="checkin-room-name h3 mb-0" id="roomName"><?= esc($room['name']) ?></h1>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge checkin-room-badge fs-6" id="roomCodeBadge">
                                    <i class="bi bi-qr-code me-1"></i> <?= esc($room['room_code']) ?>
                                </span>
                                <button type="button" class="btn btn-sm checkin-refresh-btn" id="checkinRefreshBtn" title="Refresh room status" aria-label="Refresh room status">
                                    <i class="bi bi-arrow-clockwise"></i>
                                </button>
                            </div>
                        </div>

                        <div class="checkin-room-meta d-flex flex-wrap gap-3 mt-3 text-muted small">
                            <span class="meta-item">
                                <i class="bi bi-building me-1 text-primary"></i> <span id="roomLocation"><?= esc($room['location_name']) ?></span>
                            </span>
                            <?php if (!empty($room['floor'])): ?>
                                <span class="meta-item">
                                    <i class="bi bi-layers me-1 text-info"></i> Floor <?= esc($room['floor']) ?>
                                </span>
                            <?php endif; ?>
                            <?php if (!empty($room['capacity'])): ?>
                                <span class="meta-item">
                                    <i class="bi bi-people me-1 text-success"></i> Up to <?= esc($room['capacity']) ?> people
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Meeting Content Area -->
                <div id="meetingSection">
                    <?php if ($state === 'no_eligible_booking' || empty($booking)): ?>
                        <!-- No Active Meeting Card -->
                        <div class="card checkin-card text-center p-4" id="noMeetingCard">
                            <div class="mb-3 text-muted">
                                <i class="bi bi-calendar-x" style="font-size: 2.8rem; opacity: 0.6;"></i>
                            </div>
                            <h2 class="h5 mb-2">No Active Meeting</h2>
                            <p class="text-muted small mb-3">
                                There are no approved meetings currently scheduled in this room eligible for check-in.
                            </p>

                            <div class="p-3 checkin-notice-box rounded mb-3 text-start small">
                                <div class="fw-semibold mb-1">
                                    <i class="bi bi-info-circle me-1 text-primary"></i> When does check-in open?
                                </div>
                                <div class="text-muted">
                                    Check-in opens <strong>15 minutes prior</strong> to the scheduled meeting start time and remains open until the meeting concludes.
                                </div>
                            </div>

                            <?php if ($isLoggedIn && !empty($user)): ?>
                                <div class="small text-muted mb-3">
                                    Signed in as <strong><?= esc($user['first_name'] . ' ' . $user['last_name']) ?></strong>
                                </div>
                                <div>
                                    <a href="<?= base_url('bookings') ?>" class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-plus-lg me-1"></i> Schedule a Meeting
                                    </a>
                                </div>
                            <?php else: ?>
                                <div>
                                    <a href="<?= esc($loginUrl) ?>" class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-box-arrow-in-right me-1"></i> Sign In to MeetSpace
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>

                    <?php else: ?>
                        <!-- Eligible Meeting Card -->
                        <div class="card checkin-card mb-3" id="eligibleMeetingCard">
                            <div class="card-header checkin-card-header d-flex justify-content-between align-items-center">
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                    <i class="bi bi-circle-fill me-1 small"></i> In Session / Upcoming
                                </span>
                                <?php if (!empty($booking['is_recurring'])): ?>
                                    <span class="badge bg-info-subtle text-info">
                                        <i class="bi bi-repeat me-1"></i> Recurring
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="card-body">
                                <h2 class="checkin-booking-title h4 mb-3" id="bookingTitle"><?= esc($booking['title']) ?></h2>

                                <div class="checkin-time-banner p-3 rounded mb-3 d-flex align-items-center gap-3">
                                    <i class="bi bi-clock-history text-primary fs-3 flex-shrink-0"></i>
                                    <div>
                                        <div class="fw-bold fs-6" id="bookingTimeRange">
                                            <?= date('g:i A', strtotime($booking['start_time'])) ?> &ndash; <?= date('g:i A', strtotime($booking['end_time'])) ?>
                                        </div>
                                        <div class="small text-muted" id="bookingDate">
                                            <?= date('l, F j, Y', strtotime($booking['start_time'])) ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="checkin-organizer-row d-flex align-items-center gap-2 text-muted small mb-3">
                                    <i class="bi bi-person-badge text-secondary fs-5"></i>
                                    <div>
                                        <div>Organizer: <strong class="text-white-em" id="organizerName"><?= esc($booking['organizer_name']) ?></strong></div>
                                        <?php if (!empty($booking['organizer_dept'])): ?>
                                            <div class="text-muted small"><?= esc($booking['organizer_dept']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- User Check-in Flow Area -->
                                <div class="checkin-action-area border-top pt-3 mt-2" id="checkinActionArea">
                                    <?php if (!$isLoggedIn): ?>
                                        <!-- State: Login Required -->
                                        <div class="checkin-auth-prompt text-center p-3 rounded" id="stateLoginRequired">
                                            <div class="mb-2"><i class="bi bi-shield-lock fs-2 text-primary"></i></div>
                                            <h3 class="h6 fw-bold mb-1">Sign In to Check In</h3>
                                            <p class="small text-muted mb-3">Please sign in with your enterprise account to confirm your attendance for this meeting.</p>
                                            <a href="<?= esc($loginUrl) ?>" class="btn btn-primary w-100 btn-checkin-primary py-2">
                                                <i class="bi bi-box-arrow-in-right me-2"></i> Sign In to Check In
                                            </a>
                                        </div>

                                    <?php elseif (!$isAuthorized): ?>
                                        <!-- State: Logged In but Unauthorized -->
                                        <div class="checkin-unauthorized-box p-3 rounded text-center" id="stateNotAuthorized">
                                            <div class="mb-2"><i class="bi bi-person-x fs-2 text-warning"></i></div>
                                            <h3 class="h6 fw-bold mb-1">Not on Attendee List</h3>
                                            <p class="small text-muted mb-2">
                                                You are signed in as <strong><?= esc($user['first_name'] . ' ' . $user['last_name']) ?></strong>,
                                                but you are not listed as the organizer or an attendee of this meeting.
                                            </p>
                                            <div class="small text-muted">
                                                If you should have access, please contact the organizer (<?= esc($booking['organizer_name']) ?>).
                                            </div>
                                        </div>

                                    <?php else: ?>
                                        <!-- State: Logged In & Authorized -->
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <span class="small text-muted">Attendee: <strong><?= esc($user['first_name'] . ' ' . $user['last_name']) ?></strong></span>
                                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle" id="userRoleBadge"><?= esc($roleLabel) ?></span>
                                        </div>

                                        <!-- Sub-state: Not Checked In -->
                                        <div id="stateNotCheckedIn" class="<?= $checkInStatus === 'not_checked_in' ? '' : 'd-none' ?>">
                                            <div class="small text-muted mb-3 text-center">Press the button below to confirm your presence in the room.</div>
                                            <button type="button" class="btn btn-success btn-lg w-100 btn-checkin-primary py-3 fw-bold"
                                                    id="btnCheckIn"
                                                    data-booking-id="<?= (int) $booking['id'] ?>"
                                                    data-room-id="<?= (int) $room['id'] ?>">
                                                <i class="bi bi-check2-circle me-2 fs-5"></i> Check In to Meeting
                                            </button>
                                        </div>

                                        <!-- Sub-state: Checked In -->
                                        <div id="stateCheckedIn" class="<?= $checkInStatus === 'checked_in' ? '' : 'd-none' ?>">
                                            <div class="alert alert-success d-flex align-items-center gap-3 mb-3 p-3">
                                                <i class="bi bi-check-circle-fill fs-2 text-success flex-shrink-0"></i>
                                                <div>
                                                    <div class="fw-bold fs-6">Checked In!</div>
                                                    <div class="small" id="checkedInTimestamp">
                                                        <?php if (!empty($userCheckin['check_in_time'])): ?>
                                                            Checked in at <?= date('g:i A, M j', strtotime($userCheckin['check_in_time'])) ?>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="small text-muted mb-2 text-center">Leaving the meeting early?</div>
                                            <button type="button" class="btn btn-outline-danger w-100 btn-checkout-action py-2"
                                                    id="btnCheckOut"
                                                    data-booking-id="<?= (int) $booking['id'] ?>"
                                                    data-room-id="<?= (int) $room['id'] ?>">
                                                <i class="bi bi-box-arrow-right me-2"></i> Check Out of Meeting
                                            </button>
                                        </div>

                                        <!-- Sub-state: Checked Out -->
                                        <div id="stateCheckedOut" class="<?= $checkInStatus === 'checked_out' ? '' : 'd-none' ?>">
                                            <div class="alert alert-secondary d-flex align-items-center gap-3 p-3 mb-0">
                                                <i class="bi bi-check2-all fs-2 text-secondary flex-shrink-0"></i>
                                                <div>
                                                    <div class="fw-bold fs-6">Checked Out</div>
                                                    <div class="small" id="checkedOutTimestamp">
                                                        <?php if (!empty($userCheckin['check_out_time'])): ?>
                                                            Checked out at <?= date('g:i A, M j', strtotime($userCheckin['check_out_time'])) ?>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="small text-muted mt-1">Your attendance record has been finalized.</div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        </div>
    </main>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Check-in Flow JS -->
    <script src="<?= base_url('js/checkin.js') ?>"></script>
</body>
</html>
