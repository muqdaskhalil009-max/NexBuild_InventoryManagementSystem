
const API_URL = '/api';

let token = localStorage.getItem('inventory_token');
let currentPage = 'dashboard';
let currentRecords = [];
let editingId = null;
let currentReportView = 'inventory';

const $ = (id) => document.getElementById(id);

const pageConfig = {
    products: {
        title: 'Products',
        subtitle: 'Manage your inventory products',
        endpoint: 'products',
        fields: [
            { name: 'name', label: 'Product Name', required: true },
            { name: 'sku', label: 'SKU', required: true },
            { name: 'purchase_price', label: 'Purchase Price', type: 'number', required: true },
            { name: 'selling_price', label: 'Selling Price', type: 'number', required: true },
            { name: 'quantity', label: 'Quantity', type: 'number', required: true },
            { name: 'minimum_stock', label: 'Minimum Stock', type: 'number' },
            { name: 'description', label: 'Description', type: 'textarea', full: true }
        ]
    },

    suppliers: {
        title: 'Suppliers',
        subtitle: 'Manage your business suppliers',
        endpoint: 'suppliers',
        fields: [
            { name: 'name', label: 'Supplier Name', required: true },
            { name: 'email', label: 'Email', type: 'email' },
            { name: 'phone', label: 'Phone' },
            { name: 'address', label: 'Address', type: 'textarea', full: true }
        ]
    },

    customers: {
        title: 'Customers',
        subtitle: 'Manage your registered customers',
        endpoint: 'customers',
        fields: [
            { name: 'name', label: 'Customer Name', required: true },
            { name: 'email', label: 'Email', type: 'email' },
            { name: 'phone', label: 'Phone' },
            { name: 'address', label: 'Address', type: 'textarea', full: true }
        ]
    },

    'stock-movements': {
        title: 'Stock Movements',
        subtitle: 'Track incoming and outgoing stock',
        endpoint: 'stock-movements',
        readOnly: true,
        fields: [
            { name: 'product_id', label: 'Product ID', type: 'number', required: true },
            {
                name: 'type',
                label: 'Movement Type',
                type: 'select',
                options: [
                    { value: 'in', label: 'Stock In' },
                    { value: 'out', label: 'Stock Out' }
                ],
                required: true
            },
            { name: 'quantity', label: 'Quantity', type: 'number', required: true },
            { name: 'reason', label: 'Reason', required: true }
        ]
    },

    orders: {
        title: 'Orders',
        subtitle: 'View and create customer orders',
        endpoint: 'orders',
        readOnly: true,
        fields: [
            { name: 'customer_id', label: 'Customer ID', type: 'number', required: true },
            { name: 'product_id', label: 'Product ID', type: 'number', required: true },
            { name: 'quantity', label: 'Quantity', type: 'number', required: true }
        ]
    },

    reports: {
        title: 'Reports',
        subtitle: 'Business and inventory reports',
        endpoint: 'reports/inventory',
        readOnly: true,
        fields: []
    }
};

// API REQUEST
async function apiRequest(endpoint, options = {}) {
    const headers = {
        Accept: 'application/json',
        ...(options.body ? { 'Content-Type': 'application/json' } : {}),
        ...(token ? { Authorization: `Bearer ${token}` } : {}),
        ...(options.headers || {})
    };

    const response = await fetch(`${API_URL}/${endpoint}`, {
        ...options,
        headers
    });

    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
        const message =
            data.message ||
            Object.values(data.errors || {}).flat().join(', ') ||
            'Something went wrong. Please try again.';

        throw new Error(message);
    }

    return data;
}

// GET RECORDS
function getRecords(data) {
    if (Array.isArray(data)) return data;
    if (Array.isArray(data.data)) return data.data;
    if (Array.isArray(data.data?.data)) return data.data.data;
    if (Array.isArray(data.products)) return data.products;
    if (Array.isArray(data.orders)) return data.orders;
    if (Array.isArray(data.customers)) return data.customers;
    if (Array.isArray(data.suppliers)) return data.suppliers;
    if (Array.isArray(data.movements)) return data.movements;
    if (Array.isArray(data.stock_movements)) return data.stock_movements;

    return [];
}

// MESSAGE
function showMessage(message, type = 'success') {
    const box = $('messageBox');
    if (!box) return;

    box.textContent = message;
    box.className = `message-box ${type}`;

    setTimeout(() => {
        box.className = 'message-box';
        box.textContent = '';
    }, 4500);
}

