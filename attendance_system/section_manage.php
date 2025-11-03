<?php
// section_manage.php
require 'db.php';
if (!isset($_SESSION['user']) || $_SESSION['user']['role']!=='teacher') {
    header('Location: login.php'); exit;
}
$teacher_id = $_SESSION['user']['id'];
$section_id = intval($_GET['id'] ?? 0);

// verify teacher owns section
$stmt = $pdo->prepare("SELECT * FROM sections WHERE id=? AND teacher_id=?");
$stmt->execute([$section_id,$teacher_id]);
$section = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$section) { echo "Section not found"; exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['names'])) {
    // names[] and numbers[] arrays
    $names = $_POST['names'];
    $numbers = $_POST['numbers'] ?? [];
    $ins = $pdo->prepare("INSERT INTO students_in_section (section_id, student_number, student_name) VALUES (?,?,?)");
    for ($i=0;$i<count($names);$i++) {
        $n = trim($names[$i]);
        if ($n==='') continue;
        $num = trim($numbers[$i] ?? '');
        $ins->execute([$section_id, $num, $n]);
    }
    header("Location: section_manage.php?id=$section_id");
    exit;
}

// fetch current students
$stmt = $pdo->prepare("SELECT * FROM students_in_section WHERE section_id=? ORDER BY created_at");
$stmt->execute([$section_id]);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Manage <?=htmlspecialchars($section['name'])?></title>
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
    max-width: 900px;
    margin: auto;
    background: #fff;
    border: 1px solid #dcdfe3;
    border-radius: 10px;
    padding: 30px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.06);
}

/* Tables */
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}

th, td {
    border: 1px solid #dcdfe3;
    padding: 10px;
    text-align: left;
}

th {
    background: #f1f4f8;
    font-weight: 600;
}

/* Form inputs */
input[type="text"], input[type="number"] {
    padding: 6px 8px;
    border-radius: 5px;
    border: 1px solid #dcdfe3;
    width: 100%;
    box-sizing: border-box;
}

/* Buttons */
button {
    background: #4a90e2;
    color: white;
    border: none;
    border-radius: 6px;
    padding: 6px 12px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
}

button:hover {
    background: #3a7bd5;
}

button[type="button"] {
    padding: 4px 10px;
}

a.delete-link {
    color: #e74c3c;
    text-decoration: none;
    font-weight: 500;
}

a.delete-link:hover {
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
<p>
    <a href="teacher_dashboard.php">Back</a> | 
    <a href="teacher_session.php?section_id=<?=$section_id?>">Create Attendance Session</a>
</p>

<h3>Add Students</h3>
<form method="post" id="addForm">
  <table id="addTable">
    <tr><th>Student ID</th><th>Name</th><th></th></tr>
    <tr>
      <td><input name="numbers[]" /></td>
      <td><input name="names[]" /></td>
      <td><button type="button" onclick="addRow()">+</button></td>
    </tr>
  </table>
  <br>
  <button type="submit">Save Record</button>
</form>

<h3>Current Students</h3>
<table>
<tr><th>Student #</th><th>Name</th><th>Action</th></tr>
<?php foreach($students as $st): ?>
  <tr>
    <td><?=htmlspecialchars($st['student_number'])?></td>
    <td><?=htmlspecialchars($st['student_name'])?></td>
    <td><a href="delete_student.php?id=<?=$st['id']?>&section_id=<?=$section_id?>" class="delete-link" onclick="return confirm('Delete?')">Delete</a></td>
  </tr>
<?php endforeach; ?>
</table>

<script>
function addRow(){
  let tr = document.createElement('tr');
  tr.innerHTML = '<td><input name="numbers[]" /></td><td><input name="names[]" /></td><td><button type="button" onclick="this.parentElement.parentElement.remove()">-</button></td>';
  document.getElementById('addTable').appendChild(tr);
}
</script>

</div>
</body>
</html>
