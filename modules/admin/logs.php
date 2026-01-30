<?php
/**
 * Admin Module - System Logs Viewer
 * 
 * Comprehensive logging with search, filter, sort, and pagination
 */

define('PAGE_TITLE', 'System Logs - BuzzNation PM');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole('Admin');

$conn = getDBConnection();

// Pagination
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Filters
$search = isset($_GET['search']) ? sanitizeInput($conn, $_GET['search']) : '';
$roleFilter = isset($_GET['role']) ? sanitizeInput($conn, $_GET['role']) : '';
$actionFilter = isset($_GET['action_type']) ? sanitizeInput($conn, $_GET['action_type']) : '';
$dateFrom = isset($_GET['date_from']) ? sanitizeInput($conn, $_GET['date_from']) : '';
$dateTo = isset($_GET['date_to']) ? sanitizeInput($conn, $_GET['date_to']) : '';
$sortBy = isset($_GET['sort_by']) ? sanitizeInput($conn, $_GET['sort_by']) : 'created_at';
$sortOrder = isset($_GET['sort_order']) && $_GET['sort_order'] === 'ASC' ? 'ASC' : 'DESC';

// Build WHERE clause
$whereClause = "WHERE 1=1";

if ($search) {
    $whereClause .= " AND (l.action_description LIKE '%$search%' OR u.full_name LIKE '%$search%' OR l.ip_address LIKE '%$search%')";
}

if ($roleFilter) {
    $whereClause .= " AND l.role = '$roleFilter'";
}

if ($actionFilter) {
    $whereClause .= " AND l.action_type = '$actionFilter'";
}

if ($dateFrom) {
    $whereClause .= " AND DATE(l.created_at) >= '$dateFrom'";
}

if ($dateTo) {
    $whereClause .= " AND DATE(l.created_at) <= '$dateTo'";
}

// Validate sort column
$allowedSortColumns = array('created_at', 'user_id', 'role', 'action_type', 'project_id');
if (!in_array($sortBy, $allowedSortColumns)) {
    $sortBy = 'created_at';
}

// Get total count
$sql = "SELECT COUNT(*) as total FROM logs l LEFT JOIN users u ON l.user_id = u.user_id $whereClause";
$result = mysqli_query($conn, $sql);
$totalLogs = mysqli_fetch_assoc($result)['total'];
$totalPages = ceil($totalLogs / $perPage);

// Get logs
$sql = "SELECT l.*, u.full_name as user_name, p.event_name
        FROM logs l
        LEFT JOIN users u ON l.user_id = u.user_id
        LEFT JOIN projects p ON l.project_id = p.project_id
        $whereClause
        ORDER BY l.$sortBy $sortOrder
        LIMIT $offset, $perPage";

$result = mysqli_query($conn, $sql);
$logs = array();
while ($row = mysqli_fetch_assoc($result)) {
    $logs[] = $row;
}

// Get unique action types for filter
$sql = "SELECT DISTINCT action_type FROM logs ORDER BY action_type";
$result = mysqli_query($conn, $sql);
$actionTypes = array();
while ($row = mysqli_fetch_assoc($result)) {
    $actionTypes[] = $row['action_type'];
}

