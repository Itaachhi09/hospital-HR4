<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Avalon - HMO Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" xintegrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400..900&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Georgia', serif;
        }
        h1, h2, h3, h4, h5, h6, .font-header {
            font-family: 'Cinzel', serif;
        }
        .sidebar-collapsed { width: 85px; }
        .sidebar-expanded { width: 320px; }
        .sidebar-collapsed .menu-name span,
        .sidebar-collapsed .menu-name .arrow,
        .sidebar-collapsed .sidebar-logo-name { display: none; }
        .sidebar-collapsed .menu-name i.menu-icon { margin-right: 0; }
        .sidebar-collapsed .menu-drop { display: none; }
        .sidebar-overlay { background-color: rgba(0, 0, 0, 0.5); position: fixed; inset: 0; z-index: 40; display: none; }
        .sidebar-overlay.active { display: block; }
        .close-sidebar-btn { display: none; }
        @media (max-width: 968px) {
            .sidebar { position: fixed; left: -100%; transition: left 0.3s ease-in-out; z-index: 50; }
            .sidebar.mobile-active { left: 0; }
            .main { margin-left: 0 !important; }
            .close-sidebar-btn { display: block; }
            .sidebar.mobile-active .sidebar-logo-name { display: block; }
        }
        .menu-name { position: relative; overflow: hidden; }
        .menu-name::after { content: ''; position: absolute; left: 0; bottom: 0; height: 2px; width: 0; background-color: #4E3B2A; transition: width 0.3s ease; }
        .menu-name:hover::after { width: 100%; }
        #main-content-area, #main-content-area p, #main-content-area label, #main-content-area span, #main-content-area td, #main-content-area button, #main-content-area select, #main-content-area input, #main-content-area textarea {
             font-family: 'Georgia', serif;
        }
        #main-content-area h3, #main-content-area h4, #main-content-area h5 {
             font-family: 'Cinzel', serif;
         }
         #page-title {
            font-family: 'Cinzel', serif;
         }
        .notification-item:hover {
            background-color: #f7fafc;
        }
        .notification-dot {
            position: absolute;
            top: -2px;
            right: -2px;
            height: 8px;
            width: 8px;
            background-color: #ef4444;
            border-radius: 9999px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.6rem;
            color: white;
        }
        .modal {
            transition: opacity 0.25s ease;
        }
        .modal-content {
            transition: transform 0.25s ease;
        }
    </style>
    <script>
        window.DESIGNATED_ROLE = 'System Admin';
        window.DESIGNATED_DEFAULT_SECTION = 'hmoManagement';
    </script>
    <script src="js/main.js" type="module" defer></script>
    <script src="js/admin/hmo_management_enhanced.js" type="module" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-sky-100">

    <div id="app-container" class="flex min-h-screen w-full">
        <div class="sidebar-overlay" id="sidebar-overlay"></div>

        <div class="sidebar sidebar-expanded fixed z-50 overflow-y-auto h-screen bg-white border-r border-[#F7E6CA] flex flex-col transition-width duration-300 ease-in-out">
            <div class="h-16 border-b border-[#F7E6CA] flex items-center justify-between px-4 space-x-2 sticky top-0 bg-white z-10 flex-shrink-0">
                <div class="flex items-center space-x-2 overflow-hidden">
                    <img src="logo.png" alt="HR System Logo" class="h-10 w-auto flex-shrink-0">
                    <img src="logo-name.png" alt="Avalon Logo Name" class="h-6 w-auto sidebar-logo-name">
                </div>
                <i id="close-sidebar-btn" class="fa-solid fa-xmark close-sidebar-btn font-bold text-xl cursor-pointer text-[#4E3B2A] hover:text-red-500 flex-shrink-0"></i>
            </div>

            <div class="side-menu px-4 py-6 flex-grow overflow-y-auto">
                <ul class="space-y-2">
                    <li class="menu-option">
                        <a href="admin_landing.php" class="menu-name flex justify-between items-center space-x-3 hover:bg-[#F7E6CA] px-4 py-3 rounded-lg transition duration-300 ease-in-out cursor-pointer">
                            <div class="flex items-center space-x-2">
                                <i class="fa-solid fa-tachometer-alt text-lg pr-4 menu-icon"></i>
                                <span class="text-sm font-medium">Dashboard</span>
                            </div>
                        </a>
                    </li>

                    <li class="menu-option">
                        <div class="menu-name flex justify-between items-center space-x-3 hover:bg-[#F7E6CA] px-4 py-3 rounded-lg transition duration-300 ease-in-out cursor-pointer" onclick="toggleDropdown('core-hr-dropdown', this)">
                            <div class="flex items-center space-x-2">
                                <i class="fa-solid fa-users text-lg pr-4 menu-icon"></i>
                                <span class="text-sm font-medium">Core HR</span>
                            </div>
                            <div class="arrow"><i class="bx bx-chevron-right text-lg font-semibold arrow-icon"></i></div>
                        </div>
                        <div id="core-hr-dropdown" class="menu-drop hidden flex-col w-full bg-[#F7E6CA] rounded-lg p-3 space-y-1 mt-1">
                            <ul class="space-y-1">
                                <li><a href="#" id="employees-link" class="block px-3 py-1 text-sm text-gray-800 hover:bg-white rounded hover:text-[#4E3B2A]">Employees</a></li>
                                <li><a href="#" id="documents-link" class="block px-3 py-1 text-sm text-gray-800 hover:bg-white rounded hover:text-[#4E3B2A]">Documents</a></li>
                                <li><a href="#" id="org-structure-link" class="block px-3 py-1 text-sm text-gray-800 hover:bg-white rounded hover:text-[#4E3B2A]">Org Structure</a></li>
                            </ul>
                        </div>
                    </li>

                    <li class="menu-option">
                        <div class="menu-name flex justify-between items-center space-x-3 hover:bg-[#F7E6CA] px-4 py-3 rounded-lg transition duration-300 ease-in-out cursor-pointer" onclick="toggleDropdown('time-attendance-dropdown', this)">
                            <div class="flex items-center space-x-2">
                                <i class="fa-solid fa-clock text-lg pr-4 menu-icon"></i>
                                <span class="text-sm font-medium">Time & Attendance</span>
                            </div>
                            <div class="arrow"><i class="bx bx-chevron-right text-lg font-semibold arrow-icon"></i></div>
                        </div>
                        <div id="time-attendance-dropdown" class="menu-drop hidden flex-col w-full bg-[#F7E6CA] rounded-lg p-3 space-y-1 mt-1">
                            <ul class="space-y-1">
                                <li><a href="#" id="attendance-link" class="block px-3 py-1 text-sm text-gray-800 hover:bg-white rounded hover:text-[#4E3B2A]">Attendance Records</a></li>
                                <li><a href="#" id="timesheets-link" class="block px-3 py-1 text-sm text-gray-800 hover:bg-white rounded hover:text-[#4E3B2A]">Timesheets</a></li>
                                <li><a href="#" id="schedules-link" class="block px-3 py-1 text-sm text-gray-800 hover:bg-white rounded hover:text-[#4E3B2A]">Schedules</a></li>
                                <li><a href="#" id="shifts-link" class="block px-3 py-1 text-sm text-gray-800 hover:bg-white rounded hover:text-[#4E3B2A]">Shifts</a></li>
                            </ul>
                        </div>
                    </li>

                    <li class="menu-option">
                        <div class="menu-name flex justify-between items-center space-x-3 hover:bg-[#F7E6CA] px-4 py-3 rounded-lg transition duration-300 ease-in-out cursor-pointer" onclick="toggleDropdown('payroll-dropdown', this)">
                            <div class="flex items-center space-x-2">
                                <i class="fa-solid fa-money-check-dollar text-lg pr-4 menu-icon"></i>
                                <span class="text-sm font-medium">Payroll</span>
                            </div>
                            <div class="arrow"><i class="bx bx-chevron-right text-lg font-semibold arrow-icon"></i></div>
                        </div>
                        <div id="payroll-dropdown" class="menu-drop hidden flex-col w-full bg-[#F7E6CA] rounded-lg p-3 space-y-1 mt-1">
                            <ul class="space-y-1">
                                <li><a href="#" id="payroll-runs-link" class="block px-3 py-1 text-sm text-gray-800 hover:bg-white rounded hover:text-[#4E3B2A]">Payroll Runs</a></li>
                                <li><a href="#" id="salaries-link" class="block px-3 py-1 text-sm text-gray-800 hover:bg-white rounded hover:text-[#4E3B2A]">Salaries</a></li>
                                <li><a href="#" id="bonuses-link" class="block px-3 py-1 text-sm text-gray-800 hover:bg-white rounded hover:text-[#4E3B2A]">Bonuses</a></li>
                                <li><a href="#" id="deductions-link" class="block px-3 py-1 text-sm text-gray-800 hover:bg-white rounded hover:text-[#4E3B2A]">Deductions</a></li>
                                <li><a href="#" id="payslips-link" class="block px-3 py-1 text-sm text-gray-800 hover:bg-white rounded hover:text-[#4E3B2A]">View Payslips</a></li>
                            </ul>
                        </div>
                    </li>

                    <li class="menu-option">
                        <div class="menu-name flex justify-between items-center space-x-3 hover:bg-[#F7E6CA] px-4 py-3 rounded-lg transition duration-300 ease-in-out cursor-pointer" onclick="toggleDropdown('claims-dropdown', this)">
                            <div class="flex items-center space-x-2">
                                <i class="fa-solid fa-receipt text-lg pr-4 menu-icon"></i>
                                <span class="text-sm font-medium">Claims</span>
                            </div>
                            <div class="arrow"><i class="bx bx-chevron-right text-lg font-semibold arrow-icon"></i></div>
                        </div>
                        <div id="claims-dropdown" class="menu-drop hidden flex-col w-full bg-[#F7E6CA] rounded-lg p-3 space-y-1 mt-1">
                            <ul class="space-y-1">
                                <li><a href="#" id="submit-claim-link" class="block px-3 py-1 text-sm text-gray-800 hover:bg-white rounded hover:text-[#4E3B2A]">Submit Claim</a></li>
                                <li><a href="#" id="my-claims-link" class="block px-3 py-1 text-sm text-gray-800 hover:bg-white rounded hover:text-[#4E3B2A]">My Claims</a></li>
                                <li><a href="#" id="claims-approval-link" class="block px-3 py-1 text-sm text-gray-800 hover:bg-white rounded hover:text-[#4E3B2A]">Approvals</a></li>
                                <li><a href="#" id="claim-types-admin-link" class="block px-3 py-1 text-sm text-gray-800 hover:bg-white rounded hover:text-[#4E3B2A]">Claim Types (Admin)</a></li>
                            </ul>
                        </div>
                    </li>

                    <li class="menu-option">
                        <div class="menu-name flex justify-between items-center space-x-3 hover:bg-[#F7E6CA] px-4 py-3 rounded-lg transition duration-300 ease-in-out cursor-pointer" onclick="toggleDropdown('leave-dropdown', this)">
                            <div class="flex items-center space-x-2">
                                <i class="fa-solid fa-calendar-alt text-lg pr-4 menu-icon"></i>
                                <span class="text-sm font-medium">Leave Management</span>
                            </div>
                            <div class="arrow"><i class="bx bx-chevron-right text-lg font-semibold arrow-icon"></i></div>
                        </div>
                        <div id="leave-dropdown" class="menu-drop hidden flex-col w-full bg-[#F7E6CA] rounded-lg p-3 space-y-1 mt-1">
                            <ul class="space-y-1">
                                <li><a href="#" id="leave-requests-link" class="block px-3 py-1 text-sm text-gray-800 hover:bg-white rounded hover:text-[#4E3B2A]">Leave Requests</a></li>
                                <li><a href="#" id="leave-balances-link" class="block px-3 py-1 text-sm text-gray-800 hover:bg-white rounded hover:text-[#4E3B2A]">Leave Balances</a></li>
                                <li><a href="#" id="leave-types-link" class="block px-3 py-1 text-sm text-gray-800 hover:bg-white rounded hover:text-[#4E3B2A]">Leave Types</a></li>
                            </ul>
                        </div>
                    </li>

                    <li class="menu-option">
                        <div class="menu-name flex justify-between items-center space-x-3 hover:bg-[#F7E6CA] px-4 py-3 rounded-lg transition duration-300 ease-in-out cursor-pointer" onclick="toggleDropdown('compensation-dropdown', this)">
                            <div class="flex items-center space-x-2">
                                <i class="fa-solid fa-hand-holding-dollar text-lg pr-4 menu-icon"></i>
                                <span class="text-sm font-medium">Compensation</span>
                            </div>
                            <div class="arrow"><i class="bx bx-chevron-right text-lg font-semibold arrow-icon"></i></div>
                        </div>
                        <div id="compensation-dropdown" class="menu-drop hidden flex-col w-full bg-[#F7E6CA] rounded-lg p-3 space-y-1 mt-1">
                            <ul class="space-y-1">
                                <li><a href="#" id="comp-plans-link" class="block px-3 py-1 text-sm text-gray-800 hover:bg-white rounded hover:text-[#4E3B2A]">Compensation Plans</a></li>
                                <li><a href="#" id="salary-adjust-link" class="block px-3 py-1 text-sm text-gray-800 hover:bg-white rounded hover:text-[#4E3B2A]">Salary Adjustments</a></li>
                                <li><a href="#" id="incentives-link" class="block px-3 py-1 text-sm text-gray-800 hover:bg-white rounded hover:text-[#4E3B2A]">Incentives</a></li>
                            </ul>
                        </div>
                    </li>

                    <li class="menu-option">
                        <div class="menu-name flex justify-between items-center space-x-3 hover:bg-[#F7E6CA] px-4 py-3 rounded-lg transition duration-300 ease-in-out cursor-pointer" onclick="toggleDropdown('benefits-dropdown', this)">
                            <div class="flex items-center space-x-2">
                                <i class="fa-solid fa-heart text-lg pr-4 menu-icon"></i>
                                <span class="text-sm font-medium">Benefits Management</span>
                            </div>
                            <div class="arrow"><i class="bx bx-chevron-right text-lg font-semibold arrow-icon"></i></div>
                        </div>
                        <div id="benefits-dropdown" class="menu-drop hidden flex-col w-full bg-[#F7E6CA] rounded-lg p-3 space-y-1 mt-1">
                            <ul class="space-y-1">
                                <li><a href="#" id="hmo-management-link" class="block px-3 py-1 text-sm text-gray-800 hover:bg-white rounded hover:text-[#4E3B2A]">HMO Management</a></li>
                                <li><a href="#" id="hmo-providers-link" class="block px-3 py-1 text-sm text-gray-800 hover:bg-white rounded hover:text-[#4E3B2A]">HMO Providers</a></li>
                                <li><a href="#" id="hmo-plans-link" class="block px-3 py-1 text-sm text-gray-800 hover:bg-white rounded hover:text-[#4E3B2A]">HMO Plans</a></li>
                                <li><a href="#" id="benefits-reports-link" class="block px-3 py-1 text-sm text-gray-800 hover:bg-white rounded hover:text-[#4E3B2A]">Benefits Reports</a></li>
                            </ul>
                        </div>
                    </li>

                    <li class="menu-option">
                        <div class="menu-name flex justify-between items-center space-x-3 hover:bg-[#F7E6CA] px-4 py-3 rounded-lg transition duration-300 ease-in-out cursor-pointer" onclick="toggleDropdown('analytics-dropdown', this)">
                            <div class="flex items-center space-x-2">
                                <i class="fa-solid fa-chart-line text-lg pr-4 menu-icon"></i>
                                <span class="text-sm font-medium">Analytics</span>
                            </div>
                            <div class="arrow"><i class="bx bx-chevron-right text-lg font-semibold arrow-icon"></i></div>
                        </div>
                        <div id="analytics-dropdown" class="menu-drop hidden flex-col w-full bg-[#F7E6CA] rounded-lg p-3 space-y-1 mt-1">
                            <ul class="space-y-1">
                                <li><a href="#" id="analytics-dashboards-link" class="block px-3 py-1 text-sm text-gray-800 hover:bg-white rounded hover:text-[#4E3B2A]">Dashboards</a></li>
                                <li><a href="#" id="analytics-reports-link" class="block px-3 py-1 text-sm text-gray-800 hover:bg-white rounded hover:text-[#4E3B2A]">Reports</a></li>
                                <li><a href="#" id="analytics-metrics-link" class="block px-3 py-1 text-sm text-gray-800 hover:bg-white rounded hover:text-[#4E3B2A]">Metrics</a></li>
                            </ul>
                        </div>
                    </li>

                    <li class="menu-option">
                        <div class="menu-name flex justify-between items-center space-x-3 hover:bg-[#F7E6CA] px-4 py-3 rounded-lg transition duration-300 ease-in-out cursor-pointer" onclick="toggleDropdown('admin-dropdown', this)">
                            <div class="flex items-center space-x-2">
                                <i class="fa-solid fa-shield-halved text-lg pr-4 menu-icon"></i>
                                <span class="text-sm font-medium">Admin</span>
                            </div>
                            <div class="arrow"><i class="bx bx-chevron-right text-lg font-semibold arrow-icon"></i></div>
                        </div>
                        <div id="admin-dropdown" class="menu-drop hidden flex-col w-full bg-[#F7E6CA] rounded-lg p-3 space-y-1 mt-1">
                            <ul class="space-y-1">
                                <li><a href="#" id="user-management-link" class="block px-3 py-1 text-sm text-gray-800 hover:bg-white rounded hover:text-[#4E3B2A]">User Management</a></li>
                                </ul>
                        </div>
                    </li>
                 </ul>
            </div>
        </div>

        <div class="main w-full md:ml-[320px] transition-all duration-300 ease-in-out flex flex-col min-h-screen">
            <nav class="h-16 w-full bg-white border-b border-[#F7E6CA] flex justify-between items-center px-6 py-4 sticky top-0 z-30 flex-shrink-0">
                <div class="left-nav flex items-center space-x-4 max-w-lg w-full">
                    <button aria-label="Toggle menu" class="menu-btn text-[#4E3B2A] focus:outline-none hover:bg-[#F7E6CA] p-2 rounded-full">
                        <i class="fa-solid fa-bars text-[#594423] text-xl"></i>
                    </button>
                    </div>
                <div class="right-nav flex items-center space-x-4 md:space-x-6">
                    <div class="relative">
                        <button id="notification-bell-button" aria-label="Notifications" class="text-[#4E3B2A] focus:outline-none relative hover:text-[#594423]">
                            <i class="fa-regular fa-bell text-xl"></i>
                            <span id="notification-dot" class="notification-dot hidden">
                                </span>
                        </button>
                        <div id="notification-dropdown" class="hidden absolute right-0 mt-2 w-80 md:w-96 bg-white rounded-md shadow-xl z-50 border border-gray-200">
                            <div class="p-3 border-b border-gray-200">
                                <h4 class="text-sm font-semibold text-gray-700">Notifications</h4>
                            </div>
                            <div id="notification-list" class="max-h-80 overflow-y-auto">
                                <p class="p-4 text-sm text-gray-500 text-center">No new notifications.</p>
                            </div>
                            <div class="p-2 border-t border-gray-200 text-center">
                                <a href="#" id="view-all-notifications-link" class="text-xs text-blue-600 hover:underline">View All Notifications (Not Implemented)</a>
                            </div>
                        </div>
                    </div>
                    <div class="relative">
                        <button id="user-profile-button" type="button" class="flex items-center space-x-2 cursor-pointer group focus:outline-none">
                            <i class="fa-regular fa-user bg-[#594423] text-white px-3 py-2 rounded-lg text-lg group-hover:scale-110 transition-transform"></i>
                            <div class="info hidden md:flex flex-col py-1 text-left">
                                <h1 class="text-[#4E3B2A] font-semibold text-sm group-hover:text-[#594423]" id="user-display-name">Admin</h1>
                                <p class="text-[#594423] text-xs pl-1" id="user-display-role">System Administrator</p>
                            </div>
                            <i class='bx bx-chevron-down text-[#4E3B2A] group-hover:text-[#594423] transition-transform hidden md:block' id="user-profile-arrow"></i>
                        </button>
                        <div id="user-profile-dropdown" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 ring-1 ring-black ring-opacity-5 z-50">
                            <a href="#" id="view-profile-link" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-[#4E3B2A]">View Profile</a>
                            <a href="#" id="logout-link-nav" class="block px-4 py-2 text-sm text-gray-700 hover:bg-red-100 hover:text-red-600">Logout</a>
                        </div>
                    </div>
                </div>
            </nav>

            <main class="px-6 py-8 lg:px-8 flex-grow">
                <div class="mb-6">
                    <h2 class="text-2xl font-semibold text-[#4E3B2A]" id="page-title">HMO Management</h2>
                    <p class="text-gray-600" id="page-subtitle">Manage HMO providers, plans, and employee benefits</p>
                </div>

                <div id="main-content-area">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                        <!-- Quick Stats -->
                        <div class="bg-white rounded-lg shadow-md p-6">
                            <h3 class="text-lg font-semibold text-[#4E3B2A] mb-4 font-header">HMO Overview</h3>
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Total Providers:</span>
                                    <span id="total-providers" class="font-medium">Loading...</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Active Plans:</span>
                                    <span id="active-plans" class="font-medium">Loading...</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Enrolled Employees:</span>
                                    <span id="enrolled-employees" class="font-medium">Loading...</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Pending Claims:</span>
                                    <span id="pending-claims" class="font-medium">Loading...</span>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="bg-white rounded-lg shadow-md p-6">
                            <h3 class="text-lg font-semibold text-[#4E3B2A] mb-4 font-header">Quick Actions</h3>
                            <div class="space-y-3">
                                <button id="add-provider-btn" class="w-full bg-[#594423] text-white py-2 px-4 rounded-md hover:bg-[#4E3B2A] transition duration-300">
                                    <i class="fa-solid fa-plus mr-2"></i>Add HMO Provider
                                </button>
                                <button id="add-plan-btn" class="w-full bg-[#594423] text-white py-2 px-4 rounded-md hover:bg-[#4E3B2A] transition duration-300">
                                    <i class="fa-solid fa-plus mr-2"></i>Create HMO Plan
                                </button>
                                <button id="assign-benefits-btn" class="w-full bg-[#594423] text-white py-2 px-4 rounded-md hover:bg-[#4E3B2A] transition duration-300">
                                    <i class="fa-solid fa-users mr-2"></i>Assign Benefits
                                </button>
                            </div>
                        </div>

                        <!-- Recent Activity -->
                        <div class="bg-white rounded-lg shadow-md p-6">
                            <h3 class="text-lg font-semibold text-[#4E3B2A] mb-4 font-header">Recent Activity</h3>
                            <div id="recent-activity" class="space-y-3">
                                <div class="text-sm text-gray-600">
                                    <div class="flex items-center space-x-2">
                                        <i class="fa-solid fa-circle text-xs text-green-500"></i>
                                        <span>Loading recent activity...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- HMO Management Tabs -->
                    <div class="bg-white rounded-lg shadow-md">
                        <div class="border-b border-gray-200">
                            <nav class="flex space-x-8 px-6" aria-label="Tabs">
                                <button id="providers-tab" class="py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap">
                                    HMO Providers
                                </button>
                                <button id="plans-tab" class="py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap">
                                    HMO Plans
                                </button>
                                <button id="enrollments-tab" class="py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap">
                                    Employee Enrollments
                                </button>
                                <button id="claims-tab" class="py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap">
                                    Claims Management
                                </button>
                            </nav>
                        </div>

                        <div class="p-6">
                            <!-- Providers Tab Content -->
                            <div id="providers-content" class="tab-content">
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="text-lg font-medium text-[#4E3B2A] font-header">HMO Providers</h3>
                                    <button id="add-provider-modal-btn" class="bg-[#594423] text-white px-4 py-2 rounded-md hover:bg-[#4E3B2A] transition duration-300">
                                        <i class="fa-solid fa-plus mr-2"></i>Add Provider
                                    </button>
                                </div>
                                <div id="providers-table-container" class="overflow-x-auto">
                                    <table class="min-w-full table-auto">
                                        <thead>
                                            <tr class="bg-gray-50">
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Provider Name</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="providers-table-body" class="bg-white divide-y divide-gray-200">
                                            <tr>
                                                <td colspan="5" class="px-4 py-4 text-center text-gray-500">Loading providers...</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Plans Tab Content -->
                            <div id="plans-content" class="tab-content hidden">
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="text-lg font-medium text-[#4E3B2A] font-header">HMO Plans</h3>
                                    <button id="add-plan-modal-btn" class="bg-[#594423] text-white px-4 py-2 rounded-md hover:bg-[#4E3B2A] transition duration-300">
                                        <i class="fa-solid fa-plus mr-2"></i>Add Plan
                                    </button>
                                </div>
                                <div id="plans-table-container" class="overflow-x-auto">
                                    <table class="min-w-full table-auto">
                                        <thead>
                                            <tr class="bg-gray-50">
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plan Name</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Coverage</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Premium</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="plans-table-body" class="bg-white divide-y divide-gray-200">
                                            <tr>
                                                <td colspan="5" class="px-4 py-4 text-center text-gray-500">Loading plans...</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Enrollments Tab Content -->
                            <div id="enrollments-content" class="tab-content hidden">
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="text-lg font-medium text-[#4E3B2A] font-header">Employee Enrollments</h3>
                                    <button id="assign-benefits-modal-btn" class="bg-[#594423] text-white px-4 py-2 rounded-md hover:bg-[#4E3B2A] transition duration-300">
                                        <i class="fa-solid fa-plus mr-2"></i>Assign Benefits
                                    </button>
                                </div>
                                <div id="enrollments-table-container" class="overflow-x-auto">
                                    <table class="min-w-full table-auto">
                                        <thead>
                                            <tr class="bg-gray-50">
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">HMO Provider</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plan</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Enrollment Date</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="enrollments-table-body" class="bg-white divide-y divide-gray-200">
                                            <tr>
                                                <td colspan="6" class="px-4 py-4 text-center text-gray-500">Loading enrollments...</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Claims Tab Content -->
                            <div id="claims-content" class="tab-content hidden">
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="text-lg font-medium text-[#4E3B2A] font-header">Claims Management</h3>
                                    <div class="flex space-x-2">
                                        <select id="claims-filter" class="border border-gray-300 rounded-md px-3 py-2 text-sm">
                                            <option value="all">All Claims</option>
                                            <option value="pending">Pending</option>
                                            <option value="approved">Approved</option>
                                            <option value="rejected">Rejected</option>
                                        </select>
                                    </div>
                                </div>
                                <div id="claims-table-container" class="overflow-x-auto">
                                    <table class="min-w-full table-auto">
                                        <thead>
                                            <tr class="bg-gray-50">
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Claim ID</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium
