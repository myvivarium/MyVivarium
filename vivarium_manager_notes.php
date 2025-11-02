<?php

/**
 * Vivarium Manager - Maintenance Notes Management
 *
 * This page allows Vivarium Managers and Admins to:
 * - View all maintenance notes from all cages
 * - Search and filter notes
 * - Add new maintenance notes
 * - Edit existing maintenance notes
 * - Delete maintenance notes
 * - Print maintenance reports
 *
 * Access: Admin and Vivarium Manager roles only
 */

// Start session
session_start();

// Include database connection
require 'dbcon.php';

// Disable error display in production
error_reporting(E_ALL);
ini_set('display_errors', 0);

// SECURITY: Check if user is logged in and has appropriate role
if (!isset($_SESSION['role']) ||
    ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'vivarium_manager')) {
    header("Location: index.php");
    exit;
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle AJAX requests for CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');

    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['success' => false, 'message' => 'CSRF token validation failed']);
        exit;
    }

    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'add':
            // Add new maintenance note
            $cage_id = filter_input(INPUT_POST, 'cage_id', FILTER_SANITIZE_STRING);
            $comments = filter_input(INPUT_POST, 'comments', FILTER_SANITIZE_STRING);
            $user_id = $_SESSION['user_id'];

            if (empty($cage_id)) {
                echo json_encode(['success' => false, 'message' => 'Cage ID is required']);
                exit;
            }

            $stmt = $con->prepare("INSERT INTO maintenance (cage_id, user_id, comments) VALUES (?, ?, ?)");
            $stmt->bind_param("sis", $cage_id, $user_id, $comments);

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Maintenance note added successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to add maintenance note']);
            }
            $stmt->close();
            exit;

        case 'edit':
            // Edit existing maintenance note
            $note_id = filter_input(INPUT_POST, 'note_id', FILTER_VALIDATE_INT);
            $cage_id = filter_input(INPUT_POST, 'cage_id', FILTER_SANITIZE_STRING);
            $comments = filter_input(INPUT_POST, 'comments', FILTER_SANITIZE_STRING);

            if (!$note_id) {
                echo json_encode(['success' => false, 'message' => 'Invalid note ID']);
                exit;
            }

            $stmt = $con->prepare("UPDATE maintenance SET cage_id = ?, comments = ? WHERE id = ?");
            $stmt->bind_param("ssi", $cage_id, $comments, $note_id);

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Maintenance note updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update maintenance note']);
            }
            $stmt->close();
            exit;

        case 'delete':
            // Delete maintenance note
            $note_id = filter_input(INPUT_POST, 'note_id', FILTER_VALIDATE_INT);

            if (!$note_id) {
                echo json_encode(['success' => false, 'message' => 'Invalid note ID']);
                exit;
            }

            $stmt = $con->prepare("DELETE FROM maintenance WHERE id = ?");
            $stmt->bind_param("i", $note_id);

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Maintenance note deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete maintenance note']);
            }
            $stmt->close();
            exit;

        case 'get_note':
            // Get single note for editing
            $note_id = filter_input(INPUT_POST, 'note_id', FILTER_VALIDATE_INT);

            if (!$note_id) {
                echo json_encode(['success' => false, 'message' => 'Invalid note ID']);
                exit;
            }

            $stmt = $con->prepare("SELECT id, cage_id, comments FROM maintenance WHERE id = ?");
            $stmt->bind_param("i", $note_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                echo json_encode(['success' => true, 'data' => $row]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Note not found']);
            }
            $stmt->close();
            exit;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            exit;
    }
}

// Pagination settings
$records_per_page = 50;
$current_page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($current_page - 1) * $records_per_page;

// Search functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$where_clause = '';
$search_params = [];
$param_types = '';

if (!empty($search)) {
    $search_pattern = '%' . $search . '%';
    $where_clause = "WHERE m.cage_id LIKE ? OR m.comments LIKE ? OR u.name LIKE ? OR u.username LIKE ?";
    $search_params = [$search_pattern, $search_pattern, $search_pattern, $search_pattern];
    $param_types = 'ssss';
}

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total
                FROM maintenance m
                LEFT JOIN users u ON m.user_id = u.id
                $where_clause";

if (!empty($search)) {
    $count_stmt = $con->prepare($count_query);
    $count_stmt->bind_param($param_types, ...$search_params);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $total_records = $count_result->fetch_assoc()['total'];
    $count_stmt->close();
} else {
    $total_records = $con->query($count_query)->fetch_assoc()['total'];
}

$total_pages = ceil($total_records / $records_per_page);

