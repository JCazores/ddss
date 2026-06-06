#!/usr/bin/env python3
"""
Enhanced Prediction Watcher - FIXED VERSION
This watches for trigger files and runs predictions automatically
"""

import os
import time
import json
import subprocess
import sys
from datetime import datetime
from pathlib import Path

# Configuration
WORK_DIR = os.path.dirname(os.path.abspath(__file__))
TRIGGER_FILE = os.path.join(WORK_DIR, 'prediction_trigger.json')
PREDICT_SCRIPT = os.path.join(WORK_DIR, 'predict.py')
CHECK_INTERVAL = 2  # REDUCED to 2 seconds for faster detection
LOG_FILE = os.path.join(WORK_DIR, 'watcher.log')
MAX_LOG_SIZE = 5 * 1024 * 1024  # 5MB max log size
HEARTBEAT_FILE = os.path.join(WORK_DIR, 'watcher_heartbeat.txt')  # NEW: Heartbeat file


def update_heartbeat():
    """Update heartbeat file to indicate watcher is running"""
    try:
        with open(HEARTBEAT_FILE, 'w') as f:
            f.write(datetime.now().strftime('%Y-%m-%d %H:%M:%S'))
    except Exception as e:
        print(f"Could not update heartbeat: {e}")


def rotate_log_if_needed():
    """Rotate log file if it gets too large"""
    try:
        if os.path.exists(LOG_FILE) and os.path.getsize(LOG_FILE) > MAX_LOG_SIZE:
            backup = LOG_FILE + '.old'
            if os.path.exists(backup):
                os.remove(backup)
            os.rename(LOG_FILE, backup)
            log_message("[ROTATE] Log file rotated")
    except Exception as e:
        print(f"Could not rotate log: {e}")


def log_message(message):
    """Log message to file and console with timestamp"""
    timestamp = datetime.now().strftime('%Y-%m-%d %H:%M:%S')

    # Remove problematic emoji characters for Windows compatibility
    safe_message = message.replace('ðŸ“', '[WATCH]').replace('ðŸš€', '[START]').replace('âœ…', '[OK]')
    safe_message = safe_message.replace('âŒ', '[ERROR]').replace('âš ï¸', '[WARN]').replace('ðŸ’“', '[BEAT]')
    safe_message = safe_message.replace('ðŸŽ‰', '[NEW]').replace('ðŸ“‹', 'INFO').replace('ðŸ“…', 'DATE')
    safe_message = safe_message.replace('ðŸ“Š', 'DATA').replace('ðŸ“ˆ', 'COUNT').replace('ðŸ‘¤', 'USER')
    safe_message = safe_message.replace('â°', 'TIME').replace('â³', 'WAIT').replace('ðŸ“‚', 'DIR')
    safe_message = safe_message.replace('ðŸ', 'PY').replace('ðŸŽ¯', 'TARGET').replace('â±ï¸', 'TIMER')
    safe_message = safe_message.replace('ðŸ‘€', '[MONITOR]').replace('â¸ï¸', '[STOP]')

    log_entry = f"[{timestamp}] {safe_message}"

    # Always print to console (with emojis)
    print(f"[{timestamp}] {message}")
    sys.stdout.flush()  # Force output to appear immediately

    # Write to log file (without emojis for Windows compatibility)
    try:
        with open(LOG_FILE, 'a', encoding='utf-8') as f:
            f.write(log_entry + '\n')
            f.flush()  # Force write to disk
    except Exception as e:
        print(f"WARNING: Failed to write to log: {e}")


