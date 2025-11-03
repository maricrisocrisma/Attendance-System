<?php
// login.php
require 'db.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = ['id'=>$user['id'],'name'=>$user['name'],'role'=>$user['role']];
        // redirect based on role
        if ($user['role'] === 'teacher') header('Location: teacher_dashboard.php');
        else header('Location: student_home.php');
        exit;
    } else {
        $err = "Invalid credentials.";
    }
}
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Login</title>
<link rel="stylesheet" href="style.css">

</head>
<body>
<h2>Log In</h2>
<?php if(!empty($_GET['registered'])) echo "<p style='color:green'>Registered! Please login.</p>"; ?>
<?php if(!empty($err)) echo "<p style='color:red'>$err</p>"; ?>
<form method="post">
  <label>Email</label><input name="email" type="email" required>
  <label>Password</label><input name="password" type="password" required>
  <button type="submit">Log In</button>
</form>
<p><a href="register.php">Register</a></p>
</body></html>
