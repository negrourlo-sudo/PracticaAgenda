<?php
session_start();
require 'conexion.php';

$error = '';
$logueado = false;
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $pdo = conectar();
    $stmt = $pdo->prepare("SELECT id, nombre, email, clave FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['clave'])) {
      $_SESSION['id_user'] = $user['id'];
      $_SESSION['usuarios'] = [
        'id' => $user['id'],
        'nombre' => $user['nombre']
      ];
      header('Location: agenda.php');
      exit;
    } else {
        $error = 'Email o contraseña incorrectos.';
    }

}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login con base de datos</title>
  <link rel="stylesheet" href="styles.css?v=3">
</head>
<body class="pagina-login">

  <header>
    <h1>AGENDA PERSONAL ACCESO</h1>
    <p>Acceso Cliente</p>
  </header>

  <main>

    <?php if ($logueado): ?>

      <p class="exito">Bienvenido, <?= htmlspecialchars($email) ?>. Login correcto.</p>
      

    <?php else: ?>

      <?php if ($error): ?>
        <p class="errores"><?= htmlspecialchars($error) ?></p>
      <?php endif; ?>

      <div class="acciones-login">
        <form method="post" action="index.php">
          <label for="email">Email</label>
          <input type="text" id="email" name="email" value="<?= htmlspecialchars($email) ?>">

          <label for="password">Contraseña</label>
          <input type="password" id="password" name="password">

          <button type="submit">Entrar</button>
        </form>

        <a class="boton boton-registro" href="registro.php">Registrarse</a>
      </div>

    <?php endif; ?>

  </main>

</body>
</html>
