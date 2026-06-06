#!/usr/bin/env python3
"""
Prediction System Diagnostic Tool
Run this to identify why predictions aren't starting
"""

import os
import sys
import json
from pathlib import Path
from datetime import datetime


# Colors for terminal output
class Colors:
    GREEN = '\033[92m'
    RED = '\033[91m'
    YELLOW = '\033[93m'
    BLUE = '\033[94m'
    END = '\033[0m'


def print_header(text):
    print(f"\n{Colors.BLUE}{'=' * 70}")
    print(f"{text}")
    print(f"{'=' * 70}{Colors.END}\n")


def print_success(text):
    print(f"{Colors.GREEN}✅ {text}{Colors.END}")


def print_error(text):
    print(f"{Colors.RED}❌ {text}{Colors.END}")


def print_warning(text):
    print(f"{Colors.YELLOW}⚠️  {text}{Colors.END}")


def print_info(text):
    print(f"   {text}")


def check_files():
    """Check if all required files exist"""
    print_header("1. CHECKING REQUIRED FILES")

    work_dir = os.path.dirname(os.path.abspath(__file__))
    print_info(f"Working directory: {work_dir}")

    required_files = {
        'predict.py': 'Main prediction script',
        'prediction_watcher.py': 'Background watcher',
        'upload_csv.php': 'Upload handler',
        'check_prediction_status.php': 'Status checker',
        'scaler_v2.pkl': 'ML Model - Scaler',
        'svm_model_v2.pkl': 'ML Model - SVM',
        'logistic_model_v2.pkl': 'ML Model - Logistic',
        'label_encoder_v2.pkl': 'ML Model - Encoder'
    }

    all_exist = True
    for filename, description in required_files.items():
        filepath = os.path.join(work_dir, filename)
        if os.path.exists(filepath):
            print_success(f"{filename} - {description}")
        else:
            print_error(f"{filename} - {description} - NOT FOUND!")
            all_exist = False

    return all_exist


def check_trigger_file():
    """Check trigger file status"""
    print_header("2. CHECKING TRIGGER FILE")

    work_dir = os.path.dirname(os.path.abspath(__file__))
    trigger_file = os.path.join(work_dir, 'prediction_trigger.json')

    if not os.path.exists(trigger_file):
        print_error("prediction_trigger.json NOT FOUND")
        print_info("This means:")
        print_info("  • No CSV has been uploaded yet, OR")
        print_info("  • Trigger file was already processed, OR")
        print_info("  • Upload didn't create the trigger file")
        return False

    try:
        with open(trigger_file, 'r') as f:
            data = json.load(f)

        print_success("prediction_trigger.json EXISTS")
        print_info(f"Content:")
        for key, value in data.items():
            print_info(f"  • {key}: {value}")

        # Check if it's valid
        required_fields = ['table_name', 'year', 'semester', 'records_uploaded']
        missing = [f for f in required_fields if f not in data]

        if missing:
            print_error(f"Missing required fields: {', '.join(missing)}")
            return False
        else:
            print_success("All required fields present")
            return True

    except json.JSONDecodeError as e:
        print_error(f"Trigger file is corrupted: {e}")
        return False
    except Exception as e:
        print_error(f"Error reading trigger file: {e}")
        return False


