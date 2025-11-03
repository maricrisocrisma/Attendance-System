<?php
// register.php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'] === 'teacher' ? 'teacher' : 'student';

    if (!$name || !$email || !$password) {
        $err = "All fields required.";
    } else {
        // check email
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $err = "Email already registered.";
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (name,email,password,role) VALUES (?,?,?,?)");
            $stmt->execute([$name,$email,$hash,$role]);
            header('Location: login.php?registered=1');
            exit;
        }
    }
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Register</title>
<style>
/* minimal styling */
body{font-family:Arial; padding:20px}
form{max-width:400px}
input,select{display:block;margin:8px 0;padding:8px;width:100%}
.error{color:red}
</style>
<link rel="stylesheet" href="style.css">

</head>
<body>
<h2>Register</h2>
<?php if(!empty($err)) echo "<p class='error'>$err</p>"; ?>
<form method="post">
  <label>Name</label>
  <input name="name" required>
  <label>Email</label>
  <input name="email" type="email" required>
  <label>Password</label>
  <input name="password" type="password" required>
  <label>Role</label>
  <select name="role"><option value="student">Student</option><option value="teacher">Teacher</option></select>
  <button type="submit">Register</button>
</form>
<p><a href="login.php">Login</a></p>
</body>
</html>
