<!-- Search and Filter Container -->
<div class="filter-search-container" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-radius: 10px; padding: 20px; margin-bottom: 20px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); border: 1px solid #dee2e6;">
    
    <!-- SEARCH SECTION -->
    <div class="search-section" style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #28a745;">
        <h5 class="section-title" style="margin: 0 0 10px 0; color: #28a745;">
            <i class="fa fa-search"></i> Search Students
        </h5>
        <form method="POST" action="" id="searchForm" style="margin: 0;">
            <div class="search-row" style="display: flex; gap: 10px; align-items: flex-end; flex-wrap: wrap;">
                <div class="search-input-wrapper" style="flex: 1; min-width: 250px;">
                    <div class="search-input-group" style="position: relative; display: flex;">
                        <input type="text" 
                               name="searchTerm" 
                               id="searchTerm" 
                               class="form-control" 
                               placeholder="Search by Student ID or Name..." 
                               value="<?= htmlspecialchars($searchTerm ?? '') ?>"
                               style="border-radius: 4px 0 0 4px; border-right: none; padding-right: 10px;">
                        <button type="submit" 
                                name="search" 
                                class="btn btn-success"
                                style="border-radius: 0 4px 4px 0; padding: 8px 12px; background: #28a745; border-color: #28a745;">
                            <i class="fa fa-search"></i>
                        </button>
                    </div>
                    <small class="search-help" style="color: #6c757d; font-size: 11px; display: block; margin-top: 3px;">
                        <i class="fa fa-info-circle"></i> 
                        Search by Student ID (e.g., OLFU2023001) or Student Name
                    </small>
                </div>
                
                <?php if (!empty($searchTerm)): ?>
                    <div class="clear-search-wrapper">
                        <a href="?<?= http_build_query(array_filter([
                            'filterYear' => $filterYear,
                            'filterSemester' => $filterSemester,
                            'filterCourse' => $filterCourse,
                            'filterRiskLevel' => $filterRiskLevel,
                            'entries' => $_GET['entries'] ?? null,
                            'debug' => $_GET['debug'] ?? null,
                            'clearSearch' => '1'
                        ])) ?>" class="btn btn-default clear-search-btn">
                            <i class="fa fa-times"></i> Clear Search
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </form>
    </div>
    
    <!-- SEPARATOR -->
    <div class="separator" style="border-top: 1px solid #dee2e6; margin: 20px 0;"></div>
    
    <!-- FILTER SECTION -->
    <div class="filter-section" style="background: #f1f3f4; padding: 15px; border-radius: 8px; border-left: 4px solid #007bff;">
        <h5 class="section-title" style="margin: 0 0 15px 0; color: #007bff;">
            <i class="fa fa-filter"></i> Filter Results
        </h5>
        <form method="GET" action="" id="filterForm">
            <div class="filter-row" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; align-items: end;">
                
                <!-- Academic Year Filter -->
                <div class="filter-group">
                    <label for="filterYear" class="filter-label" style="font-weight: 600; color: #495057; margin-bottom: 5px; font-size: 13px; display: block;">
                        Academic Year
                    </label>
                    <select name="filterYear" id="filterYear" class="form-control filter-select">
                        <option value="">All Years</option>
                        <?php foreach ($availableYears as $yearOption): ?>
                            <option value="<?= $yearOption ?>" <?= ($filterYear !== null && $filterYear == $yearOption) ? 'selected' : '' ?>>
                                <?= $yearOption ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Semester Filter -->
                <div class="filter-group">
                    <label for="filterSemester" class="filter-label" style="font-weight: 600; color: #495057; margin-bottom: 5px; font-size: 13px; display: block;">
                        Semester
                    </label>
                    <select name="filterSemester" id="filterSemester" class="form-control filter-select">
                        <option value="">All Semesters</option>
                        <?php foreach ($availableSemesters as $semesterOption): ?>
                            <?php $semesterDisplay = ($semesterOption == '1') ? '1st Semester' : '2nd Semester'; ?>
                            <option value="<?= $semesterOption ?>" <?= ($filterSemester !== null && $filterSemester == $semesterOption) ? 'selected' : '' ?>>
                                <?= $semesterDisplay ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Course Filter -->
                <div class="filter-group">
                    <label for="filterCourse" class="filter-label" style="font-weight: 600; color: #495057; margin-bottom: 5px; font-size: 13px; display: block;">
                        Course
                    </label>
                    <select name="filterCourse" id="filterCourse" class="form-control filter-select">
                        <option value="">All Courses</option>
                        <?php foreach ($availableCourses as $courseOption): ?>
                            <option value="<?= htmlspecialchars($courseOption) ?>" <?= ($filterCourse !== null && $filterCourse == $courseOption) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($courseOption) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Risk Level Filter -->
                <div class="filter-group">
                    <label for="filterRiskLevel" class="filter-label" style="font-weight: 600; color: #495057; margin-bottom: 5px; font-size: 13px; display: block;">
                        Risk Level
                    </label>
                    <select name="filterRiskLevel" id="filterRiskLevel" class="form-control filter-select">
                        <option value="">All Risk Levels</option>
                        <?php foreach ($availableRiskLevels as $riskOption): ?>
                            <option value="<?= htmlspecialchars($riskOption) ?>" <?= ($filterRiskLevel !== null && $filterRiskLevel == $riskOption) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($riskOption) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Filter Action Buttons -->
                <div class="filter-actions" style="display: flex; gap: 8px; flex-wrap: wrap;">
                    <button type="button" onclick="applyFilters()" class="btn btn-primary filter-btn">
                        <i class="fa fa-filter"></i> Apply Filters
                    </button>
                    
                    <?php if ($filterYear !== null || $filterSemester !== null || $filterCourse !== null || $filterRiskLevel !== null): ?>
                        <a href="?<?= http_build_query(array_filter([
                            'search' => $searchTerm ?: null,
                            'entries' => $_GET['entries'] ?? null,
                            'debug' => $_GET['debug'] ?? null
                        ])) ?>" class="btn btn-default clear-filters-btn">
                            <i class="fa fa-times"></i> Clear Filters
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Preserve search parameter -->
            <?php if (!empty($searchTerm)): ?>
                <input type="hidden" name="search" value="<?= htmlspecialchars($searchTerm) ?>">
            <?php endif; ?>
            
            <!-- Preserve other parameters -->
            <?php if (isset($_GET['entries'])): ?>
                <input type="hidden" name="entries" value="<?= htmlspecialchars($_GET['entries']) ?>">
            <?php endif; ?>
            <?php if (isset($_GET['debug'])): ?>
                <input type="hidden" name="debug" value="<?= htmlspecialchars($_GET['debug']) ?>">
            <?php endif; ?>
        </form>
    </div>

    <!-- STATUS DISPLAY -->
    <?php if (!empty($searchTerm) || $hasValidFilter): ?>
        <div class="active-status" style="margin-top: 20px; padding: 15px; background: #e8f4fd; border: 1px solid #b3d9ff; border-radius: 8px;">
            <div class="status-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <strong style="color: #0056b3;">
                    <i class="fa fa-info-circle"></i> Current Status:
                </strong>
                <div class="results-count" style="color: #6c757d; font-size: 14px;">
                    <i class="fa fa-users"></i> 
                    Showing <strong style="color: #007bff;"><?= count($searchResult) ?></strong> results
                </div>
            </div>
            
            <div class="status-badges" style="display: flex; gap: 8px; flex-wrap: wrap;">
                <?php if (!empty($searchTerm)): ?>
                    <span class="status-badge search-badge" style="background: #d4edda; color: #155724; padding: 4px 8px; border-radius: 12px; font-size: 12px; border: 1px solid #c3e6cb;">
                        <i class="fa fa-search"></i> Search: "<strong><?= htmlspecialchars($searchTerm) ?></strong>"
                    </span>
                <?php endif; ?>

                <?php foreach ($activeFilters as $filter): ?>
                    <span class="status-badge filter-badge" style="background: #cce5ff; color: #004085; padding: 4px 8px; border-radius: 12px; font-size: 12px; border: 1px solid #99d6ff;">
                        <?= htmlspecialchars($filter) ?>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="active-status default-status" style="margin-top: 20px;">
            <div class="alert alert-info" style="margin: 0;">
                <i class="fa fa-info-circle"></i> 
                <strong>Showing all students</strong> - Use search or filters above to narrow results
                <span class="total-count" style="float: right; font-weight: normal;">
                    (<?= count($searchResult) ?> total students)
                </span>
            </div>
        </div>
    <?php endif; ?>
</div>