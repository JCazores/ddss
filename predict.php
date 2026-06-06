<?php
declare(strict_types=1);

@set_time_limit(0);
@date_default_timezone_set('UTC');

// Config
$DB_CONFIG = [
    'user' => 'root',
    'password' => '',
    'host' => 'localhost',
    'database' => 'ddss',
    'charset' => 'utf8mb4',
];

$WORK_DIR = __DIR__;
$OUTPUT_DIR = $WORK_DIR . DIRECTORY_SEPARATOR . 'predictions_output';
if (!is_dir($OUTPUT_DIR)) { @mkdir($OUTPUT_DIR, 0777, true); }

// Basic utils
function println(string $msg = ''): void { echo $msg . PHP_EOL; @ob_flush(); @flush(); }
function nowIso(): string { return date('c'); }

// Simple deterministic RNG
class RNG {
    private int $seed; private ?float $spare = null;
    public function __construct(int $seed) { $this->seed = $seed & 0xffffffff; }
    private function next01(): float { $this->seed = (int)(($this->seed * 1664525 + 1013904223) & 0xffffffff); return $this->seed / 4294967296.0; }
    public function uniform(float $a, float $b): float { return $a + ($b - $a) * $this->next01(); }
    public function normal(float $m, float $s): float { if ($this->spare !== null) { $z=$this->spare; $this->spare=null; return $m + $s*$z; } $u1=max(1e-12,$this->next01()); $u2=$this->next01(); $r=sqrt(-2*log($u1)); $th=2*M_PI*$u2; $z0=$r*cos($th); $z1=$r*sin($th); $this->spare=$z1; return $m + $s*$z0; }
}

function md5_seed(string $s): int { $h = md5($s); $part = substr($h, 0, 8); return intval(hexdec($part) & 0xffffffff); }

class NextSemesterForecaster {
    private ?PDO $pdo = null;
    private array $tuitionFees = [];
    private ?array $currentData = null;
    private ?array $triggerInfo = null;
    private string $workDir; private string $outputDir; private array $dbConfig;

    public function __construct(array $dbConfig, string $workDir, string $outputDir) {
        $this->dbConfig = $dbConfig; $this->workDir = $workDir; $this->outputDir = $outputDir;
        $this->tuitionFees = [
            'BSCE' => [1=>50000,2=>60000,3=>70000,4=>80000,5=>85000],
            'BSPHARMA' => [1=>55000,2=>65000,3=>75000,4=>85000,5=>90000],
            'BSN' => [1=>60000,2=>70000,3=>80000,4=>75000],
            'BSPSYCH' => [1=>40000,2=>45000,3=>50000,4=>55000],
            'BSCS' => [1=>39000,2=>48000,3=>58000,4=>45000],
            'BSIT' => [1=>38000,2=>47000,3=>57000,4=>44000],
            'BSHACLO' => [1=>45000,2=>50000,3=>55000,4=>60000],
            'BSMEDTECH' => [1=>55000,2=>65000,3=>75000,4=>85000],
            'BSA' => [1=>42000,2=>52000,3=>62000,4=>72000],
            'BSIHM' => [1=>40000,2=>50000,3=>60000,4=>70000],
            'BSBIO' => [1=>43000,2=>50000,3=>57000,4=>64000],
            'BSEE' => [1=>48000,2=>58000,3=>68000,4=>78000],
            'BSECE' => [1=>50000,2=>60000,3=>70000,4=>80000],
            'BSSE' => [1=>40000,2=>50000,3=>60000,4=>70000],
            'BSHRM' => [1=>38000,2=>46000,3=>54000,4=>62000],
            'BSCA' => [1=>40000,2=>50000,3=>60000,4=>70000],
            'BSTM' => [1=>38000,2=>46000,3=>54000,4=>62000],
            'BSCMM' => [1=>35000,2=>45000,3=>55000,4=>62000],
        ];
    }

