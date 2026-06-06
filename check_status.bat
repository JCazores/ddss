@echo off
REM Quick Status Check
REM Save this as: check_status.bat

cd /d "C:\xampp\htdocs\SchoolFeesMgSystem-PHP\SchoolFeesMgSystem-PHP\SchoolFeesMgSystem-PHP"

echo ======================================================================
echo PREDICTION SYSTEM STATUS CHECK
echo ======================================================================
echo Time: %DATE% %TIME%
echo Location: %CD%
echo.

echo [1] Checking if watcher is running...
tasklist /FI "IMAGENAME eq python.exe" /FO CSV | findstr /i "prediction_watcher" >nul
if %errorlevel% equ 0 (
    echo     [OK] Watcher IS RUNNING
) else (
    echo     [ERROR] Watcher is NOT RUNNING
    echo     Action: Run start_watcher.bat
)
echo.

echo [2] Checking for pending trigger file...
if exist "prediction_trigger.json" (
    echo     [FOUND] Trigger file exists
    type prediction_trigger.json
) else (
    echo     [OK] No pending triggers
)
echo.

echo [3] Checking recent log entries...
if exist "watcher.log" (
    echo     Last 10 log entries:
    powershell -Command "Get-Content watcher.log -Tail 10"
) else (
    echo     [WARN] No log file found
)
echo.

echo ======================================================================
echo.
pause