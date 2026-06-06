import pandas as pd
import numpy as np
import joblib
import json
import matplotlib

matplotlib.use('Agg')
import matplotlib.pyplot as plt
import seaborn as sns
from sqlalchemy import create_engine, text
from datetime import datetime
import warnings
import os
import sys
import hashlib
import traceback
import io

if sys.platform == 'win32':
    sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8', errors='replace')
    sys.stderr = io.TextIOWrapper(sys.stderr.buffer, encoding='utf-8', errors='replace')

warnings.filterwarnings('ignore')


def update_prediction_status(engine, status, predictions_count=0, error_message=None):
    """Update the latest prediction request status"""
    try:
        from sqlalchemy import text

        if status == 'started':
            query = text("""
                UPDATE prediction_requests 
                SET status = 'processing',
                    prediction_started_at = NOW()
                WHERE status = 'processing' 
                AND prediction_started_at IS NULL
                ORDER BY id DESC 
                LIMIT 1
            """)
        elif status == 'completed':
            query = text("""
                UPDATE prediction_requests 
                SET status = 'completed',
                    prediction_completed_at = NOW(),
                    predictions_generated = :count
                WHERE status = 'processing'
                ORDER BY id DESC 
                LIMIT 1
            """)
        elif status == 'failed':
            query = text("""
                UPDATE prediction_requests 
                SET status = 'failed',
                    error_message = :error,
                    prediction_completed_at = NOW()
                WHERE status = 'processing'
                ORDER BY id DESC 
                LIMIT 1
            """)

        with engine.begin() as conn:
            if status == 'completed':
                conn.execute(query, {'count': predictions_count})
            elif status == 'failed':
                conn.execute(query, {'error': str(error_message)})
            else:
                conn.execute(query)

        print(f"[OK] Status updated: {status}")

    except Exception as e:
        print(f"[WARN] Failed to update status: {e}")


DB_CONFIG = {
    'user': 'root',
    'password': '',
    'host': 'localhost',
    'database': 'ddss'
}

MODEL_FILES = {
    'scaler': 'scaler_v2.pkl',
    'svm': 'svm_model_v2.pkl',
    'logistic': 'logistic_model_v2.pkl',
    'encoder': 'label_encoder_v2.pkl'
}

WORK_DIR = os.path.dirname(os.path.abspath(__file__))
OUTPUT_DIR = os.path.join(WORK_DIR, 'predictions_output')
os.makedirs(OUTPUT_DIR, exist_ok=True)


