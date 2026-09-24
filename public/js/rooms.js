/**
 * MeetSpace Enterprise Suite - Rooms Module JavaScript
 *
 * Handles:
 * - Room listing and rendering
 * - Room search and filtering
 * - Room creation and editing modal
 * - Room deletion with custom confirmation
 * - Room form validation and API error handling
 */

document.addEventListener('DOMContentLoaded', () => {

    // Rooms page
    if (document.getElementById('roomsTableBody')) {
        loadRooms();
        setupRoomFilters();
        setupRoomModal();
        setupRoomQrModal();
    }
});


/* ==========================================================================
   ROOMS
   ========================================================================== */

let allRooms = [];


async function loadRooms() {

    const loading =
        document.getElementById(
            'roomsLoading'
        );

    const error =
        document.getElementById(
            'roomsError'
        );

    const empty =
        document.getElementById(
            'roomsEmpty'
        );

    const tableWrapper =
        document.getElementById(
            'roomsTableWrapper'
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
                '/api/rooms'
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
                'Rooms request failed.'
            );
        }

        allRooms =
            result.data || [];

        loading?.classList.add(
            'd-none'
        );

        renderRooms(
            allRooms
        );

    } catch (err) {

        console.error(
            'Unable to load rooms:',
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


function renderRooms(rooms) {

    const tableBody =
        document.getElementById(
            'roomsTableBody'
        );

    const empty =
        document.getElementById(
            'roomsEmpty'
        );

    const tableWrapper =
        document.getElementById(
            'roomsTableWrapper'
        );

    if (!tableBody) {
        return;
    }

    tableBody.innerHTML = '';

    if (!rooms.length) {

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

    rooms.forEach((room) => {

        const row =
            document.createElement(
                'tr'
            );
        row.className = 'hover-lift';

        const isActive =
            String(
                room.is_active
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
                        room.name ||
                        'Unnamed Room'
                    )}
                </div>

            </td>

            <td>

                <span class="booking-room-code">
                    ${escapeHtml(
                        room.room_code ||
                        '—'
                    )}
                </span>

            </td>

            <td>

                <span class="booking-time">
                    ${escapeHtml(
                        room.capacity ||
                        '—'
                    )}
                </span>

            </td>

            <td>

                <span class="booking-date">
                    ${escapeHtml(
                        room.floor ||
                        '—'
                    )}
                </span>

            </td>

            <td>

                <div class="booking-description">
                    ${escapeHtml(
                        room.description ||
                        '—'
                    )}
                </div>

            </td>

            <td>

                <span
                    class="booking-status booking-status-${statusClass}"
                >
                    ${status}
                </span>

            </td>

            <td>

                <div class="room-actions">

                    <button
                        type="button"
                        class="room-action-btn room-qr-btn"
                        data-room-id="${escapeHtml(
                            room.id
                        )}"
                        data-room-name="${escapeHtml(
                            room.name || ''
                        )}"
                        data-room-code="${escapeHtml(
                            room.room_code || ''
                        )}"
                        title="View Room QR Code"
                        aria-label="View Room QR Code"
                    >
                        <i class="bi bi-qr-code"></i>
                    </button>

                    <button
                        type="button"
                        class="room-action-btn room-edit-btn"
                        data-room-id="${escapeHtml(
                            room.id
                        )}"
                        title="Edit room"
                        aria-label="Edit room"
                    >
                        <i class="bi bi-pencil"></i>
                    </button>

                    <button
                        type="button"
                        class="room-action-btn room-delete-btn"
                        data-room-id="${escapeHtml(
                            room.id
                        )}"
                        title="Delete room"
                        aria-label="Delete room"
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

    setupRoomActionButtons();
}


/* ==========================================================================
   ROOM FILTERS
   ========================================================================== */

function setupRoomFilters() {

    const searchInput =
        document.getElementById(
            'roomSearch'
        );

    const statusFilter =
        document.getElementById(
            'roomStatusFilter'
        );

    searchInput?.addEventListener(
        'input',
        applyRoomFilters
    );

    statusFilter?.addEventListener(
        'change',
        applyRoomFilters
    );
}


