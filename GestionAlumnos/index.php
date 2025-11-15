<?php
session_start();
require_once __DIR__ . '/db.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $stmt = $pdo->prepare("SELECT * FROM usuario WHERE Username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $stored = $user['Password'];

        $ok = false;
        if ($stored === $password) $ok = true;
        if (!$ok && md5($password) === $stored) $ok = true;
        if (!$ok && password_verify($password, $stored)) $ok = true;

        if ($ok) {
            $_SESSION['usuario'] = [
                'id' => $user['ID_Usuario'],
                'username' => $user['Username'],
                'rol' => $user['Rol'],
                'id_alumno' => $user['ID_Alumno'],
                'id_profesor' => $user['ID_Profesor']
            ];
            header("Location: dashboard.php");
            exit;
        }
    }
    $error = "Usuario o contraseña incorrectos.";
}
?>
<!-- LOGIN DE INICIO DE SESION -->
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Sistema de Gestión de Alumnos y Profesores</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="icon" type="image/png" href="icons/user-group-man-man.png">
</head>

<body>
    <div class="login-container">
        <div class="login-title-container">
            <h1 class="login-title">Acceso al Sistema</h1>
        </div>
        <?php if ($error): ?>
            <div class="alert" style="margin-bottom:18px;"> <?= htmlspecialchars($error) ?> </div>
        <?php endif; ?>


        <!-- Input del usuario -->
        <form method="post" class="login-form">
            <div class="field">
                <input type="text" name="username" placeholder="Usuario" required class="input">
            </div>

            <!-- Input de la contraseña -->
            <div class="field">
                <input type="password" name="password" placeholder="Contraseña" required class="input">
            </div>

            <!-- Boton para ingresar -->
            <div class="field">
                <button type="submit" class="btn">Ingresar</button>
            </div>

        </form>
        <div class="login-footer">
            Sistema de Gestión de Alumnos<br><span></span>
        </div>
    </div>
</body>

</html>