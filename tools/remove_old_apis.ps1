# PowerShell script to remove old API files
# This removes legacy API endpoint files from modules/*/api/ directories
# These are being replaced by the new v2 API structure

Write-Host "Removing old API files..." -ForegroundColor Yellow

$removedCount = 0
$errorCount = 0

# List of API directories to clean
$apiDirectories = @(
    "modules\auth\api",
    "modules\employees\api",
    "modules\payroll\api",
    "modules\hmo\api",
    "modules\benefits\api",
    "modules\leave\api",
    "modules\attendance\api",
    "modules\analytics\api",
    "modules\core_hr\api",
    "modules\dashboard\api",
    "modules\reports\api",
    "modules\notifications\api",
    "modules\integrations\api"
)

foreach ($apiDir in $apiDirectories) {
    if (Test-Path $apiDir) {
        Write-Host "Processing: $apiDir" -ForegroundColor Cyan
        
        # Get all PHP files in the api directory
        $files = Get-ChildItem -Path $apiDir -Filter "*.php" -File
        
        foreach ($file in $files) {
            try {
                # Skip v2 directory if it exists
                if ($file.FullName -like "*\v2\*") {
                    Write-Host "  Skipping v2 file: $($file.Name)" -ForegroundColor Gray
                    continue
                }
                
                Write-Host "  Removing: $($file.Name)" -ForegroundColor Gray
                Remove-Item -Path $file.FullName -Force
                $removedCount++
            } catch {
                Write-Host "  ERROR removing $($file.Name): $_" -ForegroundColor Red
                $errorCount++
            }
        }
        
        # Also remove .bak files
        $bakFiles = Get-ChildItem -Path $apiDir -Filter "*.bak" -File -ErrorAction SilentlyContinue
        foreach ($bakFile in $bakFiles) {
            try {
                Write-Host "  Removing backup: $($bakFile.Name)" -ForegroundColor Gray
                Remove-Item -Path $bakFile.FullName -Force
                $removedCount++
            } catch {
                Write-Host "  ERROR removing $($bakFile.Name): $_" -ForegroundColor Red
                $errorCount++
            }
        }
    } else {
        Write-Host "Directory not found: $apiDir" -ForegroundColor Yellow
    }
}

Write-Host "`nRemoval complete!" -ForegroundColor Green
Write-Host "Files removed: $removedCount" -ForegroundColor Green
if ($errorCount -gt 0) {
    Write-Host "Errors: $errorCount" -ForegroundColor Red
}

Write-Host "`nNote: API directories are kept but emptied." -ForegroundColor Yellow
Write-Host "The new v2 API structure uses:" -ForegroundColor Yellow
Write-Host "  - modules/*/api/v2/ for versioned controllers" -ForegroundColor Yellow
Write-Host "  - modules/*/routes/ for routing" -ForegroundColor Yellow
