/**
 * MeetSpace Enterprise Suite - Locations Module JavaScript
 *
 * Handles:
 * - Location listing and rendering
 * - Location search and filtering
 * - Location creation and editing modal
 * - Location deletion with custom confirmation
 * - Location form validation and API error handling
 */

document.addEventListener('DOMContentLoaded', () => {

    // Locations page
    if (document.getElementById('locationsTableBody')) {
        loadLocations();
        setupLocationFilters();
        setupLocationModal();
    }
});


/* ==========================================================================
   LOCATIONS
   ========================================================================== */

let allLocations = [];


async function loadLocations() {

    const loading =
        document.getElementById(
            'locationsLoading'
        );

    const error =
        document.getElementById(
            'locationsError'
        );

    const empty =
        document.getElementById(
            'locationsEmpty'
        );

    const tableWrapper =
        document.getElementById(
            'locationsTableWrapper'
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

        const response =
            await fetch(
                '/api/locations'
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
                'Locations request failed.'
            );
        }

        allLocations =
            result.data || [];

        loading?.classList.add(
            'd-none'
        );

        renderLocations(
            allLocations
        );

    } catch (err) {

        console.error(
            'Unable to load locations:',
            err
        );

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


function renderLocations(locations) {

    const tableBody =
        document.getElementById(
            'locationsTableBody'
        );

    const empty =
        document.getElementById(
            'locationsEmpty'
        );

    const tableWrapper =
        document.getElementById(
            'locationsTableWrapper'
        );

    if (!tableBody) {
        return;
    }

    tableBody.innerHTML = '';

    if (!locations.length) {

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

    locations.forEach((location) => {

        const row =
            document.createElement(
                'tr'
            );

        const isActive =
            String(
                location.is_active
            ) === '1';

        const status =
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
                        location.name ||
                        'Unnamed Location'
                    )}
                </div>

            </td>

            <td>

                <span class="booking-description">
                    ${escapeHtml(
                        location.address ||
                        '—'
                    )}
                </span>

            </td>

            <td>

                <span class="booking-date">
                    ${escapeHtml(
                        location.city ||
                        '—'
                    )}
                </span>

            </td>

            <td>

                <span class="booking-date">
                    ${escapeHtml(
                        location.state ||
                        '—'
                    )}
                </span>

            </td>

            <td>

                <span class="booking-date">
                    ${escapeHtml(
                        location.country ||
                        '—'
                    )}
                </span>

            </td>

            <td>

                <span
                    class="booking-status booking-status-${statusClass}"
                >
                    ${status}
                </span>

            </td>

            <td>

                <div class="location-actions">

                    <button
                        type="button"
                        class="location-action-btn location-edit-btn"
                        data-location-id="${escapeHtml(
                            location.id
                        )}"
                        title="Edit location"
                        aria-label="Edit location"
                    >
                        <i class="bi bi-pencil"></i>
                    </button>

                    <button
                        type="button"
                        class="location-action-btn location-delete-btn"
                        data-location-id="${escapeHtml(
                            location.id
                        )}"
                        title="Delete location"
                        aria-label="Delete location"
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

    setupLocationActionButtons();
}


/* ==========================================================================
   LOCATION FILTERS
   ========================================================================== */

function setupLocationFilters() {

    const searchInput =
        document.getElementById(
            'locationSearch'
        );

    const statusFilter =
        document.getElementById(
            'locationStatusFilter'
        );

    searchInput?.addEventListener(
        'input',
        applyLocationFilters
    );

    statusFilter?.addEventListener(
        'change',
        applyLocationFilters
    );
}


function applyLocationFilters() {

    const searchInput =
        document.getElementById(
            'locationSearch'
        );

    const statusFilter =
        document.getElementById(
            'locationStatusFilter'
        );

    const searchTerm =
        searchInput
            ? searchInput.value
                .trim()
                .toLowerCase()
            : '';

    const selectedStatus =
        statusFilter
            ? statusFilter.value
            : '';

    const filteredLocations =
        allLocations.filter(
            (location) => {

                const searchableText = [
                    location.name,
                    location.address,
                    location.city,
                    location.state,
                    location.country
                ]
                    .filter(Boolean)
                    .join(' ')
                    .toLowerCase();

                const matchesSearch =
                    !searchTerm ||
                    searchableText.includes(
                        searchTerm
                    );

                const matchesStatus =
                    !selectedStatus ||
                    String(
                        location.is_active
                    ) ===
                    selectedStatus;

                return (
                    matchesSearch &&
                    matchesStatus
                );
            }
        );

    renderLocations(
        filteredLocations
    );
}


/* ==========================================================================
   LOCATION MODAL
   ========================================================================== */