    // --- Database ---
    public function setupDatabaseConnection(): bool {
        try {
            $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $this->dbConfig['host'], $this->dbConfig['database'], $this->dbConfig['charset']);
            $this->pdo = new PDO($dsn, $this->dbConfig['user'], $this->dbConfig['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            $this->pdo->query('SELECT 1');
            println('[OK] Database connection successful');
            $this->createTablesIfNotExist();
            return true;
        } catch (Throwable $e) {
            println('[ERROR] Database connection error: ' . $e->getMessage());
            println("  Check: MySQL is running, database '{$this->dbConfig['database']}' exists");
            return false;
        }
    }

    private function createTablesIfNotExist(): bool {
        try {
            println("\n[CHECK] Checking database tables...");
            $sqlPred = "CREATE TABLE IF NOT EXISTS student_predictions (\n                id INT AUTO_INCREMENT PRIMARY KEY,\n                student_id VARCHAR(50) NOT NULL,\n                course VARCHAR(50),\n                current_year INT,\n                source_table VARCHAR(100),\n                prediction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n                current_semester_data JSON,\n                next_semester_prediction JSON,\n                risk_analysis JSON,\n                interventions JSON,\n                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n                INDEX idx_student_id (student_id),\n                INDEX idx_prediction_date (prediction_date),\n                INDEX idx_course (course),\n                INDEX idx_source_table (source_table),\n                UNIQUE KEY unique_student_source (student_id, source_table)\n            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            $sqlTrend = "CREATE TABLE IF NOT EXISTS cohort_trends (\n                id INT AUTO_INCREMENT PRIMARY KEY,\n                prediction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n                trends_data JSON,\n                intervention_summary JSON,\n                total_students INT,\n                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n                INDEX idx_prediction_date (prediction_date)\n            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            $sqlReq = "CREATE TABLE IF NOT EXISTS prediction_requests (\n                id INT AUTO_INCREMENT PRIMARY KEY,\n                status VARCHAR(50) DEFAULT 'pending',\n                prediction_started_at TIMESTAMP NULL,\n                prediction_completed_at TIMESTAMP NULL,\n                predictions_generated INT DEFAULT 0,\n                error_message TEXT NULL,\n                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n                INDEX idx_status (status),\n                INDEX idx_created_at (created_at)\n            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            $this->pdo->exec($sqlPred); println("[OK] Table 'student_predictions' ready");
            $this->pdo->exec($sqlTrend); println("[OK] Table 'cohort_trends' ready");
            $this->pdo->exec($sqlReq); println("[OK] Table 'prediction_requests' ready");
            println('[OK] All database tables verified/created successfully');
            return true;
        } catch (Throwable $e) { println('[ERROR] Error creating tables: ' . $e->getMessage()); return false; }
    }

    public function updatePredictionStatus(string $status, int $predictionsCount = 0, ?string $errorMessage = null): void {
        try {
            if (!$this->pdo) return;
            if ($status === 'started') {
                $this->pdo->exec("UPDATE prediction_requests SET status='processing', prediction_started_at=NOW() WHERE status='processing' AND prediction_started_at IS NULL ORDER BY id DESC LIMIT 1");
            } elseif ($status === 'completed') {
                $stmt = $this->pdo->prepare("UPDATE prediction_requests SET status='completed', prediction_completed_at=NOW(), predictions_generated=:cnt WHERE status='processing' ORDER BY id DESC LIMIT 1");
                $stmt->execute([':cnt' => $predictionsCount]);
            } elseif ($status === 'failed') {
                $stmt = $this->pdo->prepare("UPDATE prediction_requests SET status='failed', error_message=:err, prediction_completed_at=NOW() WHERE status='processing' ORDER BY id DESC LIMIT 1");
                $stmt->execute([':err' => $errorMessage ?? '']);
            }
            println("[OK] Status updated: {$status}");
        } catch (Throwable $e) { println('[WARN] Failed to update status: ' . $e->getMessage()); }
    }

    // --- Input ---
    private function readTriggerFile(): ?array {
        try {
            $file = $this->workDir . DIRECTORY_SEPARATOR . 'prediction_trigger.json';
            if (!is_file($file)) { println('[WARN] No trigger file found - will process all data'); return null; }
            $data = json_decode((string)file_get_contents($file), true);
            if (!is_array($data)) return null;
            println("\n[INFO] Trigger Information:");
            println('   * Table: ' . ($data['table_name'] ?? ''));
            println('   * Year: ' . ($data['year'] ?? ''));
            println('   * Semester: ' . ($data['semester'] ?? ''));
            println('   * Records: ' . ($data['records_uploaded'] ?? ''));
            println('   * Uploaded by: ' . ($data['uploaded_by'] ?? ''));
            println('   * Timestamp: ' . ($data['trigger_timestamp'] ?? ''));
            return $data;
        } catch (Throwable $e) { println('[WARN] Error reading trigger file: ' . $e->getMessage()); return null; }
    }

    private function cleanData(array &$rows): void {
        $att = array_map(fn($r) => $r['Attendance'] ?? null, $rows);
        $gpa = array_map(fn($r) => $r['GPA'] ?? null, $rows);
        $attMed = $this->arrayMedian($att, 80.0);
        $gpaMed = $this->arrayMedian($gpa, 2.5);
        foreach ($rows as &$r) {
            $r['Attendance'] = is_null($r['Attendance'] ?? null) ? $attMed : floatval($r['Attendance']);
            $r['GPA'] = is_null($r['GPA'] ?? null) ? $gpaMed : floatval($r['GPA']);
            $r['balance'] = is_null($r['balance'] ?? null) ? 0.0 : floatval($r['balance']);
        }
        unset($r);
    }

    private function arrayMedian(array $values, float $fallback): float {
        $nums = array_values(array_filter($values, fn($x) => $x !== null && $x !== '' && is_numeric($x)));
        $n = count($nums);
        if ($n === 0) return $fallback;
        sort($nums, SORT_NUMERIC);
        $mid = intdiv($n, 2);
        if ($n % 2 === 0) { return (float)(($nums[$mid - 1] + $nums[$mid]) / 2); }
        return (float)$nums[$mid];
    }

    public function fetchCurrentSemesterData(): bool {
        try {
            if (!$this->pdo) return false;
            if ($this->triggerInfo === null) {
                $this->triggerInfo = $this->readTriggerFile();
            }
            if ($this->triggerInfo && isset($this->triggerInfo['table_name'])) {
                $tableName = $this->triggerInfo['table_name'];
                println("\n[TARGET] Processing ONLY newly uploaded data from: {$tableName}");
                $existingStmt = $this->pdo->prepare('SELECT DISTINCT student_id FROM student_predictions WHERE source_table = :t');
                $existingStmt->execute([':t' => $tableName]);
                $existing = $existingStmt->fetchAll(PDO::FETCH_COLUMN, 0) ?: [];
                $existingSet = array_fill_keys(array_map('strval', $existing), true);
                if (count($existingSet) > 0) { println('   [WARN] Found ' . count($existingSet) . ' students with existing predictions'); println('   [OK] Will skip these students to prevent duplicates'); }
                $rows = $this->pdo->query("SELECT StudentID, Attendance, GPA, balance, course, year FROM `{$tableName}` WHERE delete_status = 0")->fetchAll();
                $data = [];
                foreach ($rows as $r) { $sid = (string)$r['StudentID']; if (isset($existingSet[$sid])) continue; $r['StudentID'] = $sid; $r['table'] = $tableName; $data[] = $r; }
                if (count($data) === 0) { println("\n[OK] All students from {$tableName} already have predictions - nothing to do!"); return false; }
                $this->cleanData($data); $this->currentData = $data;
                println('   [OK] Loaded ' . count($data) . ' NEW students (without existing predictions)');
                println("\n[OK] Total NEW students to predict: " . count($data));
                return true;
            }
            println('[WARN] No trigger file - processing ALL tables');
            $tables = $this->pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN, 0) ?: [];
            $all = []; $processed = 0;
            foreach ($tables as $t) {
                if (strpos($t, 'student_') === 0 && strpos($t, '_sem_') !== false) {
                    try {
                        $rows = $this->pdo->query("SELECT StudentID, Attendance, GPA, balance, course, year FROM `{$t}` WHERE delete_status = 0")->fetchAll();
                        if ($rows && count($rows) > 0) { foreach ($rows as &$r) { $r['StudentID'] = (string)$r['StudentID']; $r['table'] = $t; } unset($r); $all = array_merge($all, $rows); $processed++; println('  [OK] Loaded ' . count($rows) . ' students from ' . $t); }
                    } catch (Throwable $e) { println('  [WARN] Skipping ' . $t . ': ' . $e->getMessage()); continue; }
                }
            }
            if (count($all) === 0) { println('[ERROR] No student data found'); return false; }
            $this->cleanData($all); $this->currentData = $all;
            println("\n[OK] Total: " . count($all) . ' students from ' . $processed . ' tables');
            return true;
        } catch (Throwable $e) { println('[ERROR] Error fetching data: ' . $e->getMessage()); return false; }
    }

    // --- Domain helpers and prediction logic ---
    private function getTuitionFee(string $course, int $year): float { $year = max(1, min(5, $year)); return $this->tuitionFees[$course][$year] ?? 40000.0; }
    private function balanceRisk(float $balance, float $tuition): float { if ($tuition <= 0) return 0.0; $pct = ($balance / $tuition) * 100.0; if ($pct <= 30) return ($pct/30)*0.3; if ($pct <= 50) return 0.3 + (($pct-30)/20)*0.3; return min(1.0, 0.6 + (($pct-50)/50)*0.4); }
    private function attendanceRisk(float $attendance, int $year): float { $subjects = [1=>9,2=>8,3=>8,4=>6,5=>6][$year] ?? 8; if ($attendance >= 85) $sar=0; elseif ($attendance>=75) $sar=1+((85-$attendance)/10); elseif ($attendance>=65) $sar=2+((75-$attendance)/10)*2; elseif ($attendance>=55) $sar=4+((65-$attendance)/10)*3; else $sar=min(7+((55-$attendance)/10)*2,$subjects); if ($sar<=2) return ($sar/2)*0.3; if ($sar<=5) return 0.3 + (($sar-2)/3)*0.3; $norm = $subjects>5 ? min(($sar-5)/($subjects-5),1.0) : 1.0; return 0.6 + ($norm*0.4); }
    private function gpaRisk(float $gpa): float { $gpa = max(1.0, min(5.0, $gpa)); if ($gpa <= 2.0) return (($gpa-1.0)/1.0)*0.3; if ($gpa <= 3.0) return 0.3 + (($gpa-2.0)/1.0)*0.3; return 0.6 + (($gpa-3.0)/2.0)*0.4; }
    private function getStudentSeed(string $studentId): int { return md5_seed((string)$studentId); }

    private function classifyRiskLevel(float $attendance, float $gpa, float $balance, string $course, int $year): string {
        $tuition = $this->getTuitionFee($course, $year);
        $a = $this->attendanceRisk($attendance, $year); $g = $this->gpaRisk($gpa); $f = $this->balanceRisk($balance, $tuition);
        $c = $a*0.33 + $g*0.33 + $f*0.34; if ($c >= 0.66) return 'High Risk'; if ($c >= 0.33) return 'Medium Risk'; return 'Low Risk';
    }
    private function dropoutProbability(string $studentKey, string $riskLevel, float $attendance, float $gpa, float $balance, string $course, int $year): float {
        $tuition = $this->getTuitionFee($course, $year);
        $a=$this->attendanceRisk($attendance,$year); $g=$this->gpaRisk($gpa); $f=$this->balanceRisk($balance,$tuition);
        $ruleProb = ($a*0.33 + $g*0.33 + $f*0.34) * 100.0;
        $rng = new RNG($this->getStudentSeed($studentKey));
        if ($riskLevel==='High Risk'){ $min=65;$max=95;$variation=8; } elseif ($riskLevel==='Medium Risk'){ $min=35;$max=64;$variation=10; } else { $min=5;$max=34;$variation=5; }
        $final = $ruleProb + $rng->uniform(-$variation, $variation);
        $balancePct = $tuition>0 ? ($balance/$tuition*100.0) : 0.0; if ($attendance<70) $final += $rng->uniform(0,3); if ($gpa>3.0) $final += $rng->uniform(0,3); if ($balancePct>50) $final += $rng->uniform(0,4);
        return round(max($min, min($max, $final)), 2);
    }
    private function semesterProgressionFactors(array $s, RNG $rng): array {
        $year = (int)$s['year']; $current_gpa = (float)$s['GPA']; $current_att = (float)$s['Attendance']; $is_midyear = $rng->uniform(0,1) < 0.5; $f=['is_midyear'=>$is_midyear,'attendance_modifier'=>1.0,'gpa_modifier'=>1.0,'financial_modifier'=>1.0];
        if ($year===1){ $f['attendance_modifier']=$rng->normal(0.95,0.1); $f['gpa_modifier']=$rng->normal(1.05,0.1);} elseif($year>=4){ $f['attendance_modifier']=$rng->normal(0.90,0.1); $f['gpa_modifier']=$rng->normal(0.95,0.1); $f['financial_modifier']=$rng->normal(1.1,0.1);} if($current_gpa>3.5){ $f['gpa_modifier']*=$rng->normal(1.1,0.1); $f['attendance_modifier']*=$rng->normal(1.05,0.05);} elseif($current_gpa<2.0){ $f['gpa_modifier']*=$rng->normal(0.98,0.05);} if($current_att<75){ $f['attendance_modifier']*=$rng->normal(1.1,0.1);} if($is_midyear){ $f['attendance_modifier']*=$rng->normal(0.95,0.05); $f['financial_modifier']*=$rng->normal(0.8,0.1);} else { $f['financial_modifier']*=$rng->normal(1.2,0.1);} return $f;
    }
    private function predictNextSemesterMetrics(array $s): array {
        $sid=(string)$s['StudentID']; $rng=new RNG($this->getStudentSeed($sid)); $att=(float)$s['Attendance']; $gpa=(float)$s['GPA']; $bal=(float)$s['balance']; $course=(string)$s['course']; $year=(int)$s['year'];
        $f=$this->semesterProgressionFactors($s,$rng); $att_factor=$rng->normal(0.95,0.05); $next_att=max(0.0,min(100.0,$att*$att_factor)); $next_att*=$f['attendance_modifier']; $gpa_factor=$rng->normal(0.98,0.1); $next_gpa=max(1.0,min(5.0,$gpa*$gpa_factor)); $next_gpa*=$f['gpa_modifier']; $next_year=$f['is_midyear']?$year:min($year+1,5); $next_tuition=$this->getTuitionFee($course,$next_year); $payment=max(0.0,$bal*$rng->normal(0.3,0.2)); $next_bal=max(0.0,$bal-$payment)+$next_tuition; $next_bal*=$f['financial_modifier'];
        return ['next_semester_attendance'=>max(0.0,min(100.0,round($next_att,2))),'next_semester_gpa'=>max(1.0,min(5.0,round($next_gpa,2))),'next_semester_balance'=>max(0.0,round($next_bal,2)),'next_year_level'=>$next_year,'semester_progression'=>$f];
    }
    private function predictNextSemesterRisk(array $s, array $pm): array {
        $sid=(string)$s['StudentID']; $course=(string)$s['course']; $year=(int)$s['year'];
        $curRisk=$this->classifyRiskLevel((float)$s['Attendance'],(float)$s['GPA'],(float)$s['balance'],$course,$year); $curProb=$this->dropoutProbability($sid.'_current',$curRisk,(float)$s['Attendance'],(float)$s['GPA'],(float)$s['balance'],$course,$year);
        $nextRisk=$this->classifyRiskLevel($pm['next_semester_attendance'],$pm['next_semester_gpa'],$pm['next_semester_balance'],$course,$pm['next_year_level']); $nextProb=$this->dropoutProbability($sid.'_next',$nextRisk,$pm['next_semester_attendance'],$pm['next_semester_gpa'],$pm['next_semester_balance'],$course,$pm['next_year_level']);
        $levels=['Low Risk'=>1,'Medium Risk'=>2,'High Risk'=>3]; $chg=($levels[$nextRisk]??0)-($levels[$curRisk]??0); if($chg>0)$risk_change='Escalating ('.$chg.' level'.($chg>1?'s':'').' up)'; elseif($chg<0)$risk_change='Improving ('.abs($chg).' level'.(abs($chg)>1?'s':'').' down)'; else $risk_change='Stable';
        if($nextProb>=80)$urg='CRITICAL'; elseif($nextProb>=70 || str_contains($risk_change,'Escalating'))$urg='HIGH'; elseif($nextProb>=50)$urg='MEDIUM'; else $urg='LOW';
        return ['current_risk_level'=>$curRisk,'current_dropout_probability'=>$curProb,'next_semester_risk_level'=>$nextRisk,'next_semester_dropout_probability'=>$nextProb,'risk_level_change'=>$risk_change,'probability_change'=>round($nextProb-$curProb,2),'intervention_urgency'=>$urg];
    }
    private function generateInterventions(array $s, array $pm, array $rp): array {
        $out=[]; $urg=$rp['intervention_urgency']; if($pm['next_semester_attendance']<75){ $out[]=['type'=>'Attendance','action'=>'Implement attendance monitoring system','timing'=>'Start of semester','urgency'=>$urg]; $out[]=['type'=>'Attendance','action'=>'Schedule weekly check-ins with advisor','timing'=>'Weekly','urgency'=>$urg]; }
        if($pm['next_semester_gpa']>3.0){ $out[]=['type'=>'Academic','action'=>'Enroll in academic support program','timing'=>'Before semester starts','urgency'=>$urg]; $out[]=['type'=>'Academic','action'=>'Assign peer tutor','timing'=>'First week of semester','urgency'=>$urg]; }
        $tuition=$this->getTuitionFee((string)$s['course'],$pm['next_year_level']); if($pm['next_semester_balance']>0.3*$tuition){ $out[]=['type'=>'Financial','action'=>'Financial aid consultation','timing'=>'Before enrollment','urgency'=>$urg]; $out[]=['type'=>'Financial','action'=>'Set up payment plan','timing'=>'Before semester starts','urgency'=>$urg]; }
        if($rp['next_semester_risk_level']==='High Risk'){ $out[]=['type'=>'Counseling','action'=>'Schedule parent/guardian meeting','timing'=>'Before semester starts','urgency'=>'HIGH']; $out[]=['type'=>'Support','action'=>'Assign dedicated academic advisor','timing'=>'Start of semester','urgency'=>'HIGH']; }
        if($urg==='CRITICAL'){ $out[]=['type'=>'Emergency','action'=>"Dean's office immediate consultation", 'timing'=>'ASAP','urgency'=>'CRITICAL']; $out[]=['type'=>'Emergency','action'=>'Emergency financial aid review','timing'=>'ASAP','urgency'=>'CRITICAL']; }
        return $out;
    }

    private function analyzeCohortTrends(array $forecasts): array {
        println("\n[DATA] Analyzing Cohort Trends...");
        $risk_changes=array_map(fn($f)=>$f['risk_analysis']['risk_change'],$forecasts); $escalating=count(array_filter($risk_changes,fn($r)=>str_contains($r,'Escalating'))); $improving=count(array_filter($risk_changes,fn($r)=>str_contains($r,'Improving'))); $stable=count(array_filter($risk_changes,fn($r)=>$r==='Stable'));
        $curHigh=count(array_filter($forecasts,fn($f)=>$f['current_semester']['risk_level']==='High Risk')); $nextHigh=count(array_filter($forecasts,fn($f)=>$f['next_semester']['predicted_risk_level']==='High Risk')); $curMed=count(array_filter($forecasts,fn($f)=>$f['current_semester']['risk_level']==='Medium Risk')); $nextMed=count(array_filter($forecasts,fn($f)=>$f['next_semester']['predicted_risk_level']==='Medium Risk'));
        $critical=count(array_filter($forecasts,fn($f)=>$f['risk_analysis']['intervention_urgency']==='CRITICAL')); $high=count(array_filter($forecasts,fn($f)=>$f['risk_analysis']['intervention_urgency']==='HIGH')); $medium=count(array_filter($forecasts,fn($f)=>$f['risk_analysis']['intervention_urgency']==='MEDIUM'));
        $prob_changes=array_map(fn($f)=>$f['risk_analysis']['probability_change'],$forecasts); $avg=count($prob_changes)?array_sum($prob_changes)/count($prob_changes):0.0;
        return ['total_students'=>count($forecasts),'risk_level_changes'=>['escalating'=>$escalating,'improving'=>$improving,'stable'=>$stable,'escalating_percentage'=>count($forecasts)?($escalating/count($forecasts)*100.0):0.0,'improving_percentage'=>count($forecasts)?($improving/count($forecasts)*100.0):0.0],'risk_distribution'=>['current_semester'=>['high_risk'=>$curHigh,'medium_risk'=>$curMed,'low_risk'=>count($forecasts)-$curHigh-$curMed],'next_semester'=>['high_risk'=>$nextHigh,'medium_risk'=>$nextMed,'low_risk'=>count($forecasts)-$nextHigh-$nextMed]],'intervention_urgency'=>['critical'=>$critical,'high'=>$high,'medium'=>$medium,'low'=>count($forecasts)-$critical-$high-$medium],'average_probability_change'=>$avg,'students_needing_immediate_intervention'=>$critical+$high];
    }

    private function generateInterventionReport(array $forecasts): array {
        println("\n[INFO] Generating Intervention Report...");
        $crit=array_values(array_filter($forecasts,fn($f)=>$f['risk_analysis']['intervention_urgency']==='CRITICAL')); $high=array_values(array_filter($forecasts,fn($f)=>$f['risk_analysis']['intervention_urgency']==='HIGH')); $med=array_values(array_filter($forecasts,fn($f)=>$f['risk_analysis']['intervention_urgency']==='MEDIUM'));
        $report=['report_generated'=>nowIso(),'semester'=>'Next Semester Forecast','critical_interventions'=>[],'high_priority_interventions'=>[],'medium_priority_interventions'=>[],'summary_statistics'=>['total_students'=>count($forecasts),'students_needing_critical_intervention'=>count($crit),'students_needing_high_priority_intervention'=>count($high),'students_needing_medium_priority_intervention'=>count($med),'total_at_risk_students'=>count($crit)+count($high)]];
        foreach($crit as $st){ $report['critical_interventions'][]=['StudentID'=>$st['StudentID'],'course'=>$st['course'],'year'=>$st['current_year'],'current_risk_level'=>$st['current_semester']['risk_level'],'predicted_risk_level'=>$st['next_semester']['predicted_risk_level'],'predicted_dropout_probability'=>$st['next_semester']['predicted_dropout_probability'],'risk_change'=>$st['risk_analysis']['risk_change'],'interventions'=>$st['recommended_interventions']]; }
        foreach($high as $st){ $report['high_priority_interventions'][]=['StudentID'=>$st['StudentID'],'course'=>$st['course'],'year'=>$st['current_year'],'current_risk_level'=>$st['current_semester']['risk_level'],'predicted_risk_level'=>$st['next_semester']['predicted_risk_level'],'predicted_dropout_probability'=>$st['next_semester']['predicted_dropout_probability'],'risk_change'=>$st['risk_analysis']['risk_change'],'interventions'=>$st['recommended_interventions']]; }
        foreach($med as $st){ $report['medium_priority_interventions'][]=['StudentID'=>$st['StudentID'],'course'=>$st['course'],'year'=>$st['current_year'],'current_risk_level'=>$st['current_semester']['risk_level'],'predicted_risk_level'=>$st['next_semester']['predicted_risk_level'],'predicted_dropout_probability'=>$st['next_semester']['predicted_dropout_probability'],'risk_change'=>$st['risk_analysis']['risk_change'],'interventions'=>$st['recommended_interventions']]; }
        return $report;
    }

    private function saveToDatabase(array $forecasts, array $trends, array $report): bool {
        println("\n[SAVE] Saving predictions to database..."); if(!$this->pdo) return false;
        try { $saved=0;$updated=0;$total=count($forecasts); $sql="INSERT INTO student_predictions (student_id, course, current_year, source_table, current_semester_data, next_semester_prediction, risk_analysis, interventions) VALUES (:sid,:course,:year,:src,:cur,:next,:risk,:intv) ON DUPLICATE KEY UPDATE prediction_date=CURRENT_TIMESTAMP, current_semester_data=VALUES(current_semester_data), next_semester_prediction=VALUES(next_semester_prediction), risk_analysis=VALUES(risk_analysis), interventions=VALUES(interventions)"; $stmt=$this->pdo->prepare($sql);
            foreach($forecasts as $i=>$f){ try{ $stmt->execute([':sid'=>(string)$f['StudentID'],':course'=>(string)$f['course'],':year'=>(int)$f['current_year'],':src'=>(string)($f['table']??'unknown'),':cur'=>json_encode($f['current_semester'],JSON_UNESCAPED_SLASHES),':next'=>json_encode($f['next_semester'],JSON_UNESCAPED_SLASHES),':risk'=>json_encode($f['risk_analysis'],JSON_UNESCAPED_SLASHES),':intv'=>json_encode($f['recommended_interventions'],JSON_UNESCAPED_SLASHES)]); $rc=$stmt->rowCount(); if($rc===1)$saved++; else $updated++; if((($i+1)%50)===0) println('  Processed '.($i+1).'/'.$total.' predictions...'); } catch(Throwable $e){ println('  [WARN] Failed to save '.$f['StudentID'].': '.$e->getMessage()); }} println('[OK] Saved '.$saved.' new predictions, updated '.$updated.' existing predictions'); $stmtT=$this->pdo->prepare('INSERT INTO cohort_trends (trends_data, intervention_summary, total_students) VALUES (:t,:i,:n)'); $stmtT->execute([':t'=>json_encode($trends,JSON_UNESCAPED_SLASHES), ':i'=>json_encode(['critical_count'=>$report['summary_statistics']['students_needing_critical_intervention']??0,'high_priority_count'=>$report['summary_statistics']['students_needing_high_priority_intervention']??0,'total_at_risk'=>$report['summary_statistics']['total_at_risk_students']??0],JSON_UNESCAPED_SLASHES), ':n'=>(int)$trends['total_students']]); println('[OK] Saved cohort trends to database'); println("\n[OK] Database save completed!"); println('  * '.$saved.' new predictions saved'); println('  * '.$updated.' predictions updated'); println('  * Cohort trends saved'); println('  * Tables: student_predictions, cohort_trends'); return true; }
        catch(Throwable $e){ println('\n[ERROR] Database save error: '.$e->getMessage()); return false; }
    }

    private function saveForecastResults(array $forecasts, array $trends, array $report): bool {
        println("\n[SAVE] Saving Forecast Results to Files...");
        try { $forecastFile=$this->outputDir.DIRECTORY_SEPARATOR.'next_semester_forecast.json'; file_put_contents($forecastFile, json_encode(['generation_date'=>nowIso(),'forecast_type'=>'Next Semester Prediction','cohort_trends'=>$trends,'individual_forecasts'=>$forecasts], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)); $interventionFile=$this->outputDir.DIRECTORY_SEPARATOR.'intervention_report.json'; file_put_contents($interventionFile, json_encode($report, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)); $summaryFile=$this->outputDir.DIRECTORY_SEPARATOR.'next_semester_summary.csv'; $fp=fopen($summaryFile,'w'); fputcsv($fp,['StudentID','Course','Current_Year','Current_Risk','Next_Semester_Risk','Risk_Change','Current_Dropout_Prob','Next_Semester_Dropout_Prob','Intervention_Urgency','Predicted_Attendance','Predicted_GPA','Predicted_Balance']); foreach($forecasts as $f){ fputcsv($fp,[$f['StudentID'],$f['course'],$f['current_year'],$f['current_semester']['risk_level'],$f['next_semester']['predicted_risk_level'],$f['risk_analysis']['risk_change'],sprintf('%.2f%%',$f['current_semester']['dropout_probability']),sprintf('%.1f%%',$f['next_semester']['predicted_dropout_probability']),$f['risk_analysis']['intervention_urgency'],sprintf('%.1f%%',$f['next_semester']['predicted_attendance']),sprintf('%.2f',$f['next_semester']['predicted_gpa']),'PHP'.number_format($f['next_semester']['predicted_balance'],2)]); } fclose($fp); println('[OK] All forecast results saved:'); println('   * '.$foo=$forecastFile); println('   * '.$interventionFile); println('   * '.$summaryFile); return true; }
        catch(Throwable $e){ println('[ERROR] Error saving files: '.$e->getMessage()); return false; }
    }

    private function printExecutiveSummary(array $trends): void {
        println("\n".str_repeat('=',70)); println('[DATA] NEXT SEMESTER FORECAST - EXECUTIVE SUMMARY'); println(str_repeat('=',70)); $total=$trends['total_students']; println("\n[TARGET] COHORT OVERVIEW:"); println('   * Total Students Analyzed: '.number_format($total)); println('   * Students with Escalating Risk: '.$trends['risk_level_changes']['escalating'].' ('.number_format($trends['risk_level_changes']['escalating_percentage'],1).'%)'); println('   * Students with Improving Risk: '.$trends['risk_level_changes']['improving'].' ('.number_format($trends['risk_level_changes']['improving_percentage'],1).'%)'); println('   * Students with Stable Risk: '.$trends['risk_level_changes']['stable']); println("\n[TREND] RISK DISTRIBUTION CHANGES:"); $curH=$trends['risk_distribution']['current_semester']['high_risk']; $nextH=$trends['risk_distribution']['next_semester']['high_risk']; $chg=$nextH-$curH; println(sprintf('   * Current High Risk Students: %d (%.1f%%)', $curH, $total?($curH/$total*100.0):0)); println(sprintf('   * Predicted High Risk Students: %d (%.1f%%)', $nextH, $total?($nextH/$total*100.0):0)); $dir=$chg>0?'UP':($chg<0?'DOWN':'STABLE'); println(sprintf('   * High Risk Change: %s %+d students', $dir, $chg)); println("\n[WARN] INTERVENTION REQUIREMENTS:"); $critical=$trends['intervention_urgency']['critical']; $high=$trends['intervention_urgency']['high']; $imm=$critical+$high; println('   * Critical Interventions Needed: '.$critical.' students'); println('   * High Priority Interventions: '.$high.' students'); println(sprintf('   * Total Immediate Attention Required: %d students (%.1f%%)', $imm, $total?($imm/$total*100.0):0)); $avg=$trends['average_probability_change']; println("\n[DATA] DROPOUT PROBABILITY TRENDS:"); println(sprintf('   * Average Probability Change: %+0.1f%%', $avg)); $trend=$avg>0?'INCREASING':($avg<0?'DECREASING':'STABLE'); println('   * Overall Trend: '.$trend); println("\n[TARGET] KEY RECOMMENDATIONS:"); if($critical>0) println("   * URGENT: {$critical} students need immediate dean's office consultation"); if($high>0) println('   * HIGH PRIORITY: '.$high.' students need intensive intervention'); if($trends['risk_level_changes']['escalating_percentage']>20) println('   * ALERT: High percentage of students showing escalating risk'); if($avg>5) println('   * WARNING: Overall dropout probability increasing significantly'); if($trends['risk_level_changes']['improving']>$trends['risk_level_changes']['escalating']) println('   * POSITIVE: More students improving than escalating'); println("\n".str_repeat('=',70));
    }

    private function cleanupTriggerFile(): void { try { $file=$this->workDir.DIRECTORY_SEPARATOR.'prediction_trigger.json'; if(is_file($file)){ $archive=$this->workDir.DIRECTORY_SEPARATOR.'prediction_trigger_completed_'.date('Ymd_His').'.json'; @rename($file,$archive); println("\n[OK] Trigger file archived: {$archive}"); } } catch (Throwable $e) { println('[WARN] Could not cleanup trigger file: '.$e->getMessage()); } }

    // --- Run orchestrator ---
    public function runForecast(): bool {
        println('[START] STUDENT DROPOUT PREDICTION SYSTEM (PHP)');
        println(str_repeat('=', 70));
        try {
            if (!$this->setupDatabaseConnection()) return false;
            $this->updatePredictionStatus('started');

            if (!$this->fetchCurrentSemesterData()) {
                if ($this->currentData === null || count($this->currentData) === 0) {
                    println("\n[OK] No new students to predict - all students already have predictions");
                    $this->updatePredictionStatus('completed', 0);
                    $this->cleanupTriggerFile();
                    return true;
                }
                $this->updatePredictionStatus('failed', 0, 'No student data found');
                return false;
            }

            println("\n[PREDICT] Generating predictions for " . count($this->currentData) . ' NEW students...');
            $forecasts = [];
            foreach ($this->currentData as $i => $student) {
                try {
                    if ((($i+1) % 50) === 0) println('  Processing ' . ($i+1) . '/' . count($this->currentData) . '...');
                    $pm = $this->predictNextSemesterMetrics($student);
                    $rp = $this->predictNextSemesterRisk($student, $pm);
                    $intv = $this->generateInterventions($student, $pm, $rp);
                    $forecasts[] = [
                        'StudentID' => (string)$student['StudentID'],
                        'course' => (string)$student['course'],
                        'current_year' => (int)$student['year'],
                        'table' => (string)$student['table'],
                        'current_semester' => [
                            'attendance' => (float)$student['Attendance'],
                            'gpa' => (float)$student['GPA'],
                            'balance' => (float)$student['balance'],
                            'risk_level' => $rp['current_risk_level'],
                            'dropout_probability' => $rp['current_dropout_probability'],
                        ],
                        'next_semester' => [
                            'predicted_attendance' => $pm['next_semester_attendance'],
                            'predicted_gpa' => $pm['next_semester_gpa'],
                            'predicted_balance' => $pm['next_semester_balance'],
                            'predicted_risk_level' => $rp['next_semester_risk_level'],
                            'predicted_dropout_probability' => $rp['next_semester_dropout_probability'],
                        ],
                        'risk_analysis' => [
                            'risk_change' => $rp['risk_level_change'],
                            'probability_change' => $rp['probability_change'],
                            'intervention_urgency' => $rp['intervention_urgency'],
                        ],
                        'recommended_interventions' => $intv,
                    ];
                } catch (Throwable $e) { println('  [WARN] Error processing student ' . ($student['StudentID'] ?? 'unknown') . ': ' . $e->getMessage()); }
            }
            println("\n[OK] Generated " . count($forecasts) . ' forecasts');

            $trends = $this->analyzeCohortTrends($forecasts);
            $report = $this->generateInterventionReport($forecasts);

            if (!$this->saveToDatabase($forecasts, $trends, $report)) { $this->updatePredictionStatus('failed', 0, 'Failed to save predictions to database'); return false; }

            $this->saveForecastResults($forecasts, $trends, $report);
            $this->printExecutiveSummary($trends);
            $this->updatePredictionStatus('completed', count($forecasts));
            $this->cleanupTriggerFile();
            println("\n[SUCCESS] Next Semester Forecasting Complete!");
            println("\n[FILE] OUTPUT LOCATION:");
            println('   ' . $this->outputDir);
            println("\n[TIP] NEXT STEPS:");
            println('   * Review intervention_report.json for at-risk students');
            println('   * Query student_predictions table for individual details');
            println('   * Plan resource allocation based on cohort_trends');
            return true;
        } catch (Throwable $e) { println("\n[ERROR] Fatal error: " . $e->getMessage()); $this->updatePredictionStatus('failed', 0, $e->getMessage()); return false; }
    }

    // --- Programmatic trigger support (avoid reading file) ---
    public function setTriggerInfo(array $info): void { $this->triggerInfo = $info; }
    public function runForecastFor(string $tableName, ?int $year = null, ?int $semester = null, array $extraMeta = []): bool {
        $this->triggerInfo = array_merge([
            'trigger_timestamp' => nowIso(),
            'trigger_type' => 'in_process',
            'table_name' => $tableName,
            'year' => $year,
            'semester' => $semester,
        ], $extraMeta);
        return $this->runForecast();
    }
}

// Entrypoint
if (php_sapi_name() === 'cli' || (isset($_SERVER['SCRIPT_FILENAME']) && realpath(__FILE__) === realpath($_SERVER['SCRIPT_FILENAME']))) {
    try {
        $forecaster = new NextSemesterForecaster($DB_CONFIG, $WORK_DIR, $OUTPUT_DIR);
        $ok = $forecaster->runForecast();
        exit($ok ? 0 : 1);
    } catch (Throwable $e) { println('[ERROR] Unexpected: ' . $e->getMessage()); exit(1); }
}

?>
