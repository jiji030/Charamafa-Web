@echo off
:: CHARMAFA All-in-One Configuration and Startup
:: This batch file contains embedded PowerShell for auto-configuration

setlocal enabledelayedexpansion

echo ========================================
echo          Detecting Network...
echo ========================================
echo.

:: Create temporary PowerShell script
set "PS_SCRIPT=%TEMP%\charmafa_config.ps1"

echo Creating configuration script...

(
echo # CHARMAFA Auto Network Configuration Script
echo function Get-NetworkIP {
echo     try {
echo         $networkAdapters = Get-NetIPAddress -AddressFamily IPv4 ^| Where-Object {
echo             $_.InterfaceAlias -notlike "*Loopback*" -and
echo             $_.InterfaceAlias -notlike "*Bluetooth*" -and
echo             $_.IPAddress -notlike "169.254.*" -and
echo             $_.IPAddress -ne "127.0.0.1"
echo         }
echo         if ^($networkAdapters.Count -eq 0^) { throw "No active network connection found" }
echo         $preferredAdapter = $networkAdapters ^| Where-Object {
echo             $_.InterfaceAlias -like "*Wi-Fi*" -or 
echo             $_.InterfaceAlias -like "*Ethernet*" -or
echo             $_.InterfaceAlias -like "*Wireless*"
echo         } ^| Select-Object -First 1
echo         if ^(-not $preferredAdapter^) { $preferredAdapter = $networkAdapters ^| Select-Object -First 1 }
echo         return $preferredAdapter.IPAddress
echo     } catch {
echo         Write-Host "Error: $_" -ForegroundColor Red
echo         return $null
echo     }
echo }
echo.
echo function Update-LaravelEnv {
echo     param^([string]$IpAddress^)
echo     $envPath = ".\collector-backend\.env"
echo     if ^(-not ^(Test-Path $envPath^)^) { Write-Host "Laravel .env not found" -ForegroundColor Red; return $false }
echo     try {
echo         $content = Get-Content $envPath
echo         $updated = @^(^)
echo         $found = $false
echo         foreach ^($line in $content^) {
echo             if ^($line -match "^^APP_URL="^) {
echo                 $updated += "APP_URL=http://${IpAddress}:8000"
echo                 $found = $true
echo             } else { $updated += $line }
echo         }
echo         if ^(-not $found^) { $updated += "APP_URL=http://${IpAddress}:8000" }
echo         $updated ^| Set-Content $envPath -Encoding ASCII
echo         Write-Host "  Updated Laravel config" -ForegroundColor Green
echo         return $true
echo     } catch { Write-Host "Error: $_" -ForegroundColor Red; return $false }
echo }
echo.
echo function Update-NuxtConfig {
echo     param^([string]$IpAddress^)
echo     $configPath = ".\collector-frontend\nuxt.config.ts"
echo     if ^(-not ^(Test-Path $configPath^)^) { Write-Host "Nuxt config not found" -ForegroundColor Red; return $false }
echo     try {
echo         $content = Get-Content $configPath -Raw
echo         $content = $content -replace "apiBase:\s*'[^^']*'", "apiBase: 'http://${IpAddress}:8000/api'"
echo         $content = $content -replace "//\s*apiBase:\s*'[^^']*'", "apiBase: 'http://${IpAddress}:8000/api'"
echo         $content ^| Set-Content $configPath -Encoding ASCII
echo         Write-Host "  Updated Nuxt config" -ForegroundColor Green
echo         return $true
echo     } catch { Write-Host "Error: $_" -ForegroundColor Red; return $false }
echo }
echo.
echo function Update-StartBat {
echo     param^([string]$IpAddress^)
echo     $startBatPath = ".\start_charmafa.bat"
echo     if ^(-not ^(Test-Path $startBatPath^)^) { Write-Host "start_charmafa.bat not found" -ForegroundColor Red; return $false }
echo     try {
echo         $content = Get-Content $startBatPath -Raw
echo         $content = $content -replace "Network IP: \d+\.\d+\.\d+\.\d+", "Network IP: $IpAddress"
echo         $content = $content -replace "Frontend: https://\d+\.\d+\.\d+\.\d+:3000", "Frontend: https://${IpAddress}:3000"
echo         $content = $content -replace "Backend: http://\d+\.\d+\.\d+\.\d+:8000", "Backend: http://${IpAddress}:8000"
echo         $content = $content -replace "Frontend will be available at: https://\d+\.\d+\.\d+\.\d+:3000", "Frontend will be available at: https://${IpAddress}:3000"
echo         $content = $content -replace "Backend API available at: http://\d+\.\d+\.\d+\.\d+:8000/api", "Backend API available at: http://${IpAddress}:8000/api"
echo         $content = $content -replace "`"https://\d+\.\d+\.\d+\.\d+:3000`"", "`"https://${IpAddress}:3000`""
echo         $content ^| Set-Content $startBatPath -Encoding ASCII
echo         Write-Host "  Updated start_charmafa.bat" -ForegroundColor Green
echo         return $true
echo     } catch { Write-Host "Error updating start_charmafa.bat: $_" -ForegroundColor Red; return $false }
echo }
echo.
echo Write-Host "Detecting network IP..." -ForegroundColor Cyan
echo $ip = Get-NetworkIP
echo if ^(-not $ip^) { Write-Host "Failed to detect IP" -ForegroundColor Red; exit 1 }
echo Write-Host "Detected IP: $ip" -ForegroundColor Green
echo $l = Update-LaravelEnv -IpAddress $ip
echo $n = Update-NuxtConfig -IpAddress $ip
echo $s = Update-StartBat -IpAddress $ip
echo if ^($l -and $n -and $s^) { 
echo     Write-Host "`nConfiguration successful!" -ForegroundColor Green
echo     Write-Host "IP: $ip" -ForegroundColor Cyan
echo     exit 0
echo } else { 
echo     Write-Host "`nConfiguration failed" -ForegroundColor Red
echo     exit 1
echo }
) > "%PS_SCRIPT%"

:: Run the PowerShell script
echo Running configuration...
echo.
powershell.exe -ExecutionPolicy Bypass -File "%PS_SCRIPT%"

:: Check result
if %errorlevel% equ 0 (
    echo.
    echo ========================================
    echo       Configuration Complete!
    echo ========================================

) else (
    echo.
    echo Configuration failed. Please check the errors above.
)

:: Cleanup
if exist "%PS_SCRIPT%" del "%PS_SCRIPT%"

echo.