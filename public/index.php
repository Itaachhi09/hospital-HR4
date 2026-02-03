<?php
// No GET parameter login bypass - all authentication goes through the API
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Hospital HR System Login</title>
  <script src="https://cdn.tailwindcss.com"></script>

  <style>
  :root{
  --hvh-navy:#0A1D4D;
  --hvh-navy-900:#07143A;
  --logo-url: url("https://hebbkx1anhila5yf.public.blob.vercel-storage.com/hvh-logo-Qb4GMWRmiAugUk366zbp9Rmm3TLZww.png");
}

.hvh-bg{
  min-height:100vh;
  background: linear-gradient(180deg, var(--hvh-navy) 0%, var(--hvh-navy-900) 100%);
  position:relative;
  overflow:hidden;
}

.hvh-bg::before{
  content:"";
  position:fixed; inset:0;
  pointer-events:none; z-index:0;
  background-image: var(--logo-url);
  background-repeat: repeat;
  background-size: 200px 200px;
  opacity:.18;
  filter: blur(1px) brightness(1.1);
  animation: logosShift 40s linear infinite alternate;
}

.hvh-bg::after{
  content:"";
  position:fixed; inset:-20%;
  pointer-events:none; z-index:1;
  background:
    radial-gradient(40vmax 30vmax at 20% 25%, rgba(255,255,255,.14), transparent 60%),
    radial-gradient(35vmax 28vmax at 80% 70%, rgba(212,175,55,.18), transparent 65%),
    radial-gradient(28vmax 24vmax at 50% 85%, rgba(255,255,255,.10), transparent 70%);
  filter: blur(35px);
  animation: hvhGlow 25s ease-in-out infinite alternate;
}

@keyframes logosShift {
  0%   { background-position: 0 0; }
  100% { background-position: -200px -150px; }
}
@keyframes hvhGlow {
  0%   { transform: scale(1) translate(0,0); }
  100% { transform: scale(1.05) translate(-2%, -1%); }
}

