<?php
// student_attendance.php
require 'db.php';
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'student') {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'];
$code = trim($_GET['code'] ?? $_POST['code'] ?? '');
$err = '';
$msg = '';

// Handle marking present
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark') {
    $code = trim($_POST['code']);
    $student_row_id = intval($_POST['student_row_id']);

    $stmt = $pdo->prepare("SELECT * FROM attendance_sessions WHERE session_code = ? LIMIT 1");
    $stmt->execute([$code]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$session) {
        $err = "Invalid session code.";
    } else {
        $now = new DateTime();
        $starts = new DateTime($session['starts_at']);
        $ends = new DateTime($session['ends_at']);

        if ($now < $starts) {
            $err = "Session not started yet.";
        } elseif ($now > $ends) {
            $err = "Session already closed.";
        } else {
            $chk = $pdo->prepare("SELECT * FROM attendance WHERE session_id = ? AND student_row_id = ?");
            $chk->execute([$session['id'], $student_row_id]);
            if ($chk->fetch()) {
                $err = "You already marked attendance for this session.";
            } else {
                $ins = $pdo->prepare("
                    INSERT INTO attendance (session_id, section_id, student_row_id, status, marked_at)
                    VALUES (?, ?, ?, 'present', NOW())
                ");
                $ins->execute([$session['id'], $session['section_id'], $student_row_id]);
                $msg = "✅ Attendance marked successfully!";
            }
        }
    }
}

// Fetch session & students if code is provided
$session = null;
$students = [];
if ($code) {
    $stmt = $pdo->prepare("SELECT * FROM attendance_sessions WHERE session_code = ? LIMIT 1");
    $stmt->execute([$code]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($session) {
        $stmt = $pdo->prepare("SELECT * FROM students_in_section WHERE section_id = ? ORDER BY student_name");
        $stmt->execute([$session['section_id']]);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Student Attendance</title>
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
    max-width: 600px;
    margin: auto;
    background: #fff;
    border: 1px solid #dcdfe3;
    border-radius: 10px;
    padding: 30px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.06);
}

/* Form & Select */
form {
    margin-top: 20px;
}

label {
    display: block;
    margin-bottom: 6px;
    font-weight: 500;
}

input[type="text"], select {
    width: 100%;
    padding: 8px 10px;
    margin-bottom: 15px;
    border-radius: 6px;
    border: 1px solid #ccc;
    font-size: 14px;
}

button {
    background: #4a90e2;
    color: #fff;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 500;
    transition: background 0.2s;
}

button:hover {
    background: #3a7bd5;
}

/* Messages */
p.error {
    color: #d9534f;
    font-weight: 500;
}

p.success {
    color: #28a745;
    font-weight: 500;
}

/* Session info */
.session-info {
    background: #f2f6fc;
    padding: 15px;
    border-radius: 8px;
    margin-top: 20px;
}
</style>
</head>
<body>

<header>
    <h2>Student Attendance</h2>
    <nav>
        <span>Welcome, <?=htmlspecialchars($user['name'])?></span>
        <a href="student_home.php" class="button">Home</a>
        <a href="logout.php" class="button">Logout</a>
    </nav>
</header>

<div class="container">

<?php if ($err): ?><p class="error"><?=htmlspecialchars($err)?></p><?php endif; ?>
<?php if ($msg): ?><p class="success"><?=htmlspecialchars($msg)?></p><?php endif; ?>

<form method="get">
    <label>Enter Attendance Code:</label>
    <input type="text" name="code" value="<?=htmlspecialchars($code)?>" required>
    <button type="submit">Open</button>
</form>

<?php if ($session): ?>
    <div class="session-info">
        <h3>Session Code: <?=htmlspecialchars($session['session_code'])?></h3>
        <p>
            Section ID: <?=htmlspecialchars($session['section_id'])?><br>
            Starts: <?=htmlspecialchars($session['starts_at'])?><br>
            Ends: <?=htmlspecialchars($session['ends_at'])?>
        </p>
    </div>

    <?php if (empty($students)): ?>
        <p class="info">No students in this section.</p>
    <?php else: ?>
        <form method="post">
            <input type="hidden" name="code" value="<?=htmlspecialchars($code)?>">
            <label>Select Your Name:</label>
            <select name="student_row_id" required>
                <?php foreach ($students as $s): ?>
                    <option value="<?=$s['id']?>"><?=htmlspecialchars($s['student_number'].' - '.$s['student_name'])?></option>
                <?php endforeach; ?>
            </select>
            <button name="action" value="mark">Mark Present</button>
        </form>
    <?php endif; ?>
<?php endif; ?>

</div>
</body>
</html>