closeDBConnection($conn);

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid">
    <h1 class="mb-4"><i class="fas fa-file-alt"></i> System Logs</h1>
    
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-header">
            <h6 class="mb-0"><i class="fas fa-filter"></i> Filters & Search</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Search</label>
                            <input type="text" class="form-control" name="search" 
                                   value="<?php echo htmlspecialchars($search); ?>" 
                                   placeholder="Search description, user, IP...">
                        </div>
                    </div>
                    
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Role</label>
                            <select name="role" class="form-control">
                                <option value="">All Roles</option>
                                <option value="Admin" <?php echo $roleFilter === 'Admin' ? 'selected' : ''; ?>>Admin</option>
                                <option value="Sales" <?php echo $roleFilter === 'Sales' ? 'selected' : ''; ?>>Sales</option>
                                <option value="Design" <?php echo $roleFilter === 'Design' ? 'selected' : ''; ?>>Design</option>
                                <option value="Operation" <?php echo $roleFilter === 'Operation' ? 'selected' : ''; ?>>Operation</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Action Type</label>
                            <select name="action_type" class="form-control">
                                <option value="">All Actions</option>
                                <?php foreach ($actionTypes as $type): ?>
                                <option value="<?php echo htmlspecialchars($type); ?>" 
                                        <?php echo $actionFilter === $type ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($type); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Date From</label>
                            <input type="date" class="form-control" name="date_from" 
                                   value="<?php echo htmlspecialchars($dateFrom); ?>">
                        </div>
                    </div>
                    
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Date To</label>
                            <input type="date" class="form-control" name="date_to" 
                                   value="<?php echo htmlspecialchars($dateTo); ?>">
                        </div>
                    </div>
                    
                    <div class="col-md-1">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Sort By</label>
                            <select name="sort_by" class="form-control">
                                <option value="created_at" <?php echo $sortBy === 'created_at' ? 'selected' : ''; ?>>Date/Time</option>
                                <option value="user_id" <?php echo $sortBy === 'user_id' ? 'selected' : ''; ?>>User</option>
                                <option value="role" <?php echo $sortBy === 'role' ? 'selected' : ''; ?>>Role</option>
                                <option value="action_type" <?php echo $sortBy === 'action_type' ? 'selected' : ''; ?>>Action Type</option>
                                <option value="project_id" <?php echo $sortBy === 'project_id' ? 'selected' : ''; ?>>Project</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Order</label>
                            <select name="sort_order" class="form-control">
                                <option value="DESC" <?php echo $sortOrder === 'DESC' ? 'selected' : ''; ?>>Descending</option>
                                <option value="ASC" <?php echo $sortOrder === 'ASC' ? 'selected' : ''; ?>>Ascending</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <a href="/modules/admin/logs.php" class="btn btn-secondary btn-block">
                                <i class="fas fa-redo"></i> Reset
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Logs Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">System Logs (<?php echo $totalLogs; ?> total)</h5>
        </div>
        <div class="card-body">
            <?php if (empty($logs)): ?>
            <p class="text-muted">No logs found.</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover table-sm">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Date/Time</th>
                            <th>User</th>
                            <th>Role</th>
                            <th>Action Type</th>
                            <th>Description</th>
                            <th>Project</th>
                            <th>Status Change</th>
                            <th>IP Address</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo $log['log_id']; ?></td>
                            <td><?php echo formatDateTime($log['created_at']); ?></td>
                            <td><?php echo htmlspecialchars($log['user_name'] ?? 'System'); ?></td>
                            <td><span class="badge badge-secondary"><?php echo htmlspecialchars($log['role'] ?? '-'); ?></span></td>
                            <td><span class="badge badge-info"><?php echo htmlspecialchars($log['action_type']); ?></span></td>
                            <td><?php echo htmlspecialchars($log['action_description']); ?></td>
                            <td>
                                <?php if ($log['project_id']): ?>
                                <a href="/modules/sales/view_project.php?id=<?php echo $log['project_id']; ?>" target="_blank">
                                    #<?php echo $log['project_id']; ?>
                                    <?php if ($log['event_name']): ?>
                                    <br><small><?php echo htmlspecialchars(substr($log['event_name'], 0, 30)); ?></small>
                                    <?php endif; ?>
                                </a>
                                <?php else: ?>
                                -
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($log['old_status'] || $log['new_status']): ?>
                                <small>
                                    <?php if ($log['old_status']): ?>
                                    <?php echo htmlspecialchars(substr($log['old_status'], 0, 15)); ?> →
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars(substr($log['new_status'], 0, 15)); ?>
                                </small>
                                <?php else: ?>
                                -
                                <?php endif; ?>
                            </td>
                            <td><small><?php echo htmlspecialchars($log['ip_address'] ?? '-'); ?></small></td>
                            <td>
                                <?php if ($log['remarks']): ?>
                                <small><?php echo htmlspecialchars(substr($log['remarks'], 0, 50)); ?><?php echo strlen($log['remarks']) > 50 ? '...' : ''; ?></small>
                                <?php else: ?>
                                -
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <nav>
                <ul class="pagination justify-content-center">
                    <?php
                    $queryParams = http_build_query(array(
                        'search' => $search,
                        'role' => $roleFilter,
                        'action_type' => $actionFilter,
                        'date_from' => $dateFrom,
                        'date_to' => $dateTo,
                        'sort_by' => $sortBy,
                        'sort_order' => $sortOrder
                    ));
                    ?>
                    
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&<?php echo $queryParams; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>
                </ul>
            </nav>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
