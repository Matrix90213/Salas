<?php
// Conexión a la base de datos
$host = "localhost";
$usuario = "root";
$contrasena = "";
$basedatos = "libreriasalas";

$conexion = new mysqli($host, $usuario, $contrasena, $basedatos);

if ($conexion->connect_error) {
    die("<div class='alert alert-danger'>Error de conexión: " . $conexion->connect_error . "</div>");
}

// Consulta para obtener el historial de cortes
$sql_cortes = "SELECT * FROM corte_caja ORDER BY Fecha_Corte DESC";
$resultado_cortes = $conexion->query($sql_cortes);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Cortes - Librería Salas</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        .card-historial {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .card-header-historial {
            background: linear-gradient(135deg, #2c3e50, #3498db);
            color: white;
            padding: 1.5rem;
            border-bottom: none;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="card card-historial">
            <div class="card-header card-header-historial text-center">
                <h2 class="mb-0"><i class="fas fa-history me-2"></i>HISTORIAL DE CORTES DE CAJA</h2>
                <p class="mb-0 opacity-75">Librería Salas</p>
            </div>
            
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>ID Corte</th>
                                <th>Fecha Corte</th>
                                <th>Fecha Reporte</th>
                                <th class="text-end">Total Ventas</th>
                                <th class="text-center">Cant. Ventas</th>
                                <th>Detalles</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($resultado_cortes->num_rows > 0) {
                                while ($corte = $resultado_cortes->fetch_assoc()) {
                                    echo '<tr>
                                            <td class="fw-bold">#' . $corte['ID_Corte'] . '</td>
                                            <td>' . date('d/m/Y H:i', strtotime($corte['Fecha_Corte'])) . '</td>
                                            <td>' . date('d/m/Y', strtotime($corte['Fecha_Reporte'])) . '</td>
                                            <td class="text-end fw-bold text-success">$' . number_format($corte['Total_Ventas'], 2) . '</td>
                                            <td class="text-center">' . $corte['Cantidad_Ventas'] . '</td>
                                            <td class="text-muted">' . htmlspecialchars($corte['Detalles']) . '</td>
                                          </tr>';
                                }
                            } else {
                                echo '<tr>
                                        <td colspan="6" class="text-center py-4">
                                            <i class="far fa-folder-open fa-2x mb-3 text-muted"></i>
                                            <h5 class="text-muted">No hay cortes de caja registrados</h5>
                                        </td>
                                      </tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="d-flex justify-content-between align-items-center mt-4">
                    <a href="tu_archivo_principal.php" class="btn btn-primary">
                        <i class="fas fa-arrow-left me-2"></i>Volver al Reporte
                    </a>
                    <button onclick="window.print()" class="btn btn-outline-secondary">
                        <i class="fas fa-print me-2"></i>Imprimir Historial
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$conexion->close();
?>