/**
 * MeetSpace Enterprise Suite - Facilities Module JavaScript
 *
 * Handles:
 * - Loading and rendering facilities from /api/facilities
 * - Facility search and status filtering
 * - Facility creation and editing modal
 * - Facility deletion with custom confirmation dialog
 * - Inline form validation and API error handling
 */

(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', () => {

        if (document.getElementById('facilitiesTableBody')) {
            loadFacilities();
            setupFacilityFilters();
            setupFacilityModal();
        }
    });


    /* ==========================================================================
       STATE
       ========================================================================== */

    let allFacilities = [];
    let currentPage = 1;
    let currentPerPage = 10;
    let searchDebounceTimer = null;


    /* ==========================================================================
       DATA LOADING
       ========================================================================== */

    async function loadFacilities(page = currentPage, perPage = currentPerPage) {

        const loading =
            document.getElementById(
                'facilitiesLoading'
            );

        const error =
            document.getElementById(
                'facilitiesError'
            );

        const empty =
            document.getElementById(
                'facilitiesEmpty'
            );

        const tableWrapper =
            document.getElementById(
                'facilitiesTableWrapper'
            );

        try {

            loading?.classList.remove(
                'd-none'
            );

            error?.classList.add(
                'd-none'
            );

            empty?.classList.add(
                'd-none'
            );

            tableWrapper?.classList.add(
                'd-none'
            );

            const searchInput = document.getElementById('facilitySearch');
            const statusFilter = document.getElementById('facilityStatusFilter');
            const search = searchInput ? searchInput.value.trim() : '';
            const status = statusFilter ? statusFilter.value : '';

            const params = new URLSearchParams();
            params.append('page', page);
            params.append('per_page', perPage);
            if (search) params.append('search', search);
            if (status !== '') params.append('status', status);

            const response =
                await fetch(
                    `/api/facilities?${params.toString()}`
                );

            if (!response.ok) {

                throw new Error(
                    `HTTP error: ${response.status}`
                );
            }

            const result =
                await response.json();

            if (
                result.status !==
                'success'
            ) {

                throw new Error(
                    'Facilities request failed.'
                );
            }

            allFacilities =
                result.data || [];
            currentPage = result.page || page;
            currentPerPage = result.per_page || perPage;

            loading?.classList.add(
                'd-none'
            );

            renderFacilities(
                allFacilities
            );

            if (typeof window.renderPagination === 'function') {
                window.renderPagination(
                    '#facilitiesPagination',
                    {
                        page: result.page,
                        per_page: result.per_page,
                        total: result.total,
                        total_pages: result.total_pages
                    },
                    (newPage) => {
                        loadFacilities(newPage, currentPerPage);
                    },
                    (newPerPage) => {
                        currentPerPage = newPerPage;
                        loadFacilities(1, newPerPage);
                    }
                );
            }

        } catch (err) {

            loading?.classList.add(
                'd-none'
            );

            tableWrapper?.classList.add(
                'd-none'
            );

            empty?.classList.add(
                'd-none'
            );

            error?.classList.remove(
                'd-none'
            );
        }
    }


    /* ==========================================================================
       RENDERING
       ========================================================================== */

    function renderFacilities(facilities) {

        const tableBody =
            document.getElementById(
                'facilitiesTableBody'
            );

        const empty =
            document.getElementById(
                'facilitiesEmpty'
            );

        const tableWrapper =
            document.getElementById(
                'facilitiesTableWrapper'
            );

        if (!tableBody) {
            return;
        }

        tableBody.innerHTML = '';

        if (!facilities.length) {

            tableWrapper?.classList.add(
                'd-none'
            );

            empty?.classList.remove(
                'd-none'
            );

            return;
        }

        empty?.classList.add(
            'd-none'
        );

        tableWrapper?.classList.remove(
            'd-none'
        );

        facilities.forEach((facility) => {

            const row =
                document.createElement(
                    'tr'
                );

            const isActive =
                String(
                    facility.is_active
                ) === '1';

            const statusLabel =
                isActive
                    ? 'Active'
                    : 'Inactive';

            const statusClass =
                isActive
                    ? 'active'
                    : 'inactive';

            row.innerHTML = `

                <td>

                    <div class="booking-room-name">
                        ${escapeHtml(
                            facility.name ||
                            'Unnamed Facility'
                        )}
                    </div>

                </td>

                <td>

                    <span class="booking-description">
                        ${escapeHtml(
                            facility.description ||
                            '—'
                        )}
                    </span>

                </td>

                <td>

                    <span class="booking-status booking-status-${statusClass}">
                        ${statusLabel}
                    </span>

                </td>

                <td>

                    <div class="facility-actions">

                        <button
                            type="button"
                            class="facility-action-btn facility-edit-btn"
                            data-facility-id="${escapeHtml(
                                facility.id
                            )}"
                            title="Edit facility"
                            aria-label="Edit facility"
                        >
                            <i class="bi bi-pencil"></i>
                        </button>

                        <button
                            type="button"
                            class="facility-action-btn facility-delete-btn"
                            data-facility-id="${escapeHtml(
                                facility.id
                            )}"
                            title="Delete facility"
                            aria-label="Delete facility"
                        >
                            <i class="bi bi-trash"></i>
                        </button>

                    </div>

                </td>

            `;

            tableBody.appendChild(
                row
            );
        });

        setupFacilityActionButtons();
    }


    /* ==========================================================================
       FILTERS
       ========================================================================== */

    function setupFacilityFilters() {

        const searchInput =
            document.getElementById(
                'facilitySearch'
            );

        const statusFilter =
            document.getElementById(
                'facilityStatusFilter'
            );

        searchInput?.addEventListener(
            'input',
            () => {
                clearTimeout(searchDebounceTimer);
                searchDebounceTimer = setTimeout(() => {
                    currentPage = 1;
                    loadFacilities(1, currentPerPage);
                }, 300);
            }
        );

        statusFilter?.addEventListener(
            'change',
            () => {
                currentPage = 1;
                loadFacilities(1, currentPerPage);
            }
        );
    }


    function applyFacilityFilters() {
        currentPage = 1;
        loadFacilities(1, currentPerPage);
    }


    /* ==========================================================================
       MODAL
       ========================================================================== */

    function setupFacilityModal() {

        const modal =
            document.getElementById(
                'facilityModal'
            );

        const newFacilityBtn =
            document.getElementById(
                'newFacilityBtn'
            );

        const closeFacilityModal =
            document.getElementById(
                'closeFacilityModal'
            );

        const cancelFacilityBtn =
            document.getElementById(
                'cancelFacilityBtn'
            );

        const form =
            document.getElementById(
                'facilityForm'
            );

        if (
            !modal ||
            !newFacilityBtn ||
            !form
        ) {
            return;
        }

        newFacilityBtn.addEventListener(
            'click',
            () => {

                resetFacilityForm();

                openFacilityModal();
            }
        );

        closeFacilityModal?.addEventListener(
            'click',
            closeFacilityModalWindow
        );

        cancelFacilityBtn?.addEventListener(
            'click',
            closeFacilityModalWindow
        );

        modal.addEventListener(
            'click',
            (event) => {

                if (
                    event.target === modal
                ) {
                    closeFacilityModalWindow();
                }
            }
        );

        document.addEventListener(
            'keydown',
            (event) => {

                if (
                    event.key === 'Escape' &&
                    !modal.classList.contains(
                        'd-none'
                    )
                ) {
                    closeFacilityModalWindow();
                }
            }
        );

        form.addEventListener(
            'submit',
            handleFacilityFormSubmit
        );
    }


    function openFacilityModal() {

        const modal =
            document.getElementById(
                'facilityModal'
            );

        if (!modal) {
            return;
        }

        modal.classList.remove(
            'd-none'
        );

        document.body.classList.add(
            'booking-modal-open'
        );

        setTimeout(() => {

            document.getElementById(
                'facilityName'
            )?.focus();

        }, 50);
    }


    function closeFacilityModalWindow() {

        const modal =
            document.getElementById(
                'facilityModal'
            );

        if (!modal) {
            return;
        }

        modal.classList.add(
            'd-none'
        );

        document.body.classList.remove(
            'booking-modal-open'
        );
    }


    function resetFacilityForm() {

        const form =
            document.getElementById(
                'facilityForm'
            );

        form?.reset();

        const facilityId =
            document.getElementById(
                'facilityId'
            );

        if (facilityId) {
            facilityId.value = '';
        }

        const modalTitle =
            document.getElementById(
                'facilityModalTitle'
            );

        if (modalTitle) {

            modalTitle.textContent =
                'New Facility';
        }

        const modalSubtitle =
            document.getElementById(
                'facilityModalSubtitle'
            );

        if (modalSubtitle) {

            modalSubtitle.textContent =
                'Create a new meeting room facility or equipment item.';
        }

        const submitButton =
            document.getElementById(
                'submitFacilityBtn'
            );

        if (submitButton) {

            submitButton.disabled =
                false;

            submitButton.innerHTML = `
                <i class="bi bi-check-lg"></i>
                Save Facility
            `;
        }

        hideFacilityFormMessages();
    }


    /* ==========================================================================
       ACTION BUTTONS
       ========================================================================== */

    function setupFacilityActionButtons() {

        const editButtons =
            document.querySelectorAll(
                '.facility-edit-btn'
            );

        const deleteButtons =
            document.querySelectorAll(
                '.facility-delete-btn'
            );

        editButtons.forEach(
            (button) => {

                button.addEventListener(
                    'click',
                    () => {

                        const facilityId =
                            button.dataset.facilityId;

                        editFacility(
                            facilityId
                        );
                    }
                );
            }
        );

        deleteButtons.forEach(
            (button) => {

                button.addEventListener(
                    'click',
                    () => {

                        const facilityId =
                            button.dataset.facilityId;

                        deleteFacility(
                            facilityId
                        );
                    }
                );
            }
        );
    }


    function findFacilityById(facilityId) {

        return allFacilities.find(
            (facility) =>
                String(facility.id) ===
                String(facilityId)
        );
    }


    /* ==========================================================================
       EDIT FACILITY
       ========================================================================== */

    function editFacility(facilityId) {

        const facility =
            findFacilityById(
                facilityId
            );

        if (!facility) {

            showAppNotification(
                'Unable to find the selected facility.',
                'error',
                'Facility Not Found'
            );

            return;
        }

        const facilityIdInput =
            document.getElementById(
                'facilityId'
            );

        const facilityNameInput =
            document.getElementById(
                'facilityName'
            );

        const facilityDescriptionInput =
            document.getElementById(
                'facilityDescription'
            );

        const facilityStatusInput =
            document.getElementById(
                'facilityStatus'
            );

        if (facilityIdInput) {
            facilityIdInput.value =
                facility.id;
        }

        if (facilityNameInput) {
            facilityNameInput.value =
                facility.name || '';
        }

        if (facilityDescriptionInput) {
            facilityDescriptionInput.value =
                facility.description || '';
        }

        if (facilityStatusInput) {
            facilityStatusInput.value =
                String(
                    facility.is_active ?? '1'
                );
        }

        const modalTitle =
            document.getElementById(
                'facilityModalTitle'
            );

        if (modalTitle) {

            modalTitle.textContent =
                'Edit Facility';
        }

        const modalSubtitle =
            document.getElementById(
                'facilityModalSubtitle'
            );

        if (modalSubtitle) {

            modalSubtitle.textContent =
                'Update facility or equipment details.';
        }

        const submitButton =
            document.getElementById(
                'submitFacilityBtn'
            );

        if (submitButton) {

            submitButton.disabled =
                false;

            submitButton.innerHTML = `
                <i class="bi bi-check-lg"></i>
                Save Changes
            `;
        }

        hideFacilityFormMessages();

        openFacilityModal();
    }


    /* ==========================================================================
       CREATE / UPDATE FACILITY
       ========================================================================== */

    async function handleFacilityFormSubmit(
        event
    ) {

        event.preventDefault();

        const form =
            document.getElementById(
                'facilityForm'
            );

        const submitButton =
            document.getElementById(
                'submitFacilityBtn'
            );

        if (
            !form ||
            !submitButton
        ) {
            return;
        }

        hideFacilityFormMessages();

        const facilityId =
            document.getElementById(
                'facilityId'
            ).value.trim();

        const name =
            document.getElementById(
                'facilityName'
            ).value.trim();

        const description =
            document.getElementById(
                'facilityDescription'
            ).value.trim();

        const isActive =
            document.getElementById(
                'facilityStatus'
            ).value;

        if (!name) {

            showFacilityFormError(
                'Please enter a facility name.'
            );

            return;
        }

        if (name.length > 100) {

            showFacilityFormError(
                'Facility name cannot exceed 100 characters.'
            );

            return;
        }

        const payload = {
            name: name,
            description: description,
            is_active: Number(isActive)
        };

        const isEditing =
            Boolean(facilityId);

        const url =
            isEditing
                ? `/api/facilities/${facilityId}`
                : '/api/facilities';

        const method =
            isEditing
                ? 'PUT'
                : 'POST';

        try {

            submitButton.disabled =
                true;

            submitButton.innerHTML = `
                <i class="bi bi-arrow-repeat spin"></i>
                ${
                    isEditing
                        ? 'Updating...'
                        : 'Creating...'
                }
            `;

            const response =
                await fetch(
                    url,
                    {
                        method:
                            method,

                        headers: {
                            'Content-Type':
                                'application/json',

                            'Accept':
                                'application/json'
                        },

                        body:
                            JSON.stringify(
                                payload
                            )
                    }
                );

            const result =
                await response.json();

            if (
                !response.ok ||
                result.status !==
                'success'
            ) {

                handleFacilityApiError(
                    response.status,
                    result
                );

                return;
            }

            const successMessage =
                result.message ||
                (
                    isEditing
                        ? 'Facility updated successfully.'
                        : 'Facility created successfully.'
                );

            closeFacilityModalWindow();

            showAppNotification(
                successMessage,
                'success',
                isEditing
                    ? 'Facility Updated'
                    : 'Facility Created'
            );

            await loadFacilities();

        } catch (error) {

            showFacilityFormError(
                'Unable to save facility. Please try again.'
            );

            showAppNotification(
                'Unable to save facility. Please try again.',
                'error',
                'Facility Save Failed'
            );

        } finally {

            submitButton.disabled =
                false;

            submitButton.innerHTML = `
                <i class="bi bi-check-lg"></i>
                ${
                    isEditing
                        ? 'Save Changes'
                        : 'Save Facility'
                }
            `;
        }
    }


    /* ==========================================================================
       DELETE FACILITY
       ========================================================================== */

    async function deleteFacility(facilityId) {

        const facility =
            findFacilityById(
                facilityId
            );

        if (!facility) {

            showAppNotification(
                'Unable to find the selected facility.',
                'error',
                'Facility Not Found'
            );

            return;
        }

        showAppConfirm(

            `Are you sure you want to delete "${facility.name}"?`,

            async () => {

                await performFacilityDelete(
                    facilityId,
                    facility.name
                );
            },

            'Delete Facility',

            'Delete Facility'
        );
    }


    async function performFacilityDelete(
        facilityId,
        facilityName
    ) {

        try {

            const response =
                await fetch(
                    `/api/facilities/${facilityId}`,
                    {
                        method:
                            'DELETE',

                        headers: {
                            'Accept':
                                'application/json'
                        }
                    }
                );

            const result =
                await response.json();

            if (
                !response.ok ||
                result.status !==
                'success'
            ) {

                if (response.status === 409) {
                    showAppNotification(
                        result.message ||
                        'This facility cannot be deleted because it is assigned to rooms.',
                        'warning',
                        'Cannot Delete Facility'
                    );
                    return;
                }

                throw new Error(
                    result.message ||
                    'Unable to delete facility.'
                );
            }

            await loadFacilities();

            showAppNotification(

                result.message ||
                `"${facilityName}" has been deleted successfully.`,

                'success',

                'Facility Deleted'
            );

        } catch (error) {

            showAppNotification(

                error.message ||
                'Unable to delete facility. Please try again.',

                'error',

                'Delete Failed'
            );
        }
    }


    /* ==========================================================================
       API ERROR HANDLING
       ========================================================================== */

    function handleFacilityApiError(
        status,
        result
    ) {

        if (
            status === 422 &&
            result.errors
        ) {

            const messages =
                Object.values(
                    result.errors
                );

            const message =
                messages.join(' ');

            showFacilityFormError(
                message
            );

            showAppNotification(
                message,
                'error',
                'Validation Error'
            );

            return;
        }

        if (
            status === 422 &&
            result.message
        ) {

            showFacilityFormError(
                result.message
            );

            showAppNotification(
                result.message,
                'error',
                'Validation Error'
            );

            return;
        }

        if (
            status === 404
        ) {

            const message =
                result.message ||
                'Facility could not be found.';

            showFacilityFormError(
                message
            );

            showAppNotification(
                message,
                'error',
                'Facility Not Found'
            );

            return;
        }

        if (
            status === 409
        ) {

            const message =
                result.message ||
                'A facility with this name already exists.';

            showFacilityFormError(
                message
            );

            showAppNotification(
                message,
                'warning',
                'Duplicate Facility'
            );

            return;
        }

        const message =
            result.message ||
            'Unable to save facility.';

        showFacilityFormError(
            message
        );

        showAppNotification(
            message,
            'error',
            'Facility Error'
        );
    }


    /* ==========================================================================
       FORM MESSAGES
       ========================================================================== */

    function showFacilityFormError(
        message
    ) {

        const errorBox =
            document.getElementById(
                'facilityFormError'
            );

        const errorText =
            document.getElementById(
                'facilityFormErrorText'
            );

        const successBox =
            document.getElementById(
                'facilityFormSuccess'
            );

        successBox?.classList.add(
            'd-none'
        );

        if (errorText) {

            errorText.textContent =
                message;
        }

        errorBox?.classList.remove(
            'd-none'
        );
    }


    function showFacilityFormSuccess(
        message
    ) {

        const successBox =
            document.getElementById(
                'facilityFormSuccess'
            );

        const successText =
            document.getElementById(
                'facilityFormSuccessText'
            );

        const errorBox =
            document.getElementById(
                'facilityFormError'
            );

        errorBox?.classList.add(
            'd-none'
        );

        if (successText) {

            successText.textContent =
                message;
        }

        successBox?.classList.add(
            'd-none'
        );

        showAppNotification(
            message,
            'success'
        );
    }


    function hideFacilityFormMessages() {

        document.getElementById(
            'facilityFormError'
        )?.classList.add(
            'd-none'
        );

        document.getElementById(
            'facilityFormSuccess'
        )?.classList.add(
            'd-none'
        );
    }

})();
