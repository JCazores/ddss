<?php
// Enhanced forecasting logic to replace the current simple calculations

// Historical trend analysis function
function calculateTrend($currentValue, $historicalData, $weight = 0.7) {
    if (empty($historicalData)) {
        return 0; // No trend if no historical data
    }
    
    // Calculate moving average and trend
    $count = count($historicalData);
    $sum = array_sum($historicalData);
    $average = $sum / $count;
    
    // Simple linear regression for trend
    $trend = 0;
    if ($count >= 2) {
        $recent = array_slice($historicalData, -3); // Last 3 data points
        $trend = (end($recent) - reset($recent)) / (count($recent) - 1);
    }
    
    return ($currentValue * (1 - $weight)) + (($average + $trend) * $weight);
}

// Enhanced risk-based forecasting
function enhancedForecastCalculation($studentData, $dropoutPercentage) {
    // Define risk thresholds
    $highRiskThreshold = 70;
    $mediumRiskThreshold = 40;
    
    // Current values
    $currentGPA = floatval($studentData['GPA']);
    $currentAttendance = floatval($studentData['Attendance']);
    $currentBalance = floatval($studentData['balance']);
    
    // Risk category
    $riskLevel = 'Low';
    if ($dropoutPercentage >= $highRiskThreshold) {
        $riskLevel = 'High';
    } elseif ($dropoutPercentage >= $mediumRiskThreshold) {
        $riskLevel = 'Medium';
    }
    
    // Semester progression factor (students typically perform differently as they progress)
    $year = isset($studentData['year']) ? intval($studentData['year']) : 1;
    $semesterFactor = 1 + (($year - 1) * 0.05); // Slight improvement over years due to maturity
    
    // GPA Forecasting with more realistic bounds
    $gpaVolatility = 0.15; // Typical GPA doesn't change drastically
    $gpaBaseline = $currentGPA;
    
    switch ($riskLevel) {
        case 'High':
            // High risk students may see decline but with intervention potential
            $gpaChange = -0.1 - (rand(0, 15) / 100);
            break;
        case 'Medium':
            // Medium risk students may see slight variations
            $gpaChange = (rand(-5, 10) / 100);
            break;
        default:
            // Low risk students typically maintain or improve slightly
            $gpaChange = (rand(0, 8) / 100) * $semesterFactor;
    }
    
    $forecastGPA = max(1.0, min(5.0, $gpaBaseline + $gpaChange));
    
    // Attendance Forecasting
    $attendanceVolatility = 5; // Attendance can vary more than GPA
    $attendanceBaseline = $currentAttendance;
    
    switch ($riskLevel) {
        case 'High':
            // High risk students may see attendance drop
            $attendanceChange = -rand(3, 12);
            break;
        case 'Medium':
            // Medium risk students may fluctuate
            $attendanceChange = rand(-5, 5);
            break;
        default:
            // Low risk students typically maintain good attendance
            $attendanceChange = rand(-2, 4);
    }
    
    $forecastAttendance = max(0, min(100, $attendanceBaseline + $attendanceChange));
    
    // Financial Balance Forecasting
    $balanceBaseline = $currentBalance;
    
    // Factor in typical payment patterns and financial stress
    switch ($riskLevel) {
        case 'High':
            // High risk students may accumulate more debt
            $balanceChange = rand(1000, 5000);
            break;
        case 'Medium':
            // Medium risk students may have variable payment patterns
            $balanceChange = rand(-1000, 3000);
            break;
        default:
            // Low risk students typically pay down balances
            $balanceChange = rand(-2000, 1000);
    }
    
    $forecastBalance = max(0, $balanceBaseline + $balanceChange);
    
    // Calculate multi-factor risk score
    $academicRisk = calculateAcademicRisk($forecastGPA);
    $attendanceRisk = calculateAttendanceRisk($forecastAttendance);
    $financialRisk = calculateFinancialRisk($forecastBalance);
    
    // Weighted risk calculation
    $forecastRiskScore = ($academicRisk * 0.4) + ($attendanceRisk * 0.35) + ($financialRisk * 0.25);
    
    // Add external factors (economic conditions, personal circumstances)
    $externalFactors = rand(-5, 8); // Random external influences
    $forecastRiskScore = max(0, min(100, $forecastRiskScore + $externalFactors));
    
    return [
        'gpa' => round($forecastGPA, 2),
        'attendance' => round($forecastAttendance, 1),
        'balance' => round($forecastBalance, 2),
        'risk_score' => round($forecastRiskScore, 1)
    ];
}

