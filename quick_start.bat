@echo off
title CHARMAFA Quick Start
echo =====================================
echo      CHARMAFA Quick Start
echo =====================================
echo.

@REM echo Detecting network and configuring system...
@REM powershell -ExecutionPolicy Bypass -File "auto_configure_network.ps1"

if exist "configure.bat" (
    call configure.bat
) else (
    echo Error: Configuration script may have failed.
    echo Please run auto_configure_network.ps1 manually.
    pause
)

echo.

if exist "start_charmafa.bat" (
    call start_charmafa.bat
) else (
    echo Error: Configuration script may have failed.
    echo Please run auto_configure_network.ps1 manually.
    pause
)
