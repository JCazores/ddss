import os
import time
import subprocess

# Path to the flag file and retraining script
FLAG_FILE = "C:\\xampp\\htdocs\\DDSS\\retrain_flag.txt"
RETRAIN_SCRIPT = "C:\\xampp\\htdocs\\DDSS\\retrain_model.py"

print("Watching for upload completion flag...")
print("Press Ctrl+C to stop watching")

try:
    while True:
        if os.path.exists(FLAG_FILE):
            # Read the flag file content
            with open(FLAG_FILE, 'r') as file:
                flag_content = file.read()
                print(f"\nUpload detected: {flag_content}")

            # Delete the flag file
            os.remove(FLAG_FILE)

            # Run the retraining script
            print("\nStarting model retraining in this terminal...")
            print("-" * 50)
            subprocess.run(["python", RETRAIN_SCRIPT])
            print("-" * 50)
            print("\nRetraining complete. Resuming watching for next upload...")

        # Check every 2 seconds
        time.sleep(2)
except KeyboardInterrupt:
    print("\nWatcher stopped.")