<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-white sidebar">
    <!-- Mobile Only: Header -->
    <div class="d-flex justify-content-between align-items-center px-3 d-md-none border-bottom mb-2">
        <span class="fs-5 fw-semibold">Menu</span>
        <button type="button" class="btn-close" id="dismissSidebar" aria-label="Close"></button>
    </div>

    <div class="position-sticky">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'index.php' ? 'active' : ''; ?>" href="index.php">
                    <i class="bi bi-speedometer2 me-2"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'general-ledger.php' ? 'active' : ''; ?>" href="general-ledger.php">
                    <i class="bi bi-journal-text me-2"></i> General Ledger
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'cash-disbursement.php' ? 'active' : ''; ?>" href="cash-disbursement.php">
                    <i class="bi bi-cash-stack me-2"></i> Cash Disbursement
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'chart-of-accounts.php' ? 'active' : ''; ?>" href="chart-of-accounts.php">
                    <i class="bi bi-list-check me-2"></i> Chart of Accounts
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'trial-balance.php' ? 'active' : ''; ?>" href="trial-balance.php">
                    <i class="bi bi-calculator me-2"></i> Trial Balance
                </a>
            </li>

            <!-- Reports Submenu -->
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="collapse" href="#reportsSubmenu" role="button" 
                   aria-expanded="<?php echo (strpos($current_page, 'report-') === 0) ? 'true' : 'false'; ?>" 
                   aria-controls="reportsSubmenu">
                    <i class="bi bi-file-earmark-text me-2"></i> Reports
                    <i class="bi bi-chevron-down float-end"></i>
                </a>
                <div class="collapse <?php echo (strpos($current_page, 'report-') === 0) ? 'show' : ''; ?>" id="reportsSubmenu">
                    <ul class="nav flex-column ms-3">
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page == 'report-income-statement.php' ? 'active' : ''; ?>" 
                               href="report-income-statement.php">
                                <i class="bi bi-dash"></i> Income Statement
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page == 'report-balance-sheet.php' ? 'active' : ''; ?>" 
                               href="report-balance-sheet.php">
                                <i class="bi bi-dash"></i> Balance Sheet
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page == 'report-cash-flow.php' ? 'active' : ''; ?>" 
                               href="report-cash-flow.php">
                                <i class="bi bi-dash"></i> Cash Flow Statement
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'forecast.php' ? 'active' : ''; ?>" href="forecast.php">
                    <i class="bi bi-graph-up me-2"></i> Budget Forecast
                </a>
            </li>

            <!-- Transactions Submenu -->
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="collapse" href="#transactionsSubmenu" role="button" 
                   aria-expanded="<?php echo (strpos($current_page, 'transactions-') === 0) ? 'true' : 'false'; ?>" 
                   aria-controls="transactionsSubmenu">
                    <i class="bi bi-currency-exchange me-2"></i> Transactions
                    <i class="bi bi-chevron-down float-end"></i>
                </a>
                <div class="collapse <?php echo (strpos($current_page, 'transactions-') === 0) ? 'show' : ''; ?>" id="transactionsSubmenu">
                    <ul class="nav flex-column ms-3">
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page == 'transactions-list.php' ? 'active' : ''; ?>" 
                               href="transactions-list.php">
                                <i class="bi bi-dash"></i> View All
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page == 'student-payments.php' ? 'active' : ''; ?>" 
                               href="student-payments.php">
                                <i class="bi bi-dash"></i> Student Payments
                            </a>
                        </li>
                    </ul>
                </div>
            </li>
        </ul>

        <!-- Admin Section -->
        <?php if (isAdmin($_SESSION['user_id'])): ?>
        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
            <span>Administration</span>
        </h6>
        <ul class="nav flex-column mb-2">
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'user-control.php' ? 'active' : ''; ?>" href="user-control.php">
                    <i class="bi bi-people me-2"></i> User Control
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'system-logs.php' ? 'active' : ''; ?>" href="view-user-logs.php">
                    <i class="bi bi-clock-history me-2"></i> System Logs
                </a>
            </li>
        </ul>
        <?php endif; ?>
    </div>
</nav>

<style>
/* Base Sidebar Styles */
.sidebar {
    height: 100vh;
    border-right: 1px solid rgba(0, 0, 0, .1);
}

.sidebar .nav-link {
    color: #333;
    font-weight: 500;
    padding: 0.5rem 1rem;
}

.sidebar .nav-link.active {
    color: #2470dc;
    background-color: rgba(36, 112, 220, 0.1);
    border-radius: 0.25rem;
}

.sidebar .nav-link:hover {
    background-color: rgba(0, 0, 0, 0.05);
    border-radius: 0.25rem;
}

/* Submenu Styles */
.sidebar .collapse .nav-link {
    padding-left: 2.5rem;
    font-size: 0.95rem;
}

.sidebar .nav-link[data-bs-toggle="collapse"] .bi-chevron-down {
    transition: transform 0.2s;
}

.sidebar .nav-link[aria-expanded="true"] .bi-chevron-down {
    transform: rotate(180deg);
}

/* Mobile Styles */
@media (max-width: 767.98px) {
    .sidebar {
        position: fixed;
        top: 0;
        left: -100%;
        width: 85%;
        max-width: 320px;
        height: 100vh;
        z-index: 1045;
        background-color: #fff;
        transition: all 0.3s ease-in-out;
    }

    .sidebar.show {
        left: 0;
    }

    .sidebar-backdrop {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 1040;
    }

    .sidebar-backdrop.show {
        display: block;
    }

    body.sidebar-open {
        overflow: hidden;
    }

    .position-sticky {
        height: calc(100vh - 49px); /* Subtract mobile header height */
        overflow-y: auto;
    }

    #dismissSidebar {
        padding: 0.5rem;
    }
}

/* Desktop Styles */
@media (min-width: 768px) {
    .sidebar {
        position: fixed;
        padding-top: 48px;
    }

    #dismissSidebar,
    .sidebar-backdrop {
        display: none !important;
    }

    .position-sticky {
        overflow-y: auto;
        height: calc(100vh - 48px);
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (window.innerWidth < 768) {
        // Create backdrop
        const backdrop = document.createElement('div');
        backdrop.className = 'sidebar-backdrop';
        document.body.appendChild(backdrop);

        const sidebar = document.getElementById('sidebar');
        const sidebarCollapse = document.getElementById('sidebarCollapse');
        const dismissSidebar = document.getElementById('dismissSidebar');

        function toggleSidebar() {
            sidebar.classList.toggle('show');
            backdrop.classList.toggle('show');
            document.body.classList.toggle('sidebar-open');
        }

        if (sidebarCollapse) {
            sidebarCollapse.addEventListener('click', toggleSidebar);
        }

        if (dismissSidebar) {
            dismissSidebar.addEventListener('click', toggleSidebar);
        }

        backdrop.addEventListener('click', toggleSidebar);

        window.addEventListener('resize', function() {
            if (window.innerWidth >= 768 && sidebar.classList.contains('show')) {
                toggleSidebar();
            }
        });
    }

    // Submenu icon rotation
    const submenuToggles = document.querySelectorAll('[data-bs-toggle="collapse"]');
    submenuToggles.forEach(toggle => {
        toggle.addEventListener('click', function() {
            const icon = this.querySelector('.bi-chevron-down');
            if (icon) {
                icon.style.transform = this.getAttribute('aria-expanded') === 'true' 
                    ? 'rotate(180deg)' 
                    : 'rotate(0deg)';
            }
        });
    });
});
</script>