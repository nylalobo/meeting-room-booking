/**
 * MeetSpace Enterprise Suite - Equipment & Resource Inventory JavaScript Module
 *
 * Handles:
 * - Equipment catalog listing and rendering
 * - Multi-attribute search and filtering (search, category, status, location)
 * - Equipment creation, editing, and catalog status management
 * - Safe equipment retirement with custom confirmation modal
 * - Comprehensive asset details inspection modal
 * - RBAC visibility management (Admin and Facilities Manager actions)
 * - Validation, debounce, and error handling
 */

(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', () => {
        if (document.getElementById('equipmentTableBody')) {
            initEquipmentModule();
        }
    });

    /* ==========================================================================
       STATE
       ========================================================================== */

    let allEquipment = [];
    let locationsList = [];
    let roomsList = [];
    let currentSelectedEquipment = null;
    let searchDebounceTimer = null;
    let currentPage = 1;
    let currentPerPage = 10;

    /* ==========================================================================
       RBAC HELPER
       ========================================================================== */

    function canManageEquipment() {
        const user = window.MeetSpaceUser || {};
        if (!user.isLoggedIn) {
            return false;
        }
        if (user.role_id === 1 || user.role_id === 6) {
            return true;
        }
        return ['Admin', 'Facilities Manager', 'Facility Manager'].includes(user.role_name);
    }

    /* ==========================================================================
       INITIALIZATION
       ========================================================================== */

    function initEquipmentModule() {
        loadLocationsAndRooms();
        loadEquipment();
        setupFilters();
        setupModals();
        setupCreateButton();
    }

    /* ==========================================================================
       DATA LOADING
       ========================================================================== */

    async function loadEquipment(page = currentPage, perPage = currentPerPage) {
        const loading = document.getElementById('equipmentLoading');
        const error = document.getElementById('equipmentError');
        const empty = document.getElementById('equipmentEmpty');
        const tableWrapper = document.getElementById('equipmentTableWrapper');

        try {
            loading?.classList.remove('d-none');
            error?.classList.add('d-none');
            empty?.classList.add('d-none');
            tableWrapper?.classList.add('d-none');

            const search = document.getElementById('equipmentSearch')?.value.trim() || '';
            const category = document.getElementById('equipmentCategoryFilter')?.value || '';
            const status = document.getElementById('equipmentStatusFilter')?.value || '';
            const locationId = document.getElementById('equipmentLocationFilter')?.value || '';

            const params = new URLSearchParams();
            params.append('page', page);
            params.append('per_page', perPage);
            if (search) params.append('search', search);
            if (category) params.append('category', category);
            if (status) params.append('status', status);
            if (locationId) params.append('location_id', locationId);

            const url = '/api/equipment?' + params.toString();
            const response = await fetch(url, {
                headers: { 'Accept': 'application/json' }
            });

            if (!response.ok) {
                throw new Error(`HTTP error ${response.status}`);
            }

            const result = await response.json();
            if (result.status !== 'success') {
                throw new Error(result.message || 'Failed to load equipment.');
            }

            allEquipment = result.data || [];
            currentPage = result.page || page;
            currentPerPage = result.per_page || perPage;
            loading?.classList.add('d-none');

            renderEquipment(allEquipment);

            if (typeof window.renderPagination === 'function') {
                window.renderPagination(
                    '#equipmentPagination',
                    {
                        page: result.page,
                        per_page: result.per_page,
                        total: result.total,
                        total_pages: result.total_pages
                    },
                    (newPage) => {
                        loadEquipment(newPage, currentPerPage);
                    },
                    (newPerPage) => {
                        currentPerPage = newPerPage;
                        loadEquipment(1, newPerPage);
                    }
                );
            }
        } catch (err) {
            console.error('Unable to load equipment inventory:', err);
            loading?.classList.add('d-none');
            tableWrapper?.classList.add('d-none');
            empty?.classList.add('d-none');
            error?.classList.remove('d-none');
        }
    }

    async function loadLocationsAndRooms() {
        try {
            // 1. Fetch Locations
            const locRes = await fetch('/api/locations?all=1', {
                headers: { 'Accept': 'application/json' }
            });
            if (locRes.ok) {
                const locData = await locRes.json();
                locationsList = locData.data || [];
                populateLocationDropdowns(locationsList);
            }

            // 2. Fetch Rooms
            const rmRes = await fetch('/api/rooms?all=1', {
                headers: { 'Accept': 'application/json' }
            });
            if (rmRes.ok) {
                const rmData = await rmRes.json();
                roomsList = rmData.data || [];
                populateRoomDropdown(roomsList);
            }
        } catch (err) {
            console.warn('Could not load locations or rooms for dropdowns:', err);
        }
    }

    function populateLocationDropdowns(locations) {
        // Toolbar filter dropdown
        const filterDropdown = document.getElementById('equipmentLocationFilter');
        if (filterDropdown) {
            const currentVal = filterDropdown.value;
            filterDropdown.innerHTML = '<option value="">All Locations</option>';
            locations.forEach((loc) => {
                const opt = document.createElement('option');
                opt.value = loc.id;
                opt.textContent = loc.name;
                filterDropdown.appendChild(opt);
            });
            filterDropdown.value = currentVal;
        }

        // Form modal dropdown
        const modalDropdown = document.getElementById('equipmentLocationId');
        if (modalDropdown) {
            modalDropdown.innerHTML = '<option value="">None / Unassigned</option>';
            locations.forEach((loc) => {
                const opt = document.createElement('option');
                opt.value = loc.id;
                opt.textContent = loc.name;
                modalDropdown.appendChild(opt);
            });
        }
    }

    function populateRoomDropdown(rooms, selectedLocationId = null) {
        const modalDropdown = document.getElementById('equipmentDefaultRoomId');
        if (!modalDropdown) return;

        const currentVal = modalDropdown.value;
        modalDropdown.innerHTML = '<option value="">None / Floating Asset</option>';

        let filteredRooms = rooms;
        if (selectedLocationId) {
            filteredRooms = rooms.filter((r) => String(r.location_id) === String(selectedLocationId));
        }

        filteredRooms.forEach((rm) => {
            const opt = document.createElement('option');
            opt.value = rm.id;
            const codePart = rm.room_code ? ` (${rm.room_code})` : '';
            opt.textContent = `${rm.name}${codePart}`;
            modalDropdown.appendChild(opt);
        });

        modalDropdown.value = currentVal;
    }

    /* ==========================================================================
       TABLE RENDERING
       ========================================================================== */

    function renderEquipment(equipment) {
        const tableBody = document.getElementById('equipmentTableBody');
        const empty = document.getElementById('equipmentEmpty');
        const tableWrapper = document.getElementById('equipmentTableWrapper');

        if (!tableBody) return;

        tableBody.innerHTML = '';

        if (!equipment || equipment.length === 0) {
            tableWrapper?.classList.add('d-none');
            empty?.classList.remove('d-none');
            return;
        }

        empty?.classList.add('d-none');
        tableWrapper?.classList.remove('d-none');

        const canManage = canManageEquipment();

        equipment.forEach((item) => {
            const row = document.createElement('tr');

            // Format status badge
            const status = (item.status || 'available').toLowerCase();
            let statusBadge = '';
            switch (status) {
                case 'available':
                    statusBadge = '<span class="badge-equip-status badge-equip-available"><i class="bi bi-check-circle-fill"></i> Available</span>';
                    break;
                case 'maintenance':
                    statusBadge = '<span class="badge-equip-status badge-equip-maintenance"><i class="bi bi-wrench-adjustable"></i> Maintenance</span>';
                    break;
                case 'damaged':
                    statusBadge = '<span class="badge-equip-status badge-equip-damaged"><i class="bi bi-exclamation-octagon-fill"></i> Damaged</span>';
                    break;
                case 'retired':
                    statusBadge = '<span class="badge-equip-status badge-equip-retired"><i class="bi bi-archive-fill"></i> Retired</span>';
                    break;
                default:
                    statusBadge = `<span class="badge-equip-status">${escapeHtml(status)}</span>`;
            }

            // Location & Room display
            let locationRoomText = '—';
            if (item.location_name && item.default_room_name) {
                const roomCodeBadge = item.default_room_code ? `<span class="badge bg-secondary ms-1" style="font-size: 10px;">${escapeHtml(item.default_room_code)}</span>` : '';
                locationRoomText = `<div class="fw-medium text-white">${escapeHtml(item.location_name)}</div><div class="text-muted small">${escapeHtml(item.default_room_name)}${roomCodeBadge}</div>`;
            } else if (item.location_name) {
                locationRoomText = `<div class="fw-medium text-white">${escapeHtml(item.location_name)}</div><div class="text-muted small">Unassigned room</div>`;
            } else if (item.default_room_name) {
                locationRoomText = `<div class="fw-medium text-white">${escapeHtml(item.default_room_name)}</div>`;
            }

            // Model info
            const modelText = item.model_number ? `<div class="text-muted small mt-1">Model: ${escapeHtml(item.model_number)}</div>` : '';

            // Action buttons
            let actionButtons = `
                <button type="button" class="equip-action-btn equip-view-btn" data-id="${item.id}" title="View Details" aria-label="View Details">
                    <i class="bi bi-eye"></i>
                </button>
            `;

            if (canManage) {
                actionButtons += `
                    <button type="button" class="equip-action-btn equip-edit-btn" data-id="${item.id}" title="Edit Equipment" aria-label="Edit Equipment">
                        <i class="bi bi-pencil"></i>
                    </button>
                `;

                if (status !== 'retired') {
                    actionButtons += `
                        <button type="button" class="equip-action-btn equip-retire-btn" data-id="${item.id}" title="Retire Equipment" aria-label="Retire Equipment">
                            <i class="bi bi-archive"></i>
                        </button>
                    `;
                }
            }

            row.innerHTML = `
                <td>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="fw-semibold text-white">${escapeHtml(item.name || 'Unnamed Asset')}</span>
                        <span class="equip-code-badge">${escapeHtml(item.code || '')}</span>
                    </div>
                    ${modelText}
                </td>
                <td>
                    <span class="badge-equip-category">${escapeHtml(item.category || 'other')}</span>
                </td>
                <td>
                    ${locationRoomText}
                </td>
                <td>
                    <span class="text-white font-monospace small">${escapeHtml(item.serial_number || '—')}</span>
                </td>
                <td>
                    ${statusBadge}
                </td>
                <td>
                    <div class="equipment-actions">
                        ${actionButtons}
                    </div>
                </td>
            `;

            tableBody.appendChild(row);
        });

        setupTableActionListeners();
    }

    /* ==========================================================================
       TABLE ACTION LISTENERS
       ========================================================================== */

    function setupTableActionListeners() {
        // View Details buttons
        document.querySelectorAll('.equip-view-btn').forEach((btn) => {
            btn.addEventListener('click', () => {
                const id = btn.getAttribute('data-id');
                if (id) openDetailsModal(id);
            });
        });

        // Edit buttons
        document.querySelectorAll('.equip-edit-btn').forEach((btn) => {
            btn.addEventListener('click', () => {
                const id = btn.getAttribute('data-id');
                if (id) openEditModal(id);
            });
        });

        // Retire buttons
        document.querySelectorAll('.equip-retire-btn').forEach((btn) => {
            btn.addEventListener('click', () => {
                const id = btn.getAttribute('data-id');
                if (id) confirmRetireEquipment(id);
            });
        });
    }

    /* ==========================================================================
       FILTERS & SEARCH
       ========================================================================== */

    function setupFilters() {
        const searchInput = document.getElementById('equipmentSearch');
        const categoryFilter = document.getElementById('equipmentCategoryFilter');
        const statusFilter = document.getElementById('equipmentStatusFilter');
        const locationFilter = document.getElementById('equipmentLocationFilter');
        const clearBtn = document.getElementById('equipmentClearFiltersBtn');
        const refreshBtn = document.getElementById('equipmentRefreshBtn');
        const retryBtn = document.getElementById('equipmentRetryBtn');

        // Debounced search
        searchInput?.addEventListener('input', () => {
            clearTimeout(searchDebounceTimer);
            searchDebounceTimer = setTimeout(() => {
                currentPage = 1;
                loadEquipment(1, currentPerPage);
            }, 300);
        });

        // Dropdown filters
        const onFilterChange = () => {
            currentPage = 1;
            loadEquipment(1, currentPerPage);
        };
        categoryFilter?.addEventListener('change', onFilterChange);
        statusFilter?.addEventListener('change', onFilterChange);
        locationFilter?.addEventListener('change', onFilterChange);

        // Clear filters
        clearBtn?.addEventListener('click', () => {
            if (searchInput) searchInput.value = '';
            if (categoryFilter) categoryFilter.value = '';
            if (statusFilter) statusFilter.value = '';
            if (locationFilter) locationFilter.value = '';
            currentPage = 1;
            loadEquipment(1, currentPerPage);
        });

        // Refresh & Retry buttons
        refreshBtn?.addEventListener('click', () => loadEquipment());
        retryBtn?.addEventListener('click', () => loadEquipment());

        // Dynamic room options when location changes in form modal
        const modalLocationSelect = document.getElementById('equipmentLocationId');
        modalLocationSelect?.addEventListener('change', () => {
            const locId = modalLocationSelect.value;
            populateRoomDropdown(roomsList, locId);
        });
    }

    /* ==========================================================================
       CREATE / EDIT MODAL
       ========================================================================== */

    function setupCreateButton() {
        const createBtn = document.getElementById('newEquipmentBtn');
        createBtn?.addEventListener('click', () => {
            openCreateModal();
        });
    }

    function setupModals() {
        // Equipment Create/Edit modal close buttons
        const equipmentModal = document.getElementById('equipmentModal');
        const closeEquipmentModal = document.getElementById('closeEquipmentModal');
        const cancelEquipmentBtn = document.getElementById('cancelEquipmentBtn');

        closeEquipmentModal?.addEventListener('click', () => hideEquipmentModal());
        cancelEquipmentBtn?.addEventListener('click', () => hideEquipmentModal());

        equipmentModal?.addEventListener('click', (e) => {
            if (e.target === equipmentModal) hideEquipmentModal();
        });

        // Details modal close buttons
        const detailsModal = document.getElementById('equipmentDetailsModal');
        const closeDetailsModal = document.getElementById('closeDetailsModal');
        const closeDetailsFooterBtn = document.getElementById('closeDetailsFooterBtn');
        const detailsEditBtn = document.getElementById('detailsEditBtn');

        closeDetailsModal?.addEventListener('click', () => hideDetailsModal());
        closeDetailsFooterBtn?.addEventListener('click', () => hideDetailsModal());

        detailsModal?.addEventListener('click', (e) => {
            if (e.target === detailsModal) hideDetailsModal();
        });

        detailsEditBtn?.addEventListener('click', () => {
            hideDetailsModal();
            if (currentSelectedEquipment && currentSelectedEquipment.id) {
                openEditModal(currentSelectedEquipment.id);
            }
        });

        // Form submission
        const form = document.getElementById('equipmentForm');
        form?.addEventListener('submit', handleEquipmentFormSubmit);
    }

    function openCreateModal() {
        const modal = document.getElementById('equipmentModal');
        const form = document.getElementById('equipmentForm');
        const title = document.getElementById('equipmentModalTitle');
        const subtitle = document.getElementById('equipmentModalSubtitle');
        const submitBtnText = document.getElementById('submitEquipmentBtnText');
        const errorAlert = document.getElementById('equipmentFormError');
        const successAlert = document.getElementById('equipmentFormSuccess');

        if (!modal || !form) return;

        form.reset();
        document.getElementById('equipmentId').value = '';
        document.getElementById('equipmentStatus').value = 'available';

        errorAlert?.classList.add('d-none');
        successAlert?.classList.add('d-none');

        title.textContent = 'New Equipment';
        subtitle.textContent = 'Add a new physical equipment asset to the inventory catalog.';
        if (submitBtnText) submitBtnText.textContent = 'Save Equipment';

        populateRoomDropdown(roomsList, null);

        modal.classList.remove('d-none');
        setTimeout(() => document.getElementById('equipmentName')?.focus(), 50);
    }

    async function openEditModal(id) {
        const modal = document.getElementById('equipmentModal');
        const form = document.getElementById('equipmentForm');
        const title = document.getElementById('equipmentModalTitle');
        const subtitle = document.getElementById('equipmentModalSubtitle');
        const submitBtnText = document.getElementById('submitEquipmentBtnText');
        const errorAlert = document.getElementById('equipmentFormError');
        const successAlert = document.getElementById('equipmentFormSuccess');

        if (!modal || !form) return;

        form.reset();
        errorAlert?.classList.add('d-none');
        successAlert?.classList.add('d-none');

        title.textContent = 'Edit Equipment';
        subtitle.textContent = 'Update catalog asset specifications and status.';
        if (submitBtnText) submitBtnText.textContent = 'Update Equipment';

        try {
            const response = await fetch(`/api/equipment/${id}`, {
                headers: { 'Accept': 'application/json' }
            });

            if (!response.ok) {
                throw new Error(`HTTP error ${response.status}`);
            }

            const result = await response.json();
            const item = result.data;

            document.getElementById('equipmentId').value = item.id;
            document.getElementById('equipmentName').value = item.name || '';
            document.getElementById('equipmentCode').value = item.code || '';
            document.getElementById('equipmentCategory').value = item.category || '';
            document.getElementById('equipmentStatus').value = item.status || 'available';
            document.getElementById('equipmentModelNumber').value = item.model_number || '';
            document.getElementById('equipmentSerialNumber').value = item.serial_number || '';
            document.getElementById('equipmentLocationId').value = item.location_id || '';

            // Update rooms dropdown filtered by location
            populateRoomDropdown(roomsList, item.location_id);
            document.getElementById('equipmentDefaultRoomId').value = item.default_room_id || '';

            document.getElementById('equipmentDescription').value = item.description || '';
            document.getElementById('equipmentNotes').value = item.notes || '';

            modal.classList.remove('d-none');
            setTimeout(() => document.getElementById('equipmentName')?.focus(), 50);
        } catch (err) {
            console.error('Unable to fetch equipment for edit:', err);
            if (typeof showAppNotification === 'function') {
                showAppNotification('Unable to load equipment details for editing.', 'error');
            }
        }
    }

    function hideEquipmentModal() {
        const modal = document.getElementById('equipmentModal');
        modal?.classList.add('d-none');
    }

    async function handleEquipmentFormSubmit(e) {
        e.preventDefault();

        const errorAlert = document.getElementById('equipmentFormError');
        const errorText = document.getElementById('equipmentFormErrorText');
        const submitBtn = document.getElementById('submitEquipmentBtn');
        const submitBtnText = document.getElementById('submitEquipmentBtnText');

        errorAlert?.classList.add('d-none');

        const id = document.getElementById('equipmentId')?.value.trim();
        const isEditing = Boolean(id);

        const name = document.getElementById('equipmentName')?.value.trim();
        const code = document.getElementById('equipmentCode')?.value.trim();
        const category = document.getElementById('equipmentCategory')?.value;
        const status = document.getElementById('equipmentStatus')?.value;
        const modelNumber = document.getElementById('equipmentModelNumber')?.value.trim() || null;
        const serialNumber = document.getElementById('equipmentSerialNumber')?.value.trim() || null;
        const locationIdVal = document.getElementById('equipmentLocationId')?.value;
        const locationId = locationIdVal ? Number(locationIdVal) : null;
        const defaultRoomIdVal = document.getElementById('equipmentDefaultRoomId')?.value;
        const defaultRoomId = defaultRoomIdVal ? Number(defaultRoomIdVal) : null;
        const description = document.getElementById('equipmentDescription')?.value.trim() || null;
        const notes = document.getElementById('equipmentNotes')?.value.trim() || null;

        // Basic client validation
        if (!name || name.length < 2) {
            showFormError('Equipment name must be at least 2 characters.');
            return;
        }
        if (!code || code.length < 2) {
            showFormError('Asset code must be at least 2 characters.');
            return;
        }
        if (!category) {
            showFormError('Please select an equipment category.');
            return;
        }

        const payload = {
            name,
            code,
            category,
            status,
            model_number: modelNumber,
            serial_number: serialNumber,
            location_id: locationId,
            default_room_id: defaultRoomId,
            description,
            notes,
        };

        const originalText = submitBtnText?.textContent || 'Save Equipment';
        if (submitBtn) submitBtn.disabled = true;
        if (submitBtnText) submitBtnText.textContent = 'Saving...';

        try {
            const url = isEditing ? `/api/equipment/${id}` : '/api/equipment';
            const method = isEditing ? 'PUT' : 'POST';

            const response = await fetch(url, {
                method,
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify(payload),
            });

            const result = await response.json();

            if (!response.ok) {
                // Validation or conflict error
                if (result.errors) {
                    const errorMessages = Object.values(result.errors).join(' ');
                    throw new Error(errorMessages || result.message || 'Validation failed.');
                }
                throw new Error(result.message || 'Failed to save equipment.');
            }

            hideEquipmentModal();

            if (typeof showAppNotification === 'function') {
                showAppNotification(
                    result.message || (isEditing ? 'Equipment updated successfully.' : 'Equipment created successfully.'),
                    'success'
                );
            }

            await loadEquipment();
        } catch (err) {
            console.error('Error saving equipment:', err);
            showFormError(err.message || 'An unexpected error occurred while saving.');
        } finally {
            if (submitBtn) submitBtn.disabled = false;
            if (submitBtnText) submitBtnText.textContent = originalText;
        }
    }

    function showFormError(msg) {
        const errorAlert = document.getElementById('equipmentFormError');
        const errorText = document.getElementById('equipmentFormErrorText');
        if (errorAlert && errorText) {
            errorText.textContent = msg;
            errorAlert.classList.remove('d-none');
        }
    }

    /* ==========================================================================
       DETAILS MODAL
       ========================================================================== */

    async function openDetailsModal(id) {
        const modal = document.getElementById('equipmentDetailsModal');
        const container = document.getElementById('equipmentDetailsContent');
        if (!modal || !container) return;

        container.innerHTML = '<div class="text-center py-4"><i class="bi bi-arrow-repeat spin fs-3"></i><p class="text-muted mt-2">Loading asset details...</p></div>';
        modal.classList.remove('d-none');

        try {
            const response = await fetch(`/api/equipment/${id}`, {
                headers: { 'Accept': 'application/json' }
            });

            if (!response.ok) {
                throw new Error(`HTTP error ${response.status}`);
            }

            const result = await response.json();
            const item = result.data;
            currentSelectedEquipment = item;

            // Status badge
            const status = (item.status || 'available').toLowerCase();
            let statusBadge = '';
            switch (status) {
                case 'available':
                    statusBadge = '<span class="badge-equip-status badge-equip-available"><i class="bi bi-check-circle-fill"></i> Available</span>';
                    break;
                case 'maintenance':
                    statusBadge = '<span class="badge-equip-status badge-equip-maintenance"><i class="bi bi-wrench-adjustable"></i> Maintenance</span>';
                    break;
                case 'damaged':
                    statusBadge = '<span class="badge-equip-status badge-equip-damaged"><i class="bi bi-exclamation-octagon-fill"></i> Damaged</span>';
                    break;
                case 'retired':
                    statusBadge = '<span class="badge-equip-status badge-equip-retired"><i class="bi bi-archive-fill"></i> Retired</span>';
                    break;
                default:
                    statusBadge = `<span class="badge-equip-status">${escapeHtml(status)}</span>`;
            }

            const defaultRoomDisplay = item.default_room_name
                ? `${escapeHtml(item.default_room_name)} ${item.default_room_code ? '(' + escapeHtml(item.default_room_code) + ')' : ''}`
                : 'None (Floating)';

            container.innerHTML = `
                <div class="equip-detail-grid">
                    <div class="equip-detail-item">
                        <div class="equip-detail-label">Asset Name</div>
                        <div class="equip-detail-value">${escapeHtml(item.name || '—')}</div>
                    </div>

                    <div class="equip-detail-item">
                        <div class="equip-detail-label">Asset Code / Tag</div>
                        <div class="equip-detail-value">
                            <span class="equip-code-badge">${escapeHtml(item.code || '—')}</span>
                        </div>
                    </div>

                    <div class="equip-detail-item">
                        <div class="equip-detail-label">Category</div>
                        <div class="equip-detail-value">
                            <span class="badge-equip-category">${escapeHtml(item.category || '—')}</span>
                        </div>
                    </div>

                    <div class="equip-detail-item">
                        <div class="equip-detail-label">Catalog Status</div>
                        <div class="equip-detail-value">${statusBadge}</div>
                    </div>

                    <div class="equip-detail-item">
                        <div class="equip-detail-label">Model Number</div>
                        <div class="equip-detail-value font-monospace">${escapeHtml(item.model_number || '—')}</div>
                    </div>

                    <div class="equip-detail-item">
                        <div class="equip-detail-label">Serial Number</div>
                        <div class="equip-detail-value font-monospace">${escapeHtml(item.serial_number || '—')}</div>
                    </div>

                    <div class="equip-detail-item">
                        <div class="equip-detail-label">Assigned Location</div>
                        <div class="equip-detail-value">${escapeHtml(item.location_name || 'Unassigned')}</div>
                    </div>

                    <div class="equip-detail-item">
                        <div class="equip-detail-label">Default Room</div>
                        <div class="equip-detail-value">${defaultRoomDisplay}</div>
                    </div>

                    <div class="equip-detail-item full-width">
                        <div class="equip-detail-label">Description</div>
                        <div class="equip-detail-value text-secondary">${escapeHtml(item.description || 'No description provided.')}</div>
                    </div>

                    ${item.notes ? `
                    <div class="equip-detail-item full-width">
                        <div class="equip-detail-label">Internal Notes</div>
                        <div class="equip-detail-value text-secondary">${escapeHtml(item.notes)}</div>
                    </div>
                    ` : ''}

                    <div class="equip-detail-item">
                        <div class="equip-detail-label">Created At</div>
                        <div class="equip-detail-value small text-muted">${escapeHtml(item.created_at || '—')}</div>
                    </div>

                    <div class="equip-detail-item">
                        <div class="equip-detail-label">Last Updated</div>
                        <div class="equip-detail-value small text-muted">${escapeHtml(item.updated_at || '—')}</div>
                    </div>
                </div>
            `;
        } catch (err) {
            console.error('Unable to fetch equipment details:', err);
            container.innerHTML = '<div class="alert alert-danger">Unable to load equipment details. Please try again.</div>';
        }
    }

    function hideDetailsModal() {
        const modal = document.getElementById('equipmentDetailsModal');
        modal?.classList.add('d-none');
    }

    /* ==========================================================================
       RETIREMENT ACTION
       ========================================================================== */

    function confirmRetireEquipment(id) {
        const item = allEquipment.find((e) => String(e.id) === String(id));
        const assetName = item ? `${item.name} (${item.code})` : 'this equipment item';

        if (typeof showAppConfirm === 'function') {
            showAppConfirm(
                `Are you sure you want to retire ${assetName}? Retired equipment will no longer be available for booking reservations.`,
                async () => {
                    await executeRetireEquipment(id);
                },
                'Retire Equipment',
                'Retire Equipment'
            );
        } else {
            if (confirm(`Are you sure you want to retire ${assetName}?`)) {
                executeRetireEquipment(id);
            }
        }
    }

    async function executeRetireEquipment(id) {
        try {
            const response = await fetch(`/api/equipment/${id}`, {
                method: 'DELETE',
                headers: { 'Accept': 'application/json' }
            });

            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.message || 'Failed to retire equipment.');
            }

            if (typeof showAppNotification === 'function') {
                showAppNotification(result.message || 'Equipment retired successfully.', 'success');
            }

            await loadEquipment();
        } catch (err) {
            console.error('Error retiring equipment:', err);
            if (typeof showAppNotification === 'function') {
                showAppNotification(err.message || 'Unable to retire equipment.', 'error');
            }
        }
    }
})();