function setupLocationModal() {

    const modal =
        document.getElementById(
            'locationModal'
        );

    const newLocationBtn =
        document.getElementById(
            'newLocationBtn'
        );

    const closeLocationModal =
        document.getElementById(
            'closeLocationModal'
        );

    const cancelLocationBtn =
        document.getElementById(
            'cancelLocationBtn'
        );

    const form =
        document.getElementById(
            'locationForm'
        );

    if (
        !modal ||
        !newLocationBtn ||
        !form
    ) {
        return;
    }

    newLocationBtn.addEventListener(
        'click',
        () => {

            resetLocationForm();

            openLocationModal();
        }
    );

    closeLocationModal?.addEventListener(
        'click',
        closeLocationModalWindow
    );

    cancelLocationBtn?.addEventListener(
        'click',
        closeLocationModalWindow
    );

    modal.addEventListener(
        'click',
        (event) => {

            if (
                event.target === modal
            ) {
                closeLocationModalWindow();
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
                closeLocationModalWindow();
            }
        }
    );

    form.addEventListener(
        'submit',
        handleLocationFormSubmit
    );
}


function openLocationModal() {

    const modal =
        document.getElementById(
            'locationModal'
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
            'locationName'
        )?.focus();

    }, 50);
}


function closeLocationModalWindow() {

    const modal =
        document.getElementById(
            'locationModal'
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


function resetLocationForm() {

    const form =
        document.getElementById(
            'locationForm'
        );

    form?.reset();

    const locationId =
        document.getElementById(
            'locationId'
        );

    if (locationId) {
        locationId.value = '';
    }

    const modalTitle =
        document.getElementById(
            'locationModalTitle'
        );

    if (modalTitle) {

        modalTitle.textContent =
            'New Location';
    }

    const modalSubtitle =
        document.getElementById(
            'locationModalSubtitle'
        );

    if (modalSubtitle) {

        modalSubtitle.textContent =
            'Create a new office location.';
    }

    const submitButton =
        document.getElementById(
            'submitLocationBtn'
        );

    if (submitButton) {

        submitButton.disabled =
            false;

        submitButton.innerHTML = `
            <i class="bi bi-check-lg"></i>
            Save Location
        `;
    }

    hideLocationFormMessages();
}


/* ==========================================================================
   LOCATION ACTION BUTTONS
   ========================================================================== */

function setupLocationActionButtons() {

    const editButtons =
        document.querySelectorAll(
            '.location-edit-btn'
        );

    const deleteButtons =
        document.querySelectorAll(
            '.location-delete-btn'
        );

    editButtons.forEach(
        (button) => {

            button.addEventListener(
                'click',
                () => {

                    const locationId =
                        button.dataset.locationId;

                    editLocation(
                        locationId
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

                    const locationId =
                        button.dataset.locationId;

                    deleteLocation(
                        locationId
                    );
                }
            );
        }
    );
}


function findLocationById(locationId) {

    return allLocations.find(
        (location) =>
            String(location.id) ===
            String(locationId)
    );
}


/* ==========================================================================
   EDIT LOCATION
   ========================================================================== */

function editLocation(locationId) {

    const location =
        findLocationById(
            locationId
        );

    if (!location) {

        showLocationFormError(
            'Unable to find the selected location.'
        );

        return;
    }

    const locationIdInput =
        document.getElementById(
            'locationId'
        );

    const locationNameInput =
        document.getElementById(
            'locationName'
        );

    const locationAddressInput =
        document.getElementById(
            'locationAddress'
        );

    const locationCityInput =
        document.getElementById(
            'locationCity'
        );

    const locationStateInput =
        document.getElementById(
            'locationState'
        );

    const locationCountryInput =
        document.getElementById(
            'locationCountry'
        );

    const locationStatusInput =
        document.getElementById(
            'locationStatus'
        );

    if (locationIdInput) {
        locationIdInput.value =
            location.id;
    }

    if (locationNameInput) {
        locationNameInput.value =
            location.name || '';
    }

    if (locationAddressInput) {
        locationAddressInput.value =
            location.address || '';
    }

    if (locationCityInput) {
        locationCityInput.value =
            location.city || '';
    }

    if (locationStateInput) {
        locationStateInput.value =
            location.state || '';
    }

    if (locationCountryInput) {
        locationCountryInput.value =
            location.country || '';
    }

    if (locationStatusInput) {
        locationStatusInput.value =
            String(
                location.is_active ?? '1'
            );
    }

    const modalTitle =
        document.getElementById(
            'locationModalTitle'
        );

    if (modalTitle) {

        modalTitle.textContent =
            'Edit Location';
    }

    const modalSubtitle =
        document.getElementById(
            'locationModalSubtitle'
        );

    if (modalSubtitle) {

        modalSubtitle.textContent =
            'Update location details.';
    }

    hideLocationFormMessages();

    openLocationModal();
}


/* ==========================================================================
   CREATE / UPDATE LOCATION
   ========================================================================== */

async function handleLocationFormSubmit(
    event
) {

    event.preventDefault();

    const form =
        document.getElementById(
            'locationForm'
        );

    const submitButton =
        document.getElementById(
            'submitLocationBtn'
        );

    if (
        !form ||
        !submitButton
    ) {
        return;
    }

    hideLocationFormMessages();

    const locationId =
        document.getElementById(
            'locationId'
        ).value.trim();

    const name =
        document.getElementById(
            'locationName'
        ).value.trim();

    const address =
        document.getElementById(
            'locationAddress'
        ).value.trim();

    const city =
        document.getElementById(
            'locationCity'
        ).value.trim();

    const state =
        document.getElementById(
            'locationState'
        ).value.trim();

    const country =
        document.getElementById(
            'locationCountry'
        ).value.trim();

    const isActive =
        document.getElementById(
            'locationStatus'
        ).value;

    if (!name) {

        showLocationFormError(
            'Please enter a location name.'
        );

        return;
    }

    const payload = {
        name: name,
        address: address,
        city: city,
        state: state,
        country: country,
        is_active: Number(isActive)
    };

    const isEditing =
        Boolean(locationId);

    const url =
        isEditing
            ? `/api/locations/${locationId}`
            : '/api/locations';

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

            handleLocationApiError(
                response.status,
                result
            );

            return;
        }

        const successMessage =
            result.message ||
            (
                isEditing
                    ? 'Location updated successfully.'
                    : 'Location created successfully.'
            );

        showLocationFormSuccess(
            successMessage
        );

        showAppNotification(
            successMessage,
            'success',
            isEditing
                ? 'Location Updated'
                : 'Location Created'
        );

        await loadLocations();

        setTimeout(() => {

            closeLocationModalWindow();

        }, 800);

    } catch (error) {

        console.error(
            'Unable to save location:',
            error
        );

        showLocationFormError(
            'Unable to save location. Please try again.'
        );

        showAppNotification(
            'Unable to save location. Please try again.',
            'error',
            'Location Save Failed'
        );

    } finally {

        submitButton.disabled =
            false;

        submitButton.innerHTML = `
            <i class="bi bi-check-lg"></i>
            Save Location
        `;
    }
}


/* ==========================================================================
   DELETE LOCATION
   ========================================================================== */

async function deleteLocation(locationId) {

    const location =
        findLocationById(
            locationId
        );

    if (!location) {

        showAppNotification(
            'Unable to find the selected location.',
            'error',
            'Location Not Found'
        );

        return;
    }

    showAppConfirm(

        `Are you sure you want to delete "${location.name}"?`,

        async () => {

            await performLocationDelete(
                locationId,
                location.name
            );
        },

        'Delete Location',

        'Delete Location'
    );
}


/**
 * Actually performs the location deletion
 * after the user clicks "Delete Location".
 */
async function performLocationDelete(
    locationId,
    locationName
) {

    try {

        const response =
            await fetch(
                `/api/locations/${locationId}`,
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
                    'This location cannot be deleted because it may be used by one or more rooms.',
                    'warning',
                    'Cannot Delete Location'
                );
                return;
            }

            throw new Error(
                result.message ||
                'Unable to delete location.'
            );
        }

        await loadLocations();

        showAppNotification(

            result.message ||
            `"${locationName}" has been deleted successfully.`,

            'success',

            'Location Deleted'
        );

    } catch (error) {

        console.error(
            'Unable to delete location:',
            error
        );

        showAppNotification(

            error.message ||
            'Unable to delete location. Please try again.',

            'error',

            'Delete Failed'
        );
    }
}