// Individual risk calculation functions
function calculateAcademicRisk($gpa) {
    // GPA risk (1-5 scale, 1 is best)
    if ($gpa >= 4.5) return 85; // Very high risk
    if ($gpa >= 3.5) return 65; // High risk
    if ($gpa >= 2.5) return 40; // Medium risk
    if ($gpa >= 2.0) return 25; // Low-medium risk
    return 15; // Low risk
}

function calculateAttendanceRisk($attendance) {
    // Attendance risk (0-100%, 100% is best)
    if ($attendance < 60) return 90; // Critical risk
    if ($attendance < 70) return 75; // Very high risk
    if ($attendance < 80) return 55; // High risk
    if ($attendance < 90) return 30; // Medium risk
    return 10; // Low risk
}

function calculateFinancialRisk($balance) {
    // Financial risk based on outstanding balance
    if ($balance > 50000) return 90; // Critical financial stress
    if ($balance > 25000) return 70; // High financial stress
    if ($balance > 10000) return 45; // Moderate financial stress
    if ($balance > 5000) return 25; // Some financial pressure
    return 5; // Low financial risk
}

// Time series forecasting (if historical data is available)
function timeSeriesForecast($historicalData, $periods = 1) {
    $count = count($historicalData);
    if ($count < 2) {
        return end($historicalData); // Return last value if insufficient data
    }
    
    // Simple exponential smoothing
    $alpha = 0.3; // Smoothing factor
    $smoothed = [$historicalData[0]];
    
    for ($i = 1; $i < $count; $i++) {
        $smoothed[] = $alpha * $historicalData[$i] + (1 - $alpha) * $smoothed[$i - 1];
    }
    
    // Forecast next period
    $trend = ($historicalData[$count - 1] - $historicalData[max(0, $count - 3)]) / 3;
    $forecast = end($smoothed) + $trend;
    
    return $forecast;
}

// Intervention impact modeling
function modelInterventionImpact($baseForcast, $interventions, $studentProfile) {
    $impactMultiplier = 1.0;
    $interventionEffectiveness = [
        'Academic tutoring' => ['gpa' => 0.15, 'risk' => -8],
        'Attendance monitoring' => ['attendance' => 12, 'risk' => -6],
        'Financial counseling' => ['balance' => -0.15, 'risk' => -5], // 15% balance reduction
        'Regular counseling' => ['gpa' => 0.08, 'attendance' => 5, 'risk' => -4]
    ];
    
    foreach ($interventions as $intervention) {
        foreach ($interventionEffectiveness as $type => $effects) {
            if (stripos($intervention, $type) !== false) {
                // Apply intervention effects
                if (isset($effects['gpa'])) {
                    $baseForcast['gpa'] = max(1.0, min(5.0, $baseForcast['gpa'] + $effects['gpa']));
                }
                if (isset($effects['attendance'])) {
                    $baseForcast['attendance'] = max(0, min(100, $baseForcast['attendance'] + $effects['attendance']));
                }
                if (isset($effects['balance']) && $effects['balance'] < 0) {
                    $baseForcast['balance'] *= (1 + $effects['balance']); // Percentage reduction
                }
                if (isset($effects['risk'])) {
                    $baseForcast['risk_score'] = max(0, min(100, $baseForcast['risk_score'] + $effects['risk']));
                }
            }
        }
    }
    
    return $baseForcast;
}

// Confidence intervals for predictions
function calculatePredictionConfidence($studentData, $forecast) {
    // Calculate confidence based on data quality and student stability
    $baseConfidence = 75; // Base confidence level
    
    // Factors affecting confidence
    $stabilityFactors = 0;
    
    // GPA stability
    $gpaVariability = abs($studentData['GPA'] - 2.5) / 2.5; // Normalized variability
    $stabilityFactors += (1 - $gpaVariability) * 10;
    
    // Attendance consistency
    if ($studentData['Attendance'] > 85) $stabilityFactors += 10;
    elseif ($studentData['Attendance'] < 70) $stabilityFactors -= 15;
    
    // Financial stability
    if ($studentData['balance'] < 5000) $stabilityFactors += 8;
    elseif ($studentData['balance'] > 20000) $stabilityFactors -= 12;
    
    $confidence = max(50, min(95, $baseConfidence + $stabilityFactors));
    
    return [
        'confidence' => round($confidence, 1),
        'lower_bound' => [
            'gpa' => max(1.0, $forecast['gpa'] - 0.3),
            'attendance' => max(0, $forecast['attendance'] - 8),
            'risk_score' => max(0, $forecast['risk_score'] - 12)
        ],
        'upper_bound' => [
            'gpa' => min(5.0, $forecast['gpa'] + 0.2),
            'attendance' => min(100, $forecast['attendance'] + 6),
            'risk_score' => min(100, $forecast['risk_score'] + 10)
        ]
    ];
}

