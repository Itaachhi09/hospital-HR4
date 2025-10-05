
<?php
session_start();
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" xintegrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400..900&display=swap" rel="stylesheet">
    <style>
        /* Apply Georgia to the entire body */
        body {
            font-family: 'Georgia', serif;
        }
        /* Apply Cinzel to header tags and elements with .font-header class */
        h1, h2, h3, h4, h5, h6, .font-header {
            font-family: 'Cinzel', serif;
        }
        
        /* Ensure main content uses Georgia unless overridden */
        #main-content-area, #main-content-area p, #main-content-area label, #main-content-area span, #main-content-area td, #main-content-area button, #main-content-area select, #main-content-area input, #main-content-area textarea {
             font-family: 'Georgia', serif;
        }
        /* Ensure specific headers use Cinzel */
         #main-content-area h3, #main-content-area h4, #main-content-area h5 { /* Added h4, h5 */
             font-family: 'Cinzel', serif;
         }
         #page-title {
            font-family: 'Cinzel', serif;
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
  <!-- Module Loading -->
<script type="module">
    import { initializeApp, initializeSectionDisplayFunctions } from '/hospital-HR4/js/main.js';
    
    // Initialize the application after modules are loaded
  document.addEventListener('DOMContentLoaded', async () => {
    try {
      console.log("Initializing application...");
      // Ensure section display functions are registered before app initialization
      await initializeSectionDisplayFunctions();
      await initializeApp();
      console.log("Application initialized successfully");
        } catch (error) {
            console.error("Failed to initialize application:", error);
            document.getElementById('main-content-area').innerHTML = 
                `<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    <p class="font-bold">Error Loading Application</p>
                    <p>Please try refreshing the page. If the problem persists, contact support.</p>
                    <div class="mt-2 text-sm">
                        <strong>Error Details:</strong><br>
                        ${error.message}
                    </div>
                </div>`;
        }
    });
</script>
</head>


