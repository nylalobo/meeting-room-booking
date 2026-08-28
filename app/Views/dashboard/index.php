<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<!-- Welcome Header -->
<div class="dashboard-header">
    <h1 class="welcome-heading">Welcome back, Admin! 👋</h1>
    <p class="welcome-subtitle">Here's what's happening today in MeetSpace.</p>
</div>

<!-- Statistics Cards -->
<div class="row g-3 stats-row">

    <!-- Total Rooms -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon-wrapper stat-icon-cyan">
                <i class="bi bi-door-open"></i>
            </div>
            <div class="stat-meta">
                <div class="stat-label">TOTAL ROOMS</div>
                <div class="stat-value">12</div>
            </div>
        </div>
    </div>

    <!-- Total Bookings -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon-wrapper stat-icon-blue">
                <i class="bi bi-calendar-check"></i>
            </div>
            <div class="stat-meta">
                <div class="stat-label">TOTAL BOOKINGS</div>
                <div class="stat-value">28</div>
            </div>
        </div>
    </div>

    <!-- Pending Requests -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon-wrapper stat-icon-amber">
                <i class="bi bi-clipboard-data"></i>
            </div>
            <div class="stat-meta">
                <div class="stat-label">PENDING REQUESTS</div>
                <div class="stat-value">5</div>
            </div>
        </div>
    </div>

    <!-- Active Users -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon-wrapper stat-icon-purple">
                <i class="bi bi-people"></i>
            </div>
            <div class="stat-meta">
                <div class="stat-label">ACTIVE USERS</div>
                <div class="stat-value">36</div>
            </div>
        </div>
    </div>

</div>

<!-- Main Dashboard Grid -->
<div class="row g-4 dashboard-grid mt-1">

    <!-- Left Column: Upcoming Meetings -->
    <div class="col-12 col-lg-8">
        <div class="panel-card upcoming-meetings-card">

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
                    <tbody>
                        <tr>
                            <td class="fw-semibold text-white">Team Standup</td>
                            <td class="text-muted-blue">Huddle Room 1</td>
                            <td class="text-muted-blue">09:00 AM</td>
                            <td class="text-end">
                                <span class="badge-status badge-active">Active</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="fw-semibold text-white">Project Planning</td>
                            <td class="text-muted-blue">Boardroom A</td>
                            <td class="text-muted-blue">11:00 AM</td>
                            <td class="text-end">
                                <span class="badge-status badge-upcoming">Upcoming</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="fw-semibold text-white">Client Presentation</td>
                            <td class="text-muted-blue">Boardroom A</td>
                            <td class="text-muted-blue">02:00 PM</td>
                            <td class="text-end">
                                <span class="badge-status badge-upcoming">Upcoming</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="fw-semibold text-white">HR Interview</td>
                            <td class="text-muted-blue">Focus Pod 2</td>
                            <td class="text-muted-blue">03:30 PM</td>
                            <td class="text-end">
                                <span class="badge-status badge-upcoming">Upcoming</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="fw-semibold text-white">Marketing Sync</td>
                            <td class="text-muted-blue">Huddle Room 1</td>
                            <td class="text-muted-blue">04:30 PM</td>
                            <td class="text-end">
                                <span class="badge-status badge-upcoming">Upcoming</span>
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
        <div class="panel-card quick-availability-card">

            <div class="panel-header mb-3">
                <h2 class="panel-title d-flex align-items-center gap-2">
                    <i class="bi bi-lightning-charge-fill text-cyan"></i>
                    <span>Quick Availability</span>
                </h2>
            </div>

            <div class="room-availability-list">

                <!-- Room 1 -->
                <div class="room-avail-item">
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="room-name">Boardroom A</span>
                        <span class="avail-badge avail-now">Available Now</span>
                    </div>
                    <div class="room-features">
                        <span><i class="bi bi-person-fill"></i> 12 Seats</span>
                        <span><i class="bi bi-display"></i> VC Equipped</span>
                    </div>
                </div>

                <!-- Room 2 -->
                <div class="room-avail-item">
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="room-name">Huddle Room 1</span>
                        <span class="avail-badge avail-busy">In Use (Free in 15m)</span>
                    </div>
                    <div class="room-features">
                        <span><i class="bi bi-person-fill"></i> 4 Seats</span>
                        <span><i class="bi bi-tv"></i> Display</span>
                    </div>
                </div>

                <!-- Room 3 -->
                <div class="room-avail-item mb-0">
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="room-name">Focus Pod 2</span>
                        <span class="avail-badge avail-now">Available Now</span>
                    </div>
                    <div class="room-features">
                        <span><i class="bi bi-person-fill"></i> 1 Seat</span>
                        <span><i class="bi bi-volume-mute"></i> Soundproof</span>
                    </div>
                </div>

            </div>

        </div>

        <!-- Performance Panel -->
        <div class="panel-card performance-card">
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