@echo off
REM ============================================================================
REM Prediction API - Windows Launcher
REM ============================================================================

echo ========================================
echo   Starting Prediction API Server
echo ========================================
echo.

REM Set UTF-8 encoding for Windows console
chcp 65001 >nul 2>&1

REM Check if Python is installed
py --version >nul 2>&1
if %errorlevel% neq 0 (
    echo [ERROR] Python not found! Please install Python 3.6+
    pause
    exit /b 1
)

echo [OK] Python found
echo.

REM Check if required packages are installed
echo [INFO] Checking dependencies...
py -c "import flask, pandas, numpy, sklearn, joblib, sqlalchemy" >nul 2>&1
if %errorlevel% neq 0 (
    echo [WARN] Some dependencies missing. Installing...
    py -m pip install flask flask-cors pandas numpy scikit-learn joblib sqlalchemy pymysql
)

echo [OK] Dependencies installed
echo.

REM Check if model files exist
if not exist "scaler_v2.pkl" (
    echo [ERROR] Model file 'scaler_v2.pkl' not found!
    echo Please ensure all model files are in the current directory.
    pause
    exit /b 1
)

echo [OK] Model files found
echo.

REM Start the API server
echo [START] Starting API server...
echo [INFO] Press Ctrl+C to stop the server
echo.
echo ========================================
echo.

py prediction_api.py

echo.
echo [STOP] API server stopped
pause