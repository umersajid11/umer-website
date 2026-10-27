<?php
// Admin <-> user messaging helpers. Messages live in the messages table;
// graceful fallback (empty list / false) if the table does not exist yet
// on an older database, so pages never crash.
//
// v5 read tracking: messages.is_read (TINYINT, default 0) marks whether
// the admin has seen a user-sent message. If the column does not exist
// yet on an old DB, every helper falls back silently (no crash) and
// unread counts simply read 0 — run the ALTER in database.sql to enable
// the "New Messages" badge.

function messages_has_is_read($db) {
    if (!$db) return false;
    try {
        $stmt = $db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'messages' AND COLUMN_NAME = 'is_read'");
        $stmt->execute();
        return (int) $stmt->fetchColumn() > 0;
    } catch (Throwable $e) { return false; }
}

function get_user_messages($db, $userId) {
    if (!$db) return [];
    $hasRead = messages_has_is_read($db);
    $readCol = $hasRead ? ', is_read' : ', 0 AS is_read';
    try {
        $stmt = $db->prepare("SELECT id, sender_role, message, created_at, inquiry_id" . $readCol . " FROM messages WHERE user_id = ? ORDER BY created_at ASC, id ASC");
        $stmt->execute([(int) $userId]);
        return $stmt->fetchAll();
    } catch (Throwable $e) { return []; }
}

function send_message($db, $userId, $senderRole, $text, $inquiryId = null) {
    if (!$db || trim($text) === '') return false;
    // A user-sent message starts unread for the admin; an admin message
    // is already known to the admin, so it starts as read.
    $isRead = ($senderRole === 'admin') ? 1 : 0;
    try {
        if (messages_has_is_read($db)) {
            $stmt = $db->prepare("INSERT INTO messages (user_id, sender_role, message, inquiry_id, is_read) VALUES (?, ?, ?, ?, ?)");
            return $stmt->execute([(int) $userId, $senderRole, trim($text), $inquiryId ? (int) $inquiryId : null, $isRead]);
        }
        $stmt = $db->prepare("INSERT INTO messages (user_id, sender_role, message, inquiry_id) VALUES (?, ?, ?, ?)");
        return $stmt->execute([(int) $userId, $senderRole, trim($text), $inquiryId ? (int) $inquiryId : null]);
    } catch (Throwable $e) { return false; }
}

function mark_thread_read($db, $userId) {
    if (!$db) return false;
    try {
        if (!messages_has_is_read($db)) return false;
        $stmt = $db->prepare("UPDATE messages SET is_read = 1 WHERE user_id = ? AND sender_role = 'user' AND is_read = 0");
        return $stmt->execute([(int) $userId]);
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

// One row per registered user with a conversation: name, email, total
// messages, latest message preview + time, and unread user-messages.
// Used by the admin dashboard's "New Messages" and "Messages by User".
function get_message_threads($db) {
    if (!$db) return [];
    $hasRead = messages_has_is_read($db);
    try {
        $rows = $db->query("SELECT m.user_id, u.name, u.email, COUNT(*) AS total,
            MAX(m.created_at) AS last_at,
            SUM(CASE WHEN m.sender_role = 'user' " . ($hasRead ? "AND m.is_read = 0" : "") . " THEN 1 ELSE 0 END) AS unread
            FROM messages m LEFT JOIN users u ON u.id = m.user_id
            GROUP BY m.user_id, u.name, u.email ORDER BY last_at DESC")->fetchAll();
        foreach ($rows as &$r) {
            $r['unread'] = $hasRead ? (int) $r['unread'] : 0;
            $r['has_read_column'] = $hasRead;
            try {
                $stmt = $db->prepare("SELECT message, sender_role FROM messages WHERE user_id = ? ORDER BY created_at DESC, id DESC LIMIT 1");
                $stmt->execute([(int) $r['user_id']]);
                $last = $stmt->fetch();
                $r['last_message'] = $last ? $last['message'] : '';
                $r['last_sender'] = $last ? $last['sender_role'] : '';
            } catch (Throwable $e) { $r['last_message'] = ''; $r['last_sender'] = ''; }
        }
        return $rows;
    } catch (Throwable $e) { return []; }
}

function count_unread_messages($db) {
    if (!$db || !messages_has_is_read($db)) return 0;
    try { return (int) $db->query("SELECT COUNT(*) FROM messages WHERE sender_role = 'user' AND is_read = 0")->fetchColumn(); }
    catch (Throwable $e) { return 0; }
}

// v6.1 chat archive/delete: archived chats live in their own small
// table, so no existing table is changed. Every helper degrades
// gracefully when archived_chats does not exist yet (treated as
// "not archived", no crash) — run the CREATE in database.sql to
// enable archiving. Deleting a chat removes ONLY that user's rows
// from messages; it never touches users, clients or inquiries.
function archived_chats_has_table($db) {
    if (!$db) return false;
    try {
        $stmt = $db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'archived_chats'");
        $stmt->execute();
        return (int) $stmt->fetchColumn() > 0;
    } catch (Throwable $e) { return false; }
}

function get_archived_user_ids($db) {
    if (!$db || !archived_chats_has_table($db)) return [];
    try { return array_map('intval', $db->query("SELECT user_id FROM archived_chats")->fetchAll(PDO::FETCH_COLUMN)); }
    catch (Throwable $e) { return []; }
}

function is_chat_archived($db, $userId) {
    if (!$db) return false;
    return in_array((int) $userId, get_archived_user_ids($db), true);
}

function archive_chat($db, $userId) {
    if (!$db || !archived_chats_has_table($db)) return false;
    try {
        $stmt = $db->prepare("INSERT INTO archived_chats (user_id) VALUES (?) ON DUPLICATE KEY UPDATE archived_at = CURRENT_TIMESTAMP");
        return $stmt->execute([(int) $userId]);
    } catch (Throwable $e) { return false; }
}

function unarchive_chat($db, $userId) {
    if (!$db || !archived_chats_has_table($db)) return false;
    try {
        $stmt = $db->prepare("DELETE FROM archived_chats WHERE user_id = ?");
        return $stmt->execute([(int) $userId]);
    } catch (Throwable $e) { return false; }
}

function delete_chat_messages($db, $userId) {
    if (!$db) return false;
    try {
        $stmt = $db->prepare("DELETE FROM messages WHERE user_id = ?");
        return $stmt->execute([(int) $userId]);
    } catch (Throwable $e) { return false; }
}
