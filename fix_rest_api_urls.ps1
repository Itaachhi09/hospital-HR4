# PowerShell script to fix REST API URL patterns

$files = @(
    "js\payroll\salaries.js",
    "js\payroll\bonuses.js",
    "js\payroll\deductions.js",
    "js\payroll\shared-modals.js",
    "js\analytics\hr_analytics_dashboard.js",
    "js\compensation\compensation.js",
    "js\utils.js"
)

foreach ($file in $files) {
    if (Test-Path $file) {
        $content = Get-Content $file -Raw
        
        # Replace the pattern
        $content = $content -replace '\$\{API_BASE_URL\.replace\(''php/api/'', ''api''\)\}', '${REST_API_URL}'
        $content = $content -replace '\$\{API_BASE_URL\.replace\("php/api/", "api"\)\}', '${REST_API_URL}'
        
        # Update import if needed
        if ($content -match "import.*API_BASE_URL.*from.*config\.js") {
            $content = $content -replace "import \{ API_BASE_URL, BASE_URL \} from '\.\./config\.js';", "import { REST_API_URL, BASE_URL } from '../config.js';"
        }
        if ($content -match "import.*API_BASE_URL.*from.*\.\.\/\.\.\/config\.js") {
            $content = $content -replace "import \{ API_BASE_URL \} from '\.\./\.\./config\.js';", "import { REST_API_URL } from '../../config.js';"
        }
        
        Set-Content $file -Value $content -NoNewline
        Write-Host "Updated: $file"
    }
}

Write-Host "Done!"