function applyRoomFilters() {

    const searchInput =
        document.getElementById(
            'roomSearch'
        );

    const statusFilter =
        document.getElementById(
            'roomStatusFilter'
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

    const filteredRooms =
        allRooms.filter(
            (room) => {

                const searchableText = [
                    room.name,
                    room.room_code,
                    room.floor,
                    room.description
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
                        room.is_active
                    ) ===
                    selectedStatus;

                return (
                    matchesSearch &&
                    matchesStatus
                );
            }
        );

    renderRooms(
        filteredRooms
    );
}


/* ==========================================================================
   ROOM MODAL
   ========================================================================== */

function setupRoomModal() {

    const modal =
        document.getElementById(
            'roomModal'
        );

    const newRoomBtn =
        document.getElementById(
            'newRoomBtn'
        );

    const closeRoomModal =
        document.getElementById(
            'closeRoomModal'
        );

    const cancelRoomBtn =
        document.getElementById(
            'cancelRoomBtn'
        );

    const form =
        document.getElementById(
            'roomForm'
        );

    if (
        !modal ||
        !newRoomBtn ||
        !form
    ) {
        return;
    }

    newRoomBtn.addEventListener(
        'click',
        () => {

            resetRoomForm();

            openRoomModal();
        }
    );

    closeRoomModal?.addEventListener(
        'click',
        closeRoomModalWindow
    );

    cancelRoomBtn?.addEventListener(
        'click',
        closeRoomModalWindow
    );

    modal.addEventListener(
        'click',
        (event) => {

            if (
                event.target === modal
            ) {
                closeRoomModalWindow();
            }
        }
    );

    form.addEventListener(
        'submit',
        handleRoomFormSubmit
    );
}


function openRoomModal() {

    const modal =
        document.getElementById(
            'roomModal'
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
            'roomName'
        )?.focus();

    }, 50);
}


