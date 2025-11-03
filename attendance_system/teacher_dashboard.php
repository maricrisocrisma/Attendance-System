<?php
// teacher_dashboard.php
require 'db.php'; // Make sure session_start() is only in db.php

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'teacher') {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'];
$teacher_id = $user['id'];
$name = htmlspecialchars($user['name'] ?? '');

// Handle creating new section
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['section_name'])) {
    $section_name = trim($_POST['section_name']);
    if ($section_name !== '') {
        $stmt = $pdo->prepare("INSERT INTO sections (teacher_id, name) VALUES (?, ?)");
        $stmt->execute([$teacher_id, $section_name]);
        header('Location: teacher_dashboard.php'); // prevent duplicate POST
        exit;
    }
}

// Fetch sections for this teacher
$stmt = $pdo->prepare("SELECT * FROM sections WHERE teacher_id=? ORDER BY id DESC");
$stmt->execute([$teacher_id]);
$sections = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Teacher Dashboard</title>
<link rel="stylesheet" href="style.css">
<style>
/* Header */
header {
    width: 100%;
    background: #e6f0fa; /* very light blue */
    padding: 15px 25px;
    border-radius: 8px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    box-sizing: border-box;
}

header h2 {
    color: #4a90e2;
    margin: 0;
}

header nav a {
    margin-left: 15px;
    color: #4a90e2;
    font-weight: 500;
    text-decoration: none;
}

header nav a:hover {
    text-decoration: underline;
}

/* Container */
.container {
    max-width: 700px;
    margin: auto;
    background: #fff;
    border: 1px solid #dcdfe3;
    border-radius: 10px;
    padding: 30px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.06);
}

/* Action Buttons */
.action-buttons a {
    display: inline-block;
    margin: 10px 10px 10px 0;
    padding: 10px 15px;
    background: #4a90e2;
    color: white;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 600;
    transition: background 0.2s;
}

.action-buttons a:hover {
    background: #3a7bd5;
}

/* Section list spacing */
ul {
    list-style-type: none;
    padding-left: 0;
}

ul li {
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}
</style>
</head>
<body>

<!-- Header -->
<header>
    <h2>Attendance System</h2>
    <nav>
        <a href="teacher_dashboard.php">Dashboard</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<!-- Main Content -->
<div class="container">
    <h2>Welcome, <?= $name ?></h2>

    <h3>Create Section</h3>
    <form method="post">
        <input type="text" name="section_name" placeholder="Section name e.g. 2A" required>
        <button type="submit">Create Section</button>
    </form>

    <h3>Your Sections</h3>
    <?php if (empty($sections)): ?>
        <p>No sections yet.</p>
    <?php else: ?>
        <ul>
        <?php foreach ($sections as $sec): ?>
            <li>
                <?= htmlspecialchars($sec['name']) ?>
                <div class="action-buttons">
                    <a href="section_manage.php?id=<?= $sec['id'] ?>">Manage</a>
                    <a href="teacher_records.php?section_id=<?= $sec['id'] ?>">Records</a>
                    <a href="teacher_session.php?section_id=<?= $sec['id'] ?>">Create Attendance Session</a>
                </div>
            </li>
        <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

</body>
</html>