def read_trigger_file():
    """Read and validate trigger file - FIXED with better error handling"""
    max_retries = 3
    retry_delay = 0.5  # 500ms between retries
    
    for attempt in range(max_retries):
        try:
            if not os.path.exists(TRIGGER_FILE):
                return None

            # Check file size to ensure it's not empty
            file_size = os.path.getsize(TRIGGER_FILE)
            if file_size == 0:
                if attempt < max_retries - 1:
                    log_message(f"[WARN] Trigger file is empty, retry {attempt + 1}/{max_retries}")
                    time.sleep(retry_delay)
                    continue
                log_message("[WARN] Trigger file is empty after all retries")
                return None

            # Wait a moment to ensure file is fully written
            time.sleep(0.3)

            # Try to read the file
            with open(TRIGGER_FILE, 'r', encoding='utf-8') as f:
                content = f.read()
                
            if not content.strip():
                if attempt < max_retries - 1:
                    log_message(f"[WARN] Trigger file content empty, retry {attempt + 1}/{max_retries}")
                    time.sleep(retry_delay)
                    continue
                return None
                
            data = json.loads(content)

            # Validate required fields
            required_fields = ['table_name', 'year', 'semester', 'records_uploaded']
            missing = [f for f in required_fields if f not in data]

            if missing:
                log_message(f"[WARN] Trigger file missing fields: {', '.join(missing)}")
                return None

            log_message(f"[OK] Successfully read trigger file (size: {file_size} bytes)")
            return data

        except json.JSONDecodeError as e:
            if attempt < max_retries - 1:
                log_message(f"[WARN] JSON decode error (attempt {attempt + 1}/{max_retries}): {e}")
                time.sleep(retry_delay)
                continue
            log_message(f"[ERROR] Trigger file JSON error after all retries: {e}")
            return None
        except PermissionError:
            if attempt < max_retries - 1:
                log_message(f"[WARN] File locked, waiting... (attempt {attempt + 1}/{max_retries})")
                time.sleep(retry_delay)
                continue
            log_message("[ERROR] Could not access trigger file - permission denied")
            return None
        except Exception as e:
            if attempt < max_retries - 1:
                log_message(f"[WARN] Error reading trigger file (attempt {attempt + 1}/{max_retries}): {e}")
                time.sleep(retry_delay)
                continue
            log_message(f"[ERROR] Error reading trigger file: {e}")
            return None
    
    return None


def archive_trigger_file():
    """Archive processed trigger file - FIXED with retry logic"""
    max_retries = 3
    for attempt in range(max_retries):
        try:
            if os.path.exists(TRIGGER_FILE):
                archive_name = f"prediction_trigger_processed_{datetime.now().strftime('%Y%m%d_%H%M%S')}.json"
                archive_path = os.path.join(WORK_DIR, archive_name)

                # Copy instead of move (for debugging)
                import shutil
                shutil.copy2(TRIGGER_FILE, archive_path)
                
                # Small delay before deletion
                time.sleep(0.2)
                
                # Try to delete
                os.remove(TRIGGER_FILE)

                log_message(f"[OK] Trigger file archived: {archive_name}")
                return True
        except PermissionError:
            if attempt < max_retries - 1:
                log_message(f"[WARN] Could not delete trigger file (locked), retry {attempt + 1}/{max_retries}")
                time.sleep(1)
                continue
            log_message("[WARN] Could not delete trigger file (locked by another process)")
            return False
        except Exception as e:
            log_message(f"[WARN] Could not archive trigger file: {e}")
            return False
    
    return False


def update_database_status(status, error_msg=None):
    """Update prediction_requests table status"""
    try:
        from sqlalchemy import create_engine, text

        connection_string = "mysql+pymysql://root:@localhost/ddss"
        engine = create_engine(connection_string)

        with engine.begin() as conn:
            if status == 'processing':
                query = text("""
                    UPDATE prediction_requests 
                    SET status = 'processing',
                        prediction_started_at = NOW()
                    WHERE status = 'pending'
                    ORDER BY id DESC 
                    LIMIT 1
                """)
                conn.execute(query)
            elif status == 'failed':
                query = text("""
                    UPDATE prediction_requests 
                    SET status = 'failed',
                        error_message = :error,
                        prediction_completed_at = NOW()
                    WHERE status IN ('pending', 'processing')
                    ORDER BY id DESC 
                    LIMIT 1
                """)
                conn.execute(query, {'error': str(error_msg)})

        log_message(f"[OK] Database status updated: {status}")

    except Exception as e:
        log_message(f"[WARN] Could not update database status: {e}")


