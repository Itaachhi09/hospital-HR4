
<?php
if (session_status() === PHP_SESSION_NONE) {
    $secureFlag = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => $_SERVER['HTTP_HOST'] ?? '',
        'secure' => $secureFlag,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}   
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HMVH Hospital Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Cinzel:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <style>
        /* Modern Hospital Theme - Clean, Professional Design */
        :root {
            --hospital-blue: #2563eb;
            --hospital-blue-light: #3b82f6;
            --hospital-blue-dark: #1d4ed8;
            --hospital-sky: #0ea5e9;
            --hospital-cyan: #06b6d4;
            --hospital-teal: #14b8a6;
            --hospital-emerald: #10b981;
            --hospital-gray: #64748b;
            --hospital-gray-light: #f1f5f9;
            --hospital-gray-dark: #334155;
            --hospital-white: #ffffff;
            --hospital-warm: #fef3c7;
            --hospital-accent: #f59e0b;
            --hospital-success: #22c55e;
            --hospital-warning: #f59e0b;
            --hospital-error: #ef4444;
            --hospital-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --hospital-shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: var(--hospital-gray-light);
        }

        .font-header, h1, h2, h3, h4, h5, h6 {
            font-family: 'Cinzel', serif;
            font-weight: 600;
        }

        .page-title {
             font-family: 'Cinzel', serif;
            font-weight: 700;
        }

        /* Modern Card Styles */
        .dashboard-card {
            background: var(--hospital-white);
            border-radius: 1rem;
            box-shadow: var(--hospital-shadow);
            transition: all 0.3s ease;
            border: 1px solid rgba(37, 99, 235, 0.1);
        }

        .dashboard-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--hospital-shadow-lg);
            border-color: var(--hospital-blue);
        }

        .stat-card {
            background: linear-gradient(135deg, var(--hospital-white) 0%, #f8fafc 100%);
            border-radius: 1rem;
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--hospital-blue), var(--hospital-sky));
        }

        .trend-up {
            color: var(--hospital-success);
        }

        .trend-down {
            color: var(--hospital-error);
        }

        .trend-neutral {
            color: var(--hospital-gray);
        }

        /* Modern Button Styles */
        .btn-primary {
            background: linear-gradient(135deg, var(--hospital-blue), var(--hospital-blue-light));
            color: white;
            border: none;
            border-radius: 0.75rem;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: var(--hospital-shadow);
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: var(--hospital-shadow-lg);
        }

        /* Chart Container */
        .chart-container {
            background: var(--hospital-white);
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: var(--hospital-shadow);
            border: 1px solid rgba(37, 99, 235, 0.1);
        }

        /* Notification Styles */
        .notification-item {
            transition: all 0.2s ease;
        }

        .notification-item:hover {
            background-color: var(--hospital-gray-light);
            transform: translateX(4px);
        }

        .notification-dot {
            position: absolute;
            top: -2px;
            right: -2px;
            height: 8px;
            width: 8px;
            background: var(--hospital-error);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.6rem;
            color: white;
        }

        /* Sidebar Styles */
        .sidebar {
            background: linear-gradient(180deg, var(--hospital-blue-dark) 0%, var(--hospital-blue) 100%);
            transition: all 0.3s ease;
        }

        .sidebar-item {
            transition: all 0.2s ease;
            border-radius: 0.75rem;
            margin: 0.25rem 0;
        }

        .sidebar-item:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateX(4px);
        }

        .sidebar-item.active {
            background: rgba(255, 255, 255, 0.15);
            box-shadow: inset 0 0 0 2px rgba(255, 255, 255, 0.2);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .dashboard-card {
                margin-bottom: 1rem;
            }
            
            .stat-card {
                padding: 1rem;
            }
        }

        /* Animation Classes */
        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .slide-in {
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from { transform: translateX(-20px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
         }
        
        /* Notification Dropdown Styles */
        .notification-item:hover {
            background-color: #f7fafc; /* Tailwind gray-100 */
        }
        .notification-dot {
            position: absolute;
            top: -2px; /* Adjust as needed */
            right: -2px; /* Adjust as needed */
            height: 8px;
            width: 8px;
            background-color: #ef4444; /* Tailwind red-500 */
            border-radius: 9999px; /* full */
            display: flex; /* To center the number if you add one */
            align-items: center;
            justify-content: center;
            font-size: 0.6rem;
            color: white;
        }

    /* Ensure notification dropdown is anchored to the bell container */
    #notification-bell-button + #notification-dropdown,
    #notification-dropdown {
      transform-origin: top right;
    }
    /* When the dropdown is inside a positioned parent, absolute positioning will anchor beside the bell */
    .relative > #notification-dropdown {
      min-width: 16rem;
    }

    /* Sidebar fixed on the left: give main content a left margin to avoid overlap */
    main.flex-1 {
      transition: margin-left 0.25s ease;
      margin-left: 16rem; /* default for expanded sidebar */
    }
    /* Smaller margin when sidebar is collapsed (approx 4rem) */
    aside[style*="width: 4rem"], aside[style*="width:4rem"] ~ main.flex-1 {
      margin-left: 4rem;
    }
    /* Ensure content doesn't underflow behind fixed sidebar on small screens */
    @media (max-width: 768px) {
      main.flex-1 { margin-left: 0; }
      aside.fixed { position: fixed; bottom: 0; top: auto; height: auto; width: 100%; }
    }

    /* HMO module button styles: oval buttons with variant colors */
    .hmo-btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: .5rem;
      padding: .35rem .6rem;
      border-radius: 9999px; /* pill/oval */
      font-size: .85rem;
      line-height: 1;
      border: none;
      cursor: pointer;
      transition: transform .08s ease, box-shadow .12s ease, opacity .12s ease;
    }
    .hmo-btn:active { transform: translateY(1px); }
  .hmo-btn-primary { background:#1d4ed8; color:white; box-shadow: 0 1px 0 rgba(0,0,0,0.05); }
  .hmo-btn-success { background:#16a34a; color:white; }
  /* Make secondary (Edit/View) blue to match requested style */
  .hmo-btn-secondary { background:#2563eb; color:white; }
    .hmo-btn-warning { background:#f59e0b; color:white; }
    .hmo-btn-danger { background:#dc2626; color:white; }
    /* Make small primary buttons slightly larger visual weight */
    .hmo-btn.hmo-large { padding: .45rem .85rem; }

        /* Generic Modal Styles */
        .modal {
            transition: opacity 0.25s ease;
        }
        .modal-content {
            transition: transform 0.25s ease;
        }
  /* HMO tables: headers should be white with black text per user request */
  #hmo-providers-table thead tr,
  #hmo-plans-table thead tr,
  #hmo-enrollments-table thead tr,
  #hmo-claims-table thead tr { background: #ffffff; color: #000000; }
  #hmo-providers-table thead tr th,
  #hmo-plans-table thead tr th,
  #hmo-enrollments-table thead tr th,
  #hmo-claims-table thead tr th { color: #000; }
  /* All HMO tables: use strong header divider and solid black row separators for consistent treatment */
  #hmo-providers-table tbody tr,
  #hmo-plans-table tbody tr,
  #hmo-enrollments-table tbody tr,
  #hmo-claims-table tbody tr { border-bottom: 1px solid #000000; }
  /* Make a clear divider between the header and rows for all HMO tables */
  #hmo-providers-table thead th,
  #hmo-plans-table thead th,
  #hmo-enrollments-table thead th,
  #hmo-claims-table thead th { border-bottom: 3px solid #000000; }
  /* Add a bit more top padding to the first row so the separator reads as row divider, not header artifact */
  #hmo-providers-table tbody tr:first-child td,
  #hmo-plans-table tbody tr:first-child td,
  #hmo-enrollments-table tbody tr:first-child td,
  #hmo-claims-table tbody tr:first-child td { padding-top: 1.25rem; }
  #hmo-plans-table tbody tr,
  #hmo-enrollments-table tbody tr,
  #hmo-claims-table tbody tr { border-bottom: 1px solid rgba(13, 48, 202, 0.09); }
  #hmo-providers-table tbody tr:last-child,
  #hmo-plans-table tbody tr:last-child,
  #hmo-enrollments-table tbody tr:last-child,
  #hmo-claims-table tbody tr:last-child { border-bottom: none; }
  /* Ensure the separator spans full width when table cells have padding */
  #hmo-providers-table tbody td,
  #hmo-plans-table tbody td,
  #hmo-enrollments-table tbody td,
  #hmo-claims-table tbody td { padding-top: .75rem; padding-bottom: .75rem; }
    </style>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
   <!-- Dashboard Initialization -->
   <script>
     // Function to display the modern dashboard
     function displayModernDashboard() {
       const mainContent = document.getElementById('main-content-area');
       if (mainContent) {
         // The modern dashboard content is already in the HTML
         // Just ensure it's visible
         mainContent.style.display = 'block';
         console.log('Modern dashboard displayed');
       }
     }
     
     // Make functions globally available immediately
     window.displayModernDashboard = displayModernDashboard;
</script>

</head>

<body class="h-screen flex bg-slate-50" x-data="{ sidebarOpen: true, open: '' }">
  <div id="app-container" class="flex w-full h-full">

  <!-- Sidebar (fixed to left) -->
  <aside 
    id="sidebar"
    class="sidebar fixed left-0 top-0 bottom-0 flex flex-col text-white transition-all duration-300 z-50"
    :class="sidebarOpen ? 'w-64' : 'w-16'"
    style="width: 16rem;"
  >
    <!-- Sidebar Header -->
    <div class="flex items-center justify-between px-4 py-4 bg-gradient-to-r from-blue-800 to-blue-600">
      <div class="flex items-center gap-3" x-show="sidebarOpen" x-transition>
        <div class="h-10 w-10 rounded-full grid place-items-center border-2 border-yellow-400 bg-white">
          <span class="text-sm font-extrabold text-blue-800">HM</span>
        </div>
        <p class="font-semibold">H VILL</p>
      </div>
      <!-- Toggle Button -->
      <button @click="sidebarOpen = !sidebarOpen" class="text-white hover:text-[#d4af37]">
        <span x-show="sidebarOpen">⮜</span>
        <span x-show="!sidebarOpen">⮞</span>
      </button>
    </div>

    <!-- Modules -->
    <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-2 text-sm">
      <div>
        <p class="uppercase text-xs text-white/50 mb-1" x-show="sidebarOpen">Main</p>
        <a href="#" id="dashboard-link" class="sidebar-item flex items-center gap-2 px-3 py-2 hover:bg-white/10">
          <!-- Home Icon -->
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l9-9 9 9M4 10v10a1 1 0 001 1h3m10-11v11a1 1 0 01-1 1h-3m-6 0h6"/>
          </svg>
          <span x-show="sidebarOpen" x-transition>Overview / Dashboard</span>
        </a>
        <a href="#" id="notifications-link" class="sidebar-item flex items-center gap-2 px-3 py-2 hover:bg-white/10">
          <!-- Bell Icon -->
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405M19 13V8a7 7 0 10-14 0v5l-1.405 1.405A2.032 2.032 0 004 17h16z"/>
          </svg>
          <span x-show="sidebarOpen" x-transition>Notifications</span>
        </a>
      </div>

      <div>
        <p class="uppercase text-xs text-white/50 mb-1" x-show="sidebarOpen">Modules</p>

        <!-- Core HR -->
        <div>
          <button @click="open === 'corehr' ? open = '' : open = 'corehr'" class="w-full flex justify-between items-center px-3 py-2 rounded-lg hover:bg-white/10">
            <span class="flex items-center gap-2">
              <!-- Users Icon -->
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[#d4af37]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
              </svg>
              <span x-show="sidebarOpen" x-transition>Core HR</span>
            </span>
            <span x-show="sidebarOpen"><span x-show="open === 'corehr'">−</span><span x-show="open !== 'corehr'">+</span></span>
          </button>
          <div class="ml-6 space-y-1 text-white/80" x-show="open === 'corehr' && sidebarOpen" x-transition>
            <a href="#" id="employees-link" class="block hover:text-[#d4af37]">Employee Directory</a>
            <a href="#" id="documents-link" class="block hover:text-[#d4af37]">Document Viewer</a>
            <a href="#" id="org-structure-link" class="block hover:text-[#d4af37]">Org Structure</a>
            <a href="#" id="role-access-link" class="block hover:text-[#d4af37]">Role & Access</a>
          </div>
        </div>

        <!-- Time & Attendance - Hidden as per HR4 integration -->
        <div style="display: none;">
          <button @click="open === 'time' ? open = '' : open = 'time'" class="w-full flex justify-between items-center px-3 py-2 rounded-lg hover:bg-white/10">
            <span class="flex items-center gap-2">
              <!-- Clock Icon -->
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[#d4af37]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
              <span x-show="sidebarOpen" x-transition>Time & Attendance</span>
            </span>
            <span x-show="sidebarOpen"><span x-show="open === 'time'">−</span><span x-show="open !== 'time'">+</span></span>
          </button>
          <div class="ml-6 space-y-1 text-white/80" x-show="open === 'time' && sidebarOpen" x-transition>
            <a href="#" id="attendance-link" class="block hover:text-[#d4af37]">Attendance Records</a>
            <a href="#" id="timesheets-link" class="block hover:text-[#d4af37]">Timesheets</a>
            <a href="#" id="schedules-link" class="block hover:text-[#d4af37]">Schedules</a>
            <a href="#" id="shifts-link" class="block hover:text-[#d4af37]">Shifts</a>
          </div>
        </div>

        <!-- Payroll -->
        <div>
          <button @click="open === 'payroll' ? open = '' : open = 'payroll'" class="w-full flex justify-between items-center px-3 py-2 rounded-lg hover:bg-white/10">
            <span class="flex items-center gap-2">
              <!-- Money Icon -->
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[#d4af37]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
              </svg>
              <span x-show="sidebarOpen" x-transition>Payroll</span>
            </span>
            <span x-show="sidebarOpen"><span x-show="open === 'payroll'">−</span><span x-show="open !== 'payroll'">+</span></span>
          </button>
          <div class="ml-6 space-y-1 text-white/80" x-show="open === 'payroll' && sidebarOpen" x-transition>
            <a href="#" id="payroll-runs-link" class="block hover:text-[#d4af37]">Payroll Runs</a>
            <a href="#" id="salaries-link" class="block hover:text-[#d4af37]">Salaries</a>
            <a href="#" id="bonuses-link" class="block hover:text-[#d4af37]">Bonuses</a>
            <a href="#" id="deductions-link" class="block hover:text-[#d4af37]">Deductions</a>
            <a href="#" id="payslips-link" class="block hover:text-[#d4af37]">View Payslips</a>
          </div>
        </div>

        <!-- Claims - Hidden as per HR4 integration -->
        <div style="display: none;">
          <button @click="open === 'claims' ? open = '' : open = 'claims'" class="w-full flex justify-between items-center px-3 py-2 rounded-lg hover:bg-white/10">
            <span class="flex items-center gap-2">
              <!-- Document Icon -->
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[#d4af37]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
              </svg>
              <span x-show="sidebarOpen" x-transition>Claims</span>
            </span>
            <span x-show="sidebarOpen"><span x-show="open === 'claims'">−</span><span x-show="open !== 'claims'">+</span></span>
          </button>
          <div class="ml-6 space-y-1 text-white/80" x-show="open === 'claims' && sidebarOpen" x-transition>
            <a href="#" id="submit-claim-link" class="block hover:text-[#d4af37]">Submit Claim</a>
            <a href="#" id="my-claims-link" class="block hover:text-[#d4af37]">My Claims</a>
            <a href="#" id="claims-approval-link" class="block hover:text-[#d4af37]">Approvals</a>
            <a href="#" id="claim-types-admin-link" class="block hover:text-[#d4af37]">Claim Types (Admin)</a>
          </div>
        </div>

        <!-- HMO & Benefits -->
        <div>
          <button @click="open === 'hmo' ? open = '' : open = 'hmo'" class="w-full flex justify-between items-center px-3 py-2 rounded-lg hover:bg-white/10">
            <span class="flex items-center gap-2">
              <!-- Heart Icon -->
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[#d4af37]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
              </svg>
              <span x-show="sidebarOpen" x-transition>HMO & Benefits</span>
            </span>
            <span x-show="sidebarOpen"><span x-show="open === 'hmo'">−</span><span x-show="open !== 'hmo'">+</span></span>
          </button>
          <div class="ml-6 space-y-1 text-white/80" x-show="open === 'hmo' && sidebarOpen" x-transition>
            <a href="#" id="hmo-providers-link" class="block hover:text-[#d4af37]">HMO Providers</a>
            <a href="#" id="hmo-plans-link" class="block hover:text-[#d4af37]">Benefit Plans</a>
            <a href="#" id="hmo-enrollments-link" class="block hover:text-[#d4af37]">Employee Enrollments</a>
            <a href="#" id="hmo-claims-admin-link" class="block hover:text-[#d4af37]">HMO Claims</a>
            <a href="#" id="hmo-dashboard-link" class="block hover:text-[#d4af37]">HMO Dashboard</a>
          </div>
        </div>

        <!-- Leave Management - Hidden as per HR4 integration -->
        <div style="display: none;">
          <button @click="open === 'leave' ? open = '' : open = 'leave'" class="w-full flex justify-between items-center px-3 py-2 rounded-lg hover:bg-white/10">
            <span class="flex items-center gap-2">
              <!-- Calendar Icon -->
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[#d4af37]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
              </svg>
              <span x-show="sidebarOpen" x-transition>Leave Management</span>
            </span>
            <span x-show="sidebarOpen"><span x-show="open === 'leave'">−</span><span x-show="open !== 'leave'">+</span></span>
          </button>
          <div class="ml-6 space-y-1 text-white/80" x-show="open === 'leave' && sidebarOpen" x-transition>
            <a href="#" id="leave-requests-link" class="block hover:text-[#d4af37]">Leave Requests</a>
            <a href="#" id="leave-balances-link" class="block hover:text-[#d4af37]">Leave Balances</a>
            <a href="#" id="leave-types-link" class="block hover:text-[#d4af37]">Leave Types</a>
          </div>
        </div>

        <!-- Compensation -->
        <div>
          <button @click="open === 'comp' ? open = '' : open = 'comp'" class="w-full flex justify-between items-center px-3 py-2 rounded-lg hover:bg-white/10">
            <span class="flex items-center gap-2">
              <!-- Dollar Icon -->
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[#d4af37]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
              </svg>
              <span x-show="sidebarOpen" x-transition>Compensation</span>
            </span>
            <span x-show="sidebarOpen"><span x-show="open === 'comp'">−</span><span x-show="open !== 'comp'">+</span></span>
          </button>
          <div class="ml-6 space-y-1 text-white/80" x-show="open === 'comp' && sidebarOpen" x-transition>
            <!-- Compensation Overview link removed -->
            <a href="#" id="comp-plans-link" class="block hover:text-[#d4af37]">Compensation Plans</a>
            <a href="#" id="salary-adjust-link" class="block hover:text-[#d4af37]">Salary Adjustments</a>
            <a href="#" id="incentives-link" class="block hover:text-[#d4af37]">Incentives</a>
            <a href="#" id="salary-grades-link" class="block hover:text-[#d4af37]">Salary Grades</a>
            <a href="#" id="pay-bands-link" class="block hover:text-[#d4af37]">Pay Bands</a>
            <a href="#" id="employee-mapping-link" class="block hover:text-[#d4af37]">Employee Mapping</a>
            <a href="#" id="workflows-link" class="block hover:text-[#d4af37]">Workflows</a>
            <a href="#" id="simulation-tools-link" class="block hover:text-[#d4af37]">Simulation Tools</a>
          </div>
        </div>

        <!-- Analytics -->
        <div>
          <button @click="open === 'analytics' ? open = '' : open = 'analytics'" class="w-full flex justify-between items-center px-3 py-2 rounded-lg hover:bg-white/10">
            <span class="flex items-center gap-2">
              <!-- Chart Icon -->
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[#d4af37]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
              </svg>
              <span x-show="sidebarOpen" x-transition>Analytics</span>
            </span>
            <span x-show="sidebarOpen"><span x-show="open === 'analytics'">−</span><span x-show="open !== 'analytics'">+</span></span>
          </button>
          <div class="ml-6 space-y-1 text-white/80" x-show="open === 'analytics' && sidebarOpen" x-transition>
            <a href="#" id="analytics-dashboards-link" class="block hover:text-[#d4af37]">Dashboards</a>
            <a href="#" id="analytics-reports-link" class="block hover:text-[#d4af37]">Reports</a>
            <a href="#" id="analytics-metrics-link" class="block hover:text-[#d4af37]">Metrics</a>
          </div>
        </div>

        <!-- Admin -->
        <div>
          <button @click="open === 'admin' ? open = '' : open = 'admin'" class="w-full flex justify-between items-center px-3 py-2 rounded-lg hover:bg-white/10">
            <span class="flex items-center gap-2">
              <!-- Shield Icon -->
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[#d4af37]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
              </svg>
              <span x-show="sidebarOpen" x-transition>Admin</span>
            </span>
            <span x-show="sidebarOpen"><span x-show="open === 'admin'">−</span><span x-show="open !== 'admin'">+</span></span>
          </button>
          <div class="ml-6 space-y-1 text-white/80" x-show="open === 'admin' && sidebarOpen" x-transition>
            <a href="#" id="user-management-link" class="block hover:text-[#d4af37]">User Management</a>
          </div>
        </div>
      </div>
    </nav>

    <!-- Footer -->
    <div class="px-3 py-4 border-t border-white/20">
      <button id="logout-link-nav" onclick="handleLogout(event)" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-white/10">
        <!-- Logout Icon -->
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1m0-10V5m0 6H3"/>
        </svg>
        <span x-show="sidebarOpen" x-transition>Logout</span>
      </button>
      <p class="text-[10px] text-white/50 mt-2" x-show="sidebarOpen" x-transition>© 1999–2025 HMVH</p>
    </div>
  </aside>

  <!-- Main Content -->
  <main class="flex-1 flex flex-col">
    <!-- Top Header -->
    <header class="flex items-center justify-between px-6 py-6 border-b bg-white shadow-sm">
      <div>
        <h1 class="text-3xl font-bold page-title text-gray-900">Hospital Dashboard</h1>
        <p class="text-sm text-gray-600 mt-1">Integrity • Service • Commitment • Respect • Compassion</p>
      </div>
      <div class="flex items-center gap-3">
        <div class="relative">
          <button id="notification-bell-button" class="relative p-2 rounded-lg border hover:bg-slate-50">
            <!-- Bell Icon -->
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[#0b1b3b]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405M19 13V8a7 7 0 10-14 0v5l-1.405 1.405A2.032 2.032 0 004 17h16z"/>
            </svg>
            <span id="notification-dot" class="absolute -top-1 -right-1 h-5 w-5 rounded-full text-xs bg-[#d4af37] text-[#0b1b3b] grid place-items-center hidden"></span>
          </button>

          <!-- Notification Dropdown (anchored to bell) -->
          <div id="notification-dropdown" class="absolute top-full right-0 mt-2 w-80 bg-white rounded-lg shadow-lg border z-50 hidden transform origin-top-right scale-95 opacity-0 transition-all duration-150">
            <!-- Caret / pointer -->
            <div class="absolute -top-2 right-4 w-3 h-3 rotate-45 bg-white border-t border-l"></div>
            <div class="p-4 border-b">
              <h3 class="text-lg font-semibold text-gray-800">Notifications</h3>
            </div>
            <div id="notification-list" class="max-h-64 overflow-y-auto">
              <!-- Notifications will be loaded here -->
            </div>
          </div>
        </div>
        <div class="relative">
          <button id="user-profile-button" class="flex items-center gap-2 border px-4 py-2 rounded-lg bg-slate-50 hover:bg-slate-100">
            <span id="user-display-name" class="text-sm font-medium text-slate-700"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'User'); ?></span>
            <span id="user-display-role" class="text-sm text-slate-500">(<?php echo htmlspecialchars($_SESSION['role_name'] ?? 'Admin'); ?>)</span>
            <span id="user-profile-arrow" class="text-slate-400">▼</span>
          </button>
          
          <!-- User Profile Dropdown -->
          <div id="user-profile-dropdown" class="absolute top-full right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border z-50 hidden">
            <div class="py-2">
              <a href="#" id="view-profile-link" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">View Profile</a>
              <a href="#" id="logout-link-nav" onclick="handleLogout(event)" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Logout</a>
            </div>
          </div>
        </div>
      </div>
    </header>

    <!-- Page Content -->
    <section class="flex-1 p-6">
      <div class="mb-6">
        <h2 class="text-2xl font-semibold text-[#4E3B2A]" id="page-title">Dashboard</h2>
        <p class="text-gray-600" id="page-subtitle"></p>
      </div>

      <div id="main-content-area">
        <!-- Modern Dashboard Content -->
        <div class="space-y-6">
          <!-- Quick Stats Cards -->
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Total Employees Card -->
            <div class="stat-card dashboard-card cursor-pointer" onclick="navigateToModule('employees')">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-gray-600">Total Employees</p>
                  <p class="text-3xl font-bold text-gray-900" id="total-employees">-</p>
                  <div class="flex items-center mt-2">
                    <span class="text-sm trend-up" id="employees-trend">
                      <i class="fas fa-arrow-up mr-1"></i>+5.2%
                    </span>
                    <span class="text-xs text-gray-500 ml-2">vs last month</span>
                  </div>
                </div>
                <div class="p-3 bg-blue-100 rounded-full">
                  <i class="fas fa-users text-2xl text-blue-600"></i>
                </div>
              </div>
            </div>

            <!-- Active Employees Card -->
            <div class="stat-card dashboard-card cursor-pointer" onclick="navigateToModule('employees')">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-gray-600">Active Employees</p>
                  <p class="text-3xl font-bold text-gray-900" id="active-employees">-</p>
                  <div class="flex items-center mt-2">
                    <span class="text-sm trend-up" id="active-trend">
                      <i class="fas fa-arrow-up mr-1"></i>+2.1%
                    </span>
                    <span class="text-xs text-gray-500 ml-2">vs last month</span>
                  </div>
                </div>
                <div class="p-3 bg-green-100 rounded-full">
                  <i class="fas fa-user-check text-2xl text-green-600"></i>
                </div>
              </div>
            </div>

            <!-- Pending Leave Requests Card -->
            <div class="stat-card dashboard-card cursor-pointer" onclick="navigateToModule('leave')">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-gray-600">Pending Leave</p>
                  <p class="text-3xl font-bold text-gray-900" id="pending-leave">-</p>
                  <div class="flex items-center mt-2">
                    <span class="text-sm trend-neutral" id="leave-trend">
                      <i class="fas fa-minus mr-1"></i>0.0%
                    </span>
                    <span class="text-xs text-gray-500 ml-2">vs last month</span>
                  </div>
                </div>
                <div class="p-3 bg-yellow-100 rounded-full">
                  <i class="fas fa-calendar-clock text-2xl text-yellow-600"></i>
                </div>
              </div>
            </div>

            <!-- Recent Hires Card -->
            <div class="stat-card dashboard-card cursor-pointer" onclick="navigateToModule('employees')">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-gray-600">Recent Hires</p>
                  <p class="text-3xl font-bold text-gray-900" id="recent-hires">-</p>
                  <div class="flex items-center mt-2">
                    <span class="text-sm trend-up" id="hires-trend">
                      <i class="fas fa-arrow-up mr-1"></i>+12.5%
                    </span>
                    <span class="text-xs text-gray-500 ml-2">vs last month</span>
                  </div>
                </div>
                <div class="p-3 bg-purple-100 rounded-full">
                  <i class="fas fa-user-plus text-2xl text-purple-600"></i>
                </div>
              </div>
            </div>
          </div>

          <!-- Charts Section -->
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Employee Distribution Chart -->
            <div class="chart-container">
              <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Employee Distribution by Department</h3>
                <button class="text-sm text-blue-600 hover:text-blue-800" onclick="navigateToModule('org-structure')">
                  View Details <i class="fas fa-arrow-right ml-1"></i>
                </button>
              </div>
              <div class="h-64">
                <canvas id="departmentChart"></canvas>
              </div>
            </div>

            <!-- New Hires Trend Chart -->
            <div class="chart-container">
              <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">New Hires Trend (Last 6 Months)</h3>
                <button class="text-sm text-blue-600 hover:text-blue-800" onclick="navigateToModule('analytics')">
                  View Details <i class="fas fa-arrow-right ml-1"></i>
                </button>
              </div>
              <div class="h-64">
                <canvas id="hiresTrendChart"></canvas>
              </div>
            </div>
          </div>

          <!-- Performance Metrics -->
          <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Top Performing Departments -->
            <div class="chart-container">
              <h3 class="text-lg font-semibold text-gray-900 mb-4">Top 5 Performing Departments</h3>
              <div id="topDepartmentsChart" class="h-64"></div>
            </div>

            <!-- HR KPIs -->
            <div class="chart-container">
              <h3 class="text-lg font-semibold text-gray-900 mb-4">HR Key Performance Indicators</h3>
              <div class="space-y-4">
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                  <span class="text-sm font-medium text-gray-600">Turnover Rate</span>
                  <span class="text-lg font-bold text-gray-900">3.2%</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                  <span class="text-sm font-medium text-gray-600">Average Tenure</span>
                  <span class="text-lg font-bold text-gray-900">4.8 years</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                  <span class="text-sm font-medium text-gray-600">Employee Engagement</span>
                  <span class="text-lg font-bold text-gray-900">87%</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                  <span class="text-sm font-medium text-gray-600">Training Completion</span>
                  <span class="text-lg font-bold text-gray-900">94%</span>
                </div>
              </div>
            </div>

             <!-- Quick Actions -->
             <div class="chart-container">
               <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
               <div class="space-y-3">
                 <button class="w-full btn-primary text-left p-3" onclick="navigateToModule('payroll')">
                   <i class="fas fa-calculator mr-2"></i>Run Payroll
                 </button>
                 <button class="w-full btn-primary text-left p-3" onclick="navigateToModule('hmo')">
                   <i class="fas fa-heart mr-2"></i>Manage HMO
                 </button>
                 <button class="w-full btn-primary text-left p-3" onclick="navigateToModule('analytics')">
                   <i class="fas fa-chart-bar mr-2"></i>View Reports
                 </button>
                 <button class="w-full btn-primary text-left p-3" onclick="navigateToModule('employees')">
                   <i class="fas fa-users mr-2"></i>View Employees
                 </button>
               </div>
             </div>
          </div>

          <!-- Notifications Widget -->
          <div class="chart-container">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-lg font-semibold text-gray-900">Recent HR Activities</h3>
              <button class="text-sm text-blue-600 hover:text-blue-800" onclick="navigateToModule('notifications')">
                View All <i class="fas fa-arrow-right ml-1"></i>
              </button>
            </div>
            <div id="recent-activities" class="space-y-3">
              <!-- Activities will be loaded here -->
            </div>
          </div>
        </div>
      </div>
    </section>










  </main>

        <div id="timesheet-detail-modal" class="fixed inset-0 z-[60] hidden items-center justify-center p-4 modal" aria-labelledby="modal-title-ts" role="dialog" aria-modal="true">
            <div id="modal-overlay-ts" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            <div class="modal-content bg-white rounded-lg shadow-xl transform transition-all sm:max-w-3xl w-full p-6 space-y-4 overflow-y-auto max-h-[90vh]">
                <div class="flex justify-between items-center pb-3 border-b">
                     <h3 class="text-lg font-medium text-[#4E3B2A] font-header" id="modal-title-ts">
                        Timesheet Details (<span id="modal-timesheet-id"></span>)
                    </h3>
                    <button type="button" id="modal-close-btn-ts" class="text-gray-400 hover:text-gray-600">
                        <span class="sr-only">Close</span>
                        <i class="fa-solid fa-times text-xl"></i>
                    </button>
                </div>
                <div class="mt-4 space-y-3">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-2 text-sm">
                        <div><strong>Employee:</strong> <span id="modal-employee-name"></span></div>
                        <div><strong>Job Title:</strong> <span id="modal-employee-job"></span></div>
                        <div><strong>Period:</strong> <span id="modal-period-start"></span> to <span id="modal-period-end"></span></div>
                        <div><strong>Status:</strong> <span id="modal-status" class="font-semibold"></span></div>
                        <div><strong>Total Hours:</strong> <span id="modal-total-hours"></span></div>
                        <div><strong>Overtime Hours:</strong> <span id="modal-overtime-hours"></span></div>
                        <div><strong>Submitted:</strong> <span id="modal-submitted-date"></span></div>
                        <div><strong>Approved By:</strong> <span id="modal-approver-name"></span></div>
                    </div>
                    <hr>
                    <div>
                        <h4 class="text-md font-medium text-gray-800 mb-2 font-header">Attendance Entries</h4>
                        <div id="modal-attendance-entries" class="max-h-60 overflow-y-auto border rounded">
                            </div>
                    </div>
                </div>
                 <div class="pt-4 flex justify-end space-x-3 border-t mt-4">
                    <button type="button" id="modal-close-btn-ts-footer" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">Close</button>
                </div>
            </div>
        </div>
  <!-- Global modal container for injecting module modals -->
  <div id="modalContainer"></div>
        
        <div id="add-shift-modal" class="fixed inset-0 z-[60] hidden items-center justify-center p-4 modal" aria-labelledby="add-shift-modal-title" role="dialog" aria-modal="true">
            <div id="add-shift-modal-overlay" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
            <div class="modal-content bg-white rounded-lg shadow-xl transform transition-all sm:max-w-lg w-full p-6 space-y-4">
                <div class="flex justify-between items-center pb-3 border-b">
                    <h3 class="text-lg font-medium text-[#4E3B2A] font-header" id="add-shift-modal-title">Add New Shift</h3>
                    <button type="button" id="close-add-shift-modal-btn" class="text-gray-400 hover:text-gray-600">
                        <span class="sr-only">Close</span>
                        <i class="fa-solid fa-times text-xl"></i>
                    </button>
                </div>
                <form id="add-shift-modal-form" class="space-y-4">
                    <div>
                        <label for="modal-shift-name" class="block text-sm font-medium text-gray-700 mb-1">Shift Name:</label>
                        <input type="text" id="modal-shift-name" name="shift_name" required placeholder="e.g., Day Shift" class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-[#4E3B2A] focus:border-[#4E3B2A]">
                    </div>
                    <div>
                        <label for="modal-start-time" class="block text-sm font-medium text-gray-700 mb-1">Start Time:</label>
                        <input type="time" id="modal-start-time" name="start_time" required class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-[#4E3B2A] focus:border-[#4E3B2A]">
                    </div>
                    <div>
                        <label for="modal-end-time" class="block text-sm font-medium text-gray-700 mb-1">End Time:</label>
                        <input type="time" id="modal-end-time" name="end_time" required class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-[#4E3B2A] focus:border-[#4E3B2A]">
                    </div>
                    <div>
                        <label for="modal-break-duration" class="block text-sm font-medium text-gray-700 mb-1">Break (mins):</label>
                        <input type="number" id="modal-break-duration" name="break_duration" min="0" placeholder="e.g., 60" class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-[#4E3B2A] focus:border-[#4E3B2A]">
                    </div>
                    <div class="pt-2 flex justify-end space-x-3">
                        <button type="button" id="cancel-add-shift-modal-btn" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-[#594423] text-white rounded-md hover:bg-[#4E3B2A]">Add Shift</button>
                    </div>
                    <div id="add-shift-modal-status" class="text-sm text-center h-4 mt-2"></div>
                </form>
            </div>
        </div>

        <div id="create-timesheet-modal" class="fixed inset-0 z-[60] hidden items-center justify-center p-4 modal" aria-labelledby="create-timesheet-modal-title" role="dialog" aria-modal="true">
            <div id="create-timesheet-modal-overlay" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
            <div class="modal-content bg-white rounded-lg shadow-xl transform transition-all sm:max-w-lg w-full p-6 space-y-4">
                <div class="flex justify-between items-center pb-3 border-b">
                    <h3 class="text-lg font-medium text-[#4E3B2A] font-header" id="create-timesheet-modal-title">Create New Timesheet Period</h3>
                    <button type="button" id="close-create-timesheet-modal-btn" class="text-gray-400 hover:text-gray-600">
                        <span class="sr-only">Close</span>
                        <i class="fa-solid fa-times text-xl"></i>
                    </button>
                </div>
                <form id="create-timesheet-modal-form" class="space-y-4">
                    <div>
                        <label for="modal-ts-employee-select" class="block text-sm font-medium text-gray-700 mb-1">Employee:</label>
                        <select id="modal-ts-employee-select" name="employee_id" required class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-[#4E3B2A] focus:border-[#4E3B2A]">
                            <option value="">Loading employees...</option>
                        </select>
                    </div>
                    <div>
                        <label for="modal-ts-period-start" class="block text-sm font-medium text-gray-700 mb-1">Period Start Date:</label>
                        <input type="date" id="modal-ts-period-start" name="period_start_date" required class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-[#4E3B2A] focus:border-[#4E3B2A]">
                    </div>
                    <div>
                        <label for="modal-ts-period-end" class="block text-sm font-medium text-gray-700 mb-1">Period End Date:</label>
                        <input type="date" id="modal-ts-period-end" name="period_end_date" required class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-[#4E3B2A] focus:border-[#4E3B2A]">
                    </div>
                    <div class="pt-2 flex justify-end space-x-3">
                        <button type="button" id="cancel-create-timesheet-modal-btn" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-[#594423] text-white rounded-md hover:bg-[#4E3B2A]">Create Timesheet</button>
                    </div>
                    <div id="create-timesheet-modal-status" class="text-sm text-center h-4 mt-2"></div>
                </form>
            </div>
        </div>
        
        <div id="add-schedule-modal" class="fixed inset-0 z-[60] hidden items-center justify-center p-4 modal" aria-labelledby="add-schedule-modal-title" role="dialog" aria-modal="true">
            <div id="add-schedule-modal-overlay" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
            <div class="modal-content bg-white rounded-lg shadow-xl transform transition-all sm:max-w-xl w-full p-6 space-y-4"> <div class="flex justify-between items-center pb-3 border-b">
                    <h3 class="text-lg font-medium text-[#4E3B2A] font-header" id="add-schedule-modal-title">Assign New Schedule</h3>
                    <button type="button" id="close-add-schedule-modal-btn" class="text-gray-400 hover:text-gray-600">
                        <span class="sr-only">Close</span>
                        <i class="fa-solid fa-times text-xl"></i>
                    </button>
                </div>
                <form id="add-schedule-modal-form" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="modal-schedule-employee-select" class="block text-sm font-medium text-gray-700 mb-1">Employee:</label>
                            <select id="modal-schedule-employee-select" name="employee_id" required class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-[#4E3B2A] focus:border-[#4E3B2A]">
                                <option value="">Loading employees...</option>
                            </select>
                        </div>
                        <div>
                            <label for="modal-schedule-shift-select" class="block text-sm font-medium text-gray-700 mb-1">Shift (Optional):</label>
                            <select id="modal-schedule-shift-select" name="shift_id" class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-[#4E3B2A] focus:border-[#4E3B2A]">
                                <option value="">-- No Specific Shift --</option>
                                </select>
                        </div>
                         <div>
                            <label for="modal-schedule-workdays" class="block text-sm font-medium text-gray-700 mb-1">Work Days:</label>
                            <input type="text" id="modal-schedule-workdays" name="workdays" placeholder="e.g., Mon-Fri" class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-[#4E3B2A] focus:border-[#4E3B2A]">
                        </div>
                        <div>
                            <label for="modal-schedule-start-date" class="block text-sm font-medium text-gray-700 mb-1">Start Date:</label>
                            <input type="date" id="modal-schedule-start-date" name="start_date" required class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-[#4E3B2A] focus:border-[#4E3B2A]">
                        </div>
                        <div>
                            <label for="modal-schedule-end-date" class="block text-sm font-medium text-gray-700 mb-1">End Date (Optional):</label>
                            <input type="date" id="modal-schedule-end-date" name="end_date" class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-[#4E3B2A] focus:border-[#4E3B2A]">
                        </div>
                    </div>
                    <div class="pt-2 flex justify-end space-x-3">
                        <button type="button" id="cancel-add-schedule-modal-btn" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-[#594423] text-white rounded-md hover:bg-[#4E3B2A]">Add Schedule</button>
                    </div>
                    <div id="add-schedule-modal-status" class="text-sm text-center h-4 mt-2"></div>
                </form>
            </div>
        </div>
        
        <div id="employee-detail-modal" class="fixed inset-0 z-[60] hidden items-center justify-center p-4 modal" aria-labelledby="modal-title-employee" role="dialog" aria-modal="true">
            <div id="modal-overlay-employee" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
            <div class="modal-content bg-white rounded-lg shadow-xl transform transition-all sm:max-w-3xl w-full p-6 space-y-4 overflow-y-auto max-h-[90vh]">
                <div class="flex justify-between items-center pb-3 border-b">
                    <h3 class="text-lg font-medium text-[#4E3B2A] font-header" id="modal-title-employee">Employee Details</h3>
                    <button type="button" id="modal-close-btn-employee" class="text-gray-400 hover:text-gray-600">
                        <span class="sr-only">Close</span>
                        <i class="fa-solid fa-times text-xl"></i>
                    </button>
                </div>
                <div id="employee-detail-content" class="mt-4 space-y-3 text-sm">
                    <p>Loading details...</p> </div>
                <div class="pt-4 flex justify-end space-x-3 border-t mt-4">
                    <button type="button" id="modal-close-btn-employee-footer" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">Close</button>
                </div>
            </div>
        </div>

  </div> <!-- End app-container -->

   <!-- User Data Setup -->
   <script>
      // Set up user data from PHP session
      window.currentUser = {
        user_id: <?php echo json_encode($_SESSION['user_id'] ?? '1'); ?>,
        employee_id: <?php echo json_encode($_SESSION['employee_id'] ?? '1'); ?>,
        username: <?php echo json_encode($_SESSION['username'] ?? 'admin'); ?>,
        full_name: <?php echo json_encode($_SESSION['full_name'] ?? 'System Admin'); ?>,
        role_id: <?php echo json_encode($_SESSION['role_id'] ?? '1'); ?>,
        role_name: <?php echo json_encode($_SESSION['role_name'] ?? 'System Admin'); ?>,
        hmo_enrollment: <?php echo json_encode($_SESSION['hmo_enrollment'] ?? '1'); ?>
      };
      
      console.log("User data set:", window.currentUser);
   </script>

   <!-- Global Dashboard Functions -->
   <script>
     // Dashboard Data and Charts
     let dashboardCharts = {};

     // Function to display the modern dashboard
     function displayModernDashboard() {
       const mainContent = document.getElementById('main-content-area');
       if (mainContent) {
         // The modern dashboard content is already in the HTML
         // Just ensure it's visible
         mainContent.style.display = 'block';
         console.log('Modern dashboard displayed');
       }
     }

     // Load dashboard data from API
     async function loadDashboardData() {
       console.log('Loading dashboard data...');
       try {
         const response = await fetch('php/api/get_dashboard_summary.php');
         console.log('API response status:', response.status);
        const result = await response.json();
        console.log('API response data:', result);

        // If server indicates authentication required, redirect to login
        if (response.status === 401 || (result && result.error && /auth/i.test(result.error))) {
          console.info('Dashboard API requires authentication; redirecting to login.');
          window.location.href = 'login.php';
          return;
        }

        if (result.success && result.data) {
           const data = result.data;
           console.log('API data loaded successfully, updating stat cards...');
           // Update stat cards - check if elements exist first
           const totalEmployeesEl = document.getElementById('total-employees');
           const activeEmployeesEl = document.getElementById('active-employees');
           const pendingLeaveEl = document.getElementById('pending-leave');
           const recentHiresEl = document.getElementById('recent-hires');
           
           console.log('Stat card elements found:', {
             totalEmployees: !!totalEmployeesEl,
             activeEmployees: !!activeEmployeesEl,
             pendingLeave: !!pendingLeaveEl,
             recentHires: !!recentHiresEl
           });
           
           if (totalEmployeesEl) totalEmployeesEl.textContent = data.total_employees || '0';
           if (activeEmployeesEl) activeEmployeesEl.textContent = data.active_employees || '0';
           if (pendingLeaveEl) pendingLeaveEl.textContent = data.pending_leave_requests || '0';
           if (recentHiresEl) recentHiresEl.textContent = data.recent_hires_last_30_days || '0';
           
           console.log('Stat cards updated with real data');
           // Update charts with real data
           updateChartsWithData(data);
         } else {
           console.warn('Dashboard data not available, using mock data. Error:', result.error || 'Unknown error');
           loadMockData();
         }
       } catch (error) {
         console.error('Error loading dashboard data:', error);
         loadMockData();
       }
       
       // Final fallback - ensure data is loaded after a delay
       setTimeout(() => {
         const totalEmployeesEl = document.getElementById('total-employees');
         if (totalEmployeesEl && totalEmployeesEl.textContent === '-') {
           console.log('Data still showing dashes, loading mock data as final fallback');
           loadMockData();
         }
      }, 1000);
     }

     // Load mock data for demonstration
     function loadMockData() {
       console.log('Loading mock data...');
       const totalEmployeesEl = document.getElementById('total-employees');
       const activeEmployeesEl = document.getElementById('active-employees');
       const pendingLeaveEl = document.getElementById('pending-leave');
       const recentHiresEl = document.getElementById('recent-hires');
       
       console.log('Mock data elements found:', {
         totalEmployees: !!totalEmployeesEl,
         activeEmployees: !!activeEmployeesEl,
         pendingLeave: !!pendingLeaveEl,
         recentHires: !!recentHiresEl
       });
       
       if (totalEmployeesEl) totalEmployeesEl.textContent = '247';
       if (activeEmployeesEl) activeEmployeesEl.textContent = '231';
       if (pendingLeaveEl) pendingLeaveEl.textContent = '12';
       if (recentHiresEl) recentHiresEl.textContent = '8';
       
       console.log('Mock data loaded');
     }

     // Initialize all charts
     function initializeCharts() {
       try {
         initializeDepartmentChart();
         initializeHiresTrendChart();
         initializeTopDepartmentsChart();
       } catch (error) {
         console.error('Error initializing charts:', error);
       }
     }

     // Department Distribution Chart (Chart.js)
     function initializeDepartmentChart() {
       const ctx = document.getElementById('departmentChart');
       if (!ctx) return;

       // Destroy existing chart if it exists
       if (dashboardCharts.department) {
         dashboardCharts.department.destroy();
         dashboardCharts.department = null;
       }

       dashboardCharts.department = new Chart(ctx, {
         type: 'doughnut',
         data: {
           labels: ['Active', 'Inactive', 'On Leave'],
           datasets: [{
             data: [85, 10, 5],
             backgroundColor: ['#3b82f6', '#ef4444', '#f59e0b'],
             borderWidth: 0
           }]
         },
         options: {
           responsive: true,
           maintainAspectRatio: false,
           plugins: {
             legend: {
               position: 'bottom',
               labels: {
                 padding: 20,
                 usePointStyle: true
               }
             }
           }
         }
       });
     }

     // New Hires Trend Chart (Chart.js)
     function initializeHiresTrendChart() {
       const ctx = document.getElementById('hiresTrendChart');
       if (!ctx) return;

       // Destroy existing chart if it exists
       if (dashboardCharts.hiresTrend) {
         dashboardCharts.hiresTrend.destroy();
         dashboardCharts.hiresTrend = null;
       }

       dashboardCharts.hiresTrend = new Chart(ctx, {
         type: 'line',
         data: {
           labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
           datasets: [{
             label: 'New Hires',
             data: [12, 19, 8, 15, 22, 18],
             borderColor: '#3b82f6',
             backgroundColor: 'rgba(59, 130, 246, 0.1)',
             tension: 0.4,
             fill: true
           }]
         },
         options: {
           responsive: true,
           maintainAspectRatio: false,
           plugins: {
             legend: {
               display: false
             }
           },
           scales: {
             y: {
               beginAtZero: true
             }
           }
         }
       });
     }

     // Top Departments Chart (ApexCharts)
     function initializeTopDepartmentsChart() {
       const element = document.getElementById('topDepartmentsChart');
       if (!element) return;

       // Destroy existing chart if it exists
       if (dashboardCharts.topDepartments) {
         dashboardCharts.topDepartments.destroy();
         dashboardCharts.topDepartments = null;
       }

       dashboardCharts.topDepartments = new ApexCharts(element, {
         chart: {
           type: 'bar',
           height: 250
         },
         series: [{
           name: 'Performance Score',
           data: [95, 87, 82, 78, 72]
         }],
         xaxis: {
           categories: ['Emergency', 'Surgery', 'Cardiology', 'Pediatrics', 'Radiology']
         },
         colors: ['#3b82f6'],
         plotOptions: {
           bar: {
             borderRadius: 4
           }
         }
       });

       dashboardCharts.topDepartments.render();
     }

     // Update charts with real data
     function updateChartsWithData(data) {
       console.log('Updating charts with real data:', data);
       
       // Update department chart if data available
       if (data.charts && data.charts.employee_status_distribution) {
         const chart = dashboardCharts.department;
         if (chart) {
           chart.data.labels = data.charts.employee_status_distribution.labels;
           chart.data.datasets[0].data = data.charts.employee_status_distribution.data;
           chart.update();
           console.log('Department chart updated with real data');
         }
       }
       
       // Update department distribution chart if available
       if (data.charts && data.charts.employee_distribution_by_department) {
         const chart = dashboardCharts.department;
         if (chart) {
           chart.data.labels = data.charts.employee_distribution_by_department.labels;
           chart.data.datasets[0].data = data.charts.employee_distribution_by_department.data;
           chart.update();
           console.log('Department distribution chart updated with real data');
         }
       }
     }

     // Load recent activities
     async function loadRecentActivities() {
       const container = document.getElementById('recent-activities');
       if (!container) return;

       // Mock recent activities
       const activities = [
         { type: 'hire', message: 'New employee John Smith joined Emergency Department', time: '2 hours ago' },
         { type: 'leave', message: 'Sarah Johnson submitted a leave request', time: '4 hours ago' },
         { type: 'update', message: 'Employee profile updated for Mike Wilson', time: '6 hours ago' },
         { type: 'system', message: 'Monthly payroll run completed', time: '1 day ago' }
       ];

       container.innerHTML = activities.map(activity => `
         <div class="flex items-start space-x-3 p-3 bg-white rounded-lg border border-gray-200 hover:shadow-sm transition-shadow">
           <div class="flex-shrink-0">
             <div class="w-2 h-2 bg-blue-500 rounded-full mt-2"></div>
           </div>
           <div class="flex-1 min-w-0">
             <p class="text-sm text-gray-900">${activity.message}</p>
             <p class="text-xs text-gray-500 mt-1">${activity.time}</p>
           </div>
         </div>
       `).join('');

       // Try to load real HMO enrollments as additional activity
       try {
         const response = await fetch('php/api/hmo_enrollments.php', { credentials: 'include' });
         const data = await response.json();
         
         if (data.success && data.enrollments && data.enrollments.length > 0) {
           const recentEnrollment = data.enrollments[0];
           const employeeName = `${recentEnrollment.FirstName || ''} ${recentEnrollment.LastName || ''}`.trim();
           const planName = recentEnrollment.PlanName || 'N/A';
           const enrollDate = recentEnrollment.EnrollmentDate || recentEnrollment.EffectiveDate || new Date().toISOString();
           
           const enrollmentActivity = `
             <div class="flex items-start space-x-3 p-3 bg-white rounded-lg border border-gray-200 hover:shadow-sm transition-shadow">
               <div class="flex-shrink-0">
                 <div class="w-2 h-2 bg-green-500 rounded-full mt-2"></div>
               </div>
               <div class="flex-1 min-w-0">
                 <p class="text-sm text-gray-900">New HMO enrollment: ${employeeName} - ${planName}</p>
                 <p class="text-xs text-gray-500 mt-1">${new Date(enrollDate).toLocaleDateString()}</p>
            </div>
          </div>
        `;
           container.insertAdjacentHTML('afterbegin', enrollmentActivity);
         }
       } catch (error) {
         console.error('Error loading HMO enrollments for activities:', error);
       }
     }

     // Navigation function for clickable cards
     function navigateToModule(module) {
       console.log(`Navigating to module: ${module}`);
       
       // Map module names to existing functions
       const moduleMap = {
         'employees': 'displayEmployeeSection',
         'org-structure': 'displayOrgStructureSection',
         'analytics': 'displayAnalyticsDashboardsSection',
         'notifications': 'displayNotificationsSection',
         'leave': 'displayLeaveRequestsSection',
         'payroll': 'displayPayrollRunsSection',
         'hmo': 'displayHMODashboardSection'
       };
       
       const functionName = moduleMap[module];
       if (functionName && typeof window[functionName] === 'function') {
         window[functionName]();
       } else {
         console.warn(`Module ${module} not found or function not available`);
       }
     }

     // Make functions globally available
     window.displayModernDashboard = displayModernDashboard;
     window.loadDashboardData = loadDashboardData;
     window.initializeCharts = initializeCharts;
     window.loadRecentActivities = loadRecentActivities;
     window.navigateToModule = navigateToModule;
     
     // Define displayDashboardSection function for the dashboard link
     window.displayDashboardSection = async function() {
       console.log('Displaying modern dashboard section...');
       displayModernDashboard();
       if (document.getElementById('total-employees')) {
         loadDashboardData();
         initializeCharts();
         loadRecentActivities();
       }
     };

     // Logout function
     function handleLogout(event) {
       event.preventDefault();
       if (confirm('Are you sure you want to logout?')) {
         window.location.href = 'php/api/logout.php';
       }
     }

     // Initialize dashboard on page load
     document.addEventListener('DOMContentLoaded', function() {
       console.log('DOM loaded - initializing dashboard...');
       
       // Set up dashboard link click handler
       const dashboardLink = document.getElementById('dashboard-link');
       if (dashboardLink) {
         dashboardLink.addEventListener('click', function(e) {
           e.preventDefault();
           console.log('Dashboard link clicked');
           displayModernDashboard();
           if (document.getElementById('total-employees')) {
             loadDashboardData();
             initializeCharts();
             loadRecentActivities();
           }
         });
       }
       
       setTimeout(() => {
         displayModernDashboard();
         if (document.getElementById('total-employees')) {
           loadDashboardData();
           initializeCharts();
           loadRecentActivities();
         } else {
           console.log('Dashboard elements not ready yet, retrying...');
           setTimeout(() => {
             if (document.getElementById('total-employees')) {
               loadDashboardData();
               initializeCharts();
               loadRecentActivities();
             } else {
               console.log('Dashboard elements still not ready, loading mock data directly');
               loadMockData();
             }
           }, 500);
         }
         
         // Additional fallback - load mock data after a delay regardless
         setTimeout(() => {
           const totalEmployeesEl = document.getElementById('total-employees');
           if (totalEmployeesEl && totalEmployeesEl.textContent === '-') {
             console.log('Loading mock data as additional fallback');
             loadMockData();
           }
         }, 2000);
       }, 100);
     });

     // Make logout function globally available
     window.handleLogout = handleLogout;
  </script>
  
  <!-- Load main.js module -->
  <script type="module" src="js/main.js"></script>
</body>
</html>