def check_database_connection():
    """Check if database is accessible"""
    print_header("3. CHECKING DATABASE CONNECTION")

    try:
        from sqlalchemy import create_engine, text

        connection_string = "mysql+pymysql://root:@localhost/schoolfeesys"
        print_info(f"Connection string: {connection_string}")

        engine = create_engine(connection_string)

        with engine.connect() as conn:
            result = conn.execute(text(
                "SELECT DATABASE() as db, COUNT(*) as table_count FROM information_schema.tables WHERE table_schema = 'schoolfeesys'"))
            row = result.fetchone()
            print_success(f"Connected to database: {row[0]}")
            print_info(f"Total tables: {row[1]}")

        # Check for prediction_requests table
        with engine.connect() as conn:
            result = conn.execute(text("""
                SELECT COUNT(*) FROM information_schema.tables 
                WHERE table_schema = 'schoolfeesys' 
                AND table_name = 'prediction_requests'
            """))
            exists = result.fetchone()[0] > 0

            if exists:
                print_success("prediction_requests table exists")

                # Check pending requests
                result = conn.execute(text("""
                    SELECT id, year, semester, table_name, status, request_timestamp
                    FROM prediction_requests 
                    WHERE status = 'pending'
                    ORDER BY id DESC
                    LIMIT 5
                """))

                pending = result.fetchall()
                if pending:
                    print_warning(f"Found {len(pending)} PENDING prediction requests:")
                    for req in pending:
                        print_info(
                            f"  • ID: {req[0]} | Year: {req[1]} | Sem: {req[2]} | Table: {req[3]} | Time: {req[5]}")
                else:
                    print_info("No pending requests (all processed or none submitted)")
            else:
                print_error("prediction_requests table does NOT exist")
                return False

        return True

    except ImportError as e:
        print_error(f"Missing Python package: {e}")
        print_info("Install with: pip install sqlalchemy pymysql")
        return False
    except Exception as e:
        print_error(f"Database connection failed: {e}")
        print_info("Check:")
        print_info("  • MySQL is running")
        print_info("  • Database 'schoolfeesys' exists")
        print_info("  • User 'root' has access")
        return False


def check_python_dependencies():
    """Check Python packages"""
    print_header("4. CHECKING PYTHON DEPENDENCIES")

    required_packages = [
        'pandas',
        'numpy',
        'joblib',
        'matplotlib',
        'seaborn',
        'sqlalchemy',
        'pymysql'
    ]

    missing = []
    for package in required_packages:
        try:
            __import__(package)
            print_success(f"{package}")
        except ImportError:
            print_error(f"{package} - NOT INSTALLED")
            missing.append(package)

    if missing:
        print_warning(f"\nInstall missing packages with:")
        print_info(f"pip install {' '.join(missing)}")
        return False

    return True


def check_watcher_process():
    """Check if watcher is running"""
    print_header("5. CHECKING WATCHER PROCESS")

    import subprocess

    try:
        if sys.platform == "win32":
            # Windows
            result = subprocess.run(
                ['tasklist', '/FI', 'IMAGENAME eq python.exe', '/FO', 'CSV'],
                capture_output=True,
                text=True
            )
            output = result.stdout
        else:
            # Linux/Mac
            result = subprocess.run(
                ['ps', 'aux'],
                capture_output=True,
                text=True
            )
            output = result.stdout

        if 'prediction_watcher' in output:
            print_success("Prediction watcher IS RUNNING")

            # Extract process info
            lines = [l for l in output.split('\n') if 'prediction_watcher' in l]
            for line in lines[:3]:  # Show first 3 matches
                print_info(f"  {line.strip()}")

            return True
        else:
            print_error("Prediction watcher is NOT RUNNING")
            print_info("Start it with: py prediction_watcher.py")
            return False

    except Exception as e:
        print_warning(f"Could not check process list: {e}")
        return None


def check_watcher_log():
    """Check watcher log file"""
    print_header("6. CHECKING WATCHER LOG")

    work_dir = os.path.dirname(os.path.abspath(__file__))
    log_file = os.path.join(work_dir, 'watcher.log')

    if not os.path.exists(log_file):
        print_warning("watcher.log does NOT exist")
        print_info("This means watcher hasn't logged anything yet")
        return False

    try:
        with open(log_file, 'r') as f:
            lines = f.readlines()

        print_success(f"watcher.log exists ({len(lines)} lines)")

        # Show last 10 lines
        print_info("\nLast 10 log entries:")
        for line in lines[-10:]:
            print_info(f"  {line.strip()}")

        # Check for errors
        error_lines = [l for l in lines if 'ERROR' in l.upper() or '❌' in l]
        if error_lines:
            print_warning(f"\nFound {len(error_lines)} error(s) in log:")
            for line in error_lines[-5:]:
                print_info(f"  {line.strip()}")

        return True

    except Exception as e:
        print_error(f"Could not read log: {e}")
        return False


