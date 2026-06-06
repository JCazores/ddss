@echo off
REM Start Prediction Watcher in Background
REM Save this as: start_watcher.bat

cd /d "C:\xampp\htdocs\ddss"

echo ======================================================================
echo Starting Prediction Watcher...
echo ======================================================================
echo.
echo Location: %CD%
echo Time: %DATE% %TIME%
echo.
echo The watcher will run in the background.
echo To stop it: Use Task Manager to end "python.exe" process
echo Log file: watcher.log
echo.
echo ======================================================================
echo.

REM Start Python watcher in background (hidden window)
REM Using 'py' launcher for Windows
start /B py prediction_watcher.py

echo Watcher started!
echo Check watcher.log for status updates.
echo.

pause