@media (max-width:640px){
  .hvh-bg::before{
    background-size: 150px 150px;
    opacity:.14;
  }
}

  /* ============================================================ */
  /* Custom Healthcare Dark Navy Blue Theme - Visual Enhancement */
  /* ============================================================ */
  
  .healthcare-gradient {
      background: linear-gradient(135deg, #0f1f3d 0%, #0f1f3d 25%, #1a2d4d 50%, #0f1f3d 75%, #0f1f3d 100%);
      position: relative;
      overflow: hidden;
  }

  .healthcare-gradient::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Ccircle cx='30' cy='30' r='2'/%3E%3Cpath d='M30 20v20M20 30h20'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
      opacity: 0.1;
  }

  .bg-blue-40 {
      background: linear-gradient(180deg, #0f1f3d 0%, #0f1f3d 30%, #1a2d4d 60%, #2a3f5f 100%);
  }

  .sidebar-shadow {
      box-shadow: 0 0 30px rgba(15, 31, 61, 0.3), inset -2px 0 0 rgba(26, 45, 77, 0.2);
  }

  .nav-item {
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      position: relative;
      overflow: hidden;
      border: 1px solid transparent;
  }

  .nav-item::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(61, 90, 128, 0.12), transparent);
      transition: left 0.5s;
  }

  .nav-item:hover::before {
      left: 100%;
  }

  .nav-item:hover {
      background: linear-gradient(135deg, rgba(61, 90, 128, 0.15) 0%, rgba(45, 62, 80, 0.08) 100%) !important;
      border-color: rgba(61, 90, 128, 0.25);
      transform: translateX(3px);
      box-shadow: 0 4px 12px rgba(61, 90, 128, 0.25);
  }

  .section-divider {
      height: 1px;
      background: linear-gradient(90deg, transparent 0%, rgba(61, 90, 128, 0.4) 50%, transparent 100%);
      margin: 1.5rem 0;
  }

  .nav-item .bg-blue-100 {
      background: linear-gradient(135deg, rgba(61, 90, 128, 0.15) 0%, rgba(45, 62, 80, 0.12) 100%);
      border: 1px solid rgba(61, 90, 128, 0.2);
  }

  .nav-item:hover .bg-blue-100 {
      background: linear-gradient(135deg, rgba(61, 90, 128, 0.25) 0%, rgba(45, 62, 80, 0.2) 100%);
      border-color: rgba(61, 90, 128, 0.35);
  }

  .text-slate-700 {
      color: #f1f5f9 !important;
  }

  .text-slate-600 {
      color: #cbd5e1 !important;
  }

  .hover\:text-blue-800:hover {
      color: #60a5fa !important;
  }

  .hover\:text-blue-700:hover {
      color: #60a5fa !important;
  }

  .text-blue-400 {
      color: #60a5fa !important;
  }

  /* Status badges with gradient and animation */
  .status-badge {
      background: linear-gradient(45deg, #059669, #10b981);
      color: white;
      font-size: 0.75rem;
      padding: 0.25rem 0.5rem;
      border-radius: 9999px;
      font-weight: 600;
      animation: pulse 2s infinite;
  }

  .alert-badge {
      background: linear-gradient(45deg, #dc2626, #ef4444);
      color: white;
      font-size: 0.75rem;
      padding: 0.25rem 0.5rem;
      border-radius: 9999px;
      font-weight: 600;
      animation: pulse 2s infinite;
  }

  .count-badge {
      background: linear-gradient(45deg, #7c3aed, #8b5cf6);
      color: white;
      font-size: 0.75rem;
      padding: 0.25rem 0.5rem;
      border-radius: 9999px;
      font-weight: 600;
  }

  @keyframes pulse {
      0%, 100% {
          opacity: 1;
      }
      50% {
          opacity: 0.8;
      }
  }

  /* Submenu styling */
  .submenu-item {
      color: #94a3b8 !important;
      transition: all 0.2s ease;
  }

  .submenu-item:hover {
      color: #60a5fa !important;
      background: rgba(61, 90, 128, 0.15) !important;
  }

  .border-blue-100 {
      border-color: rgba(61, 90, 128, 0.4) !important;
  }

  /* Custom scrollbar styling */
  aside::-webkit-scrollbar {
      width: 4px;
  }

  aside::-webkit-scrollbar-track {
      background: rgba(10, 25, 41, 0.2);
  }

  aside::-webkit-scrollbar-thumb {
      background: linear-gradient(180deg, #3d5a80, #2d3e50);
      border-radius: 2px;
  }

  /* Hamburger menu toggle */
  .sidebar-hidden {
      display: none !important;
  }

  .sidebar-visible {
      display: block !important;
  }

  /* Ensure hamburger button is always visible */
  #hamburger-menu {
      display: block !important;
  }

  </style>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const form = document.querySelector('form');
      const emailInput = document.getElementById('email');
      const passwordInput = document.getElementById('password');

      // Get base path for API calls - dynamically detect from current URL
      function getBasePath() {
        const path = window.location.pathname;
        const parts = path.split('/').filter(p => p);
        if (parts.includes('public')) {
          // We're in /public/, so base is everything before 'public'
          const publicIndex = parts.indexOf('public');
          return '/' + parts.slice(0, publicIndex).join('/') + '/';
        }
        // If no 'public' found, assume we're at root level
        return parts.length > 0 ? '/' + parts[0] + '/' : '/';
      }

      form.addEventListener('submit', function(e) {
        e.preventDefault();

        const email = emailInput.value;
        const password = passwordInput.value;

        if (!email || !password) {
          Swal.fire('Error', 'Please fill in all fields', 'error');
          return;
        }

        // Submit to login API
        // Construct API URL - use absolute path based on current location
        const currentPath = window.location.pathname;
        let apiUrl;
        
        // Try to construct URL intelligently
        if (currentPath.includes('/public/')) {
          // We're in /public/, so API is at /public/api/
          const pathParts = currentPath.split('/public/')[0];
          apiUrl = pathParts + '/public/api/auth/login';
        } else {
          // Fallback: construct from current path
          const basePath = getBasePath();
          apiUrl = window.location.origin + basePath + 'public/api/auth/login';
        }
        
        console.log('=== LOGIN DEBUG INFO ===');
        console.log('Current pathname:', currentPath);
        console.log('Current origin:', window.location.origin);
        console.log('Constructed API URL:', apiUrl);
        console.log('========================');
        
        fetch(apiUrl, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          credentials: 'include',  // Important: Include cookies for session management
          body: JSON.stringify({ username: email, password: password })
        })
        .then(async response => {
          // Check if response is ok (status 200-299)
          if (!response.ok) {
            // Try to get error message from response
            let errorText = '';
            try {
              errorText = await response.text();
              const errorData = JSON.parse(errorText);
              throw new Error(errorData.message || errorData.error || `HTTP ${response.status}: ${response.statusText}`);
            } catch (parseError) {
              throw new Error(`HTTP ${response.status}: ${response.statusText}. ${errorText.substring(0, 200)}`);
            }
          }
          console.log('Response status:', response.status);
          console.log('Response URL:', response.url);
          
          // Always try to get the response text first
          const text = await response.text();
          console.log('Response text:', text.substring(0, 200));
          
          // Check if response is actually JSON
          const contentType = response.headers.get('content-type');
          if (!contentType || !contentType.includes('application/json')) {
            console.error('Non-JSON response received. Status:', response.status);
            console.error('Content-Type:', contentType);
            console.error('Full response text:', text);
            throw new Error('Server returned non-JSON response. Status: ' + response.status + '. Check console for details.');
          }
          
          // Try to parse JSON
          try {
            return JSON.parse(text);
          } catch (e) {
            console.error('JSON parse error:', e);
            console.error('Response text that failed to parse:', text);
            throw new Error('Failed to parse JSON response: ' + e.message);
          }
        })
        .then(data => {
          if (data.two_factor_required) {
            // Show 2FA code input prompt
            Swal.fire({
              title: 'Two-Factor Authentication',
              text: data.message,
              input: 'text',
              inputLabel: 'Enter the 6-digit code sent to your email',
              inputAttributes: {
                maxlength: 6,
                autocapitalize: 'off',
                autocorrect: 'off'
              },
              showCancelButton: true,
              confirmButtonText: 'Verify',
              showLoaderOnConfirm: true,
              preConfirm: (code) => {
                if (!code || code.length !== 6 || !/^\d{6}$/.test(code)) {
                  Swal.showValidationMessage('Please enter a valid 6-digit code');
                  return false;
                }
                // Verify 2FA code via API (2FA is disabled, but keeping endpoint for compatibility)
                const basePath = getBasePath();
                return fetch(basePath + '/public/api/auth/verify-2fa', {
                  method: 'POST',
                  headers: {
                    'Content-Type': 'application/json'
                  },
                  credentials: 'include',  // Important: Include cookies for session management
                  body: JSON.stringify({ user_id: data.user_id_temp, code: code })
                })
                .then(response => {
                  if (!response.ok) {
                    throw new Error('Invalid or expired code');
                  }
                  return response.json();
                })
                .catch(error => {
                  Swal.showValidationMessage(`Verification failed: ${error.message}`);
                });
              },
              allowOutsideClick: () => !Swal.isLoading()
            }).then((result) => {
              if (result.isConfirmed && result.value) {
                Swal.fire('Success', 'Login successful!', 'success').then(() => {
                  // Redirect after successful 2FA verification
                  if (result.value.redirect_url) {
                    window.location.href = result.value.redirect_url;
                  } else {
                    window.location.href = 'index.php';
                  }
                });
              }
            });
          } else if (data.success === true || data.message === 'Login successful.' || data.message === 'Login successful') {
            // Debug: Log the response data
            console.log('=== LOGIN SUCCESS ===');
            console.log('Full response data:', data);
            console.log('data.data:', data.data);
            console.log('data.data.user:', data.data?.user);
            console.log('User role:', data.data?.user?.role);
            
            // Set session data if provided
            const userData = data.data?.user || data.user || data.data;
            if (userData) {
              // Store user data in sessionStorage for client-side use
              sessionStorage.setItem('user', JSON.stringify(userData));
              console.log('User data stored in sessionStorage');
            }
            
            // Determine redirect URL - check multiple possible response structures
            let redirectUrl = 'admin_landing.php'; // default
            let userRole = null;
            
            // Try different possible response structures
            if (data.data?.user?.role) {
              userRole = data.data.user.role;
            } else if (data.user?.role) {
              userRole = data.user.role;
            } else if (data.data?.role) {
              userRole = data.data.role;
            }
            
            console.log('Detected user role:', userRole);
            
            if (userRole) {
              // Map roles to landing pages
              if (userRole === 'System Admin' || 
                  userRole === 'HR Manager' || 
                  userRole === 'HR Admin' || 
                  userRole === 'HR Director' ||
                  userRole === 'Hospital Director') {
                redirectUrl = 'admin_landing.php';
              } else if (userRole === 'Employee') {
                redirectUrl = 'employee_landing.php';
              } else {
                redirectUrl = data.redirect_url || 'admin_landing.php';
              }
            } else if (data.redirect_url) {
              redirectUrl = data.redirect_url;
            }
            
            console.log('Final redirect URL:', redirectUrl);
            
            // IMMEDIATE redirect - don't wait for Swal
            // Show success message briefly, then redirect
            Swal.fire({
              title: 'Success',
              text: 'Login successful! Redirecting...',
              icon: 'success',
              timer: 500,
              showConfirmButton: false,
              allowOutsideClick: false
            });
            
            // Redirect immediately (don't wait for Swal to close)
            console.log('Immediate redirect to:', redirectUrl);
            
            // Use replace instead of href to prevent back button issues
            setTimeout(() => {
              console.log('Executing redirect to:', redirectUrl);
              window.location.replace(redirectUrl);
            }, 600);
            
            // Backup redirect - force redirect after 1 second
            setTimeout(() => {
              if (window.location.pathname.includes('index.php') || window.location.pathname.endsWith('/')) {
                console.log('Backup redirect triggered - forcing navigation to:', redirectUrl);
                window.location.replace(redirectUrl);
              }
            }, 1000);
          } else if (data.success === false) {
            // Handle explicit error response
            Swal.fire('Error', data.message || data.error || 'Login failed', 'error');
          } else {
            // Handle unexpected response format
            console.warn('Unexpected response format:', data);
            Swal.fire('Error', data.error || data.message || 'Unexpected response from server', 'error');
          }
        })
        .catch(error => {
          console.error('=== LOGIN ERROR DETAILS ===');
          console.error('Error name:', error.name);
          console.error('Error message:', error.message);
          console.error('Error type:', error.constructor.name);
          console.error('Error stack:', error.stack);
          console.error('API URL attempted:', apiUrl);
          console.error('===========================');
          
          let errorMessage = 'An error occurred during login';
          
          // Handle different error types
          if (error.name === 'TypeError' && error.message === 'Failed to fetch') {
            errorMessage = 'Unable to connect to the server.\n\n' +
                          'Possible causes:\n' +
                          '• Server (Apache/PHP) is not running\n' +
                          '• Incorrect API URL: ' + apiUrl + '\n' +
                          '• CORS configuration issue\n' +
                          '• Network connectivity problem\n\n' +
                          'Please check:\n' +
                          '1. XAMPP Control Panel - ensure Apache is running\n' +
                          '2. Browser console (F12) for detailed errors\n' +
                          '3. Try accessing: ' + apiUrl.replace('/auth/login', '') + ' directly in browser';
          } else if (error.message) {
            errorMessage = error.message;
          }
          
          Swal.fire({
            icon: 'error',
            title: 'Login Failed',
            html: '<div style="text-align: left;">' + 
                  '<p>' + errorMessage.replace(/\n/g, '<br>') + '</p>' +
                  '<p style="font-size: 0.8em; margin-top: 10px;">' +
                  '<strong>Debug Info:</strong><br>' +
                  'URL: ' + apiUrl + '<br>' +
                  'Error: ' + error.name + '<br>' +
                  'Check browser console (F12) for more details' +
                  '</p></div>',
            width: '600px'
          });
        });
      });
    });
  </script>
