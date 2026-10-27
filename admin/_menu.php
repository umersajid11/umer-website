<?php
// Shared simple top menu for every admin page. Every page has the
// SAME menu, in the SAME order, so you never get lost.
// Pages include this file AFTER require_admin_login().
?>
<header><strong>Admin Panel</strong><nav><a href="dashboard.php">Dashboard</a> <a href="messages.php">Messages</a> <a href="clients.php">Clients</a> <a href="add-client.php">Add Client</a> <a href="users.php">Users</a> <a href="inquiries.php">Inquiries</a> <a href="blog.php">Blog</a> <a href="settings.php">Settings</a> <a href="dashboard.php?action=logout">Logout</a></nav></header>
