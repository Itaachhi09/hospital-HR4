# PowerShell script to fix all API imports in JavaScript modules

$files = @(
    "js\admin\user_management.js",
    "js\admin\hmo_management.js",
    "js\payroll\payslips.js",
    "js\payroll\deductions.js",
    "js\payroll\bonuses.js",
    "js\payroll\salaries.js",
    "js\time_attendance\timesheets.js",
    "js\time_attendance\shifts.js",
    "js\time_attendance\schedules.js",
    "js\time_attendance\attendance.js",
    "js\leave\leave.js",
    "js\claims\claims.js",
    "js\profile\profile.js",
    "js\analytics\analytics.js",
    "js\analytics\hr_analytics_dashboard.js",
    "js\dashboard\dashboard.js",
    "js\compensation\compensation.js",
    "js\admin\hmo\claims.js",
    "js\admin\hmo\dashboard.js",
    "js\admin\hmo\enrollments.js",
    "js\admin\hmo\plans.js",
    "js\admin\hmo\providers.js"
)

foreach ($file in $files) {
    if (Test-Path $file) {
        $content = Get-Content $file -Raw
        
        # Replace import statement
        $content = $content -replace "import \{ API_BASE_URL \} from '\.\./utils\.js';", "import { LEGACY_API_URL } from '../utils.js';"
        $content = $content -replace "import \{ API_BASE_URL \} from '\.\./\.\./utils\.js';", "import { LEGACY_API_URL } from '../../utils.js';"
        
        # Replace usage
        $content = $content -replace '\$\{API_BASE_URL\}', '${LEGACY_API_URL}'
        
        Set-Content $file -Value $content -NoNewline
        Write-Host "Updated: $file"
    }
}

Write-Host "Done!"