// LOGIN SCREEN
function showLogin() {
    $('loginScreen').style.display = 'flex';
    $('appLayout').style.display = 'none';
}

function showApp() {
    $('loginScreen').style.display = 'none';
    $('appLayout').style.display = 'block';
}

// LOGIN
async function login(event) {
    event.preventDefault();

    const button = $('loginForm').querySelector('button');
    const errorBox = $('loginError');

    button.disabled = true;
    button.textContent = 'Signing in...';
    errorBox.textContent = '';

    try {
        const data = await apiRequest('login', {
            method: 'POST',
            body: JSON.stringify({
                email: $('loginEmail').value.trim(),
                password: $('loginPassword').value
            })
        });

        token = data.token || data.access_token;

        if (!token) {
            throw new Error('Authentication token was not returned.');
        }

        localStorage.setItem('inventory_token', token);

        await initializeDashboard();

    } catch (error) {
        errorBox.textContent = error.message;
    } finally {
        button.disabled = false;
        button.textContent = 'Sign In';
    }
}

// LOGOUT
async function logout() {
    try {
        if (token) {
            await apiRequest('logout', { method: 'POST' });
        }
    } catch (error) {
        console.error(error);
    }

    localStorage.removeItem('inventory_token');
    token = null;

    showLogin();
}

// INITIALIZE DASHBOARD
async function initializeDashboard() {
    try {
        const response = await apiRequest('me');
        const user = response.user || response;

        const name = user.name || 'Administrator';
        const initial = name.charAt(0).toUpperCase();

        $('sidebarUserName').textContent = name;
        $('topbarUserName').textContent = name;
        $('avatar').textContent = initial;
        $('topbarAvatar').textContent = initial;

        showApp();

        await loadDashboard();

    } catch (error) {
        console.error(error);

        localStorage.removeItem('inventory_token');
        token = null;

        showLogin();
        $('loginError').textContent =
            'Session expired. Please sign in again.';
    }
}

// DASHBOARD DATA
async function loadDashboard() {
    try {
        const data = await apiRequest('dashboard');

        const stats = data.statistics || data.data?.statistics || data.data || data;

        $('totalProducts').textContent =
            stats.total_products ?? '--';

        $('totalSuppliers').textContent =
            stats.total_suppliers ?? '--';

        $('totalCustomers').textContent =
            stats.total_customers ?? '--';

        $('totalOrders').textContent =
            stats.total_orders ?? '--';

    } catch (error) {
        console.error('Dashboard statistics:', error);
    }

    try {
        const data = await apiRequest('products');
        const products = getRecords(data);

        $('recentProducts').innerHTML = products.slice(0, 5).map(product => `
            <tr>
                <td><strong>${escapeHTML(product.name || '-')}</strong></td>
                <td>${escapeHTML(product.sku || '-')}</td>
                <td>${formatCurrency(product.selling_price)}</td>
                <td>${product.quantity ?? 0}</td>
            </tr>
        `).join('') || '<tr><td colspan="4">No products found.</td></tr>';

        const lowStock = products.filter(product =>
            Number(product.quantity || 0) <=
            Number(product.minimum_stock ?? product.reorder_level ?? 5)
        );

        $('lowStockList').innerHTML = lowStock.length
            ? lowStock.map(product => `
                <div class="low-stock-item">
                    <strong>${escapeHTML(product.name || 'Product')}</strong>
                    <span class="stock-warning">
                        ${product.quantity ?? 0} left
                    </span>
                </div>
            `).join('')
            : 'All products have sufficient stock.';

    } catch (error) {
        $('recentProducts').innerHTML =
            '<tr><td colspan="4">Unable to load products.</td></tr>';

        $('lowStockList').textContent =
            'Unable to load stock information.';
    }
}