<body class="h-screen flex bg-slate-50" x-data="{ sidebarOpen: true, open: '' }">
  <div id="app-container" class="flex w-full h-full">

  <!-- Sidebar (fixed to left) -->
  <aside 
    id="sidebar"
    class="fixed left-0 top-0 bottom-0 flex flex-col bg-[#0b1b3b] text-white transition-all duration-300 z-50"
    :class="sidebarOpen ? 'w-64' : 'w-16'"
    style="width: 16rem;"
  >
    <!-- Sidebar Header -->
    <div class="flex items-center justify-between px-4 py-4 bg-gradient-to-r from-[#0b1b3b] to-[#102650]">
      <div class="flex items-center gap-3" x-show="sidebarOpen" x-transition>
        <div class="h-10 w-10 rounded-full grid place-items-center border-2 border-[#d4af37] bg-white">
         <img src="logo-removebg-preview (1).png" alt="Logo" class=" object-contain">

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
        <a href="#" id="dashboard-link" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-white/10">
          <!-- Home Icon -->
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[#d4af37]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l9-9 9 9M4 10v10a1 1 0 001 1h3m10-11v11a1 1 0 01-1 1h-3m-6 0h6"/>
          </svg>
          <span x-show="sidebarOpen" x-transition>Overview / Dashboard</span>
        </a>
        <a href="#" id="notifications-link" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-white/10">
          <!-- Bell Icon -->
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[#d4af37]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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
            <a href="#" id="employees-link" class="block hover:text-[#d4af37]">Employees</a>
            <a href="#" id="documents-link" class="block hover:text-[#d4af37]">Documents</a>
            <a href="#" id="org-structure-link" class="block hover:text-[#d4af37]">Org Structure</a>
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
            <a href="#" id="comp-plans-link" class="block hover:text-[#d4af37]">Compensation Plans</a>
            <a href="#" id="salary-adjust-link" class="block hover:text-[#d4af37]">Salary Adjustments</a>
            <a href="#" id="incentives-link" class="block hover:text-[#d4af37]">Incentives</a>
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
    <header class="flex items-center justify-between px-6 py-6 border-b bg-white shadow">
      <div>
        <h1 class="text-2xl font-bold text-[#0b1b3b]">Hospital Dashboard</h1>
        <p class="text-xs text-slate-500">Integrity • Service • Commitment • Respect • Compassion</p>
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
        <p class="text-slate-600">Welcome to HMVH Hospital Dashboard. Select a module from the sidebar.</p>
      </div>
    </section>
      <script>
        document.addEventListener('DOMContentLoaded', function(){
          try {
            fetch(`php/api/get_employee_enrollments.php`)
              .then(r=>r.json())
              .then(data=>{
                if (!data.success) return;
                const enrollments = data.enrollments || [];
                const container = document.getElementById('main-content-area');
                if (container) {
                  const card = document.createElement('div');
                  card.className='bg-white rounded-lg shadow p-4 mb-4';
                  if (enrollments.length===0) {
                    card.innerHTML = '<h3 class="text-lg font-semibold">HMO Coverage</h3><p class="text-sm">No active enrollments found.</p>';
                  } else {
                    const list = enrollments.slice(0,5).map(e=>`<li class="text-sm">${e.EmployeeName}: <strong>${e.PlanName}</strong> (${e.Status})</li>`).join('');
                    card.innerHTML = `\n                    <h3 class="text-lg font-semibold">Recent HMO Enrollments</h3>\n                    <ul class="mt-2 space-y-1">${list}</ul>\n                  `;
                  }
                  container.prepend(card);
                }
              }).catch(e=>console.error(e));
          } catch(e){console.error(e);} 
        });
      </script>
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

  <!-- JavaScript Modules -->
  <!-- main.js is already included in the head with defer; remove duplicate to avoid double-loading -->
  <!-- Modern module initialization -->
  <script type="module">
    function initializeApplication(currentUser) {
    
    // Wait for the main.js to load and then set up proper functionality
    document.addEventListener('DOMContentLoaded', function() {
      console.log("DOM loaded - setting up original functionality");
      
      // Set up user data from PHP session
      window.currentUser = {
        user_id: '<?php echo $_SESSION['user_id'] ?? '1'; ?>',
        employee_id: '<?php echo $_SESSION['employee_id'] ?? '1'; ?>',
        username: '<?php echo $_SESSION['username'] ?? 'admin'; ?>',
        full_name: '<?php echo $_SESSION['full_name'] ?? 'System Admin'; ?>',
        role_id: '<?php echo $_SESSION['role_id'] ?? '1'; ?>',
        role_name: '<?php echo $_SESSION['role_name'] ?? 'System Admin'; ?>',
        hmo_enrollment: '<?php echo $_SESSION['hmo_enrollment'] ?? '1'; ?>'
      };
      
      console.log("User data set:", window.currentUser);
      
      // Wait a bit for main.js to load, then set up click handlers
      setTimeout(function() {
        setupOriginalFunctionality();
      }, 1000);
    });
    
    function setupOriginalFunctionality() {
      console.log("Setting up module functionality...");
      
      // Setup module click handlers
      const setupModuleClickHandler = (elementId, displayFunction) => {
        const element = document.getElementById(elementId);
        if (element && typeof displayFunction === 'function') {
          element.addEventListener('click', (e) => {
            e.preventDefault();
            displayFunction();
          });
        }
      };

      // Set up all module handlers
      setupModuleClickHandler('dashboard-link', window.displayDashboardSection);
      setupModuleClickHandler('employees-link', window.displayEmployeeSection);
      setupModuleClickHandler('documents-link', window.displayDocumentsSection);
      setupModuleClickHandler('org-structure-link', window.displayOrgStructureSection);
      setupModuleClickHandler('attendance-link', window.displayAttendanceSection);
      setupModuleClickHandler('timesheets-link', window.displayTimesheetsSection);
      setupModuleClickHandler('schedules-link', window.displaySchedulesSection);
      setupModuleClickHandler('shifts-link', window.displayShiftsSection);
      setupModuleClickHandler('payroll-runs-link', window.displayPayrollRunsSection);
      setupModuleClickHandler('salaries-link', window.displaySalariesSection);
      setupModuleClickHandler('bonuses-link', window.displayBonusesSection);
      setupModuleClickHandler('deductions-link', window.displayDeductionsSection);
      setupModuleClickHandler('payslips-link', window.displayPayslipsSection);
      setupModuleClickHandler('submit-claim-link', window.displaySubmitClaimSection);
      setupModuleClickHandler('my-claims-link', window.displayMyClaimsSection);
      setupModuleClickHandler('claims-approval-link', window.displayClaimsApprovalSection);
      setupModuleClickHandler('claim-types-admin-link', window.displayClaimTypesAdminSection);
      setupModuleClickHandler('leave-requests-link', window.displayLeaveRequestsSection);
      setupModuleClickHandler('leave-balances-link', window.displayLeaveBalancesSection);
      setupModuleClickHandler('leave-types-link', window.displayLeaveTypesAdminSection);
      setupModuleClickHandler('comp-plans-link', window.displayCompensationPlansSection);
      setupModuleClickHandler('salary-adjust-link', window.displaySalaryAdjustmentsSection);
      setupModuleClickHandler('incentives-link', window.displayIncentivesSection);
      setupModuleClickHandler('analytics-dashboards-link', window.displayAnalyticsDashboardsSection);
      setupModuleClickHandler('analytics-reports-link', window.displayAnalyticsReportsSection);
      setupModuleClickHandler('analytics-metrics-link', window.displayAnalyticsMetricsSection);
      setupModuleClickHandler('user-management-link', window.displayUserManagementSection);
      setupModuleClickHandler('hmo-providers-link', window.displayHMOProvidersSection);
      setupModuleClickHandler('hmo-plans-link', window.displayHMOPlansSection);
      setupModuleClickHandler('hmo-enrollments-link', window.displayHMOEnrollmentsSection);
      setupModuleClickHandler('hmo-claims-admin-link', window.displayHMOClaimsApprovalSection);
      setupModuleClickHandler('hmo-dashboard-link', window.displayHMODashboardSection);
      setupModuleClickHandler('my-hmo-benefits-link', window.displayEmployeeHMOSection);
      setupModuleClickHandler('my-hmo-claims-link', window.displayEmployeeHMOClaimsSection);
      setupModuleClickHandler('submit-hmo-claim-link', window.displaySubmitHMOClaimSection);

      // Keep the fixed sidebar width in sync with Alpine's class toggles
      try {
        const sidebarEl = document.getElementById('sidebar');
        if (sidebarEl) {
          const mo = new MutationObserver(() => {
            const classList = sidebarEl.className;
            sidebarEl.style.width = classList.includes('w-64') ? '16rem' : '4rem';
          });
          mo.observe(sidebarEl, { attributes: true, attributeFilter: ['class'] });
          sidebarEl.style.width = sidebarEl.className.includes('w-64') ? '16rem' : '4rem';
        }
      } catch (err) { console.warn('Sidebar width observer failed', err); }
      
      // Employees click handler
      const employeesLink = document.getElementById('employees-link');
      if (employeesLink) {
        console.log("Setting up employees click handler");
        employeesLink.addEventListener('click', function(e) {
          e.preventDefault();
          console.log("Employees clicked - calling original function");
          
          // Try to call the original employees function
          if (typeof window.displayEmployeeSection === 'function') {
            window.displayEmployeeSection();
          } else {
            // Fallback to direct call
            loadEmployeesContent();
          }
        });
      }
      
      // Set up all other module click handlers
      const moduleHandlers = {
        'documents-link': 'displayDocumentsSection',
        'org-structure-link': 'displayOrgStructureSection',
        'attendance-link': 'displayAttendanceSection',
        'timesheets-link': 'displayTimesheetsSection',
        'schedules-link': 'displaySchedulesSection',
        'shifts-link': 'displayShiftsSection',
        'payroll-runs-link': 'displayPayrollRunsSection',
        'salaries-link': 'displaySalariesSection',
        'bonuses-link': 'displayBonusesSection',
        'deductions-link': 'displayDeductionsSection',
        'payslips-link': 'displayPayslipsSection',
        'submit-claim-link': 'displaySubmitClaimSection',
        'my-claims-link': 'displayMyClaimsSection',
        'claims-approval-link': 'displayClaimsApprovalSection',
  'claim-types-admin-link': 'displayClaimTypesAdminSection',
  'hmo-providers-link': 'displayHMOProvidersSection',
  'hmo-plans-link': 'displayHMOPlansSection',
  'hmo-enrollments-link': 'displayHMOEnrollmentsSection',
  'hmo-claims-admin-link': 'displayHMOClaimsApprovalSection',
  'hmo-dashboard-link': 'displayHMODashboardSection',
        'leave-requests-link': 'displayLeaveRequestsSection',
        'leave-balances-link': 'displayLeaveBalancesSection',
        'leave-types-link': 'displayLeaveTypesAdminSection',
        'comp-plans-link': 'displayCompensationPlansSection',
        'salary-adjust-link': 'displaySalaryAdjustmentsSection',
        'incentives-link': 'displayIncentivesSection',
        'analytics-dashboards-link': 'displayAnalyticsDashboardsSection',
        'analytics-reports-link': 'displayAnalyticsReportsSection',
        'analytics-metrics-link': 'displayAnalyticsMetricsSection',
        'user-management-link': 'displayUserManagementSection'
      };
      
      Object.keys(moduleHandlers).forEach(linkId => {
        const link = document.getElementById(linkId);
        if (link) {
          link.addEventListener('click', function(e) {
            e.preventDefault();
            const functionName = moduleHandlers[linkId];
            console.log(`${linkId} clicked - calling ${functionName}`);
            
            if (typeof window[functionName] === 'function') {
              window[functionName]();
            } else {
              console.warn(`Function ${functionName} not found, using fallback`);
              loadModuleContent(linkId, functionName);
            }
          });
        }
      });
    }
    
    // Handle errors gracefully if a module fails to load
    function showModuleError(mainContent, moduleName, error) {
      if (mainContent) {
        mainContent.innerHTML = `
          <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-xl font-bold text-gray-800 mb-4">${moduleName}</h3>
            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
              <p class="text-red-800 text-sm">
                <strong>Error:</strong> Failed to load module.<br>
                <strong>Details:</strong> ${error?.message || 'Unknown error'}<br>
                Please refresh the page or contact support if the issue persists.
              </p>
            </div>
          </div>
        `;
      }
    }
  </script>
</body>
</html>