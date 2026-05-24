<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'list':
            handleList();
            break;
        case 'create':
            handleCreate();
            break;
        case 'update':
            handleUpdate();
            break;
        case 'delete':
            handleDelete();
            break;
        case 'reorder':
            handleReorder();
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}

function handleList() {
    global $pdo;
    $stmt = $pdo->query('SELECT * FROM tasks ORDER BY sort_order ASC, created_at ASC');
    $tasks = $stmt->fetchAll();

    $result = array_map(function ($row) {
        return [
            'id' => $row['id'],
            'title' => $row['title'],
            'description' => $row['description'],
            'priority' => $row['priority'],
            'status' => $row['status'],
            'dueDate' => $row['due_date'],
            'assigneeType' => $row['assignee_type'],
            'assigneeName' => $row['assignee_name'],
            'createdAt' => (int) $row['created_at'],
            'updatedAt' => (int) $row['updated_at'],
        ];
    }, $tasks);

    echo json_encode($result);
}

function handleCreate() {
    global $pdo;
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data || empty(trim($data['title'] ?? ''))) {
        http_response_code(400);
        echo json_encode(['error' => 'Title is required']);
        return;
    }

    $id = $data['id'] ?? bin2hex(random_bytes(16));
    $now = round(microtime(true) * 1000);

    $stmt = $pdo->prepare('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM tasks WHERE status = ?');
    $stmt->execute(['todo']);
    $sortOrder = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare(
        'INSERT INTO tasks (id, title, description, priority, status, due_date, assignee_type, assignee_name, sort_order, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $id,
        substr(trim($data['title']), 0, 100),
        substr(trim($data['description'] ?? ''), 0, 500) ?: null,
        in_array($data['priority'] ?? '', ['low', 'medium', 'high']) ? $data['priority'] : 'medium',
        'todo',
        !empty($data['dueDate']) ? $data['dueDate'] : null,
        in_array($data['assigneeType'] ?? '', ['person', 'company']) ? $data['assigneeType'] : null,
        !empty(trim($data['assigneeName'] ?? '')) ? substr(trim($data['assigneeName']), 0, 100) : null,
        $sortOrder,
        $now,
        $now,
    ]);

    echo json_encode(['success' => true, 'id' => $id]);
}
function handleUpdate() {
    global $pdo;
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data || empty($data['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Task ID is required']);
        return;
    }

    $fields = [];
    $params = [];

    if (isset($data['title'])) {
        $fields[] = 'title = ?';
        $params[] = substr(trim($data['title']), 0, 100);
    }
    if (array_key_exists('description', $data)) {
        $fields[] = 'description = ?';
        $params[] = substr(trim($data['description'] ?? ''), 0, 500) ?: null;
    }
    if (isset($data['priority']) && in_array($data['priority'], ['low', 'medium', 'high'])) {
        $fields[] = 'priority = ?';
        $params[] = $data['priority'];
    }
    if (isset($data['status']) && in_array($data['status'], ['todo', 'in-progress', 'done'])) {
        $fields[] = 'status = ?';
        $params[] = $data['status'];
    }
    if (array_key_exists('dueDate', $data)) {
        $fields[] = 'due_date = ?';
        $params[] = !empty($data['dueDate']) ? $data['dueDate'] : null;
    }
    if (array_key_exists('assigneeType', $data)) {
        $fields[] = 'assignee_type = ?';
        $params[] = in_array($data['assigneeType'] ?? '', ['person', 'company']) ? $data['assigneeType'] : null;
    }
    if (array_key_exists('assigneeName', $data)) {
        $fields[] = 'assignee_name = ?';
        $params[] = !empty(trim($data['assigneeName'] ?? '')) ? substr(trim($data['assigneeName']), 0, 100) : null;
    }

    if (empty($fields)) {
        echo json_encode(['success' => true]);
        return;
    }

    $fields[] = 'updated_at = ?';
    $params[] = round(microtime(true) * 1000);
    $params[] = $data['id'];

    $sql = 'UPDATE tasks SET ' . implode(', ', $fields) . ' WHERE id = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    echo json_encode(['success' => true]);
}

function handleDelete() {
    global $pdo;
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data || empty($data['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Task ID is required']);
        return;
    }

    $stmt = $pdo->prepare('DELETE FROM tasks WHERE id = ?');
    $stmt->execute([$data['id']]);

    echo json_encode(['success' => true]);
}

function handleReorder() {
    global $pdo;
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data || !is_array($data['order'] ?? null)) {
        http_response_code(400);
        echo json_encode(['error' => 'Order array is required']);
        return;
    }

    $pdo->beginTransaction();
    $stmt = $pdo->prepare('UPDATE tasks SET sort_order = ?, status = ?, updated_at = ? WHERE id = ?');
    $now = round(microtime(true) * 1000);

    foreach ($data['order'] as $i => $item) {
        $stmt->execute([$i, $item['status'], $now, $item['id']]);
    }
    $pdo->commit();

    echo json_encode(['success' => true]);
}
