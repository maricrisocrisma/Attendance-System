<?php
require 'db.php';
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'teacher') {
    header('Location: login.php');
    exit;
}

$teacher_id = $_SESSION['user']['id'];
$section_id = intval($_GET['section_id'] ?? 0);

// verify section belongs to teacher
$stmt = $pdo->prepare("SELECT * FROM sections WHERE id=? AND teacher_id=?");
$stmt->execute([$section_id, $teacher_id]);
$section = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$section) {
    echo "Section not found.";
    exit;
}

$date = $_GET['date'] ?? '';
$present_students = [];
$absent_students = [];

if ($date) {
    // find sessions for that date
    $stmt = $pdo->prepare("SELECT id FROM attendance_sessions WHERE section_id=? AND DATE(starts_at)=?");
    $stmt->execute([$section_id, $date]);
    $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $session_ids = array_column($sessions, 'id');

    if ($session_ids) {
        $in = str_repeat('?,', count($session_ids) - 1) . '?';

        // Present
        $stmt = $pdo->prepare("
            SELECT s.student_number, s.student_name
            FROM attendance a
            JOIN students_in_section s ON a.student_row_id = s.id
            WHERE a.section_id = ? AND a.session_id IN ($in) AND a.status = 'present'
            GROUP BY s.id
        ");
        $stmt->execute(array_merge([$section_id], $session_ids));
        $present_students = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Absent
        $stmt = $pdo->prepare("
            SELECT s.student_number, s.student_name
            FROM students_in_section s
            WHERE s.section_id = ?
            AND s.id NOT IN (
                SELECT a.student_row_id
                FROM attendance a
                WHERE a.section_id = ? AND a.session_id IN ($in) AND a.status = 'present'
            )
            ORDER BY s.student_name
        ");
        $stmt->execute(array_merge([$section_id, $section_id], $session_ids));
        $absent_students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Attendance Records - <?= htmlspecialchars($section['name']) ?></title>
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
    max-width: 800px;
    margin: auto;
    background: #fff;
    border: 1px solid #dcdfe3;
    border-radius: 10px;
    padding: 30px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.06);
}

/* Form */
form {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 10px;
    margin-bottom: 20px;
}

form label {
    margin-right: 10px;
    font-weight: 500;
}

form input[type="date"] {
    padding: 8px;
    border: 1px solid #dcdfe3;
    border-radius: 6px;
}

form button {
    background: #4a90e2;
    color: white;
    border: none;
    border-radius: 6px;
    padding: 8px 15px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
}

form button:hover {
    background: #3a7bd5;
}

/* Tables */
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
    font-size: 14px;
    background: #fff;
    border-radius: 6px;
    overflow: hidden;
    box-shadow: 0 2px 6px rgba(0,0,0,0.05);
}

th, td {
    border: 1px solid #dcdfe3;
    padding: 10px;
    text-align: left;
    vertical-align: top;
}

th {
    background: #f1f4f8;
    font-weight: 600;
    color: #444;
}

ul {
    padding-left: 20px;
    margin: 0;
}
ul li {
    margin-bottom: 5px;
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

<!-- Main container -->
<div class="container">
    <h2>Attendance Records - <?= htmlspecialchars($section['name']) ?></h2>

    <form method="get">
        <input type="hidden" name="section_id" value="<?= $section_id ?>">
        <label>Select Date:</label>
        <input type="date" name="date" value="<?= htmlspecialchars($date) ?>" required>
        <button type="submit">Show Records</button>
    </form>

    <?php if ($date): ?>
        <h3>Records for <?= htmlspecialchars($date) ?></h3>
        <?php if (empty($present_students) && empty($absent_students)): ?>
            <p>No attendance records found for this date.</p>
        <?php else: ?>
            <table>
                <tr>
                    <th style="width:50%;">Present</th>
                    <th style="width:50%;">Absent</th>
                </tr>
                <tr>
                    <td>
                        <?php if ($present_students): ?>
                            <ul>
                                <?php foreach ($present_students as $s): ?>
                                    <li><?= htmlspecialchars($s['student_number'] . ' - ' . $s['student_name']) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <em>No one present.</em>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($absent_students): ?>
                            <ul>
                                <?php foreach ($absent_students as $s): ?>
                                    <li><?= htmlspecialchars($s['student_number'] . ' - ' . $s['student_name']) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <em>No absentees.</em>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        <?php endif; ?>
    <?php endif; ?>
</div>

</body>
</html>
