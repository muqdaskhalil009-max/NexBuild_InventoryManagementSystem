
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>StockFlow | Inventory Management</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

<div id="loginScreen" class="login-screen">
    <div class="login-card">
        <div class="brand-logo">S</div>
        <h1>StockFlow</h1>
        <p>Inventory Management System</p>

        <form id="loginForm">
            <label>Email Address</label>
            <input type="email" id="loginEmail" placeholder="Enter your email" value="admin@example.com" required>

            <label>Password</label>
            <input type="password" id="loginPassword" placeholder="Enter your password" required>

            <button type="submit" class="primary-btn">Sign In</button>
            <p id="loginError" class="error-message"></p>
        </form>
    </div>
</div>

<div class="app-layout" id="appLayout" style="display:none;">

    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <div class="brand-logo">S</div>
            <div>
                <h2>StockFlow</h2>
                <span>Inventory Manager</span>
            </div>
        </div>

        <div class="sidebar-label">MAIN MENU</div>

        <nav class="sidebar-nav">
            <a href="#" class="nav-link active" data-page="dashboard">
                <span>▦</span> Dashboard
            </a>
            <a href="#" class="nav-link" data-page="products">
                <span>□</span> Products
            </a>
            <a href="#" class="nav-link" data-page="suppliers">
                <span>♧</span> Suppliers
            </a>
            <a href="#" class="nav-link" data-page="customers">
                <span>♙</span> Customers
            </a>
            <a href="#" class="nav-link" data-page="stock-movements">
                <span>⇄</span> Stock Movements
            </a>
            <a href="#" class="nav-link" data-page="orders">
                <span>▤</span> Orders
            </a>
            <a href="#" class="nav-link" data-page="reports">
                <span>▥</span> Reports
            </a>
        </nav>

        <div class="sidebar-bottom">
            <div class="sidebar-user">
                <div class="avatar" id="avatar">A</div>
                <div>
                    <strong id="sidebarUserName">Administrator</strong>
                    <span>Admin Account</span>
                </div>
            </div>
            <button id="logoutBtn" class="logout-btn">↪ Sign Out</button>
        </div>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div class="topbar-left">
                <button class="menu-toggle" id="menuToggle">☰</button>
                <div>
                    <h2 id="pageTitle">Dashboard</h2>
                    <p id="pageSubtitle">Welcome back! Here's your business overview.</p>
                </div>
            </div>

            <div class="topbar-right">
                <span class="status-indicator"></span>
                <span class="admin-label" id="topbarUserName">Administrator</span>
                <div class="avatar" id="topbarAvatar">A</div>
            </div>
        </header>

        <section class="content-area">

            <div id="messageBox" class="message-box"></div>

            <section id="dashboardPage" class="page-section">

                <div class="welcome-banner">
                    <div>
                        <span class="welcome-tag">OVERVIEW</span>
                        <h1>Welcome to StockFlow!</h1>
                        <p>Manage your inventory, track stock and monitor your business.</p>
                    </div>
                    <div class="welcome-icon">📦</div>
                </div>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon purple">▣</div>
                        <div>
                            <p>Total Products</p>
                            <h2 id="totalProducts">--</h2>
                            <span>Products in inventory</span>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon blue">♧</div>
                        <div>
                            <p>Total Suppliers</p>
                            <h2 id="totalSuppliers">--</h2>
                            <span>Registered suppliers</span>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon green">♙</div>
                        <div>
                            <p>Total Customers</p>
                            <h2 id="totalCustomers">--</h2>
                            <span>Registered customers</span>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon orange">▤</div>
                        <div>
                            <p>Total Orders</p>
                            <h2 id="totalOrders">--</h2>
                            <span>Recorded orders</span>
                        </div>
                    </div>
                </div>

                <div class="dashboard-grid">
                    <div class="panel">
                        <div class="panel-heading">
                            <div>
                                <h3>Recent Products</h3>
                                <p>Latest products in your inventory</p>
                            </div>
                            <button class="text-btn" data-goto="products">View All</button>
                        </div>

                        <div class="table-wrapper">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>SKU</th>
                                        <th>Price</th>
                                        <th>Quantity</th>
                                    </tr>
                                </thead>
                                <tbody id="recentProducts">
                                    <tr><td colspan="4">Loading products...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="panel">
                        <div class="panel-heading">
                            <div>
                                <h3>Quick Actions</h3>
                                <p>Common inventory operations</p>
                            </div>
                        </div>

                        <div class="quick-actions">
                            <button data-goto="products">＋ Add Product</button>
                            <button data-goto="customers">＋ Add Customer</button>
                            <button data-goto="suppliers">＋ Add Supplier</button>
                            <button data-goto="stock-movements">⇄ Stock Movement</button>
                            <button data-goto="orders">＋ Create Order</button>
                        </div>
                    </div>
                </div>

                <div class="panel low-stock-panel">
                    <div class="panel-heading">
                        <div>
                            <h3>Low Stock Alerts</h3>
                            <p>Products that may need restocking</p>
                        </div>
                    </div>
                    <div id="lowStockList" class="low-stock-list">
                        Loading stock information...
                    </div>
                </div>
            </section>

            <section id="modulePage" class="page-section" style="display:none;">
                <div class="panel">
                    <div class="module-toolbar">
                        <div>
                            <h3 id="moduleHeading">Products</h3>
                            <p id="moduleDescription">Manage your records</p>
                        </div>
                        <button class="primary-btn" id="addRecordBtn">＋ Add New</button>
                    </div>

                    <div class="table-controls">
                        <input type="search" id="searchInput" placeholder="Search records...">
                    </div>

                    <div class="table-wrapper">
                        <table>
                            <thead id="moduleTableHead"></thead>
                            <tbody id="moduleTableBody">
                                <tr><td>Loading records...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

        </section>
    </main>
</div>

<div class="modal-overlay" id="recordModal">
    <div class="modal-card">
        <div class="modal-heading">
            <div>
                <h2 id="modalTitle">Add Record</h2>
                <p>Enter the required information below.</p>
            </div>
            <button class="modal-close" id="closeModal">×</button>
        </div>

        <form id="recordForm">
            <div id="modalFields" class="modal-fields"></div>
            <div class="modal-actions">
                <button type="button" class="secondary-btn" id="cancelModal">Cancel</button>
                <button type="submit" class="primary-btn" id="saveRecordBtn">Save Record</button>
            </div>
        </form>
    </div>
</div>

</body>
</html>
