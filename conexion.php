<?php

function conectar() {
    // crea y devuelve un PDO conectado a mysql:host=localhost;dbname=agenda (usuario 'root', sin contraseña)
    $pdo = new PDO('mysql:host=localhost;dbname=agenda', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $pdo;
}
