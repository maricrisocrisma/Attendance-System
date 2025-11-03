<?php 
// teacher_session.php
require 'db.php';
if (!isset($_SESSION['user']) || $_SESSION['user']['role']!=='teacher') { 
    header('Location: login.php'); exit; 
}
$teacher_id = $_SESSION['user']['id'];
$section_id = intval($_GET['section_id'] ?? 0);

// verify section belongs to teacher
$stmt = $pdo->prepare("SELECT * FROM sections WHERE id=? AND teacher_id=?");
$stmt->execute([$section_id,$teacher_id]);
$section = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$section) { echo "Section not found"; exit; }

$created = false;
$session_link = '';
$session_code = '';
$duration_min = 5; // default

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $duration_min = intval($_POST['duration_min'] ?? 5);
    if ($duration_min <= 0) $duration_min = 5;
    $starts = new DateTime();
    $ends = (clone $starts)->modify("+$duration_min minutes");
    // generate unique code
    $code = strtoupper(bin2hex(random_bytes(4)));
    $stmt = $pdo->prepare("INSERT INTO attendance_sessions (section_id, session_code, starts_at, ends_at) VALUES (?,?,?,?)");
    $stmt->execute([$section_id, $code, $starts->format('Y-m-d H:i:s'), $ends->format('Y-m-d H:i:s')]);
    $session_id = $pdo->lastInsertId();
    $created = true;
    $session_code = $code;
    $session_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . "/student_attendance.php?code=" . $code;
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Create Attendance Session</title>
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

/* Forms */
label {
    display: block;
    margin-bottom: 6px;
    font-weight: 500;
}

input[type="number"], input[type="text"] {
    padding: 6px 8px;
    border-radius: 5px;
    border: 1px solid #dcdfe3;
    width: 100%;
    box-sizing: border-box;
    margin-bottom: 12px;
}

/* Buttons */
button {
    background: #4a90e2;
    color: white;
    border: none;
    border-radius: 6px;
    padding: 8px 14px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
}

button:hover {
    background: #3a7bd5;
}

a {
    color: #4a90e2;
    text-decoration: none;
    font-weight: 500;
}

a:hover {
    text-decoration: underline;
}
</style>
</head>
<body>

<header>
    <h2>Attendance System</h2>
    <nav>
        <a href="teacher_dashboard.php">Dashboard</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<div class="container">
<h2>Section: <?=htmlspecialchars($section['name'])?></h2>
<p><a href="teacher_dashboard.php">Back to Dashboard</a></p>

<?php if(!$created): ?>
<form method="post">
  <label>Duration (minutes)</label>
  <input type="number" name="duration_min" value="<?=htmlspecialchars($duration_min)?>" min="1">
  <button type="submit">Create Attendance Session</button>
</form>
<?php else: ?>
  <h3>Session Created</h3>
  <p>Code: <strong><?=$session_code?></strong></p>
  <p>Link (share with students):<br>
  <input type="text" style="width:100%;" value="<?=htmlspecialchars($session_link)?>" readonly></p>
  <p>Students have <?=$duration_min?> minutes to mark present. After it ends, missing students are marked absent.</p>
  <p><a href="teacher_attendance.php?section_id=<?=$section_id?>&code=<?=$session_code?>">Go to Attendance Page</a></p>
<?php endif; ?>
</div>
</body>
</html>


