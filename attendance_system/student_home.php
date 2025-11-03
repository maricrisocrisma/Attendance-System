<?php
// student_home.php
require 'db.php';
if (!isset($_SESSION['user']) || $_SESSION['user']['role']!=='student') { 
    header('Location: login.php'); 
    exit; 
}
$user = $_SESSION['user'];

// Fetch attendance rows for this student
$stmt = $pdo->prepare("SELECT a.*, s.student_name, s.student_number, sec.name AS section_name
FROM attendance a
JOIN students_in_section s ON a.student_row_id = s.id
JOIN sections sec ON a.section_id = sec.id
WHERE s.student_name LIKE ? OR s.user_id = ?
ORDER BY a.marked_at DESC");
$stmt->execute(['%'.$user['name'].'%',$user['id']]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Student Home</title>
<link rel="stylesheet" href="style.css">
<style>
/* Header */
header {
    width: 100%;
    background: #e6f0fa;
    padding: 15px 25px;
    border-radius: 8px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
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
    max-width: 800px;
    margin: auto;
    background: #fff;
    border: 1px solid #dcdfe3;
    border-radius: 10px;
    padding: 30px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.06);
}

/* Table */
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

table, th, td {
    border: 1px solid #dcdfe3;
}

th, td {
    padding: 10px;
    text-align: left;
}

th {
    background: #f2f6fc;
    color: #4a90e2;
}

/* Buttons */
a.button {
    background: #4a90e2;
    color: white;
    padding: 6px 12px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 500;
    transition: background 0.2s;
}

a.button:hover {
    background: #3a7bd5;
}

p.info {
    font-style: italic;
    color: #555;
}
</style>
</head>
<body>

<header>
    <h2>Student Portal</h2>
    <nav>
        <span>Welcome, <?=htmlspecialchars($user['name'])?></span>
        <a href="student_attendance.php" class="button">Enter Code</a>
        <a href="logout.php" class="button">Logout</a>
    </nav>
</header>

<div class="container">
<h3>Your Attendance Records</h3>

<?php if(empty($rows)): ?>
  <p class="info">No records found. If you can't see your records, ask your teacher to link your account or search by your exact name.</p>
<?php else: ?>
  <table>
    <tr>
        <th>Section</th>
        <th>Student</th>
        <th>Status</th>
        <th>Marked At</th>
    </tr>
    <?php foreach($rows as $r): ?>
    <tr>
        <td><?=htmlspecialchars($r['section_name'])?></td>
        <td><?=htmlspecialchars($r['student_name'])?></td>
        <td><?=htmlspecialchars($r['status'])?></td>
        <td><?=htmlspecialchars($r['marked_at'])?></td>
    </tr>
    <?php endforeach; ?>
  </table>
<?php endif; ?>
</div>

</body>
</html>
