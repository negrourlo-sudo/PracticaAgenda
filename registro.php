<?php
session_start();
require 'conexion.php';

$error = '';
$email = '';
$clave = '';
$nombre  = '';
$creado = false;
$movil = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $clave = $_POST['clave'] ?? '';
  $nombre = trim($_POST['nombre'] ?? '');
  $movil = trim($_POST['movil'] ?? '');
  $email = trim($email);

  if ($nombre === '' || $email === '' || trim($clave) === '' || $movil === '') {
        $error = 'Todos los campos son obligatorios.';
    } else {
    $pdo = conectar();
    $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE LOWER(email) = LOWER(?) LIMIT 1');
    $stmt->execute([$email]);

    if ($stmt->fetch()) {
      $error = 'Ya existe un usuario registrado con ese email.';
    } else {
      $hash = password_hash($clave, PASSWORD_DEFAULT);
      $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email, movil, clave, activo) VALUES (?, ?, ?, ?, true)");
      $stmt->execute([$nombre, $email, $movil, $hash]);
      $_SESSION['id_user'] = $pdo->lastInsertId();
      $_SESSION['usuarios'] = [
        'id' => $_SESSION['id_user'],
        'nombre' => $nombre
      ];
      header('Location: agenda.php');
      exit;
    }
    }

    

}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login con base de datos</title>
  <link rel="stylesheet" href="styles.css?v=4">
</head>
<body class="pagina-registro">

  <header>
    <h1>AGENDA PERSONAL REGISTRO</h1>
    <p>Registro Cliente</p>
  </header>

  <nav class="navegacion" aria-label="Navegación principal">
    <a class="boton" href="index.php">Login</a>
  </nav>

  <main>

    <?php if ($creado): ?>

      <p class="exito">Usuario Creado, <?= htmlspecialchars($email) ?>. Login correcto.</p>

    <?php else: ?>

      <?php if ($error): ?>
        <p class="errores"><?= htmlspecialchars($error) ?></p>
      <?php endif; ?>

      <form method="post" action="registro.php">
        <label for="nombre">Nombre</label>
        <input type="text" id="nombre" name="nombre">
        <label for="email">Email</label>
        <input type="text" id="email" name="email" value="<?= htmlspecialchars($email) ?>">
        <label for="movil">Movil</label>
        <input type="text" id="movil" name="movil">
        <label for="clave">Contraseña</label>
        <input type="password" id="clave" name="clave">


        <button type="submit">Crear usuario</button>
      </form>

    <?php endif; ?>

  </main>

</body>
</html>
