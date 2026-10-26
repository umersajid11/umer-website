<?php
// Admin <-> user messaging helpers. Messages live in the messages table;
// graceful fallback (empty list / false) if the table does not exist yet
// on an older database, so pages never crash.

function get_user_messages($db, $userId) {
    if (!$db) return [];
    try {
        $stmt = $db->prepare("SELECT id, sender_role, message, created_at FROM messages WHERE user_id = ? ORDER BY created_at ASC, id ASC");
        $stmt->execute([(int) $userId]);
        return $stmt->fetchAll();
    } catch (Throwable $e) { return []; }
}

function send_message($db, $userId, $senderRole, $text, $inquiryId = null) {
    if (!$db || trim($text) === '') return false;
    try {
        $stmt = $db->prepare("INSERT INTO messages (user_id, sender_role, message, inquiry_id) VALUES (?, ?, ?, ?)");
        return $stmt->execute([(int) $userId, $senderRole, trim($text), $inquiryId ? (int) $inquiryId : null]);
    } catch (Throwable $e) { return false; }
}

function find_user_id_by_email($db, $email) {
    if (!$db) return null;
    try {
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([trim($email)]);
        $row = $stmt->fetch();
        return $row ? (int) $row['id'] : null;
    } catch (Throwable $e) { return null; }
}

function count_messages($db) {
    if (!$db) return 0;
    try { return (int) $db->query("SELECT COUNT(*) FROM messages")->fetchColumn(); }
    catch (Throwable $e) { return 0; }
}