</head>
<body class="hvh-bg flex flex-col items-center justify-start md:justify-center pt-4 md:pt-0 px-4 font-sans">
  <!-- Multi-logo layer (masked by glow) -->
  <div class="hvh-logos" aria-hidden="true"></div>

  <!-- Login container -->
  <div class="relative z-[3] w-full max-w-md md:max-w-3xl rounded-2xl overflow-hidden shadow-lg flex flex-col md:grid md:grid-cols-2 bg-transparent">
    <div class="flex items-center justify-center pt-2 pb-3 md:hidden">
      <div class="bg-white rounded-full flex items-center justify-center w-40 h-40 shadow-md ring-2 ring-gray-300">
        <img
          src="https://hebbkx1anhila5yf.public.blob.vercel-storage.com/hvh-logo-Qb4GMWRmiAugUk366zbp9Rmm3TLZww.png"
          alt="H Vill Hospital Logo"
          class="max-h-36 w-auto object-contain"
        />
      </div>
    </div>

    <div class="bg-slate-100 p-6 md:p-10 md:rounded-l-2xl rounded-t-2xl md:rounded-t-none">
      <div class="text-center mb-6 md:mb-8">
        <h1 class="text-2xl font-bold">Welcome back</h1>
        <p class="text-gray-600">Login to your Hospital account</p>
      </div>
      <form class="space-y-5 md:space-y-6">
        <div>
          <label for="email" class="block text-sm font-medium">Email</label>
          <input
            id="email"
            type="email"
            placeholder="m@example.com"
            required
            class="mt-2 w-full rounded-md border-2 border-gray-400 px-3 py-2 focus:outline-none focus:border-gray-600"
          />
        </div>
        <div>
          <label for="password" class="block text-sm font-medium">Password</label>
          <input
            id="password"
            type="password"
            required
            class="mt-2 w-full rounded-md border-2 border-gray-400 px-3 py-2 focus:outline-none focus:border-gray-600"
          />
        </div>

        <div class="text-right -mt-2">
          <a href="#" class="text-sm font-semibold hover:underline">Forgot your password?</a>
        </div>
        <button type="submit" class="w-full rounded-md bg-black py-2 text-white hover:bg-gray-800 transition">
          Login
        </button>

        <p class="text-center text-sm">
          Don’t have an account?
          <a href="#" class="font-semibold hover:underline">Sign up</a>
        </p>
      </form>
    </div>
    <div class="hidden md:flex items-center justify-center bg-slate-300 md:rounded-r-2xl p-8">
      <img
        src="https://hebbkx1anhila5yf.public.blob.vercel-storage.com/hvh-logo-Qb4GMWRmiAugUk366zbp9Rmm3TLZww.png"
        alt="H Vill Hospital Logo"
        class="max-h-72 w-auto object-contain"
      />
    </div>
  </div>

  <p class="relative z-[3] mt-4 md:mt-6 text-center text-xs text-gray-300 max-w-md md:max-w-none">
    By clicking continue, you agree to our
    <a href="#" class="underline hover:text-white">Terms of Service</a> and
    <a href="#" class="underline hover:text-white">Privacy Policy</a>.
  </p>
</body>
</html>