// HELPERS
function escapeHTML(value) {
    return String(value ?? '').replace(/[&<>"']/g, char => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    })[char]);
}

function formatCurrency(value) {
    return 'Rs. ' + Number(value || 0).toLocaleString('en-PK');
}

// NAVIGATION
function navigate(page) {
    if (page !== 'dashboard' && !pageConfig[page]) return;

    currentPage = page;

    document.querySelectorAll('.nav-link').forEach(link => {
        link.classList.toggle('active', link.dataset.page === page);
    });

    document.querySelectorAll('.page-section').forEach(section => {
        section.style.display = 'none';
    });

    if (page === 'dashboard') {
        $('dashboardPage').style.display = 'block';
        $('pageTitle').textContent = 'Dashboard';
        $('pageSubtitle').textContent =
            "Welcome back! Here's your business overview.";

        loadDashboard();

    } else {
        $('modulePage').style.display = 'block';
        loadModule(page);
    }

    $('sidebar').classList.remove('open');
}

// LOAD MODULE
async function loadModule(page) {
    const config = pageConfig[page];
    if (!config) return;

    $('pageTitle').textContent = config.title;
    $('pageSubtitle').textContent = config.subtitle;
    $('moduleHeading').textContent = config.title;

    $('moduleDescription').textContent = config.subtitle;

    $('addRecordBtn').style.display =
        config.readOnly ? 'none' : 'inline-block';

    if (page === 'orders' || page === 'stock-movements') {
        $('addRecordBtn').style.display = 'inline-block';
    }

    $('moduleTableBody').innerHTML =
        '<tr><td>Loading records...</td></tr>';

    if (page === 'reports') {
        currentReportView = 'inventory';
        await loadReports();
        return;
    }

    try {
        const data = await apiRequest(config.endpoint);

        currentRecords = getRecords(data);

        renderModuleTable(page, currentRecords);

    } catch (error) {
        $('moduleTableBody').innerHTML =
            `<tr><td>${escapeHTML(error.message)}</td></tr>`;
    }
}

// REPORTS
async function loadReports() {
    const endpoints = {
        inventory: 'reports/inventory',
        movements: 'reports/stock-movements',
        orders: 'reports/orders'
    };

    const titles = {
        inventory: 'Inventory Report',
        movements: 'Stock Movement Report',
        orders: 'Orders Report'
    };

    $('moduleHeading').textContent = titles[currentReportView];

    $('moduleDescription').innerHTML = `
        <div class="report-tabs">
            <button type="button" class="action-btn" data-report-view="inventory">
                Inventory
            </button>
            <button type="button" class="action-btn" data-report-view="movements">
                Stock Movements
            </button>
            <button type="button" class="action-btn" data-report-view="orders">
                Orders
            </button>
        </div>
        <p>View and review your business reports.</p>
    `;

    $('addRecordBtn').style.display = 'none';

    $('moduleTableBody').innerHTML =
        '<tr><td>Loading report...</td></tr>';

    try {
        const data = await apiRequest(endpoints[currentReportView]);

        let records = [];
        let columns = [];
        let summary = {};

        if (currentReportView === 'inventory') {
            records = data.products || [];
            summary = data.summary || {};

            columns = [
                ['name', 'Product'],
                ['sku', 'SKU'],
                ['quantity', 'Quantity'],
                ['minimum_stock', 'Minimum Stock'],
                ['purchase_price', 'Purchase Price'],
                ['selling_price', 'Selling Price']
            ];

        } else if (currentReportView === 'movements') {
            records = data.movements || [];

            columns = [
                ['product_id', 'Product ID'],
                ['type', 'Movement Type'],
                ['quantity', 'Quantity'],
                ['reason', 'Reason'],
                ['created_at', 'Date']
            ];

        } else {
            records = data.orders || [];
            summary = data.summary || {};

            columns = [
                ['order_number', 'Order Number'],
                ['customer_id', 'Customer ID'],
                ['status', 'Status'],
                ['total_amount', 'Total Amount'],
                ['created_at', 'Date']
            ];
        }

        currentRecords = records;

        renderReportSummary(summary);
        renderReportTable(columns, records);

    } catch (error) {
        $('moduleTableBody').innerHTML =
            `<tr><td>${escapeHTML(error.message)}</td></tr>`;
    }
}

function renderReportSummary(summary) {
    const entries = Object.entries(summary);

    if (!entries.length) return;

    const summaryHTML = `
        <div class="report-summary">
            ${entries.map(([key, value]) => `
                <div class="report-summary-item">
                    <strong>${escapeHTML(
                        key.replaceAll('_', ' ').replace(/\b\w/g, c => c.toUpperCase())
                    )}</strong>
                    <span>${typeof value === 'number' &&
                        key.includes('value')
                        ? formatCurrency(value)
                        : escapeHTML(value)}</span>
                </div>
            `).join('')}
        </div>
    `;

    $('moduleDescription').insertAdjacentHTML('beforeend', summaryHTML);
}

function renderReportTable(columns, records) {
    $('moduleTableHead').innerHTML = `
        <tr>
            ${columns.map(column => `<th>${column[1]}</th>`).join('')}
        </tr>
    `;

    $('moduleTableBody').innerHTML = records.length
        ? records.map(record => `
            <tr>
                ${columns.map(column => `
                    <td>${formatCell(column[0], record[column[0]])}</td>
                `).join('')}
            </tr>
        `).join('')
        : `<tr><td colspan="${columns.length}">No records found.</td></tr>`;
}

// NORMAL MODULE TABLE
function renderModuleTable(page, records) {
    const config = pageConfig[page];

    let columns = [];

    if (page === 'products') {
        columns = [
            ['name', 'Product Name'],
            ['sku', 'SKU'],
            ['purchase_price', 'Purchase Price'],
            ['selling_price', 'Selling Price'],
            ['quantity', 'Quantity']
        ];

    } else if (page === 'suppliers' || page === 'customers') {
        columns = [
            ['name', 'Name'],
            ['email', 'Email'],
            ['phone', 'Phone'],
            ['address', 'Address']
        ];

    } else if (page === 'stock-movements') {
        columns = [
            ['product_id', 'Product ID'],
            ['type', 'Type'],
            ['quantity', 'Quantity'],
            ['reason', 'Reason'],
            ['created_at', 'Date']
        ];

    } else if (page === 'orders') {
        columns = [
            ['order_number', 'Order Number'],
            ['customer_id', 'Customer ID'],
            ['status', 'Status'],
            ['total_amount', 'Total Amount'],
            ['created_at', 'Date']
        ];
    }

    $('moduleTableHead').innerHTML = `
        <tr>
            ${columns.map(column => `<th>${column[1]}</th>`).join('')}
            ${!config.readOnly ? '<th>Actions</th>' : ''}
        </tr>
    `;

    $('moduleTableBody').innerHTML = records.length
        ? records.map(record => `
            <tr>
                ${columns.map(column => `
                    <td>${formatCell(column[0], record[column[0]])}</td>
                `).join('')}

                ${!config.readOnly ? `
                    <td>
                        <button class="action-btn edit-btn" data-edit="${record.id}">
                            Edit
                        </button>
                        <button class="action-btn delete-btn" data-delete="${record.id}">
                            Delete
                        </button>
                    </td>
                ` : ''}
            </tr>
        `).join('')
        : `<tr><td colspan="${columns.length + 1}">No records found.</td></tr>`;
}

// FORMAT TABLE CELLS
function formatCell(key, value) {
    if (key.includes('price') || key === 'total_amount') {
        return formatCurrency(value);
    }

    if (key === 'created_at' && value) {
        return escapeHTML(new Date(value).toLocaleDateString());
    }

    return escapeHTML(value ?? '-');
}

// ADD / EDIT MODAL
function openModal(record = null) {
    const config = pageConfig[currentPage];
    if (!config) return;

    editingId = record?.id || null;

    $('modalTitle').textContent = editingId
        ? `Edit ${config.title}`
        : `Add ${config.title}`;

    $('modalFields').innerHTML = config.fields.map(field => {
        const value = record?.[field.name] ?? '';
        const required = field.required ? 'required' : '';
        const full = field.full ? 'full-width' : '';

        let control = '';

        if (field.type === 'select') {
            control = `
                <select name="${field.name}" ${required}>
                    ${field.options.map(option => `
                        <option value="${option.value}"
                            ${value === option.value ? 'selected' : ''}>
                            ${option.label}
                        </option>
                    `).join('')}
                </select>
            `;

        } else if (field.type === 'textarea') {
            control = `
                <textarea name="${field.name}" rows="3">${escapeHTML(value)}</textarea>
            `;

        } else {
            control = `
                <input
                    type="${field.type || 'text'}"
                    name="${field.name}"
                    value="${escapeHTML(value)}"
                    ${field.type === 'number' ? 'min="0"' : ''}
                    ${required}
                >
            `;
        }

        return `
            <div class="form-group ${full}">
                <label>${field.label}</label>
                ${control}
            </div>
        `;
    }).join('');

    $('recordModal').classList.add('show');
}

function closeModal() {
    $('recordModal').classList.remove('show');
    $('recordForm').reset();
    editingId = null;
}

// SAVE RECORD
async function saveRecord(event) {
    event.preventDefault();

    const config = pageConfig[currentPage];
    if (!config) return;

    const formData = new FormData($('recordForm'));
    const record = Object.fromEntries(formData.entries());

    for (const field of config.fields) {
        if (field.type === 'number') {
            record[field.name] = Number(record[field.name] || 0);
        }
    }

    const button = $('saveRecordBtn');
    button.disabled = true;
    button.textContent = 'Saving...';

    try {
        let endpoint = config.endpoint;
        let method = 'POST';

        if (currentPage === 'orders') {
            record.items = [{
                product_id: Number(record.product_id),
                quantity: Number(record.quantity)
            }];

            delete record.product_id;
            delete record.quantity;
        }

        if (editingId) {
            endpoint += `/${editingId}`;
            method = 'PUT';
        }

        await apiRequest(endpoint, {
            method,
            body: JSON.stringify(record)
        });

        closeModal();

        showMessage(
            editingId
                ? 'Record updated successfully.'
                : 'Record created successfully.'
        );

        await loadModule(currentPage);
        await loadDashboard();

    } catch (error) {
        showMessage(error.message, 'error');

    } finally {
        button.disabled = false;
        button.textContent = 'Save Record';
    }
}

// DELETE RECORD
async function deleteRecord(id) {
    if (!confirm('Are you sure you want to delete this record?')) return;

    const config = pageConfig[currentPage];

    try {
        await apiRequest(`${config.endpoint}/${id}`, {
            method: 'DELETE'
        });

        showMessage('Record deleted successfully.');

        await loadModule(currentPage);
        await loadDashboard();

    } catch (error) {
        showMessage(error.message, 'error');
    }
}

// SEARCH
function filterRecords() {
    const search = $('searchInput').value.toLowerCase().trim();

    const filtered = currentRecords.filter(record =>
        Object.values(record).some(value =>
            String(value ?? '').toLowerCase().includes(search)
        )
    );

    if (currentPage === 'reports') {
        const columns = currentReportView === 'inventory'
            ? [
                ['name', 'Product'],
                ['sku', 'SKU'],
                ['quantity', 'Quantity'],
                ['minimum_stock', 'Minimum Stock'],
                ['purchase_price', 'Purchase Price'],
                ['selling_price', 'Selling Price']
            ]
            : currentReportView === 'movements'
                ? [
                    ['product_id', 'Product ID'],
                    ['type', 'Movement Type'],
                    ['quantity', 'Quantity'],
                    ['reason', 'Reason'],
                    ['created_at', 'Date']
                ]
                : [
                    ['order_number', 'Order Number'],
                    ['customer_id', 'Customer ID'],
                    ['status', 'Status'],
                    ['total_amount', 'Total Amount'],
                    ['created_at', 'Date']
                ];

        renderReportTable(columns, filtered);

    } else {
        renderModuleTable(currentPage, filtered);
    }
}

// EVENTS
function setupEvents() {
    $('loginForm').addEventListener('submit', login);
    $('logoutBtn').addEventListener('click', logout);
    $('recordForm').addEventListener('submit', saveRecord);

    $('closeModal').addEventListener('click', closeModal);
    $('cancelModal').addEventListener('click', closeModal);

    $('recordModal').addEventListener('click', event => {
        if (event.target === $('recordModal')) {
            closeModal();
        }
    });

    $('addRecordBtn').addEventListener('click', () => openModal());

    $('searchInput').addEventListener('input', filterRecords);

    $('menuToggle').addEventListener('click', () => {
        $('sidebar').classList.toggle('open');
    });

    document.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', event => {
            event.preventDefault();
            navigate(link.dataset.page);
        });
    });

    document.querySelectorAll('[data-goto]').forEach(button => {
        button.addEventListener('click', () => {
            navigate(button.dataset.goto);
        });
    });

    $('moduleDescription').addEventListener('click', event => {
        const button = event.target.closest('[data-report-view]');

        if (button) {
            currentReportView = button.dataset.reportView;
            $('searchInput').value = '';
            loadReports();
        }
    });

    $('moduleTableBody').addEventListener('click', event => {
        const editButton = event.target.closest('[data-edit]');
        const deleteButton = event.target.closest('[data-delete]');

        if (editButton) {
            const record = currentRecords.find(item =>
                String(item.id) === editButton.dataset.edit
            );

            if (record) openModal(record);
        }

        if (deleteButton) {
            deleteRecord(deleteButton.dataset.delete);
        }
    });
}

// START APPLICATION
document.addEventListener('DOMContentLoaded', async () => {
    setupEvents();

    if (token) {
        await initializeDashboard();
    } else {
        showLogin();
    }
});