class NextSemesterForecaster:
    """Enhanced forecaster with unified calculation logic"""

    def __init__(self):
        self.engine = None
        self.scaler = None
        self.svm_model = None
        self.logistic_model = None
        self.label_encoder = None
        self.current_data = None
        self.trigger_info = None

        self.tuition_fees = {
            "BSCE": {1: 50000, 2: 60000, 3: 70000, 4: 80000, 5: 85000},
            "BSPHARMA": {1: 55000, 2: 65000, 3: 75000, 4: 85000, 5: 90000},
            "BSN": {1: 60000, 2: 70000, 3: 80000, 4: 75000},
            "BSPSYCH": {1: 40000, 2: 45000, 3: 50000, 4: 55000},
            "BSCS": {1: 39000, 2: 48000, 3: 58000, 4: 45000},
            "BSIT": {1: 38000, 2: 47000, 3: 57000, 4: 44000},
            "BSHACLO": {1: 45000, 2: 50000, 3: 55000, 4: 60000},
            "BSMEDTECH": {1: 55000, 2: 65000, 3: 75000, 4: 85000},
            "BSA": {1: 42000, 2: 52000, 3: 62000, 4: 72000},
            "BSIHM": {1: 40000, 2: 50000, 3: 60000, 4: 70000},
            "BSBIO": {1: 43000, 2: 50000, 3: 57000, 4: 64000},
            "BSEE": {1: 48000, 2: 58000, 3: 68000, 4: 78000},
            "BSECE": {1: 50000, 2: 60000, 3: 70000, 4: 80000},
            "BSSE": {1: 40000, 2: 50000, 3: 60000, 4: 70000},
            "BSHRM": {1: 38000, 2: 46000, 3: 54000, 4: 62000},
            "BSCA": {1: 40000, 2: 50000, 3: 60000, 4: 70000},
            "BSTM": {1: 38000, 2: 46000, 3: 54000, 4: 62000},
            "BSCMM": {1: 35000, 2: 45000, 3: 55000, 4: 62000},
            "BSBA": {1: 35000, 2: 45000, 3: 55000, 4: 42000},
            "BSCIHM": {1: 40000, 2: 50000, 3: 60000, 4: 70000},
            "ASSOCINCOMPSCIE": {1: 35000, 2: 40000},
            "BSREFCO": {1: 38000, 2: 46000, 3: 54000, 4: 62000},
            "BSAC": {1: 42000, 2: 52000, 3: 62000, 4: 72000},
        }

    def get_student_seed(self, student_id):
        """Generate consistent seed from student ID"""
        id_string = str(student_id)
        hash_object = hashlib.md5(id_string.encode())
        seed = int(hash_object.hexdigest(), 16) % (2 ** 32)
        return seed

    def setup_database_connection(self):
        """Setup database connection with error handling"""
        try:
            connection_string = f"mysql+pymysql://{DB_CONFIG['user']}:{DB_CONFIG['password']}@{DB_CONFIG['host']}/{DB_CONFIG['database']}"
            self.engine = create_engine(connection_string)

            with self.engine.connect() as conn:
                conn.execute(text("SELECT 1"))

            print("[OK] Database connection successful")
            self.create_tables_if_not_exist()
            return True
        except Exception as e:
            print(f"[ERROR] Database connection error: {e}")
            print(f"  Check: MySQL is running, database '{DB_CONFIG['database']}' exists")
            return False

    def create_tables_if_not_exist(self):
        """Create necessary tables if they don't exist"""
        try:
            print("\n[CHECK] Checking database tables...")
            
            create_predictions_table = """
            CREATE TABLE IF NOT EXISTS student_predictions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                student_id VARCHAR(50) NOT NULL,
                course VARCHAR(50),
                current_year INT,
                source_table VARCHAR(100),
                prediction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                current_semester_data JSON,
                next_semester_prediction JSON,
                risk_analysis JSON,
                interventions JSON,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_student_id (student_id),
                INDEX idx_prediction_date (prediction_date),
                INDEX idx_course (course),
                INDEX idx_source_table (source_table),
                UNIQUE KEY unique_student_source (student_id, source_table)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            """

            create_trends_table = """
            CREATE TABLE IF NOT EXISTS cohort_trends (
                id INT AUTO_INCREMENT PRIMARY KEY,
                prediction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                trends_data JSON,
                intervention_summary JSON,
                total_students INT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_prediction_date (prediction_date)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            """

            create_requests_table = """
            CREATE TABLE IF NOT EXISTS prediction_requests (
                id INT AUTO_INCREMENT PRIMARY KEY,
                status VARCHAR(50) DEFAULT 'pending',
                prediction_started_at TIMESTAMP NULL,
                prediction_completed_at TIMESTAMP NULL,
                predictions_generated INT DEFAULT 0,
                error_message TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_status (status),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            """

            with self.engine.begin() as conn:
                conn.execute(text(create_predictions_table))
                print("[OK] Table 'student_predictions' ready")
                
                conn.execute(text(create_trends_table))
                print("[OK] Table 'cohort_trends' ready")
                
                conn.execute(text(create_requests_table))
                print("[OK] Table 'prediction_requests' ready")

            print("[OK] All database tables verified/created successfully")
            return True

        except Exception as e:
            print(f"[ERROR] Error creating tables: {e}")
            traceback.print_exc()
            return False

    def load_models(self):
        """Load ML models with validation"""
        try:
            missing_files = []
            for name, filename in MODEL_FILES.items():
                filepath = os.path.join(WORK_DIR, filename)
                if not os.path.exists(filepath):
                    missing_files.append(filename)

            if missing_files:
                print(f"[ERROR] Missing model files: {', '.join(missing_files)}")
                print(f"  Expected location: {WORK_DIR}")
                return False

            self.scaler = joblib.load(MODEL_FILES['scaler'])
            self.svm_model = joblib.load(MODEL_FILES['svm'])
            self.logistic_model = joblib.load(MODEL_FILES['logistic'])
            self.label_encoder = joblib.load(MODEL_FILES['encoder'])

            print("[OK] Models loaded successfully")
            return True
        except Exception as e:
            print(f"[ERROR] Error loading models: {e}")
            return False

    def read_trigger_file(self):
        """Read the prediction trigger file to get upload details"""
        try:
            trigger_file = os.path.join(WORK_DIR, 'prediction_trigger.json')

            if not os.path.exists(trigger_file):
                print("[WARN] No trigger file found - will process all data")
                return None

            with open(trigger_file, 'r') as f:
                trigger_data = json.load(f)

            print(f"\n[INFO] Trigger Information:")
            print(f"   * Table: {trigger_data.get('table_name')}")
            print(f"   * Year: {trigger_data.get('year')}")
            print(f"   * Semester: {trigger_data.get('semester')}")
            print(f"   * Records: {trigger_data.get('records_uploaded')}")
            print(f"   * Uploaded by: {trigger_data.get('uploaded_by')}")
            print(f"   * Timestamp: {trigger_data.get('trigger_timestamp')}")

            return trigger_data

        except Exception as e:
            print(f"[WARN] Error reading trigger file: {e}")
            return None

    def fetch_current_semester_data(self):
        """Fetch student data - ONLY from newly uploaded table"""
        try:
            self.trigger_info = self.read_trigger_file()

            with self.engine.connect() as conn:
                if self.trigger_info and 'table_name' in self.trigger_info:
                    table_name = self.trigger_info['table_name']

                    print(f"\n[TARGET] Processing ONLY newly uploaded data from: {table_name}")

                    existing_predictions_query = text(f"""
                        SELECT DISTINCT student_id 
                        FROM student_predictions 
                        WHERE source_table = :table_name
                    """)

                    existing_result = conn.execute(existing_predictions_query, {'table_name': table_name})
                    existing_student_ids = {str(row[0]) for row in existing_result}

                    if existing_student_ids:
                        print(f"   [WARN] Found {len(existing_student_ids)} students with existing predictions")
                        print(f"   [OK] Will skip these students to prevent duplicates")

                    if existing_student_ids:
                        placeholders = ','.join([':id_' + str(i) for i in range(len(existing_student_ids))])
                        query = text(f"""
                            SELECT StudentID, Attendance, GPA, balance, course, year 
                            FROM `{table_name}` 
                            WHERE delete_status = 0
                            AND CAST(StudentID AS CHAR) NOT IN ({placeholders})
                        """)
                        params = {f'id_{i}': str(student_id) for i, student_id in enumerate(existing_student_ids)}
                        df = pd.read_sql(query, conn, params=params)
                    else:
                        query = text(f"""
                            SELECT StudentID, Attendance, GPA, balance, course, year 
                            FROM `{table_name}` 
                            WHERE delete_status = 0
                        """)
                        df = pd.read_sql(query, conn)
                    
                    if len(df) > 0:
                        df['StudentID'] = df['StudentID'].astype(str)

                    if len(df) > 0:
                        df['table'] = table_name
                        self.current_data = df

                        print(f"   [OK] Loaded {len(df)} NEW students (without existing predictions)")
                        if existing_student_ids:
                            print(f"   [INFO] Skipped {len(existing_student_ids)} students (already predicted)")

                        self.current_data.fillna({
                            'Attendance': self.current_data['Attendance'].median(),
                            'GPA': self.current_data['GPA'].median(),
                            'balance': 0
                        }, inplace=True)

                        print(f"\n[OK] Total NEW students to predict: {len(self.current_data)}")
                        return True
                    else:
                        print(f"\n[OK] All students from {table_name} already have predictions - nothing to do!")
                        return False

                else:
                    print("[WARN] No trigger file - processing ALL tables")
                    tables_result = conn.execute(text("SHOW TABLES"))
                    all_tables = [row[0] for row in tables_result]

                    data_frames = []
                    tables_processed = 0

                    for table_name in all_tables:
                        if table_name.startswith("student_") and "_sem_" in table_name:
                            try:
                                query = text(
                                    f"SELECT StudentID, Attendance, GPA, balance, course, year FROM `{table_name}` WHERE delete_status = 0")
                                df = pd.read_sql(query, conn)

                                if len(df) > 0:
                                    df['StudentID'] = df['StudentID'].astype(str)
                                    df['table'] = table_name
                                    data_frames.append(df)
                                    tables_processed += 1
                                    print(f"  [OK] Loaded {len(df)} students from {table_name}")
                            except Exception as e:
                                print(f"  [WARN] Skipping {table_name}: {e}")
                                continue

                    if data_frames:
                        self.current_data = pd.concat(data_frames, ignore_index=True)

                        self.current_data.fillna({
                            'Attendance': self.current_data['Attendance'].median(),
                            'GPA': self.current_data['GPA'].median(),
                            'balance': 0
                        }, inplace=True)

                        print(f"\n[OK] Total: {len(self.current_data)} students from {tables_processed} tables")
                        return True
                    else:
                        print("[ERROR] No student data found")
                        return False

        except Exception as e:
            print(f"[ERROR] Error fetching data: {e}")
            traceback.print_exc()
            return False

    def get_tuition_fee(self, course, year):
        """Get tuition fee for course and year"""
        try:
            year = int(year)
            return self.tuition_fees.get(course, {}).get(year, 40000)
        except:
            return 40000

    def attendance_to_present(self, attendance_percentage):
        """Convert attendance percentage to present days"""
        total_classes = 20
        present_days = (attendance_percentage / 100) * total_classes
        return round(present_days)

    def calculate_risk_and_dropout(self, attendance, gpa, balance, course, year, student_id=None):
        """
        UNIFIED CALCULATION - Using exact logic from first document
        Calculate risk factors and dropout probability with consistent randomness
        """
        tuition = self.get_tuition_fee(course, year)
        absences = 20 - self.attendance_to_present(attendance)
        
        # Create consistent random number generator for this student
        if student_id:
            seed = self.get_student_seed(student_id)
            rng = np.random.RandomState(seed)
        else:
            rng = np.random.RandomState()
        
        risk_factors = {"High": 0, "Medium": 0}
        reasons = []
        solutions = []
        admin_actions = []
        
        # ✅ Attendance Rule (Converted to absences) - EXACT LOGIC FROM DOCUMENT 1
        if absences >= 5:
            risk_factors["High"] += 1
            reasons.append("Student has moved to a different location.")
            solutions.append("Consider online classes or transferring to a nearby school.")
            admin_actions.append("Provide guidance on transfer options.")
        elif 3 <= absences <= 4:
            risk_factors["Medium"] += 1
            reasons.append("Difficulties in commuting to school.")
            solutions.append("Explore better transportation options or schedule adjustments.")
            admin_actions.append("Assess student's transportation challenges.")
        else:
            reasons.append("Good attendance record")
            solutions.append("Maintain good attendance.")
        
        # ✅ GPA Rule - EXACT LOGIC FROM DOCUMENT 1
        if 4.00 <= gpa <= 5.00:
            risk_factors["High"] += 1
            reasons.append("Struggling to cope with academic requirements.")
            solutions.append("Seek immediate academic support or tutoring.")
            admin_actions.append("Schedule academic intervention.")
        elif 3.00 <= gpa < 4.00:
            risk_factors["High"] += 1
            reasons.append("Poor academic performance")
            solutions.append("Attend review classes and get tutoring.")
            admin_actions.append("Schedule academic counseling session.")
        elif 2.25 <= gpa < 3.00:
            risk_factors["Medium"] += 1
            reasons.append("Moderate academic performance")
            solutions.append("Improve study habits and review lessons regularly.")
            admin_actions.append("Monitor academic progress.")
        else:
            reasons.append("Good academic standing")
            solutions.append("Maintain good academic performance.")
        
        # ✅ Financial Risk Rule - EXACT LOGIC FROM DOCUMENT 1
        if balance > 0.3 * tuition:
            risk_factors["High"] += 1
            reasons.append("Financial constraints affecting tuition payment.")
            solutions.append("Apply for financial aid or payment plan.")
            admin_actions.append("Call for financial consultation with accounting.")
        elif 0.1 * tuition <= balance <= 0.3 * tuition:
            risk_factors["Medium"] += 1
            reasons.append("Moderate unpaid balance")
            solutions.append("Consider part-time work or payment terms.")
            admin_actions.append("Notify student about tuition balance.")
        else:
            reasons.append("Financially stable")
            solutions.append("Continue managing finances well.")
        
        # ✅ Determine Overall Risk Level - EXACT LOGIC FROM DOCUMENT 1
        if risk_factors["High"] >= 2:
            final_risk_level = "High Risk"
        elif risk_factors["Medium"] >= 2 or risk_factors["High"] == 1:
            final_risk_level = "Medium Risk"
        else:
            final_risk_level = "Low Risk"
            reasons = ["None"]
            solutions = ["None"]
        
        # ✅ Generate a consistent dropout probability based on student data
        tuition_fee = tuition  # Alias for clarity in formula
        dropout_base = (absences * 2.5) + (gpa * 10) + ((balance / tuition_fee) * 100)

        if final_risk_level == "High Risk":
            # High Risk: 66-95% range with randomization
            base_prob = min(95, max(66, dropout_base * 0.8))
            random_adjustment = rng.uniform(-5, 5)  # ±3% variation
            dropout_probability = min(95, max(66, base_prob + random_adjustment))
            admin_actions.append("Schedule parent/guardian meeting for intervention.")
        elif final_risk_level == "Medium Risk":
            # Medium Risk: 26-65% range with randomization
            base_prob = min(65, max(26, dropout_base * 0.4))
            random_adjustment = rng.uniform(-4, 4)  # ±4% variation
            dropout_probability = min(65, max(26, base_prob + random_adjustment))
            admin_actions.append("Monitor student and schedule counseling if needed.")
        else:
            # Low Risk: 5-25% range with randomization
            base_prob = min(25, max(5, dropout_base * 0.4))
            random_adjustment = rng.uniform(-2, 2)  # ±2% variation
            dropout_probability = min(25, max(5, base_prob + random_adjustment))
            if "None" not in admin_actions:
                admin_actions = ["Continue regular monitoring."]
        
        return {
            'risk_level': final_risk_level,
            'dropout_probability': round(dropout_probability, 2),
            'risk_factors': risk_factors,
            'reasons': reasons,
            'solutions': solutions,
            'admin_actions': list(set(admin_actions))
        }

    def calculate_semester_progression_factors(self, student_row, rng):
        """Calculate factors that affect student performance between semesters"""
        year = student_row['year']
        current_gpa = student_row['GPA']
        current_attendance = student_row['Attendance']

        is_midyear = rng.choice([True, False])

        factors = {
            'is_midyear': is_midyear,
            'attendance_modifier': 1.0,
            'gpa_modifier': 1.0,
            'financial_modifier': 1.0
        }

        if year == 1:
            factors['attendance_modifier'] = rng.normal(0.95, 0.1)
            factors['gpa_modifier'] = rng.normal(1.05, 0.1)
        elif year >= 4:
            factors['attendance_modifier'] = rng.normal(0.90, 0.1)
            factors['gpa_modifier'] = rng.normal(0.95, 0.1)
            factors['financial_modifier'] = rng.normal(1.1, 0.1)

        if current_gpa > 3.5:
            factors['gpa_modifier'] *= rng.normal(1.1, 0.1)
            factors['attendance_modifier'] *= rng.normal(1.05, 0.05)
        elif current_gpa < 2.0:
            factors['gpa_modifier'] *= rng.normal(0.98, 0.05)

        if current_attendance < 75:
            factors['attendance_modifier'] *= rng.normal(1.1, 0.1)

        if is_midyear:
            factors['attendance_modifier'] *= rng.normal(0.95, 0.05)
            factors['financial_modifier'] *= rng.normal(0.8, 0.1)
        else:
            factors['financial_modifier'] *= rng.normal(1.2, 0.1)

        return factors

    def predict_next_semester_metrics(self, student_row):
        """Predict next semester performance"""
        student_id = student_row['StudentID']
        seed = self.get_student_seed(student_id)
        rng = np.random.RandomState(seed)

        current_attendance = float(student_row['Attendance'])
        current_gpa = float(student_row['GPA'])
        current_balance = float(student_row['balance'])
        course = str(student_row['course'])
        year = int(student_row['year'])

        semester_factors = self.calculate_semester_progression_factors(student_row, rng)

        attendance_factor = rng.normal(0.95, 0.05)
        next_attendance = max(0, min(100, current_attendance * attendance_factor))
        next_attendance *= semester_factors['attendance_modifier']

        gpa_factor = rng.normal(0.98, 0.1)
        next_gpa = max(1.0, min(5.0, current_gpa * gpa_factor))
        next_gpa *= semester_factors['gpa_modifier']

        next_year = year if semester_factors['is_midyear'] else min(year + 1, 5)
        next_tuition = self.get_tuition_fee(course, next_year)
        payment = max(0, current_balance * rng.normal(0.3, 0.2))
        next_balance = max(0, current_balance - payment) + next_tuition
        next_balance *= semester_factors['financial_modifier']

        return {
            'next_semester_attendance': max(0, min(100, round(next_attendance, 2))),
            'next_semester_gpa': max(1.0, min(5.0, round(next_gpa, 2))),
            'next_semester_balance': max(0, round(next_balance, 2)),
            'next_year_level': next_year,
            'semester_progression': semester_factors
        }

    def generate_interventions(self, student_row, predicted_metrics, current_risk_result, next_risk_result):
        """
        Generate targeted interventions using EXACT logic from document 1
        Includes random additional reasons
        """
        interventions = []
        reasons = list(next_risk_result['reasons'])
        solutions = list(next_risk_result['solutions'])
        admin_actions = list(next_risk_result['admin_actions'])
        
        # ✅ Add random additional reasons - EXACT LOGIC FROM DOCUMENT 1
        additional_reasons = [
            ("Personal circumstances impacting studies.", "Seek personal counseling support."),
            ("Health issues affecting attendance and performance.", "Consult school health services."),
            ("Class schedule conflicts with other responsibilities.", "Adjust course schedule if possible."),
            ("Desired program is not available.", "Explore similar programs or transfer options."),
            ("Student decided to shift to another course.", "Consult academic advisor for proper transition."),
            ("Student has transferred to another institution.", "Request for transfer documentation.")
        ]
        
        # Only add if at risk
        if next_risk_result['risk_level'] != "Low Risk":
            seed = self.get_student_seed(student_row['StudentID'])
            rng = np.random.RandomState(seed)
            extra_reason, extra_solution = additional_reasons[rng.randint(len(additional_reasons))]
            reasons.append(extra_reason)
            solutions.append(extra_solution)
        
        # Build interventions from reasons, solutions, and admin actions
        max_len = max(len(reasons), len(solutions), len(admin_actions))
        for i in range(max_len):
            intervention = {}
            if i < len(reasons):
                intervention['reason'] = reasons[i]
            if i < len(solutions):
                intervention['solution'] = solutions[i]
            if i < len(admin_actions):
                intervention['admin_action'] = admin_actions[i]
            
            if intervention:
                interventions.append(intervention)
        
        return interventions

    def analyze_cohort_trends(self, forecasts):
        """Analyze trends across the entire cohort"""
        print("\n[DATA] Analyzing Cohort Trends...")

        risk_changes = [f['risk_analysis']['risk_change'] for f in forecasts]
        escalating = len([r for r in risk_changes if 'Escalating' in r])
        improving = len([r for r in risk_changes if 'Improving' in r])
        stable = len([r for r in risk_changes if 'Stable' in r])

        current_high_risk = len([f for f in forecasts if f['current_semester']['risk_level'] == 'High Risk'])
        next_high_risk = len([f for f in forecasts if f['next_semester']['predicted_risk_level'] == 'High Risk'])

        current_medium_risk = len([f for f in forecasts if f['current_semester']['risk_level'] == 'Medium Risk'])
        next_medium_risk = len([f for f in forecasts if f['next_semester']['predicted_risk_level'] == 'Medium Risk'])

        critical = len([f for f in forecasts if f['risk_analysis']['intervention_urgency'] == 'CRITICAL'])
        high_urgency = len([f for f in forecasts if f['risk_analysis']['intervention_urgency'] == 'HIGH'])
        medium_urgency = len([f for f in forecasts if f['risk_analysis']['intervention_urgency'] == 'MEDIUM'])

        prob_changes = [f['risk_analysis']['probability_change'] for f in forecasts]
        avg_prob_change = np.mean(prob_changes)

        trends = {
            'total_students': len(forecasts),
            'risk_level_changes': {
                'escalating': escalating,
                'improving': improving,
                'stable': stable,
                'escalating_percentage': (escalating / len(forecasts)) * 100,
                'improving_percentage': (improving / len(forecasts)) * 100
            },
            'risk_distribution': {
                'current_semester': {
                    'high_risk': current_high_risk,
                    'medium_risk': current_medium_risk,
                    'low_risk': len(forecasts) - current_high_risk - current_medium_risk
                },
                'next_semester': {
                    'high_risk': next_high_risk,
                    'medium_risk': next_medium_risk,
                    'low_risk': len(forecasts) - next_high_risk - next_medium_risk
                }
            },
            'intervention_urgency': {
                'critical': critical,
                'high': high_urgency,
                'medium': medium_urgency,
                'low': len(forecasts) - critical - high_urgency - medium_urgency
            },
            'average_probability_change': avg_prob_change,
            'students_needing_immediate_intervention': critical + high_urgency
        }

        return trends

    def create_visualizations(self, forecasts, trends):
        """Create visualizations for next semester forecasts"""
        print("\n[DATA] Creating Visualizations...")

        output_dir = os.path.join(OUTPUT_DIR, 'plots')
        os.makedirs(output_dir, exist_ok=True)

        sns.set_style("whitegrid")
        plt.rcParams['figure.figsize'] = (12, 8)

        # 1. Risk Level Comparison
        current_risks = [f['current_semester']['risk_level'] for f in forecasts]
        next_risks = [f['next_semester']['predicted_risk_level'] for f in forecasts]

        fig, (ax1, ax2) = plt.subplots(1, 2, figsize=(15, 6))

        current_counts = pd.Series(current_risks).value_counts()
        colors = ['green', 'orange', 'red']
        ax1.pie(current_counts.values, labels=current_counts.index, autopct='%1.1f%%', colors=colors)
        ax1.set_title('Current Semester Risk Distribution', fontsize=14, fontweight='bold')

        next_counts = pd.Series(next_risks).value_counts()
        ax2.pie(next_counts.values, labels=next_counts.index, autopct='%1.1f%%', colors=colors)
        ax2.set_title('Next Semester Predicted Risk Distribution', fontsize=14, fontweight='bold')

        plt.tight_layout()
        plt.savefig(f'{output_dir}/risk_comparison.png', dpi=300, bbox_inches='tight')
        plt.close()

        # 2. Risk Level Changes
        risk_changes = [f['risk_analysis']['risk_change'] for f in forecasts]
        change_counts = {}
        for change in risk_changes:
            if 'Escalating' in change:
                change_counts['Escalating'] = change_counts.get('Escalating', 0) + 1
            elif 'Improving' in change:
                change_counts['Improving'] = change_counts.get('Improving', 0) + 1
            else:
                change_counts['Stable'] = change_counts.get('Stable', 0) + 1

        plt.figure(figsize=(10, 6))
        bars = plt.bar(change_counts.keys(), change_counts.values(),
                       color=['red', 'green', 'gray'], alpha=0.7)
        plt.title('Risk Level Changes for Next Semester', fontsize=16, fontweight='bold')
        plt.ylabel('Number of Students', fontsize=14)

        for bar in bars:
            height = bar.get_height()
            plt.text(bar.get_x() + bar.get_width() / 2., height + 5,
                     f'{int(height)}', ha='center', va='bottom', fontsize=12)

        plt.tight_layout()
        plt.savefig(f'{output_dir}/risk_changes.png', dpi=300, bbox_inches='tight')
        plt.close()

        # 3. Intervention Urgency Distribution
        urgencies = [f['risk_analysis']['intervention_urgency'] for f in forecasts]
        urgency_counts = pd.Series(urgencies).value_counts()

        plt.figure(figsize=(10, 6))
        colors_urgency = {'CRITICAL': 'darkred', 'HIGH': 'red', 'MEDIUM': 'orange', 'LOW': 'green'}
        bars = plt.bar(urgency_counts.index, urgency_counts.values,
                       color=[colors_urgency.get(x, 'gray') for x in urgency_counts.index])
        plt.title('Intervention Urgency for Next Semester', fontsize=16, fontweight='bold')
        plt.ylabel('Number of Students', fontsize=14)
        plt.xlabel('Urgency Level', fontsize=14)

        for bar in bars:
            height = bar.get_height()
            plt.text(bar.get_x() + bar.get_width() / 2., height + 2,
                     f'{int(height)}', ha='center', va='bottom', fontsize=12)

        plt.tight_layout()
        plt.savefig(f'{output_dir}/intervention_urgency.png', dpi=300, bbox_inches='tight')
        plt.close()

        # 4. Dropout Probability Changes
        prob_changes = [f['risk_analysis']['probability_change'] for f in forecasts]

        plt.figure(figsize=(12, 6))
        plt.hist(prob_changes, bins=30, alpha=0.7, color='blue', edgecolor='black')
        plt.axvline(x=0, color='red', linestyle='--', label='No Change')
        plt.title('Dropout Probability Changes for Next Semester', fontsize=16, fontweight='bold')
        plt.xlabel('Probability Change (%)', fontsize=14)
        plt.ylabel('Number of Students', fontsize=14)
        plt.legend()
        plt.grid(True, alpha=0.3)
        plt.tight_layout()
        plt.savefig(f'{output_dir}/probability_changes.png', dpi=300, bbox_inches='tight')
        plt.close()

        print(f"[OK] Visualizations saved to '{output_dir}' directory")

    def generate_intervention_report(self, forecasts):
        """Generate detailed intervention report"""
        print("\n[INFO] Generating Intervention Report...")

        critical_students = [f for f in forecasts if f['risk_analysis']['intervention_urgency'] == 'CRITICAL']
        high_urgency_students = [f for f in forecasts if f['risk_analysis']['intervention_urgency'] == 'HIGH']
        medium_urgency_students = [f for f in forecasts if f['risk_analysis']['intervention_urgency'] == 'MEDIUM']

        report = {
            'report_generated': datetime.now().isoformat(),
            'semester': 'Next Semester Forecast',
            'critical_interventions': [],
            'high_priority_interventions': [],
            'medium_priority_interventions': [],
            'summary_statistics': {
                'total_students': len(forecasts),
                'students_needing_critical_intervention': len(critical_students),
                'students_needing_high_priority_intervention': len(high_urgency_students),
                'students_needing_medium_priority_intervention': len(medium_urgency_students),
                'total_at_risk_students': len(critical_students) + len(high_urgency_students)
            }
        }

        for student in critical_students:
            report['critical_interventions'].append({
                'StudentID': student['StudentID'],
                'course': student['course'],
                'year': student['current_year'],
                'current_risk_level': student['current_semester']['risk_level'],
                'predicted_risk_level': student['next_semester']['predicted_risk_level'],
                'predicted_dropout_probability': student['next_semester']['predicted_dropout_probability'],
                'risk_change': student['risk_analysis']['risk_change'],
                'interventions': student['recommended_interventions']
            })

        for student in high_urgency_students:
            report['high_priority_interventions'].append({
                'StudentID': student['StudentID'],
                'course': student['course'],
                'year': student['current_year'],
                'current_risk_level': student['current_semester']['risk_level'],
                'predicted_risk_level': student['next_semester']['predicted_risk_level'],
                'predicted_dropout_probability': student['next_semester']['predicted_dropout_probability'],
                'risk_change': student['risk_analysis']['risk_change'],
                'interventions': student['recommended_interventions']
            })

        for student in medium_urgency_students:
            report['medium_priority_interventions'].append({
                'StudentID': student['StudentID'],
                'course': student['course'],
                'year': student['current_year'],
                'current_risk_level': student['current_semester']['risk_level'],
                'predicted_risk_level': student['next_semester']['predicted_risk_level'],
                'predicted_dropout_probability': student['next_semester']['predicted_dropout_probability'],
                'risk_change': student['risk_analysis']['risk_change'],
                'interventions': student['recommended_interventions']
            })

        return report

    def save_to_database(self, forecasts, trends, intervention_report):
        """Save predictions to database"""
        print("\n[SAVE] Saving predictions to database...")

        try:
            print("[OK] Using existing database tables")

            saved = 0
            skipped = 0
            for forecast in forecasts:
                try:
                    insert_query = text("""
                    INSERT INTO student_predictions 
                    (student_id, course, current_year, source_table, 
                     current_semester_data, next_semester_prediction, 
                     risk_analysis, interventions)
                    VALUES 
                    (:student_id, :course, :current_year, :source_table,
                     :current_data, :next_prediction, :risk_analysis, :interventions)
                    ON DUPLICATE KEY UPDATE
                     prediction_date = CURRENT_TIMESTAMP,
                     current_semester_data = VALUES(current_semester_data),
                     next_semester_prediction = VALUES(next_semester_prediction),
                     risk_analysis = VALUES(risk_analysis),
                     interventions = VALUES(interventions)
                    """)

                    with self.engine.begin() as conn:
                        result = conn.execute(insert_query, {
                            'student_id': str(forecast['StudentID']),
                            'course': forecast['course'],
                            'current_year': forecast['current_year'],
                            'source_table': forecast.get('table', 'unknown'),
                            'current_data': json.dumps(forecast['current_semester']),
                            'next_prediction': json.dumps(forecast['next_semester']),
                            'risk_analysis': json.dumps(forecast['risk_analysis']),
                            'interventions': json.dumps(forecast['recommended_interventions'])
                        })
                        
                        if result.rowcount == 1:
                            saved += 1
                        else:
                            skipped += 1

                    if (saved + skipped) % 50 == 0:
                        print(f"  Processed {saved + skipped}/{len(forecasts)} predictions...")

                except Exception as e:
                    print(f"  [WARN] Failed to save {forecast['StudentID']}: {e}")
                    continue

            print(f"[OK] Saved {saved} new predictions, updated {skipped} existing predictions")

            try:
                trends_json = json.dumps(trends)
                intervention_summary_json = json.dumps({
                    'critical_count': intervention_report['summary_statistics'][
                        'students_needing_critical_intervention'],
                    'high_priority_count': intervention_report['summary_statistics'][
                        'students_needing_high_priority_intervention'],
                    'total_at_risk': intervention_report['summary_statistics']['total_at_risk_students']
                })

                insert_trends_query = text("""
                INSERT INTO cohort_trends 
                (trends_data, intervention_summary, total_students)
                VALUES (:trends_data, :intervention_summary, :total_students)
                """)

                with self.engine.begin() as conn:
                    conn.execute(insert_trends_query, {
                        'trends_data': trends_json,
                        'intervention_summary': intervention_summary_json,
                        'total_students': trends['total_students']
                    })

                print("[OK] Saved cohort trends to database")

            except Exception as e:
                print(f"  [WARN] Failed to save cohort trends: {e}")

            print("\n[OK] Database save completed!")
            print(f"  * {saved} new predictions saved")
            print(f"  * {skipped} predictions updated")
            print("  * Cohort trends saved")
            print("  * Tables: student_predictions, cohort_trends")

            return True

        except Exception as e:
            print(f"\n[ERROR] Database save error: {e}")
            traceback.print_exc()
            return False

    def save_forecast_results(self, forecasts, trends, intervention_report):
        """Save all forecast results to files"""
        print("\n[SAVE] Saving Forecast Results to Files...")

        try:
            forecast_file = os.path.join(OUTPUT_DIR, 'next_semester_forecast.json')
            with open(forecast_file, 'w') as f:
                json.dump({
                    'generation_date': datetime.now().isoformat(),
                    'forecast_type': 'Next Semester Prediction',
                    'cohort_trends': trends,
                    'individual_forecasts': forecasts
                }, f, indent=4)

            intervention_file = os.path.join(OUTPUT_DIR, 'intervention_report.json')
            with open(intervention_file, 'w') as f:
                json.dump(intervention_report, f, indent=4)

            summary_data = []
            for forecast in forecasts:
                summary_data.append({
                    'StudentID': forecast['StudentID'],
                    'Course': forecast['course'],
                    'Current_Year': forecast['current_year'],
                    'Current_Risk': forecast['current_semester']['risk_level'],
                    'Next_Semester_Risk': forecast['next_semester']['predicted_risk_level'],
                    'Risk_Change': forecast['risk_analysis']['risk_change'],
                    'Current_Dropout_Prob': f"{forecast['current_semester']['dropout_probability']:.2f}%",
                    'Next_Semester_Dropout_Prob': f"{forecast['next_semester']['predicted_dropout_probability']:.2f}%",
                    'Intervention_Urgency': forecast['risk_analysis']['intervention_urgency'],
                    'Predicted_Attendance': f"{forecast['next_semester']['predicted_attendance']:.1f}%",
                    'Predicted_GPA': f"{forecast['next_semester']['predicted_gpa']:.2f}",
                    'Predicted_Balance': f"PHP{forecast['next_semester']['predicted_balance']:,.2f}"
                })

            summary_df = pd.DataFrame(summary_data)
            summary_file = os.path.join(OUTPUT_DIR, 'next_semester_summary.csv')
            summary_df.to_csv(summary_file, index=False)

            print("[OK] All forecast results saved:")
            print(f"   * {forecast_file}")
            print(f"   * {intervention_file}")
            print(f"   * {summary_file}")

            return True

        except Exception as e:
            print(f"[ERROR] Error saving files: {e}")
            traceback.print_exc()
            return False

    def print_executive_summary(self, trends, intervention_report):
        """Print executive summary of next semester forecast"""
        print("\n" + "=" * 70)
        print("[DATA] NEXT SEMESTER FORECAST - EXECUTIVE SUMMARY")
        print("=" * 70)

        total_students = trends['total_students']

        print(f"\n[TARGET] COHORT OVERVIEW:")
        print(f"   * Total Students Analyzed: {total_students:,}")
        print(
            f"   * Students with Escalating Risk: {trends['risk_level_changes']['escalating']} ({trends['risk_level_changes']['escalating_percentage']:.1f}%)")
        print(
            f"   * Students with Improving Risk: {trends['risk_level_changes']['improving']} ({trends['risk_level_changes']['improving_percentage']:.1f}%)")
        print(f"   * Students with Stable Risk: {trends['risk_level_changes']['stable']}")

        print(f"\n[TREND] RISK DISTRIBUTION CHANGES:")
        current_high = trends['risk_distribution']['current_semester']['high_risk']
        next_high = trends['risk_distribution']['next_semester']['high_risk']
        high_risk_change = next_high - current_high

        print(f"   * Current High Risk Students: {current_high} ({(current_high / total_students) * 100:.1f}%)")
        print(f"   * Predicted High Risk Students: {next_high} ({(next_high / total_students) * 100:.1f}%)")
        change_direction = 'UP' if high_risk_change > 0 else 'DOWN' if high_risk_change < 0 else 'STABLE'
        print(f"   * High Risk Change: {change_direction} {high_risk_change:+d} students")

        print(f"\n[WARN] INTERVENTION REQUIREMENTS:")
        critical = trends['intervention_urgency']['critical']
        high_urgency = trends['intervention_urgency']['high']
        immediate_intervention = critical + high_urgency

        print(f"   * Critical Interventions Needed: {critical} students")
        print(f"   * High Priority Interventions: {high_urgency} students")
        print(
            f"   * Total Immediate Attention Required: {immediate_intervention} students ({(immediate_intervention / total_students) * 100:.1f}%)")

        avg_change = trends['average_probability_change']
        print(f"\n[DATA] DROPOUT PROBABILITY TRENDS:")
        print(f"   * Average Probability Change: {avg_change:+.1f}%")
        trend_direction = 'INCREASING' if avg_change > 0 else 'DECREASING' if avg_change < 0 else 'STABLE'
        print(f"   * Overall Trend: {trend_direction}")

        print(f"\n[TARGET] KEY RECOMMENDATIONS:")
        if critical > 0:
            print(f"   * URGENT: {critical} students need immediate dean's office consultation")
        if high_urgency > 0:
            print(f"   * HIGH PRIORITY: {high_urgency} students need intensive intervention")
        if trends['risk_level_changes']['escalating_percentage'] > 20:
            print("   * ALERT: High percentage of students showing escalating risk")
        if avg_change > 5:
            print("   * WARNING: Overall dropout probability increasing significantly")
        if trends['risk_level_changes']['improving'] > trends['risk_level_changes']['escalating']:
            print("   * POSITIVE: More students improving than escalating")

        print("\n" + "=" * 70)

    def cleanup_trigger_file(self):
        """Clean up trigger file after successful processing"""
        try:
            trigger_file = os.path.join(WORK_DIR, 'prediction_trigger.json')
            if os.path.exists(trigger_file):
                archive_file = os.path.join(WORK_DIR,
                                            f'prediction_trigger_completed_{datetime.now().strftime("%Y%m%d_%H%M%S")}.json')
                os.rename(trigger_file, archive_file)
                print(f"\n[OK] Trigger file archived: {archive_file}")
        except Exception as e:
            print(f"[WARN] Could not cleanup trigger file: {e}")

    def run_forecast(self):
        """Run complete forecasting process"""
        print("[START] STUDENT DROPOUT PREDICTION SYSTEM")
        print("=" * 70)

        try:
            if not self.setup_database_connection():
                return False

            update_prediction_status(self.engine, 'started')

            if not self.load_models():
                update_prediction_status(self.engine, 'failed', error_message='Failed to load ML models')
                return False

            if not self.fetch_current_semester_data():
                if self.current_data is None or len(self.current_data) == 0:
                    print("\n[OK] No new students to predict - all students already have predictions")
                    update_prediction_status(self.engine, 'completed', predictions_count=0)
                    self.cleanup_trigger_file()
                    return True
                else:
                    update_prediction_status(self.engine, 'failed', error_message='No student data found')
                    return False

            print(f"\n[PREDICT] Generating predictions for {len(self.current_data)} NEW students...")

            forecasts = []
            for idx, (_, student) in enumerate(self.current_data.iterrows()):
                try:
                    if (idx + 1) % 50 == 0:
                        print(f"  Processing {idx + 1}/{len(self.current_data)}...")

                    # Calculate CURRENT semester risk using unified calculation
                    current_risk_result = self.calculate_risk_and_dropout(
                        float(student['Attendance']),
                        float(student['GPA']),
                        float(student['balance']),
                        str(student['course']),
                        int(student['year']),
                        str(student['StudentID'])  # Pass student ID for consistent randomness
                    )

                    # Predict next semester metrics
                    predicted_metrics = self.predict_next_semester_metrics(student)

                    # Calculate NEXT semester risk using unified calculation
                    next_risk_result = self.calculate_risk_and_dropout(
                        predicted_metrics['next_semester_attendance'],
                        predicted_metrics['next_semester_gpa'],
                        predicted_metrics['next_semester_balance'],
                        str(student['course']),
                        predicted_metrics['next_year_level'],
                        str(student['StudentID'])  # Pass student ID for consistent randomness
                    )

                    # Calculate risk change
                    risk_levels = {"Low Risk": 1, "Medium Risk": 2, "High Risk": 3}
                    change = risk_levels[next_risk_result['risk_level']] - risk_levels[current_risk_result['risk_level']]

                    if change > 0:
                        risk_change = f"Escalating ({change} level{'s' if change > 1 else ''} up)"
                    elif change < 0:
                        risk_change = f"Improving ({abs(change)} level{'s' if abs(change) > 1 else ''} down)"
                    else:
                        risk_change = "Stable"

                    # Determine urgency based on next semester dropout probability
                    next_dropout_prob = next_risk_result['dropout_probability']
                    if next_dropout_prob >= 75:
                        urgency = "CRITICAL"
                    elif next_dropout_prob >= 60 or (change > 0 and next_dropout_prob >= 50):
                        urgency = "HIGH"
                    elif next_dropout_prob >= 40:
                        urgency = "MEDIUM"
                    else:
                        urgency = "LOW"

                    # Generate interventions with random additional reasons
                    interventions = self.generate_interventions(student, predicted_metrics, current_risk_result, next_risk_result)

                    forecast = {
                        'StudentID': str(student['StudentID']),
                        'course': str(student['course']),
                        'current_year': int(student['year']),
                        'table': str(student['table']),
                        'current_semester': {
                            'attendance': float(student['Attendance']),
                            'gpa': float(student['GPA']),
                            'balance': float(student['balance']),
                            'risk_level': current_risk_result['risk_level'],
                            'dropout_probability': current_risk_result['dropout_probability']
                        },
                        'next_semester': {
                            'predicted_attendance': predicted_metrics['next_semester_attendance'],
                            'predicted_gpa': predicted_metrics['next_semester_gpa'],
                            'predicted_balance': predicted_metrics['next_semester_balance'],
                            'predicted_risk_level': next_risk_result['risk_level'],
                            'predicted_dropout_probability': next_risk_result['dropout_probability']
                        },
                        'risk_analysis': {
                            'risk_change': risk_change,
                            'probability_change': round(next_risk_result['dropout_probability'] - current_risk_result['dropout_probability'], 2),
                            'intervention_urgency': urgency
                        },
                        'recommended_interventions': interventions
                    }

                    forecasts.append(forecast)

                except Exception as e:
                    print(f"  [WARN] Error processing student {student.get('StudentID', 'unknown')}: {e}")
                    continue

            print(f"\n[OK] Generated {len(forecasts)} forecasts")

            # Analyze trends
            trends = self.analyze_cohort_trends(forecasts)

            # Generate intervention report
            intervention_report = self.generate_intervention_report(forecasts)

            # Create visualizations
            self.create_visualizations(forecasts, trends)

            # Save to database
            save_success = self.save_to_database(forecasts, trends, intervention_report)

            if not save_success:
                update_prediction_status(self.engine, 'failed', error_message='Failed to save predictions to database')
                return False

            # Save to files
            self.save_forecast_results(forecasts, trends, intervention_report)

            # Print summary
            self.print_executive_summary(trends, intervention_report)

            # Update status: Completed
            update_prediction_status(self.engine, 'completed', predictions_count=len(forecasts))

            # Cleanup trigger file
            self.cleanup_trigger_file()

            print("\n[SUCCESS] Next Semester Forecasting Complete!")
            print("\n[FILE] OUTPUT LOCATION:")
            print(f"   {OUTPUT_DIR}")
            print("\n[TIP] NEXT STEPS:")
            print("   * Review intervention_report.json for at-risk students")
            print("   * Check visualizations in plots/ directory")
            print("   * Query student_predictions table for individual details")
            print("   * Plan resource allocation based on cohort_trends")

            return True

        except Exception as e:
            print(f"\n[ERROR] Fatal error: {e}")
            import traceback
            traceback.print_exc()
            update_prediction_status(self.engine, 'failed', error_message=str(e))
            return False


# ============================================================================
# MAIN EXECUTION
# ============================================================================

def main():
    """Main entry point"""
    try:
        forecaster = NextSemesterForecaster()
        success = forecaster.run_forecast()

        if success:
            print("\n[SUCCESS] Prediction system completed successfully!")
            sys.exit(0)
        else:
            print("\n[ERROR] Prediction system failed")
            sys.exit(1)

    except KeyboardInterrupt:
        print("\n\n[WARN] Process interrupted by user")
        sys.exit(1)
    except Exception as e:
        print(f"\n[ERROR] Unexpected error: {e}")
        traceback.print_exc()
        sys.exit(1)


if __name__ == "__main__":
    main()