/* ==========================================================================
   LOCATION API ERROR HANDLING
   ========================================================================== */

function handleLocationApiError(
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

        showLocationFormError(
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
        status === 404
    ) {

        const message =
            result.message ||
            'Location could not be found.';

        showLocationFormError(
            message
        );

        showAppNotification(
            message,
            'error',
            'Location Not Found'
        );

        return;
    }

    if (
        status === 409
    ) {

        const message =
            result.message ||
            'Conflict occurred while processing location.';

        showLocationFormError(
            message
        );

        showAppNotification(
            message,
            'warning',
            'Location Conflict'
        );

        return;
    }

    const message =
        result.message ||
        'Unable to save location.';

    showLocationFormError(
        message
    );

    showAppNotification(
        message,
        'error',
        'Location Error'
    );
}


/* ==========================================================================
   LOCATION FORM MESSAGES
   ========================================================================== */

function showLocationFormError(
    message
) {

    const errorBox =
        document.getElementById(
            'locationFormError'
        );

    const errorText =
        document.getElementById(
            'locationFormErrorText'
        );

    const successBox =
        document.getElementById(
            'locationFormSuccess'
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


function showLocationFormSuccess(
    message
) {

    const successBox =
        document.getElementById(
            'locationFormSuccess'
        );

    const successText =
        document.getElementById(
            'locationFormSuccessText'
        );

    const errorBox =
        document.getElementById(
            'locationFormError'
        );

    errorBox?.classList.add(
        'd-none'
    );

    if (successText) {

        successText.textContent =
            message;
    }

    successBox?.classList.remove(
        'd-none'
    );
}


function hideLocationFormMessages() {

    document.getElementById(
        'locationFormError'
    )?.classList.add(
        'd-none'
    );

    document.getElementById(
        'locationFormSuccess'
    )?.classList.add(
        'd-none'
    );
}

