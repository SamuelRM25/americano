<?php
require_once '../config/database.php';

echo "Starting chat migration...\n";

try {
    // 1. Add admin_id to chat_messages
    echo "Checking for admin_id in chat_messages table...\n";
    $result = $pdo->query("SHOW COLUMNS FROM chat_messages LIKE 'admin_id'")->fetch();
    if (!$result) {
        echo "Adding admin_id to chat_messages table...\n";
        $pdo->exec("ALTER TABLE chat_messages ADD COLUMN admin_id INT NULL");

        // Associate existing messages with the first admin
        $firstAdmin = $pdo->query("SELECT id FROM admins ORDER BY id ASC LIMIT 1")->fetchColumn();
        if ($firstAdmin) {
            $pdo->prepare("UPDATE chat_messages SET admin_id = ? WHERE admin_id IS NULL")->execute([$firstAdmin]);
        }
    } else {
        echo "admin_id already exists in chat_messages.\n";
    }

    echo "Chat migration completed successfully!\n";
} catch (Exception $e) {
    echo "Chat migration failed: " . $e->getMessage() . "\n";
}