function closeRoomModalWindow() {

    const modal =
        document.getElementById(
            'roomModal'
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


function resetRoomForm() {

    const form =
        document.getElementById(
            'roomForm'
        );

    form?.reset();

    const roomId =
        document.getElementById(
            'roomId'
        );

    if (roomId) {
        roomId.value = '';
    }

    const modalTitle =
        document.getElementById(
            'roomModalTitle'
        );

    if (modalTitle) {

        modalTitle.textContent =
            'New Room';
    }

    const modalSubtitle =
        document.getElementById(
            'roomModalSubtitle'
        );

    if (modalSubtitle) {

        modalSubtitle.textContent =
            'Create a new meeting room.';
    }

    const submitButton =
        document.getElementById(
            'submitRoomBtn'
        );

    if (submitButton) {

        submitButton.disabled =
            false;

        submitButton.innerHTML = `
            <i class="bi bi-check-lg"></i>
            Save Room
        `;
    }

    hideRoomFormMessages();
}


/* ==========================================================================
   ROOM ACTION BUTTONS
   ========================================================================== */

function setupRoomActionButtons() {

    const qrButtons =
        document.querySelectorAll(
            '.room-qr-btn'
        );

    const editButtons =
        document.querySelectorAll(
            '.room-edit-btn'
        );

    const deleteButtons =
        document.querySelectorAll(
            '.room-delete-btn'
        );

    qrButtons.forEach(
        (button) => {

            button.addEventListener(
                'click',
                () => {

                    const roomId =
                        button.dataset.roomId;

                    openRoomQrModal(
                        roomId
                    );
                }
            );
        }
    );

    editButtons.forEach(
        (button) => {

            button.addEventListener(
                'click',
                () => {

                    const roomId =
                        button.dataset.roomId;

                    editRoom(
                        roomId
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

                    const roomId =
                        button.dataset.roomId;

                    deleteRoom(
                        roomId
                    );
                }
            );
        }
    );
}


function findRoomById(roomId) {

    return allRooms.find(
        (room) =>
            String(room.id) ===
            String(roomId)
    );
}


/* ==========================================================================
   EDIT ROOM
   ========================================================================== */

function editRoom(roomId) {

    const room =
        findRoomById(
            roomId
        );

    if (!room) {

        showRoomFormError(
            'Unable to find the selected room.'
        );

        return;
    }

    const roomIdInput =
        document.getElementById(
            'roomId'
        );

    const roomNameInput =
        document.getElementById(
            'roomName'
        );

    const roomCodeInput =
        document.getElementById(
            'roomCode'
        );

    const roomLocationInput =
        document.getElementById(
            'roomLocation'
        );

    const roomCapacityInput =
        document.getElementById(
            'roomCapacity'
        );

    const roomFloorInput =
        document.getElementById(
            'roomFloor'
        );

    const roomStatusInput =
        document.getElementById(
            'roomStatus'
        );

    const roomDescriptionInput =
        document.getElementById(
            'roomDescription'
        );

    if (roomIdInput) {
        roomIdInput.value =
            room.id;
    }

    if (roomNameInput) {
        roomNameInput.value =
            room.name || '';
    }

    if (roomCodeInput) {
        roomCodeInput.value =
            room.room_code || '';
    }

    if (roomLocationInput) {
        roomLocationInput.value =
            room.location_id || '';
    }

    if (roomCapacityInput) {
        roomCapacityInput.value =
            room.capacity || '';
    }

    if (roomFloorInput) {
        roomFloorInput.value =
            room.floor || '';
    }

    if (roomStatusInput) {
        roomStatusInput.value =
            String(
                room.is_active ?? '1'
            );
    }

    if (roomDescriptionInput) {
        roomDescriptionInput.value =
            room.description || '';
    }

    const modalTitle =
        document.getElementById(
            'roomModalTitle'
        );

    if (modalTitle) {

        modalTitle.textContent =
            'Edit Room';
    }

    const modalSubtitle =
        document.getElementById(
            'roomModalSubtitle'
        );

    if (modalSubtitle) {

        modalSubtitle.textContent =
            'Update meeting room details.';
    }

    hideRoomFormMessages();

    openRoomModal();
}


/* ==========================================================================
   CREATE / UPDATE ROOM
   ========================================================================== */

async function handleRoomFormSubmit(
    event
) {

    event.preventDefault();

    const form =
        document.getElementById(
            'roomForm'
        );

    const submitButton =
        document.getElementById(
            'submitRoomBtn'
        );

    if (
        !form ||
        !submitButton
    ) {
        return;
    }

    hideRoomFormMessages();

    const roomId =
        document.getElementById(
            'roomId'
        ).value.trim();

    const name =
        document.getElementById(
            'roomName'
        ).value.trim();

    const roomCode =
        document.getElementById(
            'roomCode'
        ).value.trim();

    const locationId =
        document.getElementById(
            'roomLocation'
        ).value;

    const capacity =
        document.getElementById(
            'roomCapacity'
        ).value;

    const floor =
        document.getElementById(
            'roomFloor'
        ).value.trim();

    const description =
        document.getElementById(
            'roomDescription'
        ).value.trim();

    const isActive =
        document.getElementById(
            'roomStatus'
        ).value;

    if (
        !name ||
        !roomCode ||
        !locationId ||
        !capacity
    ) {

        showRoomFormError(
            'Please fill in all required fields.'
        );

        return;
    }

    if (
        Number(locationId) <= 0
    ) {

        showRoomFormError(
            'Location ID must be greater than zero.'
        );

        return;
    }

    if (
        Number(capacity) <= 0
    ) {

        showRoomFormError(
            'Room capacity must be greater than zero.'
        );

        return;
    }

    const payload = {

        location_id:
            Number(locationId),

        name:
            name,

        room_code:
            roomCode,

        capacity:
            Number(capacity),

        floor:
            floor,

        description:
            description,

        is_active:
            Number(isActive)
    };

    const isEditing =
        Boolean(roomId);

    const url =
        isEditing
            ? `/api/rooms/${roomId}`
            : '/api/rooms';

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

            handleRoomApiError(
                response.status,
                result
            );

            return;
        }

        const successMessage =
            result.message ||
            (
                isEditing
                    ? 'Room updated successfully.'
                    : 'Room created successfully.'
            );

        showRoomFormSuccess(
            successMessage
        );

        showAppNotification(
            successMessage,
            'success',
            isEditing
                ? 'Room Updated'
                : 'Room Created'
        );

        await loadRooms();

        setTimeout(() => {

            closeRoomModalWindow();

        }, 800);

    } catch (error) {

        console.error(
            'Unable to save room:',
            error
        );

        showRoomFormError(
            'Unable to save room. Please try again.'
        );

        showAppNotification(
            'Unable to save room. Please try again.',
            'error',
            'Room Update Failed'
        );

    } finally {

        submitButton.disabled =
            false;

        submitButton.innerHTML = `
            <i class="bi bi-check-lg"></i>
            Save Room
        `;
    }
}


/* ==========================================================================
   DELETE ROOM
   ========================================================================== */

async function deleteRoom(roomId) {

    const room =
        findRoomById(
            roomId
        );

    if (!room) {

        showAppNotification(
            'Unable to find the selected room.',
            'error',
            'Room Not Found'
        );

        return;
    }

    /*
     * IMPORTANT:
     *
     * We no longer use:
     *
     * window.confirm()
     *
     * Instead, we show our custom
     * MeetSpace confirmation dialog.
     */

    showAppConfirm(

        `Are you sure you want to delete "${room.name}"?`,

        async () => {

            await performRoomDelete(
                roomId,
                room.name
            );
        },

        'Delete Room',

        'Delete Room'
    );
}


/**
 * Actually performs the room deletion
 * after the user clicks "Delete Room".
 */
async function performRoomDelete(
    roomId,
    roomName
) {

    try {

        const response =
            await fetch(
                `/api/rooms/${roomId}`,
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

            throw new Error(
                result.message ||
                'Unable to delete room.'
            );
        }

        await loadRooms();

        /*
         * Success message appears
         * INSIDE the application.
         */

        showAppNotification(

            result.message ||
            `"${roomName}" has been deleted successfully.`,

            'success',

            'Room Deleted'
        );

    } catch (error) {

        console.error(
            'Unable to delete room:',
            error
        );

        /*
         * No browser alert().
         * Use application notification instead.
         */

        showAppNotification(

            error.message ||
            'Unable to delete room. Please try again.',

            'error',

            'Delete Failed'
        );
    }
}


/* ==========================================================================
   ROOM API ERROR HANDLING
   ========================================================================== */

function handleRoomApiError(
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

        showRoomFormError(
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
            'Room or location could not be found.';

        showRoomFormError(
            message
        );

        showAppNotification(
            message,
            'error',
            'Room Not Found'
        );

        return;
    }

    const message =
        result.message ||
        'Unable to save room.';

    showRoomFormError(
        message
    );

    showAppNotification(
        message,
        'error',
        'Room Error'
    );
}


/* ==========================================================================
   ROOM FORM MESSAGES
   ========================================================================== */

function showRoomFormError(
    message
) {

    const errorBox =
        document.getElementById(
            'roomFormError'
        );

    const errorText =
        document.getElementById(
            'roomFormErrorText'
        );

    const successBox =
        document.getElementById(
            'roomFormSuccess'
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


function showRoomFormSuccess(
    message
) {

    const successBox =
        document.getElementById(
            'roomFormSuccess'
        );

    const successText =
        document.getElementById(
            'roomFormSuccessText'
        );

    const errorBox =
        document.getElementById(
            'roomFormError'
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


function hideRoomFormMessages() {

    document.getElementById(
        'roomFormError'
    )?.classList.add(
        'd-none'
    );

    document.getElementById(
        'roomFormSuccess'
    )?.classList.add(
        'd-none'
    );
}


/* ==========================================================================
   ROOM QR CODE MODAL
   ========================================================================== */

function setupRoomQrModal() {

    const modal =
        document.getElementById(
            'roomQrModal'
        );

    const closeBtn =
        document.getElementById(
            'closeRoomQrModal'
        );

    const cancelBtn =
        document.getElementById(
            'closeRoomQrBtn'
        );

    const copyBtn =
        document.getElementById(
            'roomQrCopyBtn'
        );

    const printBtn =
        document.getElementById(
            'roomQrPrintBtn'
        );

    closeBtn?.addEventListener(
        'click',
        closeRoomQrModal
    );

    cancelBtn?.addEventListener(
        'click',
        closeRoomQrModal
    );

    modal?.addEventListener(
        'click',
        (event) => {

            if (event.target === modal) {
                closeRoomQrModal();
            }
        }
    );

    document.addEventListener(
        'keydown',
        (event) => {

            if (
                event.key === 'Escape' &&
                modal &&
                !modal.classList.contains('d-none')
            ) {
                closeRoomQrModal();
            }
        }
    );

    copyBtn?.addEventListener(
        'click',
        async () => {

            const urlInput =
                document.getElementById(
                    'roomQrUrlInput'
                );

            const icon =
                document.getElementById(
                    'roomQrCopyIcon'
                );

            if (!urlInput || !urlInput.value) {
                return;
            }

            try {
                await navigator.clipboard.writeText(
                    urlInput.value
                );

                if (icon) {
                    icon.className = 'bi bi-check-lg';

                    setTimeout(() => {
                        icon.className = 'bi bi-clipboard';
                    }, 2000);
                }

                if (typeof showAppNotification === 'function') {
                    showAppNotification(
                        'Check-in link copied to clipboard.',
                        'success',
                        'Link Copied'
                    );
                }
            } catch (err) {
                urlInput.select();
                document.execCommand('copy');

                if (typeof showAppNotification === 'function') {
                    showAppNotification(
                        'Check-in link copied to clipboard.',
                        'success',
                        'Link Copied'
                    );
                }
            }
        }
    );

    printBtn?.addEventListener(
        'click',
        () => {
            window.print();
        }
    );
}


async function openRoomQrModal(roomId) {

    const modal =
        document.getElementById(
            'roomQrModal'
        );

    const loading =
        document.getElementById(
            'roomQrLoading'
        );

    const errorBox =
        document.getElementById(
            'roomQrError'
        );

    const errorText =
        document.getElementById(
            'roomQrErrorText'
        );

    const content =
        document.getElementById(
            'roomQrContent'
        );

    const nameEl =
        document.getElementById(
            'roomQrName'
        );

    const badgeEl =
        document.getElementById(
            'roomQrCodeBadge'
        );

    const displayEl =
        document.getElementById(
            'roomQrCodeDisplay'
        );

    const urlInput =
        document.getElementById(
            'roomQrUrlInput'
        );

    if (!modal) {
        return;
    }

    modal.classList.remove('d-none');
    loading?.classList.remove('d-none');
    errorBox?.classList.add('d-none');
    content?.classList.add('d-none');

    // Pre-populate known room name & code from cache if available
    const localRoom = findRoomById(roomId);
    if (localRoom) {
        if (nameEl) {
            nameEl.textContent = localRoom.name || 'Meeting Room';
        }
        if (badgeEl) {
            badgeEl.textContent = localRoom.room_code || '—';
        }
    }

    try {
        const response = await fetch(`/api/rooms/${roomId}/qr-code`);
        const result = await response.json();

        if (!response.ok || result.status !== 'success') {
            throw new Error(result.message || `Unable to load QR code (HTTP ${response.status}).`);
        }

        const data = result.data || {};

        if (nameEl) {
            nameEl.textContent = data.room_name || localRoom?.name || 'Meeting Room';
        }

        if (badgeEl) {
            badgeEl.textContent = data.room_code || localRoom?.room_code || '—';
        }

        if (urlInput) {
            urlInput.value = data.check_in_url || '';
        }

        if (displayEl) {
            if (data.qr_svg) {
                displayEl.innerHTML = data.qr_svg;
            } else if (data.qr_data_uri) {
                displayEl.innerHTML = `<img src="${escapeHtml(data.qr_data_uri)}" alt="Room QR Code" class="img-fluid" />`;
            } else {
                displayEl.innerHTML = '<span class="text-muted">QR code unavailable.</span>';
            }
        }

        loading?.classList.add('d-none');
        content?.classList.remove('d-none');

    } catch (err) {
        console.error('Error fetching room QR code:', err);
        loading?.classList.add('d-none');
        if (errorText) {
            errorText.textContent = err.message || 'Unable to generate room QR code.';
        }
        errorBox?.classList.remove('d-none');
    }
}


function closeRoomQrModal() {

    const modal =
        document.getElementById(
            'roomQrModal'
        );

    if (!modal) {
        return;
    }

    modal.classList.add('d-none');
    document.getElementById('roomQrLoading')?.classList.add('d-none');
    document.getElementById('roomQrError')?.classList.add('d-none');
    document.getElementById('roomQrContent')?.classList.add('d-none');

    const displayEl =
        document.getElementById(
            'roomQrCodeDisplay'
        );

    if (displayEl) {
        displayEl.innerHTML = '';
    }
}
