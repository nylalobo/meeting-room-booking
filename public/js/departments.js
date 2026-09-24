/**
 * MeetSpace Enterprise Suite - Departments Module JavaScript
 *
 * Handles:
 * - Encapsulation within an IIFE to prevent global namespace pollution
 * - Asynchronously fetching departments from /api/departments
 * - Real-time client-side search across department name and description
 * - Create and Edit modal workflows
 * - Deletion with custom confirmation dialog via showAppConfirm
 * - API response and validation error handling (404, 422, 500)
 * - Safe HTML rendering with escapeHtml and app toast notifications
 */

(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', () => {

        if (document.getElementById('departmentsTableBody')) {
            loadDepartments();
            setupDepartmentSearch();
            setupDepartmentModal();
        }
    });


    /* ==========================================================================
       STATE
       ========================================================================== */

    let allDepartments = [];


    /* ==========================================================================
       DATA LOADING
       ========================================================================== */

    async function loadDepartments() {

        const loading =
            document.getElementById(
                'departmentsLoading'
            );

        const error =
            document.getElementById(
                'departmentsError'
            );

        const empty =
            document.getElementById(
                'departmentsEmpty'
            );

        const tableWrapper =
            document.getElementById(
                'departmentsTableWrapper'
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
                await fetch('/api/departments');

            if (!response.ok) {
                throw new Error(
                    `HTTP error: ${response.status}`
                );
            }

            const result =
                await response.json();

            if (result.status !== 'success') {
                throw new Error(
                    'Departments request failed.'
                );
            }

            allDepartments =
                result.data || [];

            loading?.classList.add(
                'd-none'
            );

            renderDepartments(
                allDepartments
            );

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

    function renderDepartments(departments) {

        const tableBody =
            document.getElementById(
                'departmentsTableBody'
            );

        const empty =
            document.getElementById(
                'departmentsEmpty'
            );

        const tableWrapper =
            document.getElementById(
                'departmentsTableWrapper'
            );

        if (!tableBody) {
            return;
        }

        tableBody.innerHTML = '';

        if (!departments.length) {

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

        departments.forEach((department) => {

            const row =
                document.createElement('tr');

            const dateText =
                formatDateForDisplay(department.created_at);

            row.innerHTML = `

                <td>
                    <div class="department-name">
                        ${escapeHtml(department.name || 'Unnamed')}
                    </div>
                </td>

                <td>
                    <div class="department-description">
                        ${escapeHtml(department.description || '—')}
                    </div>
                </td>

                <td>
                    <span class="department-date">
                        ${escapeHtml(dateText)}
                    </span>
                </td>

                <td>
                    <div class="department-actions">
                        <button
                            type="button"
                            class="department-action-btn department-edit-btn"
                            data-department-id="${escapeHtml(department.id)}"
                            title="Edit department"
                            aria-label="Edit department"
                        >
                            <i class="bi bi-pencil"></i>
                        </button>

                        <button
                            type="button"
                            class="department-action-btn department-delete-btn"
                            data-department-id="${escapeHtml(department.id)}"
                            title="Delete department"
                            aria-label="Delete department"
                        >
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </td>

            `;

            tableBody.appendChild(row);
        });

        setupDepartmentActionButtons();
    }


    /* ==========================================================================
       SEARCH
       ========================================================================== */

    function setupDepartmentSearch() {

        const searchInput =
            document.getElementById(
                'departmentSearch'
            );

        searchInput?.addEventListener(
            'input',
            () => {

                const query =
                    searchInput.value.trim().toLowerCase();

                if (!query) {
                    renderDepartments(allDepartments);
                    return;
                }

                const filtered =
                    allDepartments.filter((dept) => {

                        const name =
                            String(dept.name || '').toLowerCase();

                        const desc =
                            String(dept.description || '').toLowerCase();

                        return (
                            name.includes(query) ||
                            desc.includes(query)
                        );
                    });

                renderDepartments(filtered);
            }
        );
    }


    /* ==========================================================================
       MODAL SETUP
       ========================================================================== */

    function setupDepartmentModal() {

        const modal =
            document.getElementById(
                'departmentModal'
            );

        const newBtn =
            document.getElementById(
                'newDepartmentBtn'
            );

        const closeBtn =
            document.getElementById(
                'closeDepartmentModal'
            );

        const cancelBtn =
            document.getElementById(
                'cancelDepartmentBtn'
            );

        const form =
            document.getElementById(
                'departmentForm'
            );

        if (!modal || !newBtn || !form) {
            return;
        }

        newBtn.addEventListener(
            'click',
            () => {
                resetDepartmentForm();
                openDepartmentModal();
            }
        );

        closeBtn?.addEventListener(
            'click',
            closeDepartmentModalWindow
        );

        cancelBtn?.addEventListener(
            'click',
            closeDepartmentModalWindow
        );

        modal.addEventListener(
            'click',
            (event) => {
                if (event.target === modal) {
                    closeDepartmentModalWindow();
                }
            }
        );

        document.addEventListener(
            'keydown',
            (event) => {
                if (
                    event.key === 'Escape' &&
                    !modal.classList.contains('d-none')
                ) {
                    closeDepartmentModalWindow();
                }
            }
        );

        form.addEventListener(
            'submit',
            handleDepartmentFormSubmit
        );
    }


    function openDepartmentModal() {

        const modal =
            document.getElementById(
                'departmentModal'
            );

        if (!modal) {
            return;
        }

        modal.classList.remove('d-none');
        document.body.classList.add('booking-modal-open');

        setTimeout(() => {
            document.getElementById('departmentName')?.focus();
        }, 50);
    }


    function closeDepartmentModalWindow() {

        const modal =
            document.getElementById(
                'departmentModal'
            );

        if (!modal) {
            return;
        }

        modal.classList.add('d-none');
        document.body.classList.remove('booking-modal-open');
    }


    function resetDepartmentForm() {

        const form =
            document.getElementById(
                'departmentForm'
            );

        form?.reset();

        const deptId =
            document.getElementById(
                'departmentId'
            );

        if (deptId) {
            deptId.value = '';
        }

        const modalTitle =
            document.getElementById(
                'departmentModalTitle'
            );

        if (modalTitle) {
            modalTitle.textContent =
                'New Department';
        }

        const modalSubtitle =
            document.getElementById(
                'departmentModalSubtitle'
            );

        if (modalSubtitle) {
            modalSubtitle.textContent =
                'Create a new organizational department.';
        }

        const submitBtn =
            document.getElementById(
                'submitDepartmentBtn'
            );

        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = `
                <i class="bi bi-diagram-3"></i>
                Create Department
            `;
        }

        hideDepartmentFormMessages();
    }


    /* ==========================================================================
       ACTION BUTTONS (EDIT / DELETE)
       ========================================================================== */

    function setupDepartmentActionButtons() {

        const editButtons =
            document.querySelectorAll(
                '.department-edit-btn'
            );

        const deleteButtons =
            document.querySelectorAll(
                '.department-delete-btn'
            );

        editButtons.forEach((button) => {
            button.addEventListener(
                'click',
                () => {
                    const deptId = button.dataset.departmentId;
                    editDepartment(deptId);
                }
            );
        });

        deleteButtons.forEach((button) => {
            button.addEventListener(
                'click',
                () => {
                    const deptId = button.dataset.departmentId;
                    deleteDepartment(deptId);
                }
            );
        });
    }


    function findDepartmentById(id) {

        return allDepartments.find(
            (d) => String(d.id) === String(id)
        );
    }


    /* ==========================================================================
       EDIT
       ========================================================================== */

    function editDepartment(deptId) {

        const dept = findDepartmentById(deptId);

        if (!dept) {
            showAppNotification(
                'Unable to find the selected department.',
                'error',
                'Department Not Found'
            );
            return;
        }

        const idInput =
            document.getElementById('departmentId');

        const nameInput =
            document.getElementById('departmentName');

        const descInput =
            document.getElementById('departmentDescription');

        if (idInput) {
            idInput.value = dept.id;
        }

        if (nameInput) {
            nameInput.value = dept.name || '';
        }

        if (descInput) {
            descInput.value = dept.description || '';
        }

        const modalTitle =
            document.getElementById('departmentModalTitle');

        if (modalTitle) {
            modalTitle.textContent = 'Edit Department';
        }

        const modalSubtitle =
            document.getElementById('departmentModalSubtitle');

        if (modalSubtitle) {
            modalSubtitle.textContent =
                'Update organizational department details.';
        }

        const submitBtn =
            document.getElementById('submitDepartmentBtn');

        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = `
                <i class="bi bi-check-lg"></i>
                Save Changes
            `;
        }

        hideDepartmentFormMessages();
        openDepartmentModal();
    }


    /* ==========================================================================
       CREATE / UPDATE SUBMISSION
       ========================================================================== */

    async function handleDepartmentFormSubmit(event) {

        event.preventDefault();

        const form =
            document.getElementById('departmentForm');

        const submitBtn =
            document.getElementById('submitDepartmentBtn');

        if (!form || !submitBtn) {
            return;
        }

        hideDepartmentFormMessages();

        const deptId =
            document.getElementById('departmentId')?.value.trim() || '';

        const name =
            document.getElementById('departmentName')?.value.trim() || '';

        const description =
            document.getElementById('departmentDescription')?.value.trim() || '';

        const isEditing = Boolean(deptId);

        if (!name) {
            showDepartmentFormError('Department name is required.');
            return;
        }

        const payload = {
            name: name,
            description: description || null
        };

        const url = isEditing
            ? `/api/departments/${deptId}`
            : '/api/departments';

        const method = isEditing ? 'PUT' : 'POST';

        try {

            submitBtn.disabled = true;
            submitBtn.innerHTML = `
                <i class="bi bi-arrow-repeat spin"></i>
                ${isEditing ? 'Updating...' : 'Creating...'}
            `;

            const response = await fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            const result = await response.json();

            if (!response.ok || result.status !== 'success') {
                handleDepartmentApiError(response.status, result);
                return;
            }

            const successMessage =
                result.message ||
                (isEditing
                    ? 'Department updated successfully.'
                    : 'Department created successfully.');

            closeDepartmentModalWindow();

            showAppNotification(
                successMessage,
                'success',
                isEditing ? 'Department Updated' : 'Department Created'
            );

            await loadDepartments();

        } catch (error) {

            showDepartmentFormError(
                'Unable to save department. Please try again.'
            );

            showAppNotification(
                'Unable to save department. Please try again.',
                'error',
                'Save Failed'
            );

        } finally {

            submitBtn.disabled = false;
            submitBtn.innerHTML = `
                <i class="bi bi-${isEditing ? 'check-lg' : 'diagram-3'}"></i>
                ${isEditing ? 'Save Changes' : 'Create Department'}
            `;
        }
    }


    /* ==========================================================================
       DELETE
       ========================================================================== */

    function deleteDepartment(deptId) {

        const dept = findDepartmentById(deptId);

        if (!dept) {
            showAppNotification(
                'Unable to find the selected department.',
                'error',
                'Department Not Found'
            );
            return;
        }

        const deptName = dept.name || 'this department';

        showAppConfirm(
            `Are you sure you want to delete "${deptName}"? Deleting this department will remove the department assignment from associated users.`,
            async () => {
                await performDepartmentDelete(deptId, deptName);
            },
            'Delete Department',
            'Delete Department'
        );
    }


    async function performDepartmentDelete(deptId, deptName) {

        try {

            const response = await fetch(`/api/departments/${deptId}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json'
                }
            });

            const result = await response.json();

            if (!response.ok || result.status !== 'success') {
                throw new Error(
                    result.message || 'Unable to delete department.'
                );
            }

            await loadDepartments();

            showAppNotification(
                result.message || `"${deptName}" has been deleted successfully.`,
                'success',
                'Department Deleted'
            );

        } catch (error) {

            showAppNotification(
                error.message || 'Unable to delete department. Please try again.',
                'error',
                'Delete Failed'
            );
        }
    }


    /* ==========================================================================
       API ERROR HANDLING
       ========================================================================== */

    function handleDepartmentApiError(status, result) {

        if (status === 422 && result.errors) {

            const messages =
                Object.values(result.errors);

            const combined =
                messages.join(' ');

            showDepartmentFormError(combined);

            showAppNotification(
                combined,
                'error',
                'Validation Error'
            );

            return;
        }

        if (status === 404) {

            const msg =
                result.message || 'Department not found.';

            showDepartmentFormError(msg);

            showAppNotification(
                msg,
                'error',
                'Not Found'
            );

            return;
        }

        const fallback =
            result.message || 'Unable to process department request.';

        showDepartmentFormError(fallback);

        showAppNotification(
            fallback,
            'error',
            'Department Error'
        );
    }


    /* ==========================================================================
       FORM MESSAGES
       ========================================================================== */

    function showDepartmentFormError(message) {

        const errorBox =
            document.getElementById('departmentFormError');

        const errorText =
            document.getElementById('departmentFormErrorText');

        const successBox =
            document.getElementById('departmentFormSuccess');

        successBox?.classList.add('d-none');

        if (errorText) {
            errorText.textContent = message;
        }

        errorBox?.classList.remove('d-none');
    }


    function showDepartmentFormSuccess(message) {

        const successBox =
            document.getElementById('departmentFormSuccess');

        const successText =
            document.getElementById('departmentFormSuccessText');

        const errorBox =
            document.getElementById('departmentFormError');

        errorBox?.classList.add('d-none');

        if (successText) {
            successText.textContent = message;
        }

        successBox?.classList.add('d-none');

        showAppNotification(message, 'success');
    }


    function hideDepartmentFormMessages() {

        document.getElementById('departmentFormError')?.classList.add('d-none');
        document.getElementById('departmentFormSuccess')?.classList.add('d-none');
    }


    /* ==========================================================================
       HELPERS
       ========================================================================== */

    function formatDateForDisplay(dateStr) {

        if (!dateStr) {
            return '—';
        }

        const parts = String(dateStr).split(' ')[0].split('-');

        if (parts.length !== 3) {
            return '—';
        }

        const year = parts[0];
        const month = Number(parts[1]);
        const day = parts[2];

        const months = [
            'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
            'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
        ];

        const monthName = months[month - 1] || '';

        return `${day} ${monthName} ${year}`;
    }

})();

