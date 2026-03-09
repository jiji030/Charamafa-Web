# CHARMAFA Auto Network Configuration Script
# This script automatically detects your network IP and configures the system

Write-Host "CHARMAFA Auto Network Configuration" -ForegroundColor Green
Write-Host "==========================================" -ForegroundColor Green

# Function to get the active network IP address
function Get-NetworkIP {
    try {
        # Get all network adapters that are up and have an IP
        $networkAdapters = Get-NetIPAddress -AddressFamily IPv4 | Where-Object {
            $_.InterfaceAlias -notlike "*Loopback*" -and
            $_.InterfaceAlias -notlike "*Bluetooth*" -and
            $_.IPAddress -notlike "169.254.*" -and
            $_.IPAddress -ne "127.0.0.1"
        }

        if ($networkAdapters.Count -eq 0) {
            throw "No active network connection found"
        }

        # Prefer WiFi or Ethernet connections
        $preferredAdapter = $networkAdapters | Where-Object {
            $_.InterfaceAlias -like "*Wi-Fi*" -or 
            $_.InterfaceAlias -like "*Ethernet*" -or
            $_.InterfaceAlias -like "*Wireless*"
        } | Select-Object -First 1

        if (-not $preferredAdapter) {
            $preferredAdapter = $networkAdapters | Select-Object -First 1
        }

        return $preferredAdapter.IPAddress
    }
    catch {
        Write-Host "Error detecting IP: $($_.Exception.Message)" -ForegroundColor Red
        return $null
    }
}

# Function to update Laravel .env file
function Update-LaravelEnv {
    param([string]$IpAddress)
    
    $envPath = ".\collector-backend\.env"
    
    if (-not (Test-Path $envPath)) {
        Write-Host "Laravel .env file not found at $envPath" -ForegroundColor Red
        return $false
    }

    try {
        $envContent = Get-Content $envPath
        $updatedContent = @()
        $appUrlUpdated = $false

        foreach ($line in $envContent) {
            if ($line -match "^APP_URL=") {
                $updatedContent += "APP_URL=http://${IpAddress}:8000"
                $appUrlUpdated = $true
                Write-Host "  ✓ Updated APP_URL to http://${IpAddress}:8000" -ForegroundColor Cyan
            }
            else {
                $updatedContent += $line
            }
        }

        # If APP_URL wasn't found, add it
        if (-not $appUrlUpdated) {
            $updatedContent += "APP_URL=http://${IpAddress}:8000"
            Write-Host "  ✓ Added APP_URL=http://${IpAddress}:8000" -ForegroundColor Cyan
        }

        $updatedContent | Set-Content $envPath -Encoding UTF8
        return $true
    }
    catch {
        Write-Host "Error updating Laravel .env: $($_.Exception.Message)" -ForegroundColor Red
        return $false
    }
}

# Function to update Nuxt config
function Update-NuxtConfig {
    param([string]$IpAddress)
    
    $configPath = ".\collector-frontend\nuxt.config.ts"
    
    if (-not (Test-Path $configPath)) {
        Write-Host "Nuxt config file not found at $configPath" -ForegroundColor Red
        return $false
    }

    try {
        $configContent = Get-Content $configPath -Raw
        
        # Update the apiBase configuration
        $pattern = "apiBase:\s*'[^']*'"
        $replacement = "apiBase: 'http://${IpAddress}:8000/api'"
        
        if ($configContent -match $pattern) {
            $configContent = $configContent -replace $pattern, $replacement
            Write-Host "  ✓ Updated apiBase to http://${IpAddress}:8000/api" -ForegroundColor Cyan
        }
        else {
            Write-Host "Could not find apiBase pattern in Nuxt config" -ForegroundColor Yellow
        }

        $configContent | Set-Content $configPath -Encoding UTF8
        return $true
    }
    catch {
        Write-Host "Error updating Nuxt config: $($_.Exception.Message)" -ForegroundColor Red
        return $false
    }
}

