<?php
// index.php
include 'conexion.php'; // Incluye la conexión a la base de datos
session_start(); // Inicia la sesión para manejar el cierre de sesión
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Librería Salas</title>
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    /* Estilos personalizados */
    body {
      background-image: url('salas.jpg'); /* Cambia 'ruta/a/tu/imagen-de-fondo.jpg' por la ruta de tu imagen */
      background-size: cover;
      background-position: center;
      background-repeat: no-repeat;
      height: 100vh;
      color: #fff;
    }

    .navbar {
      background-color: rgba(0, 0, 0, 0.7) !important; /* Fondo semi-transparente para el menú */
    }

    .navbar-brand, .nav-link, .dropdown-item {
      color: #fff !important;
    }

    .navbar-brand {
      font-size: 1.5vw;
      font-weight: bold;
    }

    .navbar-nav .nav-link {
      font-size: 1.2vw;
      margin-right: 15px;
    }

    .dropdown-menu {
      background-color: rgba(0, 0, 0, 0.7); /* Fondo semi-transparente para el menú desplegable */
    }

    .dropdown-item:hover {
      background-color: rgba(255, 255, 255, 0.1); /* Efecto hover en los items del menú */
    }

    #content {
      background-color: rgba(0, 0, 0, 0.5); /* Fondo semi-transparente para el contenido */
      padding: 20px;
      border-radius: 10px;
      margin-top: 20px;
    }

    h2 {
      font-size: 2.5vw;
      font-weight: bold;
      margin-bottom: 20px;
    }

    p {
      font-size: 1.2vw;
    }
    
    .logout-btn {
      margin-left: auto;
    }
  </style>
</head>
<body>
  <!-- Menú de Navegación -->
  <nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container-fluid">
      <a class="navbar-brand" href="index.php">
        <i class="fas fa-book"></i> Librería Salas
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav">
          <!-- Gestión de Inventario -->
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownInventario" role="button" data-bs-toggle="dropdown">
              Gestión de Inventario
            </a>
            <ul class="dropdown-menu">
              <li><a class="dropdown-item" href="acciones.php?accion=ver_inventario">Ver Inventario</a></li>
              <li><a class="dropdown-item" href="acciones.php?accion=agregar_libro">Agregar Libro</a></li>
            </ul>
          </li>
          <!-- Gestión de Ventas -->
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownVentas" role="button" data-bs-toggle="dropdown">
              Gestión de Ventas
            </a>
            <ul class="dropdown-menu">
              <li><a class="dropdown-item" href="acciones.php?accion=ver_ventas">Ver Ventas</a></li>
              <li><a class="dropdown-item" href="acciones.php?accion=nueva_venta">Nueva Venta</a></li>
              <li><a class="dropdown-item" href="acciones.php?accion=gestion_clientes">Gestión de Clientes</a></li>
              
              <li><a class="dropdown-item" href="acciones.php?accion=reporte_caja">Reporte de Caja</a></li>
            </ul>
          </li>
          <!-- Gestión de Usuarios -->
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownUsuarios" role="button" data-bs-toggle="dropdown">
              Gestión de Empleados
            </a>
            <ul class="dropdown-menu">
              <li><a class="dropdown-item" href="acciones.php?accion=ver_empleados">Ver Empleados</a></li>
            </ul>
          </li>
          
          <!-- Informes y Estadísticas -->
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownInformes" role="button" data-bs-toggle="dropdown">
              Informes y Estadísticas
            </a>
            <ul class="dropdown-menu">
              
              <li><a class="dropdown-item" href="acciones.php?accion=libros_vendidos">Libros Más Vendidos</a></li>
              <li><a class="dropdown-item" href="acciones.php?accion=clientes_frecuentes">Clientes Frecuentes</a></li>
              <li><a class="dropdown-item" href="acciones.php?accion=gestion_editoriales">Gestión de Editoriales</a></li>
            </ul>
          </li>
          <!-- Seguridad y Administración -->
          
        <!-- Botón de Cerrar Sesión -->
        <div class="logout-btn">
          
        </div>
      </div>
    </div>
  </nav>

  <!-- Contenido Dinámico -->
  <div id="content" class="container mt-4">
    <h2 class="text-center">
      <i class="fas fa-book"></i> Bienvenido a Librería Salas
    </h2>
    <p class="text-center">¡Somos tu mejor opcción!</p>
    <p class="text-center">Selecciona una opción del menú para comenzar.</p>
  </div>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>