def run_prediction():
    """Execute the prediction script"""
    try:
        log_message("[START] Starting prediction process...")
        log_message(f"[DIR] Working directory: {WORK_DIR}")
        log_message(f"[PY] Python executable: {sys.executable}")
        log_message(f"[TARGET] Predict script: {PREDICT_SCRIPT}")

        # Update database status BEFORE running prediction
        update_database_status('processing')

        # Check if predict.py exists
        if not os.path.exists(PREDICT_SCRIPT):
            error_msg = f"predict.py not found at {PREDICT_SCRIPT}"
            log_message(f"[ERROR] {error_msg}")
            update_database_status('failed', error_msg)
            return False

        # Run predict.py with real-time output
        log_message("[WAIT] Executing predict.py (this may take several minutes)...")

        process = subprocess.Popen(
            [sys.executable, PREDICT_SCRIPT],
            stdout=subprocess.PIPE,
            stderr=subprocess.PIPE,
            cwd=WORK_DIR,
            text=True,
            bufsize=1,  # Line buffered
            universal_newlines=True
        )

        # Stream output in real-time
        while True:
            output = process.stdout.readline()
            if output:
                log_message(f"[predict.py] {output.strip()}")

            # Check if process finished
            if output == '' and process.poll() is not None:
                break

        # Get any remaining output
        stderr_output = process.stderr.read()

        if process.returncode == 0:
            log_message("[OK] Prediction completed successfully!")
            log_message(f"   Return code: {process.returncode}")
            return True
        else:
            log_message(f"[ERROR] Prediction failed with return code {process.returncode}")
            if stderr_output:
                log_message(f"[ERROR] Error output:")
                for line in stderr_output.split('\n')[:10]:  # First 10 lines
                    if line.strip():
                        log_message(f"   {line.strip()}")

            update_database_status('failed', f"Return code: {process.returncode}, Error: {stderr_output[:200]}")
            return False

    except FileNotFoundError:
        error_msg = f"Python executable not found: {sys.executable}"
        log_message(f"[ERROR] {error_msg}")
        update_database_status('failed', error_msg)
        return False
    except Exception as e:
        error_msg = f"Error running prediction: {e}"
        log_message(f"[ERROR] {error_msg}")
        log_message(f"   Exception type: {type(e).__name__}")

        import traceback
        log_message("   Traceback:")
        for line in traceback.format_exc().split('\n'):
            log_message(f"   {line}")

        update_database_status('failed', str(e))
        return False


def check_system_health():
    """Check if system is healthy"""
    issues = []

    # Check if predict.py exists
    if not os.path.exists(PREDICT_SCRIPT):
        issues.append(f"predict.py not found at {PREDICT_SCRIPT}")

    # Check if required model files exist
    model_files = ['scaler_v2.pkl', 'svm_model_v2.pkl', 'logistic_model_v2.pkl', 'label_encoder_v2.pkl']
    for model_file in model_files:
        if not os.path.exists(os.path.join(WORK_DIR, model_file)):
            issues.append(f"Model file missing: {model_file}")

    # Check database connectivity
    try:
        from sqlalchemy import create_engine, text
        connection_string = "mysql+pymysql://root:@localhost/ddss"
        engine = create_engine(connection_string)
        with engine.connect() as conn:
            conn.execute(text("SELECT 1"))
    except Exception as e:
        issues.append(f"Database connection failed: {e}")

    return issues