# Function to create a batch file for easy startup
function Create-StartupScript {
    param([string]$IpAddress)
    
    $batchContent = @"
@echo off
title CHARMAFA System
echo Starting CHARMAFA System...
echo Network IP: $IpAddress
echo Frontend: https://${IpAddress}:3000
echo Backend: http://${IpAddress}:8000

echo.
echo Starting Laravel backend...
start /min cmd /k "cd /d %~dp0collector-backend && php artisan serve --host=0.0.0.0 --port=8000"

echo Waiting for backend to initialize...
timeout /t 3 /nobreak >nul

echo Starting Nuxt frontend...
start /min cmd /k "cd /d %~dp0collector-frontend && npm run dev -- --host 0.0.0.0"

echo.
echo System is starting up...
echo Frontend will be available at: https://${IpAddress}:3000
echo Backend API available at: http://${IpAddress}:8000/api

timeout /t 10

echo Opening browser...
if exist "C:\Program Files\Google\Chrome\Application\chrome.exe" (
    echo Opening with Chrome...
    "C:\Program Files\Google\Chrome\Application\chrome.exe" "https://${IpAddress}:3000"
) else if exist "C:\Program Files (x86)\Google\Chrome\Application\chrome.exe" (
    echo Opening with Chrome...
    "C:\Program Files (x86)\Google\Chrome\Application\chrome.exe" "https://${IpAddress}:3000"
) else (
    echo Chrome not found, opening with default browser...
    start "" "https://${IpAddress}:3000"
)

echo.
echo CHARMAFA System is now running!
echo Press any key to exit this window (servers will continue running)
pause >nul
"@

    $batchContent | Set-Content "start_charmafa.bat" -Encoding UTF8
    Write-Host "  ✓ Created startup script: start_charmafa.bat" -ForegroundColor Cyan
}

# Main execution
Write-Host "Detecting network IP address..." -ForegroundColor Yellow

$detectedIP = Get-NetworkIP

if (-not $detectedIP) {
    Write-Host "Could not detect network IP address. Please check your network connection." -ForegroundColor Red
    Read-Host "Press Enter to exit"
    exit 1
}

Write-Host "Detected IP Address: $detectedIP" -ForegroundColor Green
Write-Host "Network Interface: $((Get-NetIPAddress -IPAddress $detectedIP).InterfaceAlias)" -ForegroundColor Cyan

Write-Host "`nUpdating configuration files..." -ForegroundColor Yellow

# Update Laravel configuration
Write-Host "  Updating Laravel backend configuration..." -ForegroundColor Cyan
$laravelUpdated = Update-LaravelEnv -IpAddress $detectedIP

# Update Nuxt configuration
Write-Host "  Updating Nuxt frontend configuration..." -ForegroundColor Cyan
$nuxtUpdated = Update-NuxtConfig -IpAddress $detectedIP

# Create startup script
Write-Host "  Creating startup script..." -ForegroundColor Cyan
Create-StartupScript -IpAddress $detectedIP

if ($laravelUpdated -and $nuxtUpdated) {
    Write-Host "`nConfiguration completed successfully!" -ForegroundColor Green
    Write-Host "==========================================" -ForegroundColor Green
    Write-Host "Your CHARMAFA system is configured for:" -ForegroundColor White
    Write-Host " Network IP: $detectedIP" -ForegroundColor Cyan
    Write-Host "  Frontend: https://${detectedIP}:3000" -ForegroundColor Cyan
    Write-Host " Backend:  http://${detectedIP}:8000" -ForegroundColor Cyan
    Write-Host "`nTo start the system, run: .\start_charmafa.bat" -ForegroundColor Yellow
    Write-Host "   Or manually run both servers with 'host=0.0.0.0' parameter" -ForegroundColor Gray
}
else {
    Write-Host "`nConfiguration completed with some issues." -ForegroundColor Yellow
    Write-Host "   Please check the error messages above and try again." -ForegroundColor Gray
}

Write-Host "`nPress Enter to exit..." -ForegroundColor Gray
Read-Host