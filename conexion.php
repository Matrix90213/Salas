<?php
// conexion.php

// Datos de conexión a la base de datos
$servidor = "localhost"; // Servidor de la base de datos (XAMPP usa "localhost")
$usuario = "root";       // Usuario de la base de datos (por defecto en XAMPP es "root")
$contrasena = "";        // Contraseña de la base de datos (por defecto en XAMPP está vacía)
$base_datos = "libreriasalas"; // Nombre de la base de datos

// Crear la conexión
$conn = new mysqli($servidor, $usuario, $contrasena, $base_datos);

// Verificar si hay errores en la conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
?>