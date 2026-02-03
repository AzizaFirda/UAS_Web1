<?php
require_once __DIR__ . '/../config/database.php';

try {
    $db = getDB();
    
    // Check user 5 categories
    $stmt = $db->prepare('SELECT COUNT(*) as cnt FROM categories WHERE user_id = 5');
    $stmt->execute();
    $result = $stmt->fetch();
    
    echo "User 5 has " . $result['cnt'] . " categories\n";
    
    if ($result['cnt'] > 0) {
        // List them
        $stmt = $db->prepare('SELECT name, type, icon FROM categories WHERE user_id = 5 ORDER BY type');
        $stmt->execute();
        $cats = $stmt->fetchAll();
        foreach ($cats as $cat) {
            echo "  - " . $cat['name'] . " (" . $cat['type'] . ")\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
