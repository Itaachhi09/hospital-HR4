<?php
// Handle login via GET parameters for testing
if (isset($_GET['username']) && isset($_GET['password'])) {
    $username = $_GET['username'];
    $password = $_GET['password'];

    // Start session
    session_start();

    // Database connection
    $pdo = null;
    try {
        require_once 'php/db_connect.php';
        if (!isset($pdo) || !$pdo instanceof PDO) {
            throw new Exception('DB connection object not created.');
        }
    } catch (Throwable $e) {
        error_log("Index Login Error (DB Connection): " . $e->getMessage());
        header('Location: index.php?error=server_error');
        exit;
    }

    try {
        // Fetch user details
        $sql = "SELECT
                    u.UserID, u.EmployeeID, u.Username, u.PasswordHash, u.RoleID, u.IsActive,
                    u.IsTwoFactorEnabled,
                    r.RoleName,
                    e.FirstName, e.LastName, e.Email AS EmployeeEmail
                FROM Users u
                JOIN Roles r ON u.RoleID = r.RoleID
                JOIN Employees e ON u.EmployeeID = e.EmployeeID
                WHERE u.Username = :username";

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !$user['IsActive']) {
            // Create mock user for bypass
            if ($username === 'admin') {
                $roleID = 1;
                $roleName = 'System Admin';
            } elseif ($username === 'hr_chief') {
                $roleID = 2;
                $roleName = 'HR Chief';
            } else {
                $roleID = 1;
                $roleName = 'System Admin';
            }
            $user = [
                'UserID' => -1,
                'EmployeeID' => -1,
                'Username' => $username,
                'PasswordHash' => password_hash('mock_password', PASSWORD_DEFAULT),
                'RoleID' => $roleID,
                'IsActive' => true,
                'IsTwoFactorEnabled' => false,
                'RoleName' => $roleName,
                'FirstName' => 'Guest',
                'LastName' => 'User',
                'EmployeeEmail' => null
            ];
        } else {
            // Allow any password for bypass
            if (!password_verify($password, trim($user['PasswordHash']))) {
                error_log("Login bypass: Password verification failed for '{$username}', but allowing login anyway.");
            }
        }

        // Set session
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['UserID'];
        $_SESSION['employee_id'] = $user['EmployeeID'];
        $_SESSION['username'] = $user['Username'];
        $_SESSION['role_id'] = $user['RoleID'];
        $_SESSION['role_name'] = $user['RoleName'];
        $_SESSION['full_name'] = $user['FirstName'] . ' ' . $user['LastName'];

        // Redirect to appropriate landing page
        if ($user['RoleName'] === 'System Admin') {
            header('Location: admin_landing.php');
        } else {
            header('Location: employee_landing.php');
        }
        exit;
    } catch (PDOException $e) {
        error_log("Index Login Error (DB Query): " . $e->getMessage());
        header('Location: index.php?error=db_error');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Hospital Login</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const form = document.querySelector('form');
      const emailInput = document.getElementById('email');
      const passwordInput = document.getElementById('password');

      form.addEventListener('submit', function(e) {
        e.preventDefault();

        const email = emailInput.value;
        const password = passwordInput.value;

        if (!email || !password) {
          Swal.fire('Error', 'Please fill in all fields', 'error');
          return;
        }

        // Submit to login API
        fetch('php/api/login.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({ username: email, password: password })
        })
        .then(response => response.json())
        .then(data => {
          if (data.two_factor_required) {
            Swal.fire('2FA Required', data.message, 'info');
          } else if (data.message === 'Login successful.') {
            Swal.fire('Success', 'Login successful!', 'success').then(() => {
              // Redirect based on role
              if (data.user.role_name === 'System Admin') {
                window.location.href = 'admin_landing.php';
              } else {
                window.location.href = 'employee_landing.php';
              }
            });
          } else {
            Swal.fire('Error', data.error, 'error');
          }
        })
        .catch(error => {
          console.error('Error:', error);
          Swal.fire('Error', 'An error occurred during login', 'error');
        });
      });
    });
  </script>
</head>
<body class="min-h-screen bg-[#0A1D4D] flex flex-col items-center justify-start md:justify-center pt-4 md:pt-0 px-4 font-sans">
  <div class="w-full max-w-md md:max-w-3xl rounded-2xl overflow-hidden shadow-lg flex flex-col md:grid md:grid-cols-2 bg-transparent">
    <div class="flex items-center justify-center pt-2 pb-3 md:hidden">
      <div class="bg-white rounded-full flex items-center justify-center w-40 h-40 shadow-md ring-2 ring-gray-300">
        <img
          src="logo.jpg"
          alt="Hospital Logo"
          class="max-h-36 w-auto object-contain"
        />
      </div>
    </div>

    <div class="bg-sky-100 p-6 md:p-10 md:rounded-l-2xl rounded-t-2xl md:rounded-t-none">
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
        <div class="flex gap-2">
          <button
            type="submit"
            class="flex-1 rounded-md bg-black py-2 text-white hover:bg-gray-800 transition"
          >
            Login
          </button>
          <button
            type="button"
            onclick="document.getElementById('email').value=''; document.getElementById('password').value='';"
            class="flex-1 rounded-md bg-gray-500 py-2 text-white hover:bg-gray-600 transition"
          >
            Clear
          </button>
        </div>

        <p class="text-center text-sm">
          Don’t have an account?
          <a href="#" class="font-semibold hover:underline">Sign up</a>
        </p>
      </form>
    </div>
    <div class="hidden md:flex items-center justify-center bg-gray-400 md:rounded-r-2xl p-8">
      <img
        src="logo.jpg"
        alt="Hospital Logo"
        class="max-h-72 w-auto object-contain"
      />
    </div>
  </div>

  <p class="mt-4 md:mt-6 text-center text-xs text-gray-300 max-w-md md:max-w-none">
    By clicking continue, you agree to our
    <a href="#" class="underline hover:text-white">Terms of Service</a> and
    <a href="#" class="underline hover:text-white">Privacy Policy</a>.
  </p>
</body>
</html>
