<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'Chipidje@24');
define('DB_NAME', 'quicklist');

function getDB() {
    static $db = null;
    if ($db === null) {
        $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($db->connect_error) {
            die("Connection failed: " . $db->connect_error);
        }
    }
    return $db;
}

function initDB() {
    $db = getDB();
    $db->query("CREATE TABLE IF NOT EXISTS lists (
        id VARCHAR(50) PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    $db->query("CREATE TABLE IF NOT EXISTS items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        list_id VARCHAR(50) NOT NULL,
        text TEXT NOT NULL,
        completed TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (list_id) REFERENCES lists(id) ON DELETE CASCADE
    )");
    $result = $db->query("SELECT id FROM lists WHERE id = 'community-picnic'");
    if ($result->num_rows == 0) {
        $db->query("INSERT INTO lists (id, name, description) VALUES (
            'community-picnic', 
            'Community Picnic Supplies', 
            'Add items you can bring to the community picnic. Check off items that are already covered.'
        )");
    }
}
// API requests
$action = $_POST['action'] ?? '';
$listId = $_POST['list_id'] ?? '';
$response = ['success' => false, 'message' => 'Unknown action'];

try {
    initDB();
    $db = getDB();
    
    switch ($action) {
        case 'get':
            $since = $_POST['since'] ?? 0;
            $items = [];
            
            $stmt = $db->prepare("SELECT id, text, completed, UNIX_TIMESTAMP(updated_at) as timestamp 
                                 FROM items WHERE list_id = ? AND UNIX_TIMESTAMP(updated_at) > ? 
                                 ORDER BY created_at ASC");
            $stmt->bind_param("si", $listId, $since);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $items[] = [
                    'id' => $row['id'],
                    'text' => $row['text'],
                    'completed' => (bool)$row['completed'],
                    'timestamp' => $row['timestamp']
                ];
            }
            
            $response = [
                'success' => true,
                'items' => $items,
                'timestamp' => time()
            ];
            break;
            
        case 'add':
            $text = $_POST['text'] ?? '';
            if (empty($text)) {
                $response = ['success' => false, 'message' => 'Item text cannot be empty'];
                break;
            }
            
            $stmt = $db->prepare("INSERT INTO items (list_id, text) VALUES (?, ?)");
            $stmt->bind_param("ss", $listId, $text);
            
            if ($stmt->execute()) {
                $newId = $db->insert_id;
                $response = [
                    'success' => true,
                    'item' => [
                        'id' => $newId,
                        'text' => $text,
                        'completed' => false
                    ]
                ];
            } else {
                $response = ['success' => false, 'message' => 'Failed to add item'];
            }
            break;
            
        case 'toggle':
            $itemId = $_POST['item_id'] ?? 0;
            $stmt = $db->prepare("UPDATE items SET completed = NOT completed WHERE id = ? AND list_id = ?");
            $stmt->bind_param("is", $itemId, $listId);
            
            if ($stmt->execute()) {
                $response = ['success' => true];
            } else {
                $response = ['success' => false, 'message' => 'Failed to update item'];
            }
            break;
            
        case 'delete':
            $itemId = $_POST['item_id'] ?? 0;
            $stmt = $db->prepare("DELETE FROM items WHERE id = ? AND list_id = ?");
            $stmt->bind_param("is", $itemId, $listId);
            
            if ($stmt->execute()) {
                $response = ['success' => true];
            } else {
                $response = ['success' => false, 'message' => 'Failed to delete item'];
            }
            break;
            
        default:
            $response = ['success' => false, 'message' => 'Invalid action'];
    }
} catch (Exception $e) {
    $response = ['success' => false, 'message' => 'Server error: ' . $e->getMessage()];
}

echo json_encode($response);

?>
