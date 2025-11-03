<?php
// teacher_attendance.php
require 'db.php';
if (!isset($_SESSION['user']) || $_SESSION['user']['role']!=='teacher') { 
    header('Location: login.php'); 
    exit; 
}

$teacher_id = $_SESSION['user']['id'];
$section_id = intval($_GET['section_id'] ?? 0);
$code = trim($_GET['code'] ?? '');

// verify section belongs to teacher
$stmt = $pdo->prepare("SELECT * FROM sections WHERE id=? AND teacher_id=?");
$stmt->execute([$section_id,$teacher_id]);
$section = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$section) { echo "Section not found"; exit; }

// find session if code provided
$session = null;
if ($code) {
    $stmt = $pdo->prepare("SELECT * FROM attendance_sessions WHERE section_id=? AND session_code=? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$section_id,$code]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$session) {
    echo "Provide a valid session code (create one first). <p><a href='teacher_dashboard.php'>Back</a></p>";
    exit;
}

// Handle delete attendance request
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    $pdo->prepare("DELETE FROM attendance WHERE id=?")->execute([$del_id]);
    header("Location: teacher_attendance.php?section_id=$section_id&code=$code");
    exit;
}

// fetch students in section
$stmt = $pdo->prepare("SELECT * FROM students_in_section WHERE section_id=? ORDER BY id");
$stmt->execute([$section_id]);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// fetch attendance records for this session
$stmt = $pdo->prepare("SELECT * FROM attendance WHERE session_id=?");
$stmt->execute([$session['id']]);
$att = [];
foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) $att[$r['student_row_id']] = $r;

$now = new DateTime();
$ends = new DateTime($session['ends_at']);
$session_closed = $now > $ends;

// mark missing students absent automatically if session closed
if ($session_closed) {
    $pdo->beginTransaction();
    $ins = $pdo->prepare("INSERT INTO attendance (session_id, section_id, student_row_id, status, marked_at) VALUES (?,?,?,?,NOW())");
    foreach ($students as $s) {
        if (!isset($att[$s['id']])) {
            $ins->execute([$session['id'],$section_id,$s['id'],'absent']);
        }
    }
    $pdo->commit();
    // refresh attendance
    $stmt = $pdo->prepare("SELECT * FROM attendance WHERE session_id=?");
    $stmt->execute([$session['id']]);
    $att = [];
    foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) $att[$r['student_row_id']] = $r;
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Attendance - <?=htmlspecialchars($section['name'])?></title>
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
table { width:100%; border-collapse:collapse; margin-top:20px; }
th, td { border:1px solid #ccc; padding:8px; text-align:left; }
th { background:#f2f2f2; }
a.delete-btn { color:#e74c3c; font-weight:bold; text-decoration:none; }
a.delete-btn:hover { text-decoration:underline; }

/* Session info */
.session-info {
    background: #f2f6fc;
    padding: 15px;
    border-radius: 8px;
    margin-top: 20px;
}

/* Status */
.status-present { color: #28a745; font-weight: bold; }
.status-absent { color: #d9534f; font-weight: bold; }
.status-empty { color: #999; font-style: italic; }
</style>
</head>
<body>

<header>
    <h2>Attendance - <?=htmlspecialchars($section['name'])?></h2>
    <nav>
        <span>Welcome, <?=htmlspecialchars($_SESSION['user']['name'])?></span>
        <a href="teacher_dashboard.php">Dashboard</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<div class="container">
    <div class="session-info">
        <p><strong>Session Code:</strong> <?=htmlspecialchars($session['session_code'])?><br>
           <strong>Starts:</strong> <?=htmlspecialchars($session['starts_at'])?><br>
           <strong>Ends:</strong> <?=htmlspecialchars($session['ends_at'])?></p>
    </div>

    <table>
        <tr><th>Student #</th><th>Name</th><th>Status</th><th>Marked At</th><th>Action</th></tr>
        <?php foreach($students as $s): 
            $row = $att[$s['id']] ?? null;
            $status = $row ? $row['status'] : '—';
            $marked_at = $row ? $row['marked_at'] : '';
            $delete_link = $row ? "teacher_attendance.php?section_id=$section_id&code=$code&delete=".$row['id'] : '';
            $status_class = $status === 'present' ? 'status-present' : ($status==='absent' ? 'status-absent' : 'status-empty');
        ?>
        <tr>
          <td><?=htmlspecialchars($s['student_number'])?></td>
          <td><?=htmlspecialchars($s['student_name'])?></td>
          <td class="<?=$status_class?>"><?=htmlspecialchars($status)?></td>
          <td><?=htmlspecialchars($marked_at)?></td>
          <td>
            <?php if ($row && $row['status'] === 'present'): ?>
              <a class="delete-btn" href="<?=$delete_link?>" onclick="return confirm('Delete this attendance record?')">Delete</a>
            <?php else: ?>
              — 
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>
</body>
</html>