def main():
    """Main watcher loop"""
    log_message("=" * 70)
    log_message("[WATCH] PREDICTION WATCHER STARTED")
    log_message("=" * 70)
    log_message(f"[DIR] Working directory: {WORK_DIR}")
    log_message(f"[TARGET] Watching for: {TRIGGER_FILE}")
    log_message(f"[PY] Predict script: {PREDICT_SCRIPT}")
    log_message(f"[TIMER] Check interval: {CHECK_INTERVAL} seconds")
    log_message(f"[PY] Python: {sys.executable} (v{sys.version.split()[0]})")
    log_message("=" * 70)

    # Initial system health check
    log_message("[INFO] Performing initial system health check...")
    health_issues = check_system_health()

    if health_issues:
        log_message("[WARN] SYSTEM HEALTH ISSUES DETECTED:")
        for issue in health_issues:
            log_message(f"   [ERROR] {issue}")
        log_message("[WARN] Watcher will continue but predictions may fail")
    else:
        log_message("[OK] System health check passed")

    log_message("\n[MONITOR] Monitoring for uploads... (Press Ctrl+C to stop)\n")

    consecutive_errors = 0
    max_errors = 5
    check_count = 0

    try:
        while True:
            try:
                check_count += 1
                rotate_log_if_needed()
                
                # Update heartbeat every check
                update_heartbeat()

                # Log heartbeat every 30 checks (1 minute at 2 second intervals)
                if check_count % 30 == 0:
                    log_message(f"[BEAT] Heartbeat: Still monitoring... ({check_count} checks, {check_count * CHECK_INTERVAL}s)")

                # Check for trigger file
                trigger_data = read_trigger_file()

                if trigger_data:
                    log_message("\n" + "=" * 70)
                    log_message("[NEW] NEW UPLOAD DETECTED!")
                    log_message("=" * 70)
                    log_message(f"   [INFO] Table: {trigger_data.get('table_name')}")
                    log_message(f"   [DATE] Year: {trigger_data.get('year')}")
                    log_message(f"   [DATA] Semester: {trigger_data.get('semester')}")
                    log_message(f"   [COUNT] Records: {trigger_data.get('records_uploaded')}")
                    log_message(f"   [USER] Uploaded by: {trigger_data.get('uploaded_by', 'Unknown')}")
                    log_message(f"   [TIME] Timestamp: {trigger_data.get('trigger_timestamp', 'Unknown')}")
                    log_message("=" * 70)

                    # Run prediction
                    success = run_prediction()

                    if success:
                        # Archive trigger file only on success
                        archive_trigger_file()
                        log_message("=" * 70)
                        log_message("[OK] PREDICTION CYCLE COMPLETED SUCCESSFULLY")
                        log_message("=" * 70)
                        consecutive_errors = 0
                    else:
                        consecutive_errors += 1
                        log_message(f"[WARN] Prediction failed (error {consecutive_errors}/{max_errors})")

                        if consecutive_errors >= max_errors:
                            log_message(f"[ERROR] Too many consecutive errors ({max_errors})")
                            log_message("[INFO] Archiving problematic trigger file")
                            archive_trigger_file()
                            consecutive_errors = 0

                    log_message("\n[MONITOR] Resuming monitoring...\n")

                # Wait before next check
                time.sleep(CHECK_INTERVAL)

            except KeyboardInterrupt:
                raise  # Re-raise to outer handler
            except Exception as e:
                log_message(f"[WARN] Error in watcher loop: {e}")
                log_message(f"   Exception type: {type(e).__name__}")
                consecutive_errors += 1

                if consecutive_errors >= max_errors:
                    log_message(f"[ERROR] Too many consecutive errors - stopping watcher")
                    raise

                time.sleep(CHECK_INTERVAL)

    except KeyboardInterrupt:
        log_message("\n" + "=" * 70)
        log_message("[STOP] WATCHER STOPPED BY USER")
        log_message("=" * 70)
        # Clean up heartbeat file
        try:
            if os.path.exists(HEARTBEAT_FILE):
                os.remove(HEARTBEAT_FILE)
        except:
            pass
    except Exception as e:
        log_message(f"\n[ERROR] FATAL ERROR: {e}")
        log_message("=" * 70)
        log_message("Watcher stopped")
        # Clean up heartbeat file
        try:
            if os.path.exists(HEARTBEAT_FILE):
                os.remove(HEARTBEAT_FILE)
        except:
            pass
        raise


if __name__ == "__main__":
    main()