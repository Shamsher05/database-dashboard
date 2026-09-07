<?php
/**
 * index.php
 * ---------
 * Main dashboard page. Records are loaded via AJAX from api.php,
 * so there is no hardcoded/static data anywhere on this page.
 */
require_once __DIR__ . '/config/database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Database Management Dashboard</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

<style>
    :root {
        --primary: #2c3e50;
        --accent: #3b82f6;
    }
    body {
        background-color: #f4f6f9;
        font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
    }
    .page-header {
        background: linear-gradient(135deg, var(--primary), #34495e);
        color: #fff;
        border-radius: 0 0 24px 24px;
        padding: 40px 0 50px;
        margin-bottom: -30px;
    }
    .page-header h1 { font-weight: 700; }
    .page-header p { opacity: 0.85; margin-bottom: 0; }

    .main-card {
        border: none;
        border-radius: 16px;
        box-shadow: 0 6px 24px rgba(0,0,0,0.08);
    }

    .btn-primary-custom {
        background-color: var(--accent);
        border: none;
        font-weight: 500;
    }
    .btn-primary-custom:hover { background-color: #2563eb; }

    .table thead th {
        background-color: var(--primary);
        color: #fff;
        border: none;
        font-weight: 500;
        white-space: nowrap;
    }
    .table tbody tr { transition: background 0.15s ease; }
    .table tbody tr:hover { background-color: #f1f5ff; }
    .table td, .table th { vertical-align: middle; }

    .action-btn { border: none; background: none; font-size: 1.05rem; padding: 4px 8px; }
    .action-btn.edit { color: #3b82f6; }
    .action-btn.delete { color: #ef4444; }
    .action-btn:hover { opacity: 0.7; }

    .empty-state { padding: 60px 20px; text-align: center; color: #94a3b8; }
    .empty-state i { font-size: 3rem; display: block; margin-bottom: 10px; }

    #exportMenu { min-width: 220px; padding: 14px; }
    .toast-container { z-index: 2000; }

    .field-error { font-size: 0.8rem; color: #ef4444; margin-top: 2px; }

    @media (max-width: 576px) {
        .top-actions { flex-direction: column; align-items: stretch !important; }
        .top-actions > * { width: 100%; }
    }
</style>
</head>
<body>

<!-- ================= HEADER ================= -->
<div class="page-header text-center">
    <div class="container">
        <h1><i class="bi bi-database-fill-gear"></i> Welcome in Our Database</h1>
        <p>Manage, create, update and export your database records.</p>
    </div>
</div>

<div class="container my-5">
    <div class="card main-card p-4">

        <!-- ================= TOP ACTIONS ================= -->
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 top-actions mb-4">
            <button class="btn btn-primary-custom text-white px-4 py-2" data-bs-toggle="modal" data-bs-target="#recordModal" id="btnNewRecord">
                <i class="bi bi-plus-lg"></i> New Record
            </button>

            <div class="dropdown">
                <button class="btn btn-outline-secondary px-4 py-2" type="button" id="exportDropdownBtn" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-download"></i> Export / Download
                </button>
                <div class="dropdown-menu dropdown-menu-end p-3" id="exportMenu">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="exportCsv">
                        <label class="form-check-label" for="exportCsv">CSV</label>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="exportPdf">
                        <label class="form-check-label" for="exportPdf">PDF</label>
                    </div>
                    <div id="exportError" class="text-danger small mb-2 d-none">Please select at least one export format.</div>
                    <button class="btn btn-primary-custom text-white w-100" id="btnDownload">Download</button>
                </div>
            </div>
        </div>

        <!-- ================= RECORDS TABLE ================= -->
        <h5 class="mb-3 text-secondary"><i class="bi bi-table"></i> Database Records</h5>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Serial No.</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Age</th>
                        <th>Address</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody id="recordsTableBody">
                    <!-- Populated dynamically via AJAX -->
                    <tr><td colspan="7" class="text-center py-4">
                        <div class="spinner-border text-secondary" role="status"></div>
                    </td></tr>
                </tbody>
            </table>
        </div>

        <div id="emptyState" class="empty-state d-none">
            <i class="bi bi-inbox"></i>
            <div><strong>No records found.</strong></div>
            <div>Create your first record by clicking "New Record".</div>
        </div>
    </div>
</div>

<!-- ================= RECORD MODAL (Create / Edit) ================= -->
<div class="modal fade" id="recordModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="recordModalTitle">New Record</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="recordForm" novalidate>
                <div class="modal-body">
                    <input type="hidden" id="recordId" value="">

                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" class="form-control" id="fieldName" required>
                        <div class="field-error" id="errName"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" id="fieldEmail" required>
                        <div class="field-error" id="errEmail"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" class="form-control" id="fieldPhone" required>
                        <div class="field-error" id="errPhone"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Age</label>
                        <input type="number" min="1" max="120" class="form-control" id="fieldAge" required>
                        <div class="field-error" id="errAge"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea class="form-control" id="fieldAddress" rows="3" required></textarea>
                        <div class="field-error" id="errAddress"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-custom text-white" id="btnSaveRecord">Save Record</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ================= DELETE CONFIRM MODAL ================= -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger"><i class="bi bi-exclamation-triangle"></i> Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this record?<br>
                <small class="text-muted">This action cannot be undone.</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="btnConfirmDelete">Delete</button>
            </div>
        </div>
    </div>
</div>

<!-- ================= TOAST ================= -->
<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="mainToast" class="toast" role="alert">
        <div class="toast-header">
            <strong class="me-auto" id="toastTitle">Notice</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body" id="toastBody"></div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const API_URL = 'api.php';

const recordModalEl   = document.getElementById('recordModal');
const recordModal     = new bootstrap.Modal(recordModalEl);
const deleteModal     = new bootstrap.Modal(document.getElementById('deleteModal'));
const toastEl         = document.getElementById('mainToast');
const toast           = new bootstrap.Toast(toastEl, { delay: 3000 });

let recordIdPendingDelete = null;

// ---------- Utility: show toast ----------
function showToast(message, isError = false) {
    document.getElementById('toastTitle').textContent = isError ? 'Error' : 'Success';
    document.getElementById('toastBody').textContent = message;
    toastEl.classList.remove('text-bg-danger', 'text-bg-success');
    toastEl.classList.add(isError ? 'text-bg-danger' : 'text-bg-success');
    toast.show();
}

// ---------- Utility: escape HTML to prevent XSS in rendered table ----------
function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str ?? '';
    return div.innerHTML;
}

// ---------- Load & render records ----------
async function loadRecords() {
    try {
        const res = await fetch(`${API_URL}?action=list`);
        const data = await res.json();

        const tbody = document.getElementById('recordsTableBody');
        const emptyState = document.getElementById('emptyState');

        if (!data.success) {
            tbody.innerHTML = `<tr><td colspan="7" class="text-center text-danger py-4">Failed to load records.</td></tr>`;
            return;
        }

        const records = data.records || [];

        if (records.length === 0) {
            tbody.innerHTML = '';
            emptyState.classList.remove('d-none');
            return;
        }

        emptyState.classList.add('d-none');

        tbody.innerHTML = records.map((r, index) => `
            <tr>
                <td>${index + 1}</td>
                <td>${escapeHtml(r.name)}</td>
                <td>${escapeHtml(r.email)}</td>
                <td>${escapeHtml(r.phone)}</td>
                <td>${escapeHtml(r.age)}</td>
                <td>${escapeHtml(r.address)}</td>
                <td class="text-center">
                    <button class="action-btn edit" title="Edit" onclick='openEditModal(${JSON.stringify(r)})'>
                        <i class="bi bi-pencil-square"></i>
                    </button>
                    <button class="action-btn delete" title="Delete" onclick="openDeleteModal(${r.id})">
                        <i class="bi bi-trash3"></i>
                    </button>
                </td>
            </tr>
        `).join('');
    } catch (e) {
        console.error(e);
        showToast('Something went wrong. Please try again.', true);
    }
}

// ---------- Reset modal / errors ----------
function clearFormErrors() {
    ['Name', 'Email', 'Phone', 'Age', 'Address'].forEach(f => {
        document.getElementById('err' + f).textContent = '';
        document.getElementById('field' + f).classList.remove('is-invalid');
    });
}

function resetForm() {
    document.getElementById('recordForm').reset();
    document.getElementById('recordId').value = '';
    clearFormErrors();
}

// ---------- New Record ----------
document.getElementById('btnNewRecord').addEventListener('click', () => {
    resetForm();
    document.getElementById('recordModalTitle').textContent = 'New Record';
    document.getElementById('btnSaveRecord').textContent = 'Save Record';
});

// ---------- Edit Record ----------
function openEditModal(record) {
    resetForm();
    document.getElementById('recordModalTitle').textContent = 'Edit Record';
    document.getElementById('btnSaveRecord').textContent = 'Update Record';

    document.getElementById('recordId').value = record.id;
    document.getElementById('fieldName').value = record.name;
    document.getElementById('fieldEmail').value = record.email;
    document.getElementById('fieldPhone').value = record.phone;
    document.getElementById('fieldAge').value = record.age;
    document.getElementById('fieldAddress').value = record.address;

    recordModal.show();
}

// ---------- Delete Record ----------
function openDeleteModal(id) {
    recordIdPendingDelete = id;
    deleteModal.show();
}

document.getElementById('btnConfirmDelete').addEventListener('click', async () => {
    if (!recordIdPendingDelete) return;

    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('id', recordIdPendingDelete);

    try {
        const res = await fetch(API_URL, { method: 'POST', body: formData });
        const data = await res.json();
        deleteModal.hide();

        showToast(data.message, !data.success);
        if (data.success) loadRecords();
    } catch (e) {
        deleteModal.hide();
        showToast('Something went wrong. Please try again.', true);
    }
});

// ---------- Basic client-side validation (backend always re-validates) ----------
function validateClientSide() {
    let valid = true;
    clearFormErrors();

    const name = document.getElementById('fieldName').value.trim();
    const email = document.getElementById('fieldEmail').value.trim();
    const phone = document.getElementById('fieldPhone').value.trim();
    const age = document.getElementById('fieldAge').value.trim();
    const address = document.getElementById('fieldAddress').value.trim();

    const setError = (field, msg) => {
        document.getElementById('err' + field).textContent = msg;
        document.getElementById('field' + field).classList.add('is-invalid');
        valid = false;
    };

    if (name.length < 2) setError('Name', 'Name must be at least 2 characters.');
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) setError('Email', 'Please enter a valid email address.');
    if (!/^[0-9+\-\s()]{7,20}$/.test(phone)) setError('Phone', 'Please enter a valid phone number.');
    if (!age || age < 1 || age > 120) setError('Age', 'Age must be between 1 and 120.');
    if (address.length < 1) setError('Address', 'Address is required.');

    return valid;
}

// ---------- Submit (Create or Update) ----------
document.getElementById('recordForm').addEventListener('submit', async (e) => {
    e.preventDefault();

    if (!validateClientSide()) return;

    const id = document.getElementById('recordId').value;
    const isEdit = !!id;

    const formData = new FormData();
    formData.append('action', isEdit ? 'update' : 'create');
    if (isEdit) formData.append('id', id);
    formData.append('name', document.getElementById('fieldName').value.trim());
    formData.append('email', document.getElementById('fieldEmail').value.trim());
    formData.append('phone', document.getElementById('fieldPhone').value.trim());
    formData.append('age', document.getElementById('fieldAge').value.trim());
    formData.append('address', document.getElementById('fieldAddress').value.trim());

    const btn = document.getElementById('btnSaveRecord');
    btn.disabled = true;

    try {
        const res = await fetch(API_URL, { method: 'POST', body: formData });
        const data = await res.json();

        if (!data.success) {
            if (data.errors) {
                Object.keys(data.errors).forEach(field => {
                    const cap = field.charAt(0).toUpperCase() + field.slice(1);
                    const errEl = document.getElementById('err' + cap);
                    const fieldEl = document.getElementById('field' + cap);
                    if (errEl && fieldEl) {
                        errEl.textContent = data.errors[field];
                        fieldEl.classList.add('is-invalid');
                    }
                });
            }
            showToast(data.message, true);
        } else {
            showToast(data.message);
            recordModal.hide();
            resetForm();
            loadRecords();
        }
    } catch (err) {
        showToast('Something went wrong. Please try again.', true);
    } finally {
        btn.disabled = false;
    }
});

// ---------- Export / Download ----------
document.getElementById('btnDownload').addEventListener('click', () => {
    const csv = document.getElementById('exportCsv').checked;
    const pdf = document.getElementById('exportPdf').checked;
    const errorBox = document.getElementById('exportError');

    if (!csv && !pdf) {
        errorBox.classList.remove('d-none');
        return;
    }
    errorBox.classList.add('d-none');

    // Trigger downloads via temporary links (works for one or both formats)
    if (csv) triggerDownload('export.php?type=csv');
    if (pdf) setTimeout(() => triggerDownload('export.php?type=pdf'), csv ? 400 : 0);
});

function triggerDownload(url) {
    const link = document.createElement('a');
    link.href = url;
    link.style.display = 'none';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// ---------- Initial load ----------
document.addEventListener('DOMContentLoaded', loadRecords);
</script>
</body>
</html>
