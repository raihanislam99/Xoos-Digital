<?php
require_once __DIR__ . '/../inc/functions.php';
require_login();
$pdo = db();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

$pdo->exec("CREATE TABLE IF NOT EXISTS note_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    color VARCHAR(7) DEFAULT '#6b7280',
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL DEFAULT 'Untitled',
    content LONGTEXT,
    category_id INT DEFAULT NULL,
    is_pinned TINYINT(1) DEFAULT 0,
    note_color VARCHAR(7) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

// Migrate old 'color' column to 'note_color' if needed
try {
    $pdo->exec("ALTER TABLE notes CHANGE COLUMN color note_color VARCHAR(7) DEFAULT NULL");
} catch (Exception $e) {
    // Column may not exist or already renamed — ignore
}

$count = $pdo->query("SELECT COUNT(*) FROM note_categories")->fetchColumn();
if ($count == 0) {
    $pdo->exec("INSERT INTO note_categories (name, color, sort_order) VALUES
        ('General','#6b7280',1),
        ('Important','#ff4757',2),
        ('Ideas','#9b6dff',3),
        ('Follow-up','#4f8ef7',4),
        ('Client','#ff8c42',5)
    ");
}

try {

switch ($action) {

    case 'get_categories':
        $stmt = $pdo->query("
            SELECT nc.*, COUNT(n.id) as note_count
            FROM note_categories nc
            LEFT JOIN notes n ON n.category_id = nc.id
            GROUP BY nc.id
            ORDER BY nc.sort_order ASC
        ");
        json_response($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    case 'create_category':
        $name  = trim($_POST['name'] ?? '');
        $color = trim($_POST['color'] ?? '#6b7280');
        if (!$name) { json_response(['error' => 'Name required'], 400); break; }
        $stmt = $pdo->prepare("INSERT INTO note_categories (name, color) VALUES (?, ?)");
        $stmt->execute([$name, $color]);
        json_response(['success' => true, 'id' => (int)$pdo->lastInsertId()]);
        break;

    case 'delete_category':
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare("UPDATE notes SET category_id=NULL WHERE category_id=?")->execute([$id]);
        $pdo->prepare("DELETE FROM note_categories WHERE id=?")->execute([$id]);
        json_response(['success' => true]);
        break;

    case 'get_notes':
        $cat    = (int)($_POST['category_id'] ?? 0);
        $search = trim($_POST['search'] ?? '');
        $sort   = $_POST['sort'] ?? 'newest';

        $where = ['1=1'];
        $params = [];

        if ($cat > 0) {
            $where[] = 'n.category_id = ?';
            $params[] = $cat;
        }
        if ($search) {
            $where[] = '(n.title LIKE ? OR n.content LIKE ?)';
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $orderMap = [
            'newest'  => 'n.updated_at DESC',
            'oldest'  => 'n.updated_at ASC',
            'az'      => 'n.title ASC',
            'pinned'  => 'n.is_pinned DESC, n.updated_at DESC',
        ];
        $order = $orderMap[$sort] ?? 'n.updated_at DESC';

        $sql = "SELECT n.*,
                       nc.name as category_name,
                       nc.color as category_color
                FROM notes n
                LEFT JOIN note_categories nc ON n.category_id = nc.id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY $order";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        json_response($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    case 'get_note':
        $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
        $stmt = $pdo->prepare("SELECT n.*, nc.name as category_name, nc.color as category_color
                               FROM notes n
                               LEFT JOIN note_categories nc ON n.category_id = nc.id
                               WHERE n.id=?");
        $stmt->execute([$id]);
        $note = $stmt->fetch(PDO::FETCH_ASSOC);
        json_response($note ?: ['error' => 'Not found']);
        break;

    case 'create_note':
        $title   = trim($_POST['title'] ?? 'Untitled');
        $content = trim($_POST['content'] ?? '');
        $cat_id  = (int)($_POST['category_id'] ?? 0) ?: null;
        $color   = trim($_POST['note_color'] ?? '') ?: null;
        $stmt = $pdo->prepare("INSERT INTO notes (title, content, category_id, note_color) VALUES (?, ?, ?, ?)");
        $stmt->execute([$title, $content, $cat_id, $color]);
        json_response([
            'success'    => true,
            'id'         => (int)$pdo->lastInsertId(),
            'created_at' => date('Y-m-d H:i:s')
        ]);
        break;

    case 'update_note':
        $id      = (int)($_POST['id'] ?? 0);
        $title   = trim($_POST['title'] ?? 'Untitled');
        $content = trim($_POST['content'] ?? '');
        $cat_id  = (int)($_POST['category_id'] ?? 0) ?: null;
        $color   = trim($_POST['note_color'] ?? '') ?: null;
        $stmt = $pdo->prepare("UPDATE notes SET title=?, content=?, category_id=?, note_color=?, updated_at=NOW() WHERE id=?");
        $stmt->execute([$title, $content, $cat_id, $color, $id]);
        json_response(['success' => true, 'updated_at' => date('Y-m-d H:i:s')]);
        break;

    case 'delete_note':
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare("DELETE FROM notes WHERE id=?")->execute([$id]);
        json_response(['success' => true]);
        break;

    case 'toggle_pin':
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare("UPDATE notes SET is_pinned = 1 - is_pinned WHERE id=?")->execute([$id]);
        $note = $pdo->prepare("SELECT is_pinned FROM notes WHERE id=?");
        $note->execute([$id]);
        $row = $note->fetch(PDO::FETCH_ASSOC);
        json_response(['success' => true, 'is_pinned' => (int)$row['is_pinned']]);
        break;

    default:
        json_response(['error' => 'Unknown action'], 400);
}

} catch (Exception $e) {
    json_response(['error' => $e->getMessage()], 500);
}
