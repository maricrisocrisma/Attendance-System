<?php
// delete_student.php
require 'db.php';
if (!isset($_SESSION['user']) || $_SESSION['user']['role']!=='teacher') { header('Location: login.php'); exit; }
$id = intval($_GET['id'] ?? 0);
$section_id = intval($_GET['section_id'] ?? 0);

// quick auth: check section belongs to teacher
$stmt = $pdo->prepare("SELECT s.id FROM students_in_section s JOIN sections sec ON s.section_id=sec.id WHERE s.id=? AND sec.teacher_id=?");
$stmt->execute([$id, $_SESSION['user']['id']]);
if ($stmt->fetch()) {
    $pdo->prepare("DELETE FROM students_in_section WHERE id=?")->execute([$id]);
}
header("Location: section_manage.php?id=$section_id");
exit;
