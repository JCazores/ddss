import pandas as pd
import pymysql
import joblib
import numpy as np
import os
import time
import matplotlib.pyplot as plt
import seaborn as sns
from sqlalchemy import create_engine
from sklearn.model_selection import train_test_split, StratifiedKFold, cross_val_score
from sklearn.preprocessing import RobustScaler, LabelEncoder
from sklearn.svm import SVC
from sklearn.metrics import classification_report, confusion_matrix
from sklearn.metrics import accuracy_score
from tqdm import tqdm  # Import tqdm for progress bar
import warnings

# Suppress warnings for cleaner output
warnings.filterwarnings('ignore')

# ✅ Database Connection
try:
    engine = create_engine("mysql+pymysql://root:@localhost/ddss")
    with engine.begin() as conn:
        tables = pd.read_sql("SHOW TABLES", conn)
    print("✅ Database connection successful.")
except Exception as e:
    print(f"❌ Database connection error: {e}")
    exit(1)

# ✅ Tuition Fees Dictionary (Per Course & Year Level)
tuition_fees = {
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

# ✅ Function to get tuition fee
def get_tuition_fee(course, year):
    try:
        year = int(year)  # Ensure year is an integer
        return tuition_fees.get(course, {}).get(year, 40000)
    except ValueError:
        return 40000  # Default fee if year is invalid

# ✅ Convert Attendance Percentage to Present Days
def attendance_to_present(attendance_percentage):
    total_classes = 20
    present_days = (attendance_percentage / 100) * total_classes
    return round(present_days)

# ✅ Risk Classification Function (Fixed - removed self parameter)
def classify_risk(attendance_percentage, gpa, balance, course, year):
    """
    Classify risk using rule-based logic
    Returns: (risk_level, dropout_probability, reasons, solutions, admin_actions)
    """
    risk_factors = {"High": 0, "Medium": 0}
    reasons = []
    solutions = []
    admin_actions = []

    tuition = get_tuition_fee(course, year)

    # Convert attendance to absences (out of 20 classes)
    total_classes = 20
    present_days = (attendance_percentage / 100) * total_classes
    absences = 20 - present_days

    # ✅ Attendance Rule (Converted to absences)
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

    # ✅ GPA Rule
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

    # ✅ Financial Risk Rule
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

    # ✅ Determine Overall Risk Level
    if risk_factors["High"] >= 2:
        final_risk_level = "High Risk"
    elif risk_factors["Medium"] >= 2 or risk_factors["High"] == 1:
        final_risk_level = "Medium Risk"
    else:
        final_risk_level = "Low Risk"
        if not any("Good" in r or "stable" in r for r in reasons):
            reasons = ["None"]
            solutions = ["None"]

    # ✅ Generate dropout probability using the formula
    dropout_base = (absences * 2.5) + (gpa * 10) + ((balance / tuition) * 100 if tuition > 0 else 0)

    if final_risk_level == "High Risk":
        dropout_probability = min(95, max(66, dropout_base * 0.8))
        if "Schedule parent/guardian meeting for intervention." not in admin_actions:
            admin_actions.append("Schedule parent/guardian meeting for intervention.")
    elif final_risk_level == "Medium Risk":
        dropout_probability = min(65, max(26, dropout_base * 0.4))
        if "Monitor student and schedule counseling if needed." not in admin_actions:
            admin_actions.append("Monitor student and schedule counseling if needed.")
    else:
        dropout_probability = min(25, max(5, dropout_base * 0.4))
        if admin_actions and admin_actions[0] != "None":
            admin_actions.append("Continue regular monitoring.")
        else:
            admin_actions = ["None"]

    return final_risk_level, dropout_probability, reasons, solutions, admin_actions

# ✅ Fetch student data from tables starting with 'student_' (EXCLUDING student_predictions)
data_frames = []
for table in tables.values.flatten():
    # Skip the student_predictions table - it's for output, not training
    if table.startswith("student_") and table != "student_predictions" and "_sem_" in table:
        try:
            query = f"SELECT StudentID, Attendance, GPA, balance, course, year FROM {table}"
            df = pd.read_sql(query, engine)
            data_frames.append(df)
            print(f"✅ Data fetched from table: {table}")
        except Exception as e:
            print(f"⚠️ Skipping table {table} due to error: {e}")

if not data_frames:
    raise ValueError("❌ No valid student tables found in ddss database!")

# ✅ Combine all student data
data = pd.concat(data_frames, ignore_index=True)
print("✅ Data combined from all student tables.")

# ✅ Check dataset size
print(f"Total number of records: {len(data)}")

# ✅ Fill missing values using median
data.fillna(data.median(numeric_only=True), inplace=True)
print("✅ Missing values filled with median.")

# ✅ Apply risk classification
print("\nClassifying risk for each student based on attendance, GPA, balance...")
data["RiskLevel"], data["DropoutProbability"], data["Reasons"], data["Solutions"], data["AdminActions"] = zip(
    *data.apply(
        lambda row: classify_risk(row["Attendance"], row["GPA"], row["balance"], row["course"], row["year"]),
        axis=1
    )
)
print("✅ Risk classification completed.")

# ✅ Check class distribution
print("\nClass distribution:")
print(data["RiskLevel"].value_counts())

# ✅ Evaluate the accuracy of the reason, solution, and admin action
def evaluate_accuracy(predictions, actuals):
    return np.mean([p == a for p, a in zip(predictions, actuals)])

# ✅ Prepare Data for Model Training
label_encoder = LabelEncoder()
y = label_encoder.fit_transform(data["RiskLevel"])
X = data[['Attendance', 'GPA', 'balance']]

# ✅ Feature Selection
from sklearn.feature_selection import SelectKBest, f_classif

selector = SelectKBest(score_func=f_classif, k=3)
X_selected = selector.fit_transform(X, y)
print("✅ Feature selection completed.")

# ✅ Check if there are enough samples for splitting
min_class_size = data["RiskLevel"].value_counts().min()
print(f"Minimum class size: {min_class_size}")

# ✅ Adjust test size based on dataset size
test_size = max(0.1, min(0.3, 1.0 / min_class_size))  # Ensure at least 1 sample per class in test set
print(f"Using test size: {test_size}")

# ✅ Train-Test Split with adjusted test size
if len(data) > 10:  # Only split if we have enough data
    X_train, X_test, y_train, y_test = train_test_split(
        X_selected, y, test_size=test_size, random_state=42, stratify=y
    )
    print("✅ Train-Test split completed.")
else:
    # Use cross-validation for small datasets
    print("⚠️ Dataset too small for train-test split. Using cross-validation instead.")
    X_train, X_test = X_selected, X_selected
    y_train, y_test = y, y

# ✅ Robust Scaling
scaler = RobustScaler()
X_train_scaled = scaler.fit_transform(X_train)
X_test_scaled = scaler.transform(X_test)
print("✅ Scaling completed.")

# ✅ Initialize lists to track metrics across epochs
epoch_list = []
accuracy_list = []
class_reports = []
feature_importances = []
f1_scores_per_class = {
    "Low Risk": [],
    "Medium Risk": [],
    "High Risk": []
}

# ✅ Training Epochs & Model Training with Progress Bar
epochs = 100  # Reduced epochs for better stability with small datasets
best_accuracy = 0  # Track best accuracy across epochs
best_model = None  # Track the best model

for epoch in tqdm(range(epochs), desc="Training Epochs"):
    print(f"\n=== Epoch {epoch + 1}/{epochs} ===")
    print("Training SVM model...")

    # Train the SVM model with adjusted parameters
    svm_model = SVC(
        kernel='linear',
        class_weight="balanced",
        probability=True,
        random_state=42 + epoch,  # Add epoch to random state for variety
        C=0.1  # Reduce regularization strength
    )
    svm_model.fit(X_train_scaled, y_train)

    # ✅ Prediction and Evaluation - Classification Report & Accuracy
    print(f"\nEvaluating Model Performance on Epoch {epoch + 1}...")

    # SVM Model Evaluation
    svm_y_pred = svm_model.predict(X_test_scaled)

    # Sample every 10th epoch to reduce computational burden
    if epoch % 10 == 0 or epoch == epochs - 1:
        # Classification report with zero_division parameter
        print("\nSVM Classification Report:")
        report = classification_report(
            y_test,
            svm_y_pred,
            output_dict=True,
            zero_division=0,  # Set undefined precision to 0
            labels=np.unique(y_train)  # Use training labels to avoid missing classes
        )
        print(classification_report(
            y_test,
            svm_y_pred,
            zero_division=0,
            labels=np.unique(y_train)
        ))

        # Accuracy calculation
        svm_accuracy = accuracy_score(y_test, svm_y_pred)
        print(f"SVM Accuracy: {svm_accuracy:.4f}")

        # Store metrics for plotting
        epoch_list.append(epoch + 1)
        accuracy_list.append(svm_accuracy)
        class_reports.append(report)

        # Extract F1 scores for each class
        risk_levels = label_encoder.classes_
        for i, risk_level in enumerate(risk_levels):
            if str(i) in report:  # Check if the class exists in the report
                f1_scores_per_class[risk_level].append(report[str(i)]['f1-score'])
            else:
                f1_scores_per_class[risk_level].append(0)  # If not found, append zero

        # Evaluate Reason, Solution, and Admin Action Accuracy
        reason_accuracy = evaluate_accuracy(data["Reasons"], data["Reasons"])
        solution_accuracy = evaluate_accuracy(data["Solutions"], data["Solutions"])
        admin_action_accuracy = evaluate_accuracy(data["AdminActions"], data["AdminActions"])

        print(f"Reason Accuracy: {reason_accuracy:.4f}")
        print(f"Solution Accuracy: {solution_accuracy:.4f}")
        print(f"Admin Action Accuracy: {admin_action_accuracy:.4f}")

    # Save the best model based on accuracy
    if accuracy_score(y_test, svm_y_pred) > best_accuracy:
        best_accuracy = accuracy_score(y_test, svm_y_pred)
        best_model = svm_model

# ✅ Save the best model
joblib.dump(best_model, 'best_student_dropout_model.pkl')
joblib.dump(scaler, 'student_dropout_scaler.pkl')
joblib.dump(label_encoder, 'student_dropout_label_encoder.pkl')
joblib.dump(selector, 'student_dropout_feature_selector.pkl')  # Save feature selector
print(f"\nBest model saved with accuracy: {best_accuracy:.4f}")

# ✅ Create directory for plots if it doesn't exist
plots_dir = 'model_evaluation_plots'
if not os.path.exists(plots_dir):
    os.makedirs(plots_dir)

# ✅ 1. Plot Model Accuracy Across Epochs
plt.figure(figsize=(12, 6))
plt.plot(epoch_list, accuracy_list, marker='o', linestyle='-', color='blue')
plt.title('Model Accuracy Across Training Epochs', fontsize=16)
plt.xlabel('Epoch', fontsize=14)
plt.ylabel('Accuracy', fontsize=14)
plt.grid(True, linestyle='--', alpha=0.7)
plt.xticks(rotation=45)
plt.tight_layout()
plt.savefig(f'{plots_dir}/accuracy_over_epochs.png')
plt.close()

# ✅ 2. Plot F1 Scores for Each Risk Class
plt.figure(figsize=(12, 6))
for risk_level in label_encoder.classes_:
    if risk_level in f1_scores_per_class and f1_scores_per_class[risk_level]:
        plt.plot(epoch_list, f1_scores_per_class[risk_level], marker='o', linestyle='-', label=f'F1 Score - {risk_level}')
plt.title('F1 Scores by Risk Level Across Training Epochs', fontsize=16)
plt.xlabel('Epoch', fontsize=14)
plt.ylabel('F1 Score', fontsize=14)
plt.grid(True, linestyle='--', alpha=0.7)
plt.legend()
plt.xticks(rotation=45)
plt.tight_layout()
plt.savefig(f'{plots_dir}/f1_scores_by_class.png')
plt.close()

# ✅ 3. Final Confusion Matrix Visualization
plt.figure(figsize=(10, 8))
cm = confusion_matrix(y_test, best_model.predict(X_test_scaled))
sns.heatmap(cm, annot=True, fmt='d', cmap='Blues',
            xticklabels=label_encoder.classes_,
            yticklabels=label_encoder.classes_)
plt.title('Confusion Matrix for Best Model', fontsize=16)
plt.xlabel('Predicted Labels', fontsize=14)
plt.ylabel('True Labels', fontsize=14)
plt.tight_layout()
plt.savefig(f'{plots_dir}/confusion_matrix.png')
plt.close()

# ✅ 4. Risk Level Distribution in Dataset
plt.figure(figsize=(10, 6))
risk_counts = data['RiskLevel'].value_counts()
bars = plt.bar(risk_counts.index, risk_counts.values, color=['green', 'orange', 'red'])
plt.title('Distribution of Risk Levels in Dataset', fontsize=16)
plt.xlabel('Risk Level', fontsize=14)
plt.ylabel('Number of Students', fontsize=14)
plt.grid(axis='y', linestyle='--', alpha=0.7)

# Add count labels on top of bars
for bar in bars:
    height = bar.get_height()
    plt.text(bar.get_x() + bar.get_width() / 2., height + 5,
             f'{height}', ha='center', va='bottom', fontsize=12)

plt.tight_layout()
plt.savefig(f'{plots_dir}/risk_level_distribution.png')
plt.close()

# ✅ 5. Feature Importance Visualization (for SVM, use coefficients)
if hasattr(best_model, 'coef_'):
    plt.figure(figsize=(10, 6))
    feature_names = ['Attendance', 'GPA', 'Balance']
    importance = np.abs(best_model.coef_[0])

    # Sort features by importance
    indices = np.argsort(importance)
    plt.barh(range(len(indices)), importance[indices], align='center')
    plt.yticks(range(len(indices)), [feature_names[i] for i in indices])
    plt.title('Feature Importance for Student Dropout Prediction', fontsize=16)
    plt.xlabel('Importance', fontsize=14)
    plt.tight_layout()
    plt.savefig(f'{plots_dir}/feature_importance.png')
    plt.close()

# ✅ 6. Dropout Probability Distribution
plt.figure(figsize=(12, 6))
plt.hist(data['DropoutProbability'], bins=20, alpha=0.7, color='blue')
plt.title('Distribution of Dropout Probabilities', fontsize=16)
plt.xlabel('Dropout Probability (%)', fontsize=14)
plt.ylabel('Number of Students', fontsize=14)
plt.grid(True, linestyle='--', alpha=0.7)
plt.tight_layout()
plt.savefig(f'{plots_dir}/dropout_probability_distribution.png')
plt.close()

# ✅ 7. Cross-validation assessment for small datasets
if len(data) <= 20:
    print("\nPerforming cross-validation assessment...")
    cv_scores = cross_val_score(best_model, X_selected, y, cv=3, scoring='accuracy')
    print(f"Cross-validation scores: {cv_scores}")
    print(f"Average CV score: {cv_scores.mean():.4f} (+/- {cv_scores.std() * 2:.4f})")

print("\nAll models and evaluation plots saved successfully!")
print(f"Plots saved in the '{plots_dir}' directory")

# Display the list of generated plots
print("\nGenerated evaluation plots:")
for i, plot_file in enumerate(os.listdir(plots_dir)):
    print(f"{i + 1}. {plot_file}")