def test_predict_manually():
    """Try to run predict.py manually"""
    print_header("7. TESTING MANUAL PREDICTION RUN")

    work_dir = os.path.dirname(os.path.abspath(__file__))
    predict_script = os.path.join(work_dir, 'predict.py')

    if not os.path.exists(predict_script):
        print_error("predict.py not found")
        return False

    print_info("Attempting to run predict.py...")
    print_info("(This will take a moment)")

    import subprocess

    try:
        result = subprocess.run(
            [sys.executable, predict_script],
            capture_output=True,
            text=True,
            timeout=30
        )

        if result.returncode == 0:
            print_success("predict.py ran successfully!")
            print_info("\nOutput (first 500 chars):")
            print_info(result.stdout[:500])
        else:
            print_error(f"predict.py failed with return code {result.returncode}")
            print_info("\nError output:")
            print_info(result.stderr[:500])

        return result.returncode == 0

    except subprocess.TimeoutExpired:
        print_warning("predict.py is taking longer than 30 seconds (this is normal)")
        print_success("Script is running - wait for completion")
        return True
    except Exception as e:
        print_error(f"Could not run predict.py: {e}")
        return False


def provide_solutions():
    """Provide common solutions"""
    print_header("💡 COMMON SOLUTIONS")

    print(f"{Colors.YELLOW}Problem: Trigger file exists but prediction not starting{Colors.END}")
    print_info("Solution:")
    print_info("  1. Stop watcher: Ctrl+C or kill the process")
    print_info("  2. Delete trigger file: prediction_trigger.json")
    print_info("  3. Restart watcher: py prediction_watcher.py")
    print_info("  4. Upload CSV again")

    print(f"\n{Colors.YELLOW}Problem: Watcher not detecting uploads{Colors.END}")
    print_info("Solution:")
    print_info("  1. Check watcher is running: ps aux | grep prediction_watcher")
    print_info("  2. Check watcher.log for errors")
    print_info("  3. Verify upload_csv.php creates trigger file")
    print_info("  4. Check file permissions")

    print(f"\n{Colors.YELLOW}Problem: Database connection failing{Colors.END}")
    print_info("Solution:")
    print_info("  1. Start MySQL: sudo systemctl start mysql")
    print_info("  2. Check credentials in predict.py (lines 75-80)")
    print_info("  3. Grant permissions: GRANT ALL ON schoolfeesys.* TO 'root'@'localhost'")

    print(f"\n{Colors.YELLOW}Problem: Missing Python packages{Colors.END}")
    print_info("Solution:")
    print_info("  pip install pandas numpy joblib sqlalchemy pymysql matplotlib seaborn")


def main():
    """Run all diagnostics"""
    print(f"\n{Colors.BLUE}")
    print("=" * 70)
    print("🔍 PREDICTION SYSTEM DIAGNOSTIC TOOL")
    print("=" * 70)
    print(f"{Colors.END}")
    print_info(f"Run time: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")

    results = {
        'Files': check_files(),
        'Trigger File': check_trigger_file(),
        'Database': check_database_connection(),
        'Dependencies': check_python_dependencies(),
        'Watcher Process': check_watcher_process(),
        'Watcher Log': check_watcher_log(),
    }

    # Summary
    print_header("📊 DIAGNOSTIC SUMMARY")

    for check, result in results.items():
        if result is True:
            print_success(f"{check}: PASS")
        elif result is False:
            print_error(f"{check}: FAIL")
        else:
            print_warning(f"{check}: UNKNOWN")

    # Overall status
    failed = [k for k, v in results.items() if v is False]

    print()
    if not failed:
        print_success("✅ ALL CHECKS PASSED!")
        print_info("\nSystem should be working. If predictions still don't run:")
        print_info("  1. Check prediction_requests table for errors")
        print_info("  2. Try manual run: python predict.py")
        print_info("  3. Review watcher.log for clues")
    else:
        print_error(f"❌ {len(failed)} CHECK(S) FAILED: {', '.join(failed)}")
        print_info("\nFix these issues before predictions can run")

    # Provide solutions
    provide_solutions()

    # Ask if user wants to test manual run
    print_header("🧪 MANUAL TEST")
    response = input("Do you want to test running predict.py manually now? (y/n): ").strip().lower()

    if response == 'y':
        test_predict_manually()

    print(f"\n{Colors.BLUE}Diagnostic complete!{Colors.END}\n")


if __name__ == "__main__":
    main()