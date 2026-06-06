#!/usr/bin/env python3
"""
Test script to verify prediction system setup
"""
import sys
import os

print("=" * 70)
print("PREDICTION SYSTEM TEST")
print("=" * 70)

# Test 1: Python version
print("\n1. Testing Python version...")
print(f"   Python: {sys.version}")
if sys.version_info >= (3, 6):
    print("   ✓ Python version OK")
else:
    print("   ✗ Python 3.6+ required")
    sys.exit(1)

# Test 2: Required modules
print("\n2. Testing required modules...")
modules = {
    'pandas': 'pip install pandas',
    'numpy': 'pip install numpy',
    'joblib': 'pip install joblib',
    'sqlalchemy': 'pip install sqlalchemy',
    'pymysql': 'pip install pymysql'
}

missing = []
for module, install_cmd in modules.items():
    try:
        __import__(module)
        print(f"   ✓ {module}")
    except ImportError:
        print(f"   ✗ {module} - Install with: {install_cmd}")
        missing.append(module)

if missing:
    print(f"\n❌ Missing modules: {', '.join(missing)}")
    sys.exit(1)

# Test 3: Model files
print("\n3. Testing model files...")
work_dir = os.path.dirname(os.path.abspath(__file__))
model_files = {
    'scaler_v2.pkl': 'Scaler',
    'svm_model_v2.pkl': 'SVM Model',
    'logistic_model_v2.pkl': 'Logistic Model',
    'label_encoder_v2.pkl': 'Label Encoder'
}

missing_models = []
for filename, desc in model_files.items():
    filepath = os.path.join(work_dir, filename)
    if os.path.exists(filepath):
        size = os.path.getsize(filepath)
        print(f"   ✓ {desc}: {filename} ({size:,} bytes)")
    else:
        print(f"   ✗ {desc}: {filename} not found")
        missing_models.append(filename)

if missing_models:
    print(f"\n❌ Missing model files: {', '.join(missing_models)}")
    print(f"Location: {work_dir}")
    print("\nYou need to train the models first before running predictions.")
    sys.exit(1)

# Test 4: Database connection
print("\n4. Testing database connection...")
try:
    from sqlalchemy import create_engine, text

    DB_CONFIG = {
        'user': 'root',
        'password': '',
        'host': 'localhost',
        'database': 'ddss'
    }

    connection_string = f"mysql+pymysql://{DB_CONFIG['user']}:{DB_CONFIG['password']}@{DB_CONFIG['host']}/{DB_CONFIG['database']}"
    engine = create_engine(connection_string)

    with engine.connect() as conn:
        result = conn.execute(text("SELECT VERSION()"))
        version = result.fetchone()[0]
        print(f"   ✓ Connected to MySQL: {version}")

        # Check for student tables
        tables_result = conn.execute(text("SHOW TABLES LIKE 'student_%_sem_%'"))
        tables = [row[0] for row in tables_result]
        print(f"   ✓ Found {len(tables)} student tables")

        if tables:
            for table in tables[:5]:  # Show first 5
                count_result = conn.execute(text(f"SELECT COUNT(*) FROM `{table}`"))
                count = count_result.fetchone()[0]
                print(f"      - {table}: {count} students")
            if len(tables) > 5:
                print(f"      ... and {len(tables) - 5} more tables")
        else:
            print("   ⚠ No student tables found - upload CSV data first")

except Exception as e:
    print(f"   ✗ Database error: {e}")
    print("\nCheck:")
    print("   - MySQL is running")
    print("   - Database 'ddss' exists")
    print("   - Connection credentials are correct")
    sys.exit(1)

# Test 5: Load models
print("\n5. Testing model loading...")
try:
    import joblib

    scaler = joblib.load(os.path.join(work_dir, 'scaler_v2.pkl'))
    print("   ✓ Scaler loaded")

    svm_model = joblib.load(os.path.join(work_dir, 'svm_model_v2.pkl'))
    print("   ✓ SVM model loaded")

    logistic_model = joblib.load(os.path.join(work_dir, 'logistic_model_v2.pkl'))
    print("   ✓ Logistic model loaded")

    label_encoder = joblib.load(os.path.join(work_dir, 'label_encoder_v2.pkl'))
    print(f"   ✓ Label encoder loaded ({len(label_encoder.classes_)} classes)")

except Exception as e:
    print(f"   ✗ Model loading error: {e}")
    sys.exit(1)

# All tests passed
print("\n" + "=" * 70)
print("✅ ALL TESTS PASSED!")
print("=" * 70)
print("\nYour prediction system is ready to use.")
print("\nTo run predictions:")
print(f"   python {os.path.basename(__file__).replace('test_', '')}")
print("\nOr simply upload a CSV file through the web interface.")
print("=" * 70)