// Fetch maintenance notes with user information
$query = "SELECT m.id, m.cage_id, m.comments, m.timestamp,
                 u.name as user_name, u.username, u.initials
          FROM maintenance m
          LEFT JOIN users u ON m.user_id = u.id
          $where_clause
          ORDER BY m.timestamp DESC
          LIMIT ? OFFSET ?";

if (!empty($search)) {
    $stmt = $con->prepare($query);
    $search_params[] = $records_per_page;
    $search_params[] = $offset;
    $param_types .= 'ii';
    $stmt->bind_param($param_types, ...$search_params);
} else {
    $stmt = $con->prepare($query);
    $stmt->bind_param("ii", $records_per_page, $offset);
}

$stmt->execute();
$result = $stmt->get_result();
$maintenance_notes = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get all cage IDs for the add/edit form dropdown
$cage_query = "SELECT DISTINCT cage_id FROM cages ORDER BY cage_id";
$cage_result = $con->query($cage_query);
$cages = $cage_result->fetch_all(MYSQLI_ASSOC);

// Include header
require 'header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Notes Manager | <?php echo htmlspecialchars($labName); ?></title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">

    <style>
        .content {
            padding: 20px;
            min-height: 80vh;
        }

        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .search-box {
            flex: 1;
            max-width: 400px;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .notes-table {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .notes-table table {
            margin-bottom: 0;
        }

        .notes-table th {
            background-color: #343a40;
            color: white;
            font-weight: 500;
        }

        .notes-table td {
            vertical-align: middle;
        }

        .timestamp {
            font-size: 0.9em;
            color: #666;
        }

        .comments-cell {
            max-width: 300px;
            word-wrap: break-word;
        }

        .pagination-info {
            margin: 15px 0;
            color: #666;
        }

        .modal-header {
            background-color: #343a40;
            color: white;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            .notes-table {
                box-shadow: none;
            }

            body {
                font-size: 12pt;
            }

            .print-header {
                display: block !important;
                text-align: center;
                margin-bottom: 20px;
            }
        }

        .print-header {
            display: none;
        }

        @media (max-width: 768px) {
            .header-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .search-box {
                max-width: 100%;
            }

            .action-buttons {
                flex-wrap: wrap;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid content">
        <!-- Print Header (hidden on screen) -->
        <div class="print-header">
            <h2><?php echo htmlspecialchars($labName); ?></h2>
            <h3>Vivarium Maintenance Notes Report</h3>
            <p>Generated: <?php echo date('Y-m-d H:i:s'); ?></p>
            <?php if (!empty($search)): ?>
                <p>Search Filter: "<?php echo htmlspecialchars($search); ?>"</p>
            <?php endif; ?>
            <hr>
        </div>

        <!-- Page Header -->
        <div class="no-print">
            <h1><i class="fas fa-clipboard-list"></i> Vivarium Maintenance Notes Manager</h1>

            <!-- Search and Action Bar -->
            <div class="header-actions">
                <form method="GET" action="" class="search-box">
                    <div class="input-group">
                        <input type="text"
                               class="form-control"
                               name="search"
                               placeholder="Search cage ID, comments, or user..."
                               value="<?php echo htmlspecialchars($search); ?>">
                        <button class="btn btn-primary" type="submit">
                            <i class="fas fa-search"></i> Search
                        </button>
                        <?php if (!empty($search)): ?>
                            <a href="vivarium_manager_notes.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Clear
                            </a>
                        <?php endif; ?>
                    </div>
                </form>

                <div class="action-buttons">
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addNoteModal">
                        <i class="fas fa-plus"></i> Add Note
                    </button>
                    <button class="btn btn-info" onclick="window.print()">
                        <i class="fas fa-print"></i> Print
                    </button>
                </div>
            </div>

            <!-- Pagination Info -->
            <div class="pagination-info">
                Showing <?php echo $offset + 1; ?> - <?php echo min($offset + $records_per_page, $total_records); ?>
                of <?php echo $total_records; ?> total records
            </div>
        </div>

        <!-- Maintenance Notes Table -->
        <div class="notes-table">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Cage ID</th>
                        <th>Date/Time</th>
                        <th>Added By</th>
                        <th class="comments-cell">Comments</th>
                        <th class="no-print">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($maintenance_notes)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <p>No maintenance notes found.</p>
                                <?php if (!empty($search)): ?>
                                    <p>Try adjusting your search criteria.</p>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($maintenance_notes as $note): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($note['id']); ?></td>
                                <td><strong><?php echo htmlspecialchars($note['cage_id']); ?></strong></td>
                                <td class="timestamp">
                                    <?php echo date('Y-m-d', strtotime($note['timestamp'])); ?><br>
                                    <small><?php echo date('H:i:s', strtotime($note['timestamp'])); ?></small>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($note['user_name'] ?? 'Unknown'); ?>
                                    <?php if (!empty($note['initials'])): ?>
                                        <br><small class="text-muted">(<?php echo htmlspecialchars($note['initials']); ?>)</small>
                                    <?php endif; ?>
                                </td>
                                <td class="comments-cell">
                                    <?php echo htmlspecialchars($note['comments'] ?? 'No comments'); ?>
                                </td>
                                <td class="no-print">
                                    <button class="btn btn-sm btn-primary"
                                            onclick="editNote(<?php echo $note['id']; ?>)"
                                            title="Edit Note">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger"
                                            onclick="deleteNote(<?php echo $note['id']; ?>)"
                                            title="Delete Note">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <nav aria-label="Page navigation" class="mt-4 no-print">
                <ul class="pagination justify-content-center">
                    <?php if ($current_page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo ($current_page - 1); ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                                Previous
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php for ($i = max(1, $current_page - 2); $i <= min($total_pages, $current_page + 2); $i++): ?>
                        <li class="page-item <?php echo $i == $current_page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($current_page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo ($current_page + 1); ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                                Next
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>

    <!-- Add Note Modal -->
    <div class="modal fade" id="addNoteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus-circle"></i> Add Maintenance Note</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addNoteForm">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="ajax" value="1">
                        <input type="hidden" name="action" value="add">

                        <div class="mb-3">
                            <label for="add_cage_id" class="form-label">Cage ID <span class="text-danger">*</span></label>
                            <select class="form-select" id="add_cage_id" name="cage_id" required>
                                <option value="">Select Cage</option>
                                <?php foreach ($cages as $cage): ?>
                                    <option value="<?php echo htmlspecialchars($cage['cage_id']); ?>">
                                        <?php echo htmlspecialchars($cage['cage_id']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="add_comments" class="form-label">Comments</label>
                            <textarea class="form-control" id="add_comments" name="comments" rows="4"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" onclick="submitAddNote()">
                        <i class="fas fa-save"></i> Save Note
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Note Modal -->
    <div class="modal fade" id="editNoteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit"></i> Edit Maintenance Note</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editNoteForm">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="ajax" value="1">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" id="edit_note_id" name="note_id">

                        <div class="mb-3">
                            <label for="edit_cage_id" class="form-label">Cage ID <span class="text-danger">*</span></label>
                            <select class="form-select" id="edit_cage_id" name="cage_id" required>
                                <option value="">Select Cage</option>
                                <?php foreach ($cages as $cage): ?>
                                    <option value="<?php echo htmlspecialchars($cage['cage_id']); ?>">
                                        <?php echo htmlspecialchars($cage['cage_id']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="edit_comments" class="form-label">Comments</label>
                            <textarea class="form-control" id="edit_comments" name="comments" rows="4"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="submitEditNote()">
                        <i class="fas fa-save"></i> Update Note
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom JavaScript -->
    <script>
        // Add Note
        function submitAddNote() {
            const form = document.getElementById('addNoteForm');
            const formData = new FormData(form);

            fetch('vivarium_manager_notes.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('An error occurred. Please try again.');
                console.error('Error:', error);
            });
        }

        // Load note data for editing
        function editNote(noteId) {
            const formData = new FormData();
            formData.append('ajax', '1');
            formData.append('action', 'get_note');
            formData.append('note_id', noteId);
            formData.append('csrf_token', '<?php echo $_SESSION['csrf_token']; ?>');

            fetch('vivarium_manager_notes.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('edit_note_id').value = data.data.id;
                    document.getElementById('edit_cage_id').value = data.data.cage_id;
                    document.getElementById('edit_comments').value = data.data.comments || '';

                    const editModal = new bootstrap.Modal(document.getElementById('editNoteModal'));
                    editModal.show();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('An error occurred. Please try again.');
                console.error('Error:', error);
            });
        }

        // Submit edit note
        function submitEditNote() {
            const form = document.getElementById('editNoteForm');
            const formData = new FormData(form);

            fetch('vivarium_manager_notes.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('An error occurred. Please try again.');
                console.error('Error:', error);
            });
        }

        // Delete note
        function deleteNote(noteId) {
            if (!confirm('Are you sure you want to delete this maintenance note? This action cannot be undone.')) {
                return;
            }

            const formData = new FormData();
            formData.append('ajax', '1');
            formData.append('action', 'delete');
            formData.append('note_id', noteId);
            formData.append('csrf_token', '<?php echo $_SESSION['csrf_token']; ?>');

            fetch('vivarium_manager_notes.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('An error occurred. Please try again.');
                console.error('Error:', error);
            });
        }
    </script>

    <?php include 'footer.php'; ?>
</body>
</html>

<?php
$con->close();
?>