// Main enhanced forecasting function
function generateEnhancedForecast($studentData, $dropoutPercentage, $interventions = []) {
    // Generate base forecast
    $baseForecast = enhancedForecastCalculation($studentData, $dropoutPercentage);
    
    // Apply intervention impacts
    if (!empty($interventions)) {
        $baseForecast = modelInterventionImpact($baseForecast, $interventions, $studentData);
    }
    
    // Calculate confidence intervals
    $confidence = calculatePredictionConfidence($studentData, $baseForecast);
    
    // Generate multiple scenario forecasts
    $scenarios = [
        'optimistic' => [
            'gpa' => min(5.0, $baseForecast['gpa'] + 0.15),
            'attendance' => min(100, $baseForecast['attendance'] + 5),
            'balance' => max(0, $baseForecast['balance'] * 0.9),
            'risk_score' => max(0, $baseForecast['risk_score'] - 8)
        ],
        'realistic' => $baseForecast,
        'pessimistic' => [
            'gpa' => max(1.0, $baseForecast['gpa'] - 0.2),
            'attendance' => max(0, $baseForecast['attendance'] - 8),
            'balance' => $baseForecast['balance'] * 1.15,
            'risk_score' => min(100, $baseForecast['risk_score'] + 12)
        ]
    ];
    
    return [
        'scenarios' => $scenarios,
        'confidence' => $confidence,
        'recommendation' => generateRecommendations($baseForecast, $studentData),
        'forecast_date' => date('Y-m-d H:i:s'),
        'factors_considered' => [
            'current_performance',
            'risk_level',
            'semester_progression',
            'intervention_potential',
            'external_factors'
        ]
    ];
}

// Enhanced recommendation system
function generateRecommendations($forecast, $studentData) {
    $recommendations = [];
    
    // Academic recommendations
    if ($forecast['gpa'] > 3.0) {
        $recommendations['academic'] = [
            'priority' => 'High',
            'actions' => [
                'Immediate academic intervention required',
                'Weekly tutoring sessions',
                'Study skills workshop enrollment',
                'Faculty mentor assignment'
            ]
        ];
    } elseif ($forecast['gpa'] > 2.5) {
        $recommendations['academic'] = [
            'priority' => 'Medium',
            'actions' => [
                'Bi-weekly academic check-ins',
                'Supplemental instruction sessions',
                'Time management training'
            ]
        ];
    }
    
    // Attendance recommendations
    if ($forecast['attendance'] < 75) {
        $recommendations['attendance'] = [
            'priority' => 'High',
            'actions' => [
                'Daily attendance monitoring',
                'Transportation assistance evaluation',
                'Personal barrier assessment',
                'Flexible scheduling options'
            ]
        ];
    }
    
    // Financial recommendations
    if ($forecast['balance'] > 15000) {
        $recommendations['financial'] = [
            'priority' => 'High',
            'actions' => [
                'Emergency financial aid review',
                'Payment plan restructuring',
                'Work-study program placement',
                'External scholarship applications'
            ]
        ];
    }
    
    return $recommendations;
}

// Usage Example:
/*
$enhancedForecast = generateEnhancedForecast($studentData, $dropoutPercentage, $interventions);

// Access different scenarios
$realisticForecast = $enhancedForecast['scenarios']['realistic'];
$optimisticForecast = $enhancedForecast['scenarios']['optimistic'];
$pessimisticForecast = $enhancedForecast['scenarios']['pessimistic'];

// Get confidence levels
$confidence = $enhancedForecast['confidence']['confidence'];
$lowerBounds = $enhancedForecast['confidence']['lower_bound'];
$upperBounds = $enhancedForecast['confidence']['upper_bound'];

// Get recommendations
$recommendations = $enhancedForecast['recommendation'];
*/
?>