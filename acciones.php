<?php
// acciones.php

// Incluir la conexión a la base de datos
include 'conexion.php';

// Obtener la acción desde la URL
$accion = isset($_GET['accion']) ? $_GET['accion'] : 'inicio';

switch ($accion) {
    case 'buscar_por_id':
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($id > 0) {
            $query = "SELECT * FROM libros WHERE id = $id";
            $resultado = mysqli_query($conexion, $query);

            if ($resultado && mysqli_num_rows($resultado) > 0) {
                $libro = mysqli_fetch_assoc($resultado);
                echo "<pre>" . print_r($libro, true) . "</pre>";
            } else {
                echo "No se encontró ningún libro con ID $id.";
            }
        } else {
            echo "ID inválido.";
        }
        break;

    case 'buscar_por_nombre':
        $nombre = isset($_GET['nombre']) ? mysqli_real_escape_string($conexion, $_GET['nombre']) : '';
        if (!empty($nombre)) {
            $query = "SELECT * FROM libros WHERE titulo LIKE '%$nombre%'";
            $resultado = mysqli_query($conexion, $query);

            if ($resultado && mysqli_num_rows($resultado) > 0) {
                while ($libro = mysqli_fetch_assoc($resultado)) {
                    echo "<pre>" . print_r($libro, true) . "</pre>";
                }
            } else {
                echo "No se encontraron libros con el nombre '$nombre'.";
            }
        } else {
            echo "Nombre inválido.";
        }
        break;

    case 'agregar_libro':
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Agregar Libro</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        </head>
        <body>
            <div class="container mt-4">
                <h2>Agregar Nuevo Libro</h2>
                <form action="acciones.php?accion=guardar_libro" method="POST">
                    <div class="mb-3">
                        <label for="isbn" class="form-label">ISBN:</label>
                        <input type="text" id="isbn" name="isbn" class="form-control" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label for="titulo" class="form-label">Título:</label>
                        <input type="text" id="titulo" name="titulo" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="categoria" class="form-label">Categoría:</label>
                        <input type="text" id="categoria" name="categoria" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="autor" class="form-label">Autor:</label>
                        <input type="text" id="autor" name="autor" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="nombre_editorial" class="form-label">Nombre de la Editorial:</label>
                        <input type="text" id="nombre_editorial" name="nombre_editorial" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="precio" class="form-label">Precio:</label>
                        <input type="number" id="precio" name="precio" class="form-control" required step="0.01">
                    </div>
                    <div class="mb-3">
                        <label for="stock" class="form-label">Cantidad en Stock:</label>
                        <input type="number" id="stock" name="stock" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Registrar Libro</button>
                    <a href="index.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Volver al Menú
                            </a>
                </form>
            </div>
    
            <script>
                document.getElementById('isbn').addEventListener('change', function() {
                    let isbn = this.value;
                    if (isbn.length >= 10) {
                        fetch(`https://openlibrary.org/api/books?bibkeys=ISBN:${isbn}&format=json&jscmd=data`)
                            .then(response => response.json())
                            .then(data => {
                                let bookData = data[`ISBN:${isbn}`];
                                if (bookData) {
                                    document.getElementById('titulo').value = bookData.title || "";
                                    document.getElementById('autor').value = bookData.authors ? bookData.authors[0].name : "";
                                    document.getElementById('nombre_editorial').value = bookData.publishers ? bookData.publishers[0].name : "";
                                } else {
                                    alert("No se encontró información para el ISBN proporcionado.");
                                }
                            })
                            .catch(error => {
                                console.log('Error al obtener datos del libro', error);
                                alert("Error al conectar con la API. Inténtalo de nuevo.");
                            });
                    }
                });
            </script>
    
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
            
        </body>
        </html>
        <?php
        break;

    case 'guardar_libro':
        $isbn = $_POST['isbn'];
        $titulo = $_POST['titulo'];
        $categoria = $_POST['categoria'];
        $autor = $_POST['autor'];
        $nombre_editorial = $_POST['nombre_editorial'];
        $precio = $_POST['precio'];
        $stock = $_POST['stock'];

        $sql_check_editorial = "SELECT ID_Editorial FROM editorial WHERE Nombre = ?";
        $stmt_check_editorial = $conn->prepare($sql_check_editorial);
        $stmt_check_editorial->bind_param("s", $nombre_editorial);
        $stmt_check_editorial->execute();
        $stmt_check_editorial->store_result();

        if ($stmt_check_editorial->num_rows > 0) {
            $stmt_check_editorial->bind_result($id_editorial);
            $stmt_check_editorial->fetch();
        } else {
            $sql_max_id = "SELECT MAX(CAST(ID_Editorial AS UNSIGNED)) AS max_id FROM editorial";
            $result_max_id = $conn->query($sql_max_id);
            $row = $result_max_id->fetch_assoc();
            $max_id = $row['max_id'];
            $new_id = str_pad($max_id + 1, 5, '0', STR_PAD_LEFT);

            $sql_insert_editorial = "INSERT INTO editorial (ID_Editorial, Nombre) VALUES (?, ?)";
            $stmt_insert_editorial = $conn->prepare($sql_insert_editorial);
            $stmt_insert_editorial->bind_param("ss", $new_id, $nombre_editorial);
            $stmt_insert_editorial->execute();
            $id_editorial = $new_id;
        }
        $stmt_check_editorial->close();

        $sql_check_isbn = "SELECT ISBN FROM libro WHERE ISBN = ?";
        $stmt_check_isbn = $conn->prepare($sql_check_isbn);
        $stmt_check_isbn->bind_param("s", $isbn);
        $stmt_check_isbn->execute();
        $stmt_check_isbn->store_result();

        if ($stmt_check_isbn->num_rows > 0) {
            echo "<script>alert('El ISBN ya existe'); window.history.back();</script>";
            $stmt_check_isbn->close();
            exit();
        }
        $stmt_check_isbn->close();

        $sql_insert_libro = "INSERT INTO libro (ISBN, Titulo, Categoria, Autor, ID_Editorial, Precio, Stock) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt_insert_libro = $conn->prepare($sql_insert_libro);
        $stmt_insert_libro->bind_param("sssssdi", $isbn, $titulo, $categoria, $autor, $id_editorial, $precio, $stock);

        if ($stmt_insert_libro->execute()) {
            echo "<script>alert('Libro agregado correctamente'); window.location.href='acciones.php?accion=ver_inventario';</script>";
        } else {
            echo "<script>alert('Error al agregar el libro'); window.history.back();</script>";
        }

        $stmt_insert_libro->close();
        $conn->close();
        break;

    case 'ver_inventario':
    $busqueda = isset($_GET['busqueda']) ? $_GET['busqueda'] : '';
    $mostrado = isset($_GET['mostrado']) ? $_GET['mostrado'] : false;

    $sql = "SELECT l.ISBN, l.Titulo, l.Categoria, l.Autor, e.Nombre AS Editorial, l.Precio, l.Stock 
            FROM libro l 
            INNER JOIN editorial e ON l.ID_Editorial = e.ID_Editorial";

    if (!empty($busqueda)) {
        $sql .= " WHERE l.ISBN LIKE ? OR l.Titulo LIKE ?";
        $param_busqueda = "%$busqueda%";
    }

    $stmt = $conn->prepare($sql);

    if (!empty($busqueda)) {
        $stmt->bind_param("ss", $param_busqueda, $param_busqueda);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    // Mostrar siempre la interfaz, solo cambiar el contenido según los resultados
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Ver Inventario</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body>
        <div class="container mt-4">
            <h2>Inventario de Libros</h2>

            <form action="acciones.php" method="GET" class="mb-4">
                <input type="hidden" name="accion" value="ver_inventario">
                <div class="input-group">
                    <input type="text" name="busqueda" class="form-control" placeholder="Buscar por ISBN o nombre..." value="<?php echo htmlspecialchars($busqueda); ?>">
                    <button type="submit" class="btn btn-primary">Buscar</button>
                </div>
            </form>

            <?php
            if ($result->num_rows > 0) {
                ?>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>ISBN</th>
                            <th>Título</th>
                            <th>Categoría</th>
                            <th>Autor</th>
                            <th>Editorial</th>
                            <th>Precio</th>
                            <th>Stock</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>
                                    <td>{$row['ISBN']}</td>
                                    <td>{$row['Titulo']}</td>
                                    <td>{$row['Categoria']}</td>
                                    <td>{$row['Autor']}</td>
                                    <td>{$row['Editorial']}</td>
                                    <td>{$row['Precio']}</td>
                                    <td>{$row['Stock']}</td>
                                    <td>
                                        <a href='acciones.php?accion=editar_libro&isbn={$row['ISBN']}' class='btn btn-warning btn-sm'>Editar</a>
                                        <a href='acciones.php?accion=eliminar_libro&isbn={$row['ISBN']}' class='btn btn-danger btn-sm' onclick='return confirm(\"¿Estás seguro de eliminar este libro?\")'>Eliminar</a>
                                    </td>
                                  </tr>";
                        }
                        ?>
                    </tbody>
                </table>
                <?php
            } else {
                if (empty($busqueda)) {
                    echo "<div class='alert alert-warning'>No se encontraron libros en el inventario.</div>";
                } else {
                    echo "<div class='alert alert-warning'>No se encontraron libros que coincidan con la búsqueda.</div>";
                }
            }
            ?>
        </div>
        <a href="index.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Volver al Menú
                            </a>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>
    <?php
    break;
    case 'editar_libro':
        $isbn = $_GET['isbn'];

        $sql = "SELECT l.ISBN, l.Titulo, l.Categoria, l.Autor, e.Nombre AS Editorial, l.Precio, l.Stock 
                FROM libro l 
                INNER JOIN editorial e ON l.ID_Editorial = e.ID_Editorial 
                WHERE l.ISBN = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $isbn);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if ($row) {
            ?>
            <!DOCTYPE html>
            <html lang="es">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Editar Libro</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
            </head>
            <body>
                <div class="container mt-4">
                    <h2>Editar Libro</h2>
                    <form action="acciones.php?accion=actualizar_libro" method="POST">
                        <input type="hidden" name="isbn" value="<?php echo $row['ISBN']; ?>">
                        <div class="mb-3">
                            <label for="titulo" class="form-label">Título:</label>
                            <input type="text" id="titulo" name="titulo" class="form-control" value="<?php echo $row['Titulo']; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="categoria" class="form-label">Categoría:</label>
                            <input type="text" id="categoria" name="categoria" class="form-control" value="<?php echo $row['Categoria']; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="autor" class="form-label">Autor:</label>
                            <input type="text" id="autor" name="autor" class="form-control" value="<?php echo $row['Autor']; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="nombre_editorial" class="form-label">Nombre de la Editorial:</label>
                            <input type="text" id="nombre_editorial" name="nombre_editorial" class="form-control" value="<?php echo $row['Editorial']; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="precio" class="form-label">Precio:</label>
                            <input type="number" id="precio" name="precio" class="form-control" value="<?php echo $row['Precio']; ?>" required step="0.01">
                        </div>
                        <div class="mb-3">
                            <label for="stock" class="form-label">Cantidad en Stock:</label>
                            <input type="number" id="stock" name="stock" class="form-control" value="<?php echo $row['Stock']; ?>" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Actualizar Libro</button>
                    </form>
                </div>
                <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
            </body>
            </html>
            <?php
        } else {
            echo "<script>alert('Libro no encontrado'); window.location.href='acciones.php?accion=ver_inventario';</script>";
        }
        break;

    case 'actualizar_libro':
        $isbn = $_POST['isbn'];
        $titulo = $_POST['titulo'];
        $categoria = $_POST['categoria'];
        $autor = $_POST['autor'];
        $nombre_editorial = $_POST['nombre_editorial'];
        $precio = $_POST['precio'];
        $stock = $_POST['stock'];

        $sql_check_editorial = "SELECT ID_Editorial FROM editorial WHERE Nombre = ?";
        $stmt_check_editorial = $conn->prepare($sql_check_editorial);
        $stmt_check_editorial->bind_param("s", $nombre_editorial);
        $stmt_check_editorial->execute();
        $stmt_check_editorial->store_result();

        if ($stmt_check_editorial->num_rows > 0) {
            $stmt_check_editorial->bind_result($id_editorial);
            $stmt_check_editorial->fetch();
        } else {
            $sql_max_id = "SELECT MAX(CAST(ID_Editorial AS UNSIGNED)) AS max_id FROM editorial";
            $result_max_id = $conn->query($sql_max_id);
            $row = $result_max_id->fetch_assoc();
            $max_id = $row['max_id'];
            $new_id = str_pad($max_id + 1, 5, '0', STR_PAD_LEFT);

            $sql_insert_editorial = "INSERT INTO editorial (ID_Editorial, Nombre) VALUES (?, ?)";
            $stmt_insert_editorial = $conn->prepare($sql_insert_editorial);
            $stmt_insert_editorial->bind_param("ss", $new_id, $nombre_editorial);
            $stmt_insert_editorial->execute();
            $id_editorial = $new_id;
        }
        $stmt_check_editorial->close();

        $sql_update_libro = "UPDATE libro SET Titulo = ?, Categoria = ?, Autor = ?, ID_Editorial = ?, Precio = ?, Stock = ? WHERE ISBN = ?";
        $stmt_update_libro = $conn->prepare($sql_update_libro);
        $stmt_update_libro->bind_param("ssssdis", $titulo, $categoria, $autor, $id_editorial, $precio, $stock, $isbn);

        if ($stmt_update_libro->execute()) {
            echo "<script>alert('Libro actualizado correctamente'); window.location.href='acciones.php?accion=ver_inventario';</script>";
        } else {
            echo "<script>alert('Error al actualizar el libro'); window.history.back();</script>";
        }

        $stmt_update_libro->close();
        $conn->close();
        break;

    case 'eliminar_libro':
    $isbn = $_GET['isbn'];

    // Primero, eliminar los detalles de venta que hacen referencia a este libro
    $sql_detalle = "DELETE FROM detalleventa WHERE ISBN = ?";
    $stmt_detalle = $conn->prepare($sql_detalle);
    $stmt_detalle->bind_param("s", $isbn);
    $stmt_detalle->execute();
    $stmt_detalle->close();

    // Luego, eliminar el libro
    $sql = "DELETE FROM libro WHERE ISBN = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $isbn);

    if ($stmt->execute()) {
        echo "<script>alert('Libro eliminado correctamente'); window.location.href='acciones.php?accion=ver_inventario';</script>";
    } else {
        echo "<script>alert('Error al eliminar el libro'); window.history.back();</script>";
    }

    $stmt->close();
    $conn->close();
    break;


    case 'procesar_venta':
    // Validar datos requeridos
    if(empty($_POST['nombre_cliente']) || empty($_POST['email_cliente']) || empty($_POST['id_empleado'])) {
        echo '<script>alert("Faltan datos requeridos."); window.history.back();</script>';
        exit();
    }

    $nombre_cliente = $_POST['nombre_cliente'];
    $email_cliente = $_POST['email_cliente'];
    $telefono_cliente = $_POST['telefono_cliente'] ?? '';
    $direccion_cliente = $_POST['direccion_cliente'] ?? '';
    $id_empleado = $_POST['id_empleado'];
    $libros_seleccionados = $_POST['libros_seleccionados'] ?? [];
    $cantidades = $_POST['cantidad'] ?? [];
    
    if (empty($libros_seleccionados)) {
        echo '<script>alert("Debe seleccionar al menos un libro."); window.history.back();</script>';
        exit();
    }
    
    $conn->begin_transaction();
    
    try {
        // 1. VERIFICAR QUE EL EMPLEADO EXISTA
        $sql_check_empleado = "SELECT ID_Empleado FROM empleado WHERE ID_Empleado = ?";
        $stmt_check_empleado = $conn->prepare($sql_check_empleado);
        $stmt_check_empleado->bind_param("s", $id_empleado);
        $stmt_check_empleado->execute();
        $stmt_check_empleado->store_result();
        
        if ($stmt_check_empleado->num_rows === 0) {
            throw new Exception("El empleado seleccionado no existe. ID: $id_empleado");
        }
        $stmt_check_empleado->close();

        // 2. GESTIONAR CLIENTE
        $sql_check_cliente = "SELECT ID_Cliente FROM cliente WHERE Email = ?";
        $stmt_check = $conn->prepare($sql_check_cliente);
        $stmt_check->bind_param("s", $email_cliente);
        $stmt_check->execute();
        $stmt_check->store_result();
        
        if ($stmt_check->num_rows > 0) {
            $stmt_check->bind_result($id_cliente);
            $stmt_check->fetch();
            
            $sql_update_cliente = "UPDATE cliente SET Nombre = ?, Telefono = ?, Direccion = ? WHERE ID_Cliente = ?";
            $stmt_update = $conn->prepare($sql_update_cliente);
            $stmt_update->bind_param("ssss", $nombre_cliente, $telefono_cliente, $direccion_cliente, $id_cliente);
            $stmt_update->execute();
            $stmt_update->close();
        } else {
            $sql_max_id = "SELECT MAX(CAST(SUBSTRING(ID_Cliente, 4) AS UNSIGNED)) as max_id FROM cliente WHERE ID_Cliente LIKE 'CLI%'";
            $result = $conn->query($sql_max_id);
            $row = $result->fetch_assoc();
            $next_id = $row['max_id'] ? $row['max_id'] + 1 : 1;
            $id_cliente = 'CLI' . str_pad($next_id, 5, '0', STR_PAD_LEFT);
            
            $sql_insert_cliente = "INSERT INTO cliente (ID_Cliente, Nombre, Email, Telefono, Direccion) 
                                  VALUES (?, ?, ?, ?, ?)";
            $stmt_insert = $conn->prepare($sql_insert_cliente);
            $stmt_insert->bind_param("sssss", $id_cliente, $nombre_cliente, $email_cliente, $telefono_cliente, $direccion_cliente);
            $stmt_insert->execute();
            $stmt_insert->close();
        }
        $stmt_check->close();

        // 3. CALCULAR MONTO TOTAL Y VERIFICAR STOCK
        $monto_total = 0;
        foreach ($libros_seleccionados as $isbn) {
            if (!isset($cantidades[$isbn]) || $cantidades[$isbn] <= 0) {
                throw new Exception("Cantidad inválida para el libro ISBN: $isbn");
            }
            
            $cantidad = intval($cantidades[$isbn]);
            
            // Verificar stock disponible
            $sql_stock = "SELECT Stock, Precio FROM libro WHERE ISBN = ?";
            $stmt_stock = $conn->prepare($sql_stock);
            $stmt_stock->bind_param("s", $isbn);
            $stmt_stock->execute();
            $stmt_stock->bind_result($stock, $precio);
            $stmt_stock->fetch();
            $stmt_stock->close();
            
            if ($stock < $cantidad) {
                throw new Exception("Stock insuficiente para el libro: $isbn. Stock disponible: $stock, solicitado: $cantidad");
            }
            
            $monto_total += $precio * $cantidad;
        }

        // 4. CREAR VENTA
        $sql_max_venta = "SELECT MAX(CAST(SUBSTRING(ID_Venta, 4) AS UNSIGNED)) as max_id FROM venta WHERE ID_Venta LIKE 'VTA%'";
        $result = $conn->query($sql_max_venta);
        $row = $result->fetch_assoc();
        $next_id_venta = $row['max_id'] ? $row['max_id'] + 1 : 1;
        $id_venta = 'VTA' . str_pad($next_id_venta, 5, '0', STR_PAD_LEFT);
        
        // INSERT sin campo Fecha (según tu estructura de tabla)
        $sql_insert_venta = "INSERT INTO venta (ID_Venta, Monto_Total, ID_Cliente, ID_Empleado) 
                            VALUES (?, ?, ?, ?)";
        $stmt_venta = $conn->prepare($sql_insert_venta);
        $stmt_venta->bind_param("sdss", $id_venta, $monto_total, $id_cliente, $id_empleado);
        $stmt_venta->execute();
        $stmt_venta->close();

        // 5. PROCESAR DETALLES DE VENTA Y ACTUALIZAR STOCK
        foreach ($libros_seleccionados as $isbn) {
            $cantidad = intval($cantidades[$isbn]);
            
            // Obtener precio para el detalle
            $sql_precio = "SELECT Precio FROM libro WHERE ISBN = ?";
            $stmt_precio = $conn->prepare($sql_precio);
            $stmt_precio->bind_param("s", $isbn);
            $stmt_precio->execute();
            $stmt_precio->bind_result($precio);
            $stmt_precio->fetch();
            $stmt_precio->close();
            
            $subtotal = $precio * $cantidad;
            
            // Insertar en detalleventa
            $sql_detalle = "INSERT INTO detalleventa (ID_Venta, ISBN, Cantidad, Fecha, Hora, Total) 
                           VALUES (?, ?, ?, CURDATE(), CURTIME(), ?)";
            $stmt_detalle = $conn->prepare($sql_detalle);
            $stmt_detalle->bind_param("ssid", $id_venta, $isbn, $cantidad, $subtotal);
            $stmt_detalle->execute();
            $stmt_detalle->close();
            
            // Actualizar stock
            $sql_update_stock = "UPDATE libro SET Stock = Stock - ? WHERE ISBN = ?";
            $stmt_stock = $conn->prepare($sql_update_stock);
            $stmt_stock->bind_param("is", $cantidad, $isbn);
            $stmt_stock->execute();
            
            if ($stmt_stock->affected_rows === 0) {
                throw new Exception("Error al actualizar stock para ISBN: $isbn");
            }
            $stmt_stock->close();
        }
        
        $conn->commit();
        
        echo "<script>
                alert('Venta registrada exitosamente. ID de venta: $id_venta');
                window.location.href = 'acciones.php?accion=detalle_venta&id=$id_venta';
              </script>";
              
    } catch (Exception $e) {
        $conn->rollback();
        
        echo "<script>
                alert('Error al registrar la venta: " . addslashes($e->getMessage()) . "');
                window.history.back();
              </script>";
    }
    break;

        case 'nueva_venta':
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Venta - Librería Salas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .book-card {
            cursor: pointer;
            transition: transform 0.2s;
        }
        .book-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .book-card.selected {
            border: 2px solid #0d6efd;
            background-color: #f8f9fa;
        }
        .cart-item {
            border-bottom: 1px solid #dee2e6;
            padding: 10px 0;
        }
        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-md-8">
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-cash-register me-2"></i>Nueva Venta</h2>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Volver al Menú
                    </a>
                </div>

                <!-- Información del Cliente -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-user me-2"></i>Información del Cliente</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nombre del Cliente *</label>
                                <input type="text" id="nombre_cliente" name="nombre_cliente" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email *</label>
                                <input type="email" id="email_cliente" name="email_cliente" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Teléfono</label>
                                <input type="text" id="telefono_cliente" name="telefono_cliente" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Dirección</label>
                                <input type="text" id="direccion_cliente" name="direccion_cliente" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Información del Empleado -->
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-user-tie me-2"></i>Información del Empleado</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label">Empleado que realiza la venta *</label>
                                <select id="id_empleado" name="id_empleado" class="form-select" required>
                                    <option value="">Seleccione un empleado</option>
                                    <?php
                                    // Consultar empleados disponibles
                                    $sql_empleados = "SELECT ID_Empleado, Nombre FROM empleado ORDER BY Nombre";
                                    $result_empleados = $conn->query($sql_empleados);
                                    if ($result_empleados->num_rows > 0) {
                                        while ($empleado = $result_empleados->fetch_assoc()) {
                                            echo "<option value='{$empleado['ID_Empleado']}'>{$empleado['Nombre']} ({$empleado['ID_Empleado']})</option>";
                                        }
                                    }
                                    ?>
                                </select>
                                <small class="form-text text-muted">Seleccione el empleado que está realizando la venta</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Búsqueda de Libros -->
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-search me-2"></i>Buscar Libros</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <input type="text" id="buscar_libro" class="form-control" placeholder="Buscar por título, autor o ISBN...">
                            </div>
                            <div class="col-md-4">
                                <button type="button" id="btn_buscar" class="btn btn-primary w-100">
                                    <i class="fas fa-search me-1"></i> Buscar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Lista de Libros Disponibles -->
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="fas fa-books me-2"></i>Libros Disponibles</h5>
                    </div>
                    <div class="card-body">
                        <div id="lista_libros" class="row">
                            <!-- Los libros se cargarán aquí mediante JavaScript -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Carrito de Compra -->
            <div class="col-md-4">
                <div class="card sticky-top" style="top: 20px;">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0"><i class="fas fa-shopping-cart me-2"></i>Carrito de Compra</h5>
                    </div>
                    <div class="card-body">
                        <div id="carrito_vacio" class="text-center py-4">
                            <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No hay productos en el carrito</p>
                        </div>
                        <div id="carrito_items" style="display: none;">
                            <!-- Los items del carrito se mostrarán aquí -->
                        </div>
                        <div id="resumen_compra" style="display: none;">
                            <hr>
                            <div class="d-flex justify-content-between fw-bold">
                                <span>Total:</span>
                                <span id="total_venta">$0.00</span>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <form id="form_venta" action="acciones.php?accion=procesar_venta" method="POST">
                            <input type="hidden" name="nombre_cliente" id="hidden_nombre_cliente">
                            <input type="hidden" name="email_cliente" id="hidden_email_cliente">
                            <input type="hidden" name="telefono_cliente" id="hidden_telefono_cliente">
                            <input type="hidden" name="direccion_cliente" id="hidden_direccion_cliente">
                            <input type="hidden" name="id_empleado" id="hidden_id_empleado">
                            
                            <!-- Los libros seleccionados se agregarán aquí mediante JavaScript -->
                            <div id="libros_seleccionados"></div>
                            
                            <button type="submit" id="btn_procesar_venta" class="btn btn-success w-100" disabled>
                                <i class="fas fa-credit-card me-1"></i> Procesar Venta
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let carrito = [];
        let librosDisponibles = [];

        // Cargar libros disponibles al iniciar
        document.addEventListener('DOMContentLoaded', function() {
            cargarLibrosDisponibles();
            
            // Evento de búsqueda
            document.getElementById('btn_buscar').addEventListener('click', buscarLibros);
            document.getElementById('buscar_libro').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') buscarLibros();
            });

            // Sincronizar campos del cliente y empleado
            sincronizarCampos();
        });

        function sincronizarCampos() {
            // Campos del cliente
            const camposCliente = ['nombre_cliente', 'email_cliente', 'telefono_cliente', 'direccion_cliente'];
            camposCliente.forEach(campo => {
                document.getElementById(campo).addEventListener('input', function() {
                    document.getElementById('hidden_' + campo).value = this.value;
                    validarFormulario();
                });
            });

            // Campo del empleado
            document.getElementById('id_empleado').addEventListener('change', function() {
                document.getElementById('hidden_id_empleado').value = this.value;
                validarFormulario();
            });
        }

        function cargarLibrosDisponibles() {
            fetch('acciones.php?accion=obtener_libros_disponibles')
                .then(response => response.json())
                .then(data => {
                    librosDisponibles = data;
                    mostrarLibros(data);
                })
                .catch(error => console.error('Error:', error));
        }

        function buscarLibros() {
            const termino = document.getElementById('buscar_libro').value.toLowerCase();
            const librosFiltrados = librosDisponibles.filter(libro => 
                libro.Titulo.toLowerCase().includes(termino) ||
                libro.Autor.toLowerCase().includes(termino) ||
                libro.ISBN.includes(termino)
            );
            mostrarLibros(librosFiltrados);
        }

        function mostrarLibros(libros) {
            const contenedor = document.getElementById('lista_libros');
            contenedor.innerHTML = '';

            if (libros.length === 0) {
                contenedor.innerHTML = '<div class="col-12 text-center py-4"><p class="text-muted">No se encontraron libros</p></div>';
                return;
            }

            libros.forEach(libro => {
                const enCarrito = carrito.find(item => item.ISBN === libro.ISBN);
                const cantidad = enCarrito ? enCarrito.cantidad : 0;
                
                const libroHTML = `
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="card book-card ${cantidad > 0 ? 'selected' : ''}" onclick="seleccionarLibro('${libro.ISBN}')">
                            <div class="card-body">
                                <h6 class="card-title">${libro.Titulo}</h6>
                                <p class="card-text small text-muted">${libro.Autor}</p>
                                <p class="card-text"><strong>ISBN:</strong> ${libro.ISBN}</p>
                                <p class="card-text"><strong>Precio:</strong> $${parseFloat(libro.Precio).toFixed(2)}</p>
                                <p class="card-text"><strong>Stock:</strong> ${libro.Stock}</p>
                                ${cantidad > 0 ? `
                                    <div class="quantity-controls">
                                        <button class="btn btn-sm btn-outline-danger" onclick="quitarDelCarrito('${libro.ISBN}', event)">-</button>
                                        <span class="mx-2">${cantidad}</span>
                                        <button class="btn btn-sm btn-outline-success" onclick="agregarAlCarrito('${libro.ISBN}', event)" ${cantidad >= libro.Stock ? 'disabled' : ''}>+</button>
                                    </div>
                                ` : `
                                    <button class="btn btn-primary btn-sm w-100" onclick="agregarAlCarrito('${libro.ISBN}', event)">
                                        Agregar al Carrito
                                    </button>
                                `}
                            </div>
                        </div>
                    </div>
                `;
                contenedor.innerHTML += libroHTML;
            });
        }

        function seleccionarLibro(isbn) {
            // Solo para mostrar visualmente qué libro está seleccionado
            document.querySelectorAll('.book-card').forEach(card => {
                card.classList.remove('selected');
            });
            event.currentTarget.classList.add('selected');
        }

        function agregarAlCarrito(isbn, event) {
            event.stopPropagation();
            const libro = librosDisponibles.find(l => l.ISBN === isbn);
            
            if (libro.Stock <= 0) {
                alert('No hay stock disponible de este libro');
                return;
            }

            const itemExistente = carrito.find(item => item.ISBN === isbn);
            
            if (itemExistente) {
                if (itemExistente.cantidad >= libro.Stock) {
                    alert('No hay suficiente stock disponible');
                    return;
                }
                itemExistente.cantidad++;
            } else {
                carrito.push({
                    ISBN: libro.ISBN,
                    Titulo: libro.Titulo,
                    Precio: libro.Precio,
                    cantidad: 1
                });
            }

            actualizarCarrito();
            mostrarLibros(librosDisponibles);
        }

        function quitarDelCarrito(isbn, event) {
            event.stopPropagation();
            const itemIndex = carrito.findIndex(item => item.ISBN === isbn);
            
            if (itemIndex !== -1) {
                carrito[itemIndex].cantidad--;
                
                if (carrito[itemIndex].cantidad <= 0) {
                    carrito.splice(itemIndex, 1);
                }
            }

            actualizarCarrito();
            mostrarLibros(librosDisponibles);
        }

        function actualizarCarrito() {
            const carritoItems = document.getElementById('carrito_items');
            const carritoVacio = document.getElementById('carrito_vacio');
            const resumenCompra = document.getElementById('resumen_compra');
            const librosSeleccionados = document.getElementById('libros_seleccionados');
            const btnProcesar = document.getElementById('btn_procesar_venta');

            if (carrito.length === 0) {
                carritoVacio.style.display = 'block';
                carritoItems.style.display = 'none';
                resumenCompra.style.display = 'none';
                btnProcesar.disabled = true;
            } else {
                carritoVacio.style.display = 'none';
                carritoItems.style.display = 'block';
                resumenCompra.style.display = 'block';
                btnProcesar.disabled = false;

                // Actualizar items del carrito
                carritoItems.innerHTML = '';
                let total = 0;
                librosSeleccionados.innerHTML = '';

                carrito.forEach((item, index) => {
                    const subtotal = item.Precio * item.cantidad;
                    total += subtotal;

                    carritoItems.innerHTML += `
                        <div class="cart-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">${item.Titulo}</h6>
                                    <small class="text-muted">ISBN: ${item.ISBN}</small>
                                    <div class="quantity-controls mt-2">
                                        <button class="btn btn-sm btn-outline-danger" onclick="quitarDelCarrito('${item.ISBN}')">-</button>
                                        <span class="mx-2">${item.cantidad}</span>
                                        <button class="btn btn-sm btn-outline-success" onclick="agregarAlCarrito('${item.ISBN}')">+</button>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div>$${parseFloat(subtotal).toFixed(2)}</div>
                                    <small class="text-muted">$${parseFloat(item.Precio).toFixed(2)} c/u</small>
                                </div>
                            </div>
                        </div>
                    `;

                    // Agregar campos hidden al formulario
                    librosSeleccionados.innerHTML += `
                        <input type="hidden" name="libros_seleccionados[]" value="${item.ISBN}">
                        <input type="hidden" name="cantidad[${item.ISBN}]" value="${item.cantidad}">
                    `;
                });

                document.getElementById('total_venta').textContent = `$${parseFloat(total).toFixed(2)}`;
            }

            validarFormulario();
        }

        function validarFormulario() {
            const nombre = document.getElementById('nombre_cliente').value.trim();
            const email = document.getElementById('email_cliente').value.trim();
            const empleado = document.getElementById('id_empleado').value;
            const btnProcesar = document.getElementById('btn_procesar_venta');
            
            btnProcesar.disabled = !(nombre && email && empleado && carrito.length > 0);
        }
    </script>
</body>
</html>
<?php
break;

case 'obtener_libros_disponibles':
    // Este case es para la petición AJAX que carga los libros
    header('Content-Type: application/json');
    
    $sql = "SELECT l.ISBN, l.Titulo, l.Autor, l.Precio, l.Stock, e.Nombre as Editorial 
            FROM libro l 
            INNER JOIN editorial e ON l.ID_Editorial = e.ID_Editorial 
            WHERE l.Stock > 0 
            ORDER BY l.Titulo";
    
    $result = $conn->query($sql);
    $libros = [];
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $libros[] = $row;
        }
    }
    
    echo json_encode($libros);
    exit();
    break;

    case 'ver_ventas':
        $sql = "SELECT v.ID_Venta, v.Fecha, v.Monto_Total, 
                       c.Nombre AS NombreCliente, c.Email AS EmailCliente,
                       e.Nombre AS NombreEmpleado
                FROM venta v
                INNER JOIN cliente c ON v.ID_Cliente = c.ID_Cliente
                INNER JOIN empleado e ON v.ID_Empleado = e.ID_Empleado
                ORDER BY v.Fecha DESC";
        
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            ?>
            <!DOCTYPE html>
            <html lang="es">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Ver Ventas</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
                <style>
                    .table-responsive {
                        margin-top: 20px;
                    }
                    .btn-action {
                        margin-right: 5px;
                    }
                </style>
            </head>
            <body>
                <div class="container mt-4">
                    <h2 class="text-center mb-4">Registro de Ventas</h2>
                    
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID Venta</th>
                                    <th>Fecha</th>
                                    <th>Monto Total</th>
                                    <th>Cliente</th>
                                    <th>Email Cliente</th>
                                    <th>Empleado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['ID_Venta']; ?></td>
                                    <td><?php echo $row['Fecha']; ?></td>
                                    <td>$<?php echo number_format($row['Monto_Total'], 2); ?></td>
                                    <td><?php echo $row['NombreCliente']; ?></td>
                                    <td><?php echo $row['EmailCliente']; ?></td>
                                    <td><?php echo $row['NombreEmpleado']; ?></td>
                                    <td>
                                        <a href="acciones.php?accion=detalle_venta&id=<?php echo $row['ID_Venta']; ?>" 
                                           class="btn btn-info btn-sm btn-action">
                                            Detalle
                                        </a>
                                        
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="text-center mt-3">
                        <a href="index.php" class="btn btn-primary">Volver al Menú</a>
                    </div>
                </div>
                
                <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
                <a href="index.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Volver al Menú
                            </a>
            </body>
            </html>
            <?php
        } else {
            echo '<div class="alert alert-warning">No hay ventas registradas.</div>';
        }
        break;

    case 'detalle_venta':
    $id_venta = $_GET['id'];
    
    $sql_venta = "SELECT v.ID_Venta, v.Monto_Total, 
                          c.Nombre AS NombreCliente, c.Email AS EmailCliente, c.Telefono AS TelefonoCliente,
                          e.Nombre AS NombreEmpleado
                   FROM venta v
                   INNER JOIN cliente c ON v.ID_Cliente = c.ID_Cliente
                   INNER JOIN empleado e ON v.ID_Empleado = e.ID_Empleado
                   WHERE v.ID_Venta = ?";
    $stmt_venta = $conn->prepare($sql_venta);
    $stmt_venta->bind_param("s", $id_venta);
    $stmt_venta->execute();
    $result_venta = $stmt_venta->get_result();
    $venta = $result_venta->fetch_assoc();
    
    if (!$venta) {
        echo '<script>alert("Venta no encontrada."); window.location.href="index.php";</script>';
        exit();
    }
    
    $sql_detalle = "SELECT d.ISBN, l.Titulo, l.Precio, d.Cantidad, (l.Precio * d.Cantidad) AS Subtotal
                    FROM detalleventa d
                    INNER JOIN libro l ON d.ISBN = l.ISBN
                    WHERE d.ID_Venta = ?";
    $stmt_detalle = $conn->prepare($sql_detalle);
    $stmt_detalle->bind_param("s", $id_venta);
    $stmt_detalle->execute();
    $result_detalle = $stmt_detalle->get_result();
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Detalle de Venta</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body {
                font-size: 14px;
            }
            .invoice-container {
                max-width: 800px;
                margin: 0 auto;
                padding: 15px;
            }
            .invoice-header {
                text-align: center;
                margin-bottom: 15px;
                padding: 10px;
                border-bottom: 2px solid #ddd;
            }
            .customer-info {
                padding: 10px;
                margin-bottom: 15px;
                border: 1px solid #eee;
                border-radius: 4px;
            }
            .table-details {
                margin-bottom: 15px;
            }
            .table-details th, .table-details td {
                padding: 5px 8px;
            }
            .total-section {
                padding: 10px;
                margin-top: 15px;
                border: 1px solid #eee;
                border-radius: 4px;
                background-color: #f9f9f9;
            }
            @media print {
                body {
                    font-size: 12px;
                    padding: 0;
                    margin: 0;
                }
                .invoice-container {
                    width: 100%;
                    max-width: 100%;
                    padding: 10px;
                }
                .no-print {
                    display: none !important;
                }
                .table-details th, .table-details td {
                    padding: 3px 5px;
                }
                .invoice-header, .customer-info, .total-section {
                    margin-bottom: 10px;
                    padding: 8px;
                }
            }
        </style>
    </head>
    <body>
        <div class="invoice-container">
            <div class="invoice-header">
                <h3 style="margin-bottom: 5px;">Librería Salas</h3>
                <h2>"Calle Zapata #12 Col. Centro, Iguala, Gro.</h2>
                <h4 style="margin: 5px 0; font-size: 1.2rem;">Factura #<?php echo $venta['ID_Venta']; ?></h4>
                <p style="margin: 0;"><strong>Fecha:</strong> <?php echo $venta['Fecha']; ?></p>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="customer-info">
                        <h5 style="font-size: 1rem; margin-bottom: 8px;">Información del Cliente</h5>
                        <p style="margin: 3px 0;"><strong>Nombre:</strong> <?php echo $venta['NombreCliente']; ?></p>
                        <p style="margin: 3px 0;"><strong>Email:</strong> <?php echo $venta['EmailCliente']; ?></p>
                        <p style="margin: 3px 0;"><strong>Teléfono:</strong> <?php echo $venta['TelefonoCliente'] ?: 'No proporcionado'; ?></p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="customer-info">
                        <h5 style="font-size: 1rem; margin-bottom: 8px;">Información de la Venta</h5>
                        <p style="margin: 3px 0;"><strong>Atendido por:</strong> <?php echo $venta['NombreEmpleado']; ?></p>
                        <p style="margin: 3px 0;"><strong>ID Venta:</strong> <?php echo $venta['ID_Venta']; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="table-responsive table-details">
                <table class="table table-bordered" style="margin-bottom: 10px;">
                    <thead class="table-dark" style="font-size: 0.9rem;">
                        <tr>
                            <th>ISBN</th>
                            <th>Título</th>
                            <th>P. Unitario</th>
                            <th>Cant.</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($libro = $result_detalle->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $libro['ISBN']; ?></td>
                            <td><?php echo $libro['Titulo']; ?></td>
                            <td>$<?php echo number_format($libro['Precio'], 2); ?></td>
                            <td><?php echo $libro['Cantidad']; ?></td>
                            <td>$<?php echo number_format($libro['Subtotal'], 2); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="row justify-content-end">
                <div class="col-md-4 col-sm-6">
                    <div class="total-section">
                        <h5 style="font-size: 1rem; margin-bottom: 8px;">Resumen de Pago</h5>
                        <p style="margin: 3px 0;"><strong>Subtotal:</strong> $<?php echo number_format($venta['Monto_Total'], 2); ?></p>
                        <p style="margin: 3px 0;"><strong>Total:</strong> $<?php echo number_format($venta['Monto_Total'], 2); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-3 no-print">
                <button onclick="window.print()" class="btn btn-primary btn-sm no-print">Imprimir Factura</button>
                <a href="acciones.php?accion=ver_ventas" class="btn btn-secondary btn-sm ms-2 no-print">Volver a Ventas</a>
            </div>
        </div>
        
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>
    <?php
    break;

        case 'gestion_clientes':
            // Procesar parámetro de búsqueda
            $busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
            
            // Consulta base
            $sql = "SELECT ID_Cliente, Nombre, Email, Telefono, Direccion 
                    FROM cliente";
            
            // Si hay búsqueda, agregar condiciones WHERE
            if(!empty($busqueda)) {
                // Verificar si la búsqueda es numérica (para ID)
                if(is_numeric($busqueda)) {
                    $sql .= " WHERE ID_Cliente = ?";
                    $param_busqueda = $busqueda;
                    $tipo_param = "s"; // Tipo string aunque sea numérico
                } else {
                    $sql .= " WHERE Nombre LIKE ? 
                             OR Email LIKE ? 
                             OR Telefono LIKE ? 
                             OR Direccion LIKE ?";
                    $param_busqueda = "%$busqueda%";
                    $tipo_param = "ssss";
                }
            }
            
            $sql .= " ORDER BY Nombre";
            
            // Preparar la consulta
            $stmt = $conn->prepare($sql);
            
            if(!empty($busqueda)) {
                if(is_numeric($busqueda)) {
                    $stmt->bind_param($tipo_param, $param_busqueda);
                } else {
                    $stmt->bind_param($tipo_param, $param_busqueda, $param_busqueda, $param_busqueda, $param_busqueda);
                }
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            ?>
            <!DOCTYPE html>
            <html lang="es">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Gestión de Clientes</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
                <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
                <style>
                    .table-responsive {
                        margin-top: 20px;
                    }
                    .search-box {
                        margin-bottom: 20px;
                    }
                    .btn-action {
                        margin-right: 5px;
                    }
                </style>
            </head>
            <body>
                <div class="container mt-4">
                    <h2 class="text-center mb-4">Gestión de Clientes</h2>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <a href="acciones.php?accion=nuevo_cliente" class="btn btn-success mb-3">
                                <i class="fas fa-plus-circle me-1"></i> Nuevo Cliente
                            </a>
                        </div>
                        <div class="col-md-6">
                            <div class="search-box">
                                <form method="GET" action="acciones.php">
                                    <input type="hidden" name="accion" value="gestion_clientes">
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="busqueda" 
                                               placeholder="Buscar por ID, nombre, email..." 
                                               value="<?php echo htmlspecialchars($busqueda); ?>">
                                        <button class="btn btn-primary" type="submit">
                                            <i class="fas fa-search me-1"></i> Buscar
                                        </button>
                                        <?php if(!empty($busqueda)): ?>
                                            <a href="acciones.php?accion=gestion_clientes" class="btn btn-danger">
                                                <i class="fas fa-times"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Email</th>
                                    <th>Teléfono</th>
                                    <th>Dirección</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($result->num_rows > 0): ?>
                                    <?php while($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['ID_Cliente']); ?></td>
                                        <td><?php echo htmlspecialchars($row['Nombre']); ?></td>
                                        <td><?php echo htmlspecialchars($row['Email']); ?></td>
                                        <td><?php echo htmlspecialchars($row['Telefono'] ?: 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($row['Direccion'] ?: 'N/A'); ?></td>
                                        <td>
                                            <a href="acciones.php?accion=editar_cliente&id=<?php echo $row['ID_Cliente']; ?>" 
                                               class="btn btn-warning btn-sm btn-action">
                                                <i class="fas fa-edit"></i> Editar
                                            </a>
                                            <a href="acciones.php?accion=eliminar_cliente&id=<?php echo $row['ID_Cliente']; ?>" 
                                               class="btn btn-danger btn-sm btn-action" 
                                               onclick="return confirm('¿Está seguro de eliminar este cliente?');">
                                                <i class="fas fa-trash-alt"></i> Eliminar
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4">
                                            <?php if(empty($busqueda)): ?>
                                                No hay clientes registrados
                                            <?php else: ?>
                                                No se encontraron resultados para "<?php echo htmlspecialchars($busqueda); ?>"
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="text-center mt-3">
                        <a href="index.php" class="btn btn-primary">
                            <i class="fas fa-home me-1"></i> Volver al Menú
                        </a>
                    </div>
                </div>
                
                <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
            </body>
            </html>
            <?php
            break;


    case 'nuevo_cliente':
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Nuevo Cliente</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        </head>
        <body>
            <div class="container mt-4">
                <h2 class="text-center mb-4">Registrar Nuevo Cliente</h2>
                
                <form action="acciones.php?accion=guardar_cliente" method="POST">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="nombre" class="form-label">Nombre completo</label>
                                <input type="text" class="form-control" id="nombre" name="nombre" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="telefono" class="form-label">Teléfono</label>
                                <input type="text" class="form-control" id="telefono" name="telefono">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="direccion" class="form-label">Dirección</label>
                                <input type="text" class="form-control" id="direccion" name="direccion">
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center mt-3">
                        <button type="submit" class="btn btn-primary">Guardar Cliente</button>
                        <a href="acciones.php?accion=gestion_clientes" class="btn btn-secondary ms-2">Cancelar</a>
                    </div>
                </form>
            </div>
            
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        </body>
        </html>
        <?php
        break;

    case 'guardar_cliente':
        $nombre = $_POST['nombre'];
        $email = $_POST['email'];
        $telefono = $_POST['telefono'] ?? '';
        $direccion = $_POST['direccion'] ?? '';
        
        $sql_check = "SELECT ID_Cliente FROM cliente WHERE Email = ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("s", $email);
        $stmt_check->execute();
        $stmt_check->store_result();
        
        if ($stmt_check->num_rows > 0) {
            echo '<script>alert("El email ya está registrado."); window.history.back();</script>';
            exit();
        }
        
        $sql_max_id = "SELECT MAX(CAST(SUBSTRING(ID_Cliente, 4) AS UNSIGNED)) FROM cliente WHERE ID_Cliente LIKE 'CLI%'";
        $result = $conn->query($sql_max_id);
        $row = $result->fetch_row();
        $next_id = $row[0] ? $row[0] + 1 : 1;
        $id_cliente = 'CLI' . str_pad($next_id, 5, '0', STR_PAD_LEFT);
        
        $sql_insert = "INSERT INTO cliente (ID_Cliente, Nombre, Email, Telefono, Direccion) 
                       VALUES (?, ?, ?, ?, ?)";
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bind_param("sssss", $id_cliente, $nombre, $email, $telefono, $direccion);
        
        if ($stmt_insert->execute()) {
            echo '<script>alert("Cliente registrado exitosamente."); window.location.href="acciones.php?accion=gestion_clientes";</script>';
        } else {
            echo '<script>alert("Error al registrar el cliente."); window.history.back();</script>';
        }
        break;

    case 'editar_cliente':
        $id_cliente = $_GET['id'];
        
        $sql = "SELECT ID_Cliente, Nombre, Email, Telefono, Direccion 
                FROM cliente 
                WHERE ID_Cliente = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $id_cliente);
        $stmt->execute();
        $result = $stmt->get_result();
        $cliente = $result->fetch_assoc();
        
        if (!$cliente) {
            echo '<script>alert("Cliente no encontrado."); window.location.href="acciones.php?accion=gestion_clientes";</script>';
            exit();
        }
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Editar Cliente</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        </head>
        <body>
            <div class="container mt-4">
                <h2 class="text-center mb-4">Editar Cliente</h2>
                
                <form action="acciones.php?accion=actualizar_cliente" method="POST">
                    <input type="hidden" name="id_cliente" value="<?php echo $cliente['ID_Cliente']; ?>">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="nombre" class="form-label">Nombre completo</label>
                                <input type="text" class="form-control" id="nombre" name="nombre" 
                                       value="<?php echo $cliente['Nombre']; ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo $cliente['Email']; ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="telefono" class="form-label">Teléfono</label>
                                <input type="text" class="form-control" id="telefono" name="telefono" 
                                       value="<?php echo $cliente['Telefono']; ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="direccion" class="form-label">Dirección</label>
                                <input type="text" class="form-control" id="direccion" name="direccion" 
                                       value="<?php echo $cliente['Direccion']; ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center mt-3">
                        <button type="submit" class="btn btn-primary">Actualizar Cliente</button>
                        <a href="acciones.php?accion=gestion_clientes" class="btn btn-secondary ms-2">Cancelar</a>
                    </div>
                </form>
            </div>
            
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        </body>
        </html>
        <?php
        break;

    case 'actualizar_cliente':
        $id_cliente = $_POST['id_cliente'];
        $nombre = $_POST['nombre'];
        $email = $_POST['email'];
        $telefono = $_POST['telefono'] ?? '';
        $direccion = $_POST['direccion'] ?? '';
        
        $sql_check = "SELECT ID_Cliente FROM cliente WHERE Email = ? AND ID_Cliente != ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("ss", $email, $id_cliente);
        $stmt_check->execute();
        $stmt_check->store_result();
        
        if ($stmt_check->num_rows > 0) {
            echo '<script>alert("El email ya está registrado por otro cliente."); window.history.back();</script>';
            exit();
        }
        
        $sql_update = "UPDATE cliente 
                       SET Nombre = ?, Email = ?, Telefono = ?, Direccion = ? 
                       WHERE ID_Cliente = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("sssss", $nombre, $email, $telefono, $direccion, $id_cliente);
        
        if ($stmt_update->execute()) {
            echo '<script>alert("Cliente actualizado exitosamente."); window.location.href="acciones.php?accion=gestion_clientes";</script>';
        } else {
            echo '<script>alert("Error al actualizar el cliente."); window.history.back();</script>';
        }
        break;

    case 'eliminar_cliente':
        $id_cliente = $_GET['id'];
        
        $sql_check_ventas = "SELECT COUNT(*) FROM venta WHERE ID_Cliente = ?";
        $stmt_check = $conn->prepare($sql_check_ventas);
        $stmt_check->bind_param("s", $id_cliente);
        $stmt_check->execute();
        $stmt_check->bind_result($num_ventas);
        $stmt_check->fetch();
        $stmt_check->close();
        
        if ($num_ventas > 0) {
            echo '<script>alert("No se puede eliminar el cliente porque tiene ventas asociadas."); window.location.href="acciones.php?accion=gestion_clientes";</script>';
            exit();
        }
        
        $sql_delete = "DELETE FROM cliente WHERE ID_Cliente = ?";
        $stmt_delete = $conn->prepare($sql_delete);
        $stmt_delete->bind_param("s", $id_cliente);
        
        if ($stmt_delete->execute()) {
            echo '<script>alert("Cliente eliminado exitosamente."); window.location.href="acciones.php?accion=gestion_clientes";</script>';
        } else {
            echo '<script>alert("Error al eliminar el cliente."); window.history.back();</script>';
        }
        break;
        

        

    case 'gestion_empleados':
        $sql = "SELECT * FROM empleado";
        $result = $conn->query($sql);
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Gestión de Empleados</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
        </head>
        <body>
            <div class="container mt-4">
                <h2>Gestión de Empleados</h2>
                <table class="table table-bordered table-striped text-center align-middle">
                    <thead class="table-primary">
                        <tr>
                            <th>ID Empleado</th>
                            <th>Nombre</th>
                            <th>Puesto</th>
                            <th>Dirección</th>
                            <th>Teléfono</th>
                            <th>Email</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>
                                        <td>{$row['ID_Empleado']}</td>
                                        <td>{$row['Nombre']}</td>
                                        <td>{$row['Puesto']}</td>
                                        <td>{$row['Direccion']}</td>
                                        <td>{$row['Telefono']}</td>
                                        <td>{$row['Email']}</td>
                                        <td>
                                            <a href='acciones.php?accion=editar_empleado&id_empleado={$row['ID_Empleado']}' class='btn btn-warning btn-sm'>
                                                <i class='bi bi-pencil-square'></i> Editar
                                            </a>
                                            <a href='acciones.php?accion=eliminar_empleado&id_empleado={$row['ID_Empleado']}' 
                                               class='btn btn-danger btn-sm' 
                                               onclick='return confirm(\"¿Estás seguro de eliminar este empleado?\")'>
                                               <i class='bi bi-trash'></i> Eliminar
                                            </a>
                                        </td>
                                      </tr>";
                        }
                        ?>
                    </tbody>
                </table>
                
                <div class="d-flex gap-2">
                    <a href="acciones.php?accion=agregar_empleado" class="btn btn-primary">
                        <i class="bi bi-person-plus"></i> Agregar Empleado
                    </a>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="bi bi-house"></i> Volver al Inicio
                    </a>
                </div>
                
            </div>
            
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        </body>
        </html>
        <?php
        break;


    case 'gestion_clientes':
        $sql = "SELECT ID_Cliente, Nombre, Email, Telefono, Direccion 
                FROM cliente
                ORDER BY Nombre";
        $result = $conn->query($sql);
    ?>
        <!DOCTYPE html>
        <html lang="es">

        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Gestión de Clientes</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
            <style>
                .table-responsive {
                    margin-top: 20px;
                }

                .search-box {
                    margin-bottom: 20px;
                }

                .btn-action {
                    margin-right: 5px;
                }
            </style>
        </head>

        <body>
            <div class="container mt-4">
                <h2 class="text-center mb-4">Gestión de Clientes</h2>

                <div class="row">
                    <div class="col-md-6">
                        <a href="acciones.php?accion=nuevo_cliente" class="btn btn-success mb-3">
                            <i class="bi bi-plus-circle"></i> Nuevo Cliente
                        </a>
                    </div>
                    <div class="col-md-6">
                        <div class="search-box">
                            <form method="GET" action="acciones.php">
                                <input type="hidden" name="accion" value="gestion_clientes">
                                <div class="input-group">
                                    <input type="text" class="form-control" name="busqueda" placeholder="Buscar cliente...">
                                    <button class="btn btn-primary" type="submit">Buscar</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Email</th>
                                <th>Teléfono</th>
                                <th>Dirección</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['ID_Cliente']; ?></td>
                                    <td><?php echo $row['Nombre']; ?></td>
                                    <td><?php echo $row['Email']; ?></td>
                                    <td><?php echo $row['Telefono'] ?: 'N/A'; ?></td>
                                    <td><?php echo $row['Direccion'] ?: 'N/A'; ?></td>
                                    <td>
                                        <a href="acciones.php?accion=editar_cliente&id=<?php echo $row['ID_Cliente']; ?>"
                                            class="btn btn-warning btn-sm btn-action">
                                            Editar
                                        </a>
                                        <a href="acciones.php?accion=eliminar_cliente&id=<?php echo $row['ID_Cliente']; ?>"
                                            class="btn btn-danger btn-sm btn-action"
                                            onclick="return confirm('¿Está seguro de eliminar este cliente?');">
                                            Eliminar
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <div class="text-center mt-3">
                    <a href="index.php" class="btn btn-primary">Volver al Menú</a>
                </div>
            </div>

            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        </body>

        </html>
    <?php
        break;

    case 'nuevo_cliente':
    ?>
        <!DOCTYPE html>
        <html lang="es">

        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Nuevo Cliente</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        </head>

        <body>
            <div class="container mt-4">
                <h2 class="text-center mb-4">Registrar Nuevo Cliente</h2>

                <form action="acciones.php?accion=guardar_cliente" method="POST">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="nombre" class="form-label">Nombre completo</label>
                                <input type="text" class="form-control" id="nombre" name="nombre" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="telefono" class="form-label">Teléfono</label>
                                <input type="text" class="form-control" id="telefono" name="telefono">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="direccion" class="form-label">Dirección</label>
                                <input type="text" class="form-control" id="direccion" name="direccion">
                            </div>
                        </div>
                    </div>

                    <div class="text-center mt-3">
                        <button type="submit" class="btn btn-primary">Guardar Cliente</button>
                        <a href="acciones.php?accion=gestion_clientes" class="btn btn-secondary ms-2">Cancelar</a>
                    </div>
                </form>
            </div>

            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        </body>

        </html>
    <?php
        break;

    case 'guardar_cliente':
        $nombre = $_POST['nombre'];
        $email = $_POST['email'];
        $telefono = $_POST['telefono'] ?? '';
        $direccion = $_POST['direccion'] ?? '';

        $sql_check = "SELECT ID_Cliente FROM cliente WHERE Email = ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("s", $email);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            echo '<script>alert("El email ya está registrado."); window.history.back();</script>';
            exit();
        }

        $sql_max_id = "SELECT MAX(CAST(SUBSTRING(ID_Cliente, 4) AS UNSIGNED)) FROM cliente WHERE ID_Cliente LIKE 'CLI%'";
        $result = $conn->query($sql_max_id);
        $row = $result->fetch_row();
        $next_id = $row[0] ? $row[0] + 1 : 1;
        $id_cliente = 'CLI' . str_pad($next_id, 5, '0', STR_PAD_LEFT);

        $sql_insert = "INSERT INTO cliente (ID_Cliente, Nombre, Email, Telefono, Direccion) 
                       VALUES (?, ?, ?, ?, ?)";
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bind_param("sssss", $id_cliente, $nombre, $email, $telefono, $direccion);

        if ($stmt_insert->execute()) {
            echo '<script>alert("Cliente registrado exitosamente."); window.location.href="acciones.php?accion=gestion_clientes";</script>';
        } else {
            echo '<script>alert("Error al registrar el cliente."); window.history.back();</script>';
        }
        break;

    case 'editar_cliente':
        $id_cliente = $_GET['id'];

        $sql = "SELECT ID_Cliente, Nombre, Email, Telefono, Direccion 
                FROM cliente 
                WHERE ID_Cliente = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $id_cliente);
        $stmt->execute();
        $result = $stmt->get_result();
        $cliente = $result->fetch_assoc();

        if (!$cliente) {
            echo '<script>alert("Cliente no encontrado."); window.location.href="acciones.php?accion=gestion_clientes";</script>';
            exit();
        }
    ?>
        <!DOCTYPE html>
        <html lang="es">

        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Editar Cliente</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        </head>

        <body>
            <div class="container mt-4">
                <h2 class="text-center mb-4">Editar Cliente</h2>

                <form action="acciones.php?accion=actualizar_cliente" method="POST">
                    <input type="hidden" name="id_cliente" value="<?php echo $cliente['ID_Cliente']; ?>">

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="nombre" class="form-label">Nombre completo</label>
                                <input type="text" class="form-control" id="nombre" name="nombre"
                                    value="<?php echo $cliente['Nombre']; ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email"
                                    value="<?php echo $cliente['Email']; ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="telefono" class="form-label">Teléfono</label>
                                <input type="text" class="form-control" id="telefono" name="telefono"
                                    value="<?php echo $cliente['Telefono']; ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="direccion" class="form-label">Dirección</label>
                                <input type="text" class="form-control" id="direccion" name="direccion"
                                    value="<?php echo $cliente['Direccion']; ?>">
                            </div>
                        </div>
                    </div>

                    <div class="text-center mt-3">
                        <button type="submit" class="btn btn-primary">Actualizar Cliente</button>
                        <a href="acciones.php?accion=gestion_clientes" class="btn btn-secondary ms-2">Cancelar</a>
                    </div>
                </form>
            </div>

            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        </body>

        </html>
    <?php
        break;

    case 'actualizar_cliente':
        $id_cliente = $_POST['id_cliente'];
        $nombre = $_POST['nombre'];
        $email = $_POST['email'];
        $telefono = $_POST['telefono'] ?? '';
        $direccion = $_POST['direccion'] ?? '';

        $sql_check = "SELECT ID_Cliente FROM cliente WHERE Email = ? AND ID_Cliente != ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("ss", $email, $id_cliente);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            echo '<script>alert("El email ya está registrado por otro cliente."); window.history.back();</script>';
            exit();
        }

        $sql_update = "UPDATE cliente 
                       SET Nombre = ?, Email = ?, Telefono = ?, Direccion = ? 
                       WHERE ID_Cliente = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("sssss", $nombre, $email, $telefono, $direccion, $id_cliente);

        if ($stmt_update->execute()) {
            echo '<script>alert("Cliente actualizado exitosamente."); window.location.href="acciones.php?accion=gestion_clientes";</script>';
        } else {
            echo '<script>alert("Error al actualizar el cliente."); window.history.back();</script>';
        }
        break;

    case 'eliminar_cliente':
        $id_cliente = $_GET['id'];

        $sql_check_ventas = "SELECT COUNT(*) FROM venta WHERE ID_Cliente = ?";
        $stmt_check = $conn->prepare($sql_check_ventas);
        $stmt_check->bind_param("s", $id_cliente);
        $stmt_check->execute();
        $stmt_check->bind_result($num_ventas);
        $stmt_check->fetch();
        $stmt_check->close();

        if ($num_ventas > 0) {
            echo '<script>alert("No se puede eliminar el cliente porque tiene ventas asociadas."); window.location.href="acciones.php?accion=gestion_clientes";</script>';
            exit();
        }

        $sql_delete = "DELETE FROM cliente WHERE ID_Cliente = ?";
        $stmt_delete = $conn->prepare($sql_delete);
        $stmt_delete->bind_param("s", $id_cliente);

        if ($stmt_delete->execute()) {
            echo '<script>alert("Cliente eliminado exitosamente."); window.location.href="acciones.php?accion=gestion_clientes";</script>';
        } else {
            echo '<script>alert("Error al eliminar el cliente."); window.history.back();</script>';
        }
        break;

        case 'ver_empleados':
            // Consulta base
            $sql = "SELECT * FROM empleado";
            
            // Procesar búsqueda si existe
            $busqueda = '';
            if(isset($_GET['busqueda']) && !empty($_GET['busqueda'])) {
                $busqueda = trim($_GET['busqueda']);
                $sql .= " WHERE ID_Empleado LIKE '%".$conn->real_escape_string($busqueda)."%' 
                         OR Nombre LIKE '%".$conn->real_escape_string($busqueda)."%'
                         OR Puesto LIKE '%".$conn->real_escape_string($busqueda)."%'
                         OR Email LIKE '%".$conn->real_escape_string($busqueda)."%'";
            }
            
            $sql .= " ORDER BY Nombre";
            $result = $conn->query($sql);
            
            if ($result === false) {
                die("Error en la consulta: " . $conn->error);
            }
            ?>
            <!DOCTYPE html>
            <html lang="es">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Gestión de Empleados</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
                <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
            </head>
            <body>
                <div class="container mt-4">
                    <div class="card shadow">
                        <div class="card-header bg-primary text-white">
                            <h2 class="mb-0"><i class="fas fa-users me-2"></i>Gestión de Empleados</h2>
                        </div>
                        <div class="card-body">
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <a href="acciones.php?accion=agregar_empleado" class="btn btn-success">
                                        <i class="fas fa-user-plus me-1"></i> Agregar Empleado
                                    </a>
                                    <a href="index.php" class="btn btn-secondary">Volver al Inicio</a>
                                </div>
                                <div class="col-md-6">
                                    <form method="GET" action="acciones.php" class="d-flex">
                                        <input type="hidden" name="accion" value="ver_empleados">
                                        <div class="input-group">
                                            <input type="text" name="busqueda" class="form-control" 
                                                   placeholder="Buscar empleado por ID, Nombre, Puesto o Email..." 
                                                   value="<?php echo htmlspecialchars($busqueda); ?>">
                                            <button class="btn btn-outline-secondary" type="submit">
                                                <i class="fas fa-search"></i>
                                            </button>
                                            <?php if(!empty($busqueda)): ?>
                                                <a href="acciones.php?accion=ver_empleados" class="btn btn-outline-danger">
                                                    <i class="fas fa-times"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </form>
                                </div>
                            </div>
        
                            <?php if ($result->num_rows > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover table-bordered">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>ID</th>
                                                <th>Nombre</th>
                                                <th>Puesto</th>
                                                <th>Dirección</th>
                                                <th>Teléfono</th>
                                                <th>Email</th>
                                                <th class="text-center">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while($row = $result->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($row['ID_Empleado']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['Nombre']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['Puesto']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['Direccion']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['Telefono']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['Email']); ?></td>
                                                    <td class="text-center">
                                                        <div class="btn-group" role="group">
                                                            <a href="acciones.php?accion=editar_empleado&id=<?php echo $row['ID_Empleado']; ?>" 
                                                               class="btn btn-warning btn-sm" title="Editar">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <a href="acciones.php?accion=eliminar_empleado&id=<?php echo $row['ID_Empleado']; ?>" 
                                                               class="btn btn-danger btn-sm" 
                                                               onclick="return confirm('¿Estás seguro de eliminar este empleado?')"
                                                               title="Eliminar">
                                                                <i class="fas fa-trash-alt"></i>
                                                            </a>
                                                            <a href="acciones.php?accion=ver_detalles_empleado&id=<?php echo $row['ID_Empleado']; ?>" 
                                                               class="btn btn-info btn-sm" title="Ver Detalles">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <?php echo empty($busqueda) ? 'No hay empleados registrados' : 'No se encontraron resultados para "'.htmlspecialchars($busqueda).'"'; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
            </body>
            </html>
            <?php
            break;

            case 'agregar_empleado':
                ?>
                <!DOCTYPE html>
                <html lang="es">
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title>Agregar Empleado</title>
                    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
                    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
                </head>
                <body>
                    <div class="container mt-4">
                        <div class="card shadow">
                            <div class="card-header bg-primary text-white">
                                <h2 class="mb-0"><i class="fas fa-user-plus me-2"></i>Agregar Nuevo Empleado</h2>
                            </div>
                            <div class="card-body">
                                <form action="acciones.php?accion=guardar_empleado" method="post">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">ID Empleado</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-id-badge"></i></span>
                                                <input type="text" name="id_empleado" class="form-control" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Nombre Completo</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                                <input type="text" name="nombre" class="form-control" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Puesto</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-briefcase"></i></span>
                                                <input type="text" name="puesto" class="form-control" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Dirección</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                                                <input type="text" name="direccion" class="form-control">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Teléfono</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                                <input type="text" name="telefono" class="form-control">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Email</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                                <input type="email" name="email" class="form-control">
                                            </div>
                                        </div>
                                        <div class="col-12 mt-3">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save me-1"></i> Guardar Empleado
                                            </button>
                                            <a href="acciones.php?accion=ver_empleados" class="btn btn-secondary">
                                                <i class="fas fa-times me-1"></i> Cancelar
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
                </body>
                </html>
                <?php
                break;

                case 'guardar_empleado':
                    // Recoger datos del formulario
                    $id_empleado = $_POST['id_empleado'] ?? '';
                    $nombre = $_POST['nombre'] ?? '';
                    $puesto = $_POST['puesto'] ?? '';
                    $direccion = $_POST['direccion'] ?? '';
                    $telefono = $_POST['telefono'] ?? '';
                    $email = $_POST['email'] ?? '';
                    
                    // Validar campos requeridos
                    if(empty($id_empleado) || empty($nombre) || empty($puesto)) {
                        $_SESSION['mensaje'] = [
                            'tipo' => 'error',
                            'texto' => 'Error: Faltan campos requeridos'
                        ];
                        header("Location: acciones.php?accion=agregar_empleado");
                        exit();
                    }
                    
                    // Preparar consulta SQL
                    $sql = "INSERT INTO empleado (ID_Empleado, Nombre, Puesto, Direccion, Telefono, Email) 
                            VALUES (?, ?, ?, ?, ?, ?)";
                    
                    $stmt = $conn->prepare($sql);
                    if($stmt === false) {
                        $_SESSION['mensaje'] = [
                            'tipo' => 'error',
                            'texto' => 'Error al preparar la consulta: ' . $conn->error
                        ];
                        header("Location: acciones.php?accion=agregar_empleado");
                        exit();
                    }
                    
                    $stmt->bind_param("ssssss", $id_empleado, $nombre, $puesto, $direccion, $telefono, $email);
                    
                    if($stmt->execute()) {
                        $_SESSION['mensaje'] = [
                            'tipo' => 'success',
                            'texto' => 'Empleado agregado correctamente'
                        ];
                        header("Location: acciones.php?accion=ver_empleados");
                        exit();
                    } else {
                        $_SESSION['mensaje'] = [
                            'tipo' => 'error',
                            'texto' => 'Error al guardar el empleado: ' . $stmt->error
                        ];
                        header("Location: acciones.php?accion=agregar_empleado");
                        exit();
                    }
                    break;

    case 'editar_empleado':
        $id = $_GET['id'];
        $sql = "SELECT * FROM empleado WHERE ID_Empleado = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $empleado = $stmt->get_result()->fetch_assoc();

        if (!$empleado) {
            echo "<script>alert('Empleado no encontrado'); window.location.href='acciones.php?accion=ver_empleados';</script>";
            exit();
        }
    ?>
        <!DOCTYPE html>
        <html lang="es">

        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Editar Empleado</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        </head>

        <body>
            <div class="container mt-4">
                <h2>Editar Empleado</h2>
                <form action="acciones.php?accion=actualizar_empleado" method="post">
                    <input type="hidden" name="ID_Empleado" value="<?= $empleado['ID_Empleado'] ?>">
                    <div class="mb-3">
                        <label class="form-label">Nombre</label>
                        <input type="text" name="Nombre" class="form-control" value="<?= $empleado['Nombre'] ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Puesto</label>
                        <input type="text" name="Puesto" class="form-control" value="<?= $empleado['Puesto'] ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Dirección</label>
                        <input type="text" name="Direccion" class="form-control" value="<?= $empleado['Direccion'] ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Teléfono</label>
                        <input type="text" name="Telefono" class="form-control" value="<?= $empleado['Telefono'] ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="Email" class="form-control" value="<?= $empleado['Email'] ?>">
                    </div>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                    <a href="acciones.php?accion=ver_empleados" class="btn btn-secondary">Cancelar</a>
                </form>
            </div>
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        </body>

        </html>
    <?php
        break;

    case 'actualizar_empleado':
        $ID_Empleado = $_POST['ID_Empleado'];
        $Nombre = $_POST['Nombre'];
        $Puesto = $_POST['Puesto'] ?? null;
        $Direccion = $_POST['Direccion'] ?? null;
        $Telefono = $_POST['Telefono'] ?? null;
        $Email = $_POST['Email'] ?? null;

        $sql = "UPDATE empleado SET 
                    Nombre = ?, 
                    Puesto = ?, 
                    Direccion = ?, 
                    Telefono = ?, 
                    Email = ? 
                    WHERE ID_Empleado = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssss", $Nombre, $Puesto, $Direccion, $Telefono, $Email, $ID_Empleado);

        if ($stmt->execute()) {
            echo "<script>alert('Empleado actualizado correctamente'); window.location.href='acciones.php?accion=ver_empleados';</script>";
        } else {
            echo "<script>alert('Error al actualizar empleado'); window.history.back();</script>";
        }
        break;

   case 'eliminar_empleado':
    $id = $_GET['id'];
    
    // Verificar si el empleado tiene ventas
    $check_sql = "SELECT COUNT(*) AS total FROM venta WHERE ID_Empleado = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['total'] > 0) {
        echo "<script>
                if(confirm('Este empleado tiene ventas registradas. ¿Desea eliminarlo junto con sus ventas?')) {
                    window.location.href='acciones.php?accion=confirmar_eliminar_empleado&id=$id';
                } else {
                    window.history.back();
                }
              </script>";
    } else {
        // Eliminar directamente si no tiene ventas
        $sql = "DELETE FROM empleado WHERE ID_Empleado = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $id);
        
        if ($stmt->execute()) {
            echo "<script>
                    alert('Empleado eliminado correctamente');
                    window.location.href='acciones.php?accion=ver_empleados';
                  </script>";
        } else {
            echo "<script>
                    alert('Error al eliminar empleado');
                    window.history.back();
                  </script>";
        }
    }
    break;

// Nuevo case para la eliminación confirmada
case 'confirmar_eliminar_empleado':
    $id = $_GET['id'];
    $conn->begin_transaction();
    
    try {
        // Eliminar ventas
        $sql1 = "DELETE FROM venta WHERE ID_Empleado = ?";
        $stmt1 = $conn->prepare($sql1);
        $stmt1->bind_param("s", $id);
        $stmt1->execute();
        
        // Eliminar empleado
        $sql2 = "DELETE FROM empleado WHERE ID_Empleado = ?";
        $stmt2 = $conn->prepare($sql2);
        $stmt2->bind_param("s", $id);
        $stmt2->execute();
        
        $conn->commit();
        echo "<script>
                alert('Empleado y ventas asociadas eliminados correctamente');
                window.location.href='acciones.php?accion=ver_empleados';
              </script>";
    } catch (Exception $e) {
        $conn->rollback();
        echo "<script>
                alert('Error al eliminar: ".addslashes($e->getMessage())."');
                window.history.back();
              </script>";
    }
    break;

       case 'reporte_caja':
    // Conexión a la base de datos
    $host = "localhost";
    $usuario = "root";
    $contrasena = "";
    $basedatos = "libreriasalas";

    $conexion = new mysqli($host, $usuario, $contrasena, $basedatos);

    if ($conexion->connect_error) {
        die("<div class='alert alert-danger'>Error de conexión: " . $conexion->connect_error . "</div>");
    }

    // Procesar corte de caja si se envió el formulario
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['corte_caja'])) {
        // Consulta para resumen del día
        $sql_resumen = "SELECT 
                        SUM(Monto_Total) AS total_ventas,
                        COUNT(ID_Venta) AS cantidad_ventas
                        FROM venta
                        WHERE DATE(Fecha) = CURDATE()";
        
        $resultado_resumen = $conexion->query($sql_resumen);
        $resumen = $resultado_resumen->fetch_assoc();
        
        $total_ventas = $resumen['total_ventas'] ?? 0;
        $cantidad_ventas = $resumen['cantidad_ventas'] ?? 0;
        
        // Insertar en la tabla corte_caja
        $sql_insert = "INSERT INTO corte_caja (Total_Ventas, Cantidad_Ventas, Fecha_Reporte, Detalles) 
                       VALUES (?, ?, CURDATE(), ?)";
        
        $stmt = $conexion->prepare($sql_insert);
        $detalles = "Corte de caja automático - " . $cantidad_ventas . " ventas procesadas";
        $stmt->bind_param("dis", $total_ventas, $cantidad_ventas, $detalles);
        
        if ($stmt->execute()) {
            $mensaje_exito = "<div class='alert alert-success alert-dismissible fade show' role='alert'>
                                <i class='fas fa-check-circle me-2'></i>
                                <strong>¡Corte de caja realizado exitosamente!</strong> Se han guardado " . $cantidad_ventas . " ventas por un total de $" . number_format($total_ventas, 2) . "
                                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                              </div>";
        } else {
            $mensaje_error = "<div class='alert alert-danger alert-dismissible fade show' role='alert'>
                                <i class='fas fa-exclamation-triangle me-2'></i>
                                <strong>Error al realizar el corte:</strong> " . $conexion->error . "
                                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                              </div>";
        }
        $stmt->close();
    }

    // Consulta SQL para obtener las ventas del día
    $sql_ventas = "SELECT ID_Venta, Fecha, Monto_Total, 
                          c.Nombre AS Cliente,
                          e.Nombre AS Empleado
                   FROM venta v
                   JOIN cliente c ON v.ID_Cliente = c.ID_Cliente
                   JOIN empleado e ON v.ID_Empleado = e.ID_Empleado
                   WHERE DATE(v.Fecha) = CURDATE()
                   ORDER BY v.Fecha DESC";

    $resultado_ventas = $conexion->query($sql_ventas);

    // Consulta para resumen del día
    $sql_resumen = "SELECT 
                    SUM(Monto_Total) AS total_ventas,
                    COUNT(ID_Venta) AS cantidad_ventas
                    FROM venta
                    WHERE DATE(Fecha) = CURDATE()";

    $resultado_resumen = $conexion->query($sql_resumen);
    $resumen = $resultado_resumen->fetch_assoc();

    echo '
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Reporte de Caja - Librería Salas</title>
        
        <!-- Bootstrap 5 CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        
        <!-- Font Awesome -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        
        <style>
            :root {
                --color-primario: #2c3e50;
                --color-secundario: #3498db;
                --color-exito: #2ecc71;
                --color-peligro: #e74c3c;
            }
            
            .card-reporte {
                border: none;
                border-radius: 15px;
                box-shadow: 0 10px 20px rgba(0,0,0,0.1);
                overflow: hidden;
            }
            
            .card-header-reporte {
                background: linear-gradient(135deg, var(--color-primario), var(--color-secundario));
                color: white;
                padding: 1.5rem;
                border-bottom: none;
            }
            
            .table-custom {
                border-collapse: separate;
                border-spacing: 0;
            }
            
            .table-custom thead th {
                background-color: #f8f9fa;
                border-bottom: 2px solid #dee2e6;
                font-weight: 600;
            }
            
            .table-custom tbody tr:hover {
                background-color: rgba(52, 152, 219, 0.05);
            }
            
            .resumen-box {
                background-color: #f8f9fa;
                border-radius: 10px;
                padding: 15px;
                margin-bottom: 20px;
            }
            
            .venta-id {
                font-weight: bold;
                color: var(--color-primario);
            }
            
            .btn-corte {
                background: linear-gradient(135deg, var(--color-exito), #27ae60);
                border: none;
                color: white;
                padding: 10px 20px;
                border-radius: 8px;
                font-weight: 600;
                transition: all 0.3s ease;
            }
            
            .btn-corte:hover {
                transform: translateY(-2px);
                box-shadow: 0 5px 15px rgba(46, 204, 113, 0.4);
            }
        </style>
    </head>
    <body>
        <div class="container py-4">';

    // Mostrar mensajes de éxito o error
    if (isset($mensaje_exito)) {
        echo $mensaje_exito;
    }
    if (isset($mensaje_error)) {
        echo $mensaje_error;
    }

    echo '
            <div class="card card-reporte">
                <div class="card-header card-header-reporte text-center">
                    <h2 class="mb-0"><i class="fas fa-cash-register me-2"></i>REPORTE DE CAJA DIARIO</h2>
                    <p class="mb-0 opacity-75">Librería Salas - ' . date('d/m/Y') . '</p>
                </div>
                
                <div class="card-body">
                    <!-- Resumen rápido -->
                    <div class="resumen-box">
                        <div class="row">
                            <div class="col-md-4 text-center">
                                <h5 class="text-muted">VENTAS TOTALES</h5>
                                <h3 class="text-primary">' . ($resumen['cantidad_ventas'] ?? 0) . '</h3>
                            </div>
                            <div class="col-md-4 text-center">
                                <h5 class="text-muted">MONTO TOTAL</h5>
                                <h3 class="text-success">$' . number_format(($resumen['total_ventas'] ?? 0), 2) . '</h3>
                            </div>
                            <div class="col-md-4 text-center">
                                <form method="POST" onsubmit="return confirm(\'¿Estás seguro de realizar el corte de caja? Esta acción guardará el reporte actual y no se podrá modificar.\')">
                                    <button type="submit" name="corte_caja" class="btn-corte mt-2">
                                        <i class="fas fa-scissors me-2"></i>Realizar Corte
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tabla de ventas -->
                    <div class="table-responsive">
                        <table class="table table-custom table-hover align-middle">
                            <thead>
                                <tr>
                                    <th class="text-center"><i class="far fa-calendar-alt me-2"></i>Fecha/Hora</th>
                                    <th class="text-center"><i class="fas fa-hashtag me-2"></i>Venta #</th>
                                    <th><i class="fas fa-user me-2"></i>Cliente</th>
                                    <th><i class="fas fa-user-tie me-2"></i>Empleado</th>
                                    <th class="text-end"><i class="fas fa-dollar-sign me-2"></i>Monto</th>
                                </tr>
                            </thead>
                            <tbody>';

    if ($resultado_ventas->num_rows > 0) {
        while ($venta = $resultado_ventas->fetch_assoc()) {
            echo '<tr>
                    <td class="text-center">' . date('H:i', strtotime($venta['Fecha'])) . '</td>
                    <td class="text-center venta-id">' . $venta['ID_Venta'] . '</td>
                    <td>' . htmlspecialchars($venta['Cliente']) . '</td>
                    <td>' . htmlspecialchars($venta['Empleado']) . '</td>
                    <td class="text-end">$' . number_format($venta['Monto_Total'], 2) . '</td>
                </tr>';
        }
    } else {
        echo '<tr>
                <td colspan="5" class="text-center py-4">
                    <i class="far fa-folder-open fa-2x mb-3 text-muted"></i>
                    <h5 class="text-muted">No hay ventas registradas hoy</h5>
                </td>
              </tr>';
    }

    echo '          </tbody>
                        </table>
                    </div>
                    
                    <!-- Pie del reporte -->
                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <div class="text-muted small">
                            <i class="fas fa-info-circle me-2"></i>
                            Reporte generado el ' . date('d/m/Y \a \l\a\s H:i:s') . '
                        </div>
                        <div>
                            <button onclick="window.print()" class="btn btn-primary me-2">
                                <i class="fas fa-print me-2"></i>Imprimir
                            </button>
                            <a href="historial_cortes.php" class="btn btn-outline-secondary">
                                <i class="fas fa-history me-2"></i>Ver Historial
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Bootstrap 5 JS Bundle with Popper -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>';

    $conexion->close();
    break;


        case 'ventas_periodo':
            ?>
                <div class="container mt-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h2 class="mb-0"><i class="fas fa-chart-line me-2"></i>Reporte de Ventas por Período</h2>
                        </div>
                        <div class="card-body">
                            <form method="GET" action="acciones.php" class="needs-validation" novalidate>
                                <input type="hidden" name="accion" value="ventas_periodo">
                                <div class="row mb-4 g-3 align-items-end">
                                    <div class="col-md-5">
                                        <label for="fecha_inicio" class="form-label">Fecha de inicio:</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="far fa-calendar-alt"></i></span>
                                            <input type="date" id="fecha_inicio" name="fecha_inicio" class="form-control" required>
                                        </div>
                                    </div>
                                    <div class="col-md-5">
                                        <label for="fecha_fin" class="form-label">Fecha de fin:</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="far fa-calendar-alt"></i></span>
                                            <input type="date" id="fecha_fin" name="fecha_fin" class="form-control" required>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="fas fa-search me-1"></i> Buscar
                                        </button>
                                    </div>
                                </div>
                            </form>
            
                            <?php
                            if (isset($_GET['fecha_inicio']) && isset($_GET['fecha_fin'])) {
                                $inicio = $_GET['fecha_inicio'];
                                $fin = $_GET['fecha_fin'];
                                $query = "SELECT * FROM ventas WHERE fecha BETWEEN '$inicio' AND '$fin' ORDER BY fecha DESC";
                                $result = mysqli_query($conexion, $query);
                                
                                // Calcular total general
                                $query_total = "SELECT SUM(total) as total_general FROM ventas WHERE fecha BETWEEN '$inicio' AND '$fin'";
                                $result_total = mysqli_query($conexion, $query_total);
                                $total_row = mysqli_fetch_assoc($result_total);
                                $total_general = $total_row['total_general'] ?? 0;
                                
                                echo '<div class="mt-4">';
                                echo '<h4 class="mb-3"><i class="fas fa-list-alt me-2"></i>Resultados del '.date('d/m/Y', strtotime($inicio)).' al '.date('d/m/Y', strtotime($fin)).'</h4>';
                                
                                if(mysqli_num_rows($result) > 0) {
                                    echo '<div class="table-responsive">';
                                    echo '<table class="table table-hover table-striped table-bordered">';
                                    echo '<thead class="table-dark">';
                                    echo '<tr><th class="text-center">ID</th><th class="text-center">Fecha</th><th class="text-end">Total</th></tr>';
                                    echo '</thead><tbody>';
                                    
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        echo '<tr>';
                                        echo '<td class="text-center">'.$row['id'].'</td>';
                                        echo '<td class="text-center">'.date('d/m/Y', strtotime($row['fecha'])).'</td>';
                                        echo '<td class="text-end">$'.number_format($row['total'], 2).'</td>';
                                        echo '</tr>';
                                    }
                                    
                                    echo '</tbody>';
                                    echo '<tfoot class="table-secondary fw-bold">';
                                    echo '<tr><td colspan="2" class="text-end">Total General:</td><td class="text-end">$'.number_format($total_general, 2).'</td></tr>';
                                    echo '</tfoot>';
                                    echo '</table>';
                                    echo '</div>';
                                } else {
                                    echo '<div class="alert alert-warning">No se encontraron ventas en el período seleccionado.</div>';
                                }
                                echo '</div>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            <?php
            break;

            case 'libros_vendidos':
                // Configurar límite de resultados (puedes hacerlo configurable)
                $limite = isset($_GET['limite']) ? (int)$_GET['limite'] : 10;
                $periodo = isset($_GET['periodo']) ? $_GET['periodo'] : 'total'; // 'total', 'mes', 'semana'
                
                // Construir la consulta según el período seleccionado
                $sql = "SELECT l.ISBN, l.Titulo, l.Autor, e.Nombre AS Editorial, 
                               SUM(dv.Cantidad) AS TotalVendido, 
                               SUM(dv.Cantidad * l.Precio) AS TotalIngresos
                        FROM detalleventa dv
                        JOIN libro l ON dv.ISBN = l.ISBN
                        JOIN editorial e ON l.ID_Editorial = e.ID_Editorial";
                
                // Añadir filtro de período si es necesario
                if ($periodo === 'mes') {
                    $sql .= " JOIN venta v ON dv.ID_Venta = v.ID_Venta 
                             WHERE v.Fecha >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
                } elseif ($periodo === 'semana') {
                    $sql .= " JOIN venta v ON dv.ID_Venta = v.ID_Venta 
                             WHERE v.Fecha >= DATE_SUB(CURDATE(), INTERVAL 1 WEEK)";
                }
                
                $sql .= " GROUP BY l.ISBN, l.Titulo, l.Autor, e.Nombre
                         ORDER BY TotalVendido DESC
                         LIMIT ?";
                
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $limite);
                $stmt->execute();
                $result = $stmt->get_result();
                ?>
                <!DOCTYPE html>
                <html lang="es">
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title>Libros Más Vendidos</title>
                    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
                    <style>
                        .badge-vendidos {
                            background-color: #ffc107;
                            color: #000;
                            font-size: 1em;
                        }
                        .table-ranking {
                            counter-reset: ranking;
                        }
                        .table-ranking tbody tr::before {
                            counter-increment: ranking;
                            content: counter(ranking);
                            display: inline-block;
                            width: 2em;
                            margin-right: 0.5em;
                            font-weight: bold;
                            text-align: center;
                        }
                        .table-ranking tbody tr:nth-child(1)::before {
                            color: #ffc107;
                            font-weight: bold;
                        }
                        .table-ranking tbody tr:nth-child(2)::before {
                            color: #6c757d;
                            font-weight: bold;
                        }
                        .table-ranking tbody tr:nth-child(3)::before {
                            color: #cd7f32;
                            font-weight: bold;
                        }
                    </style>
                </head>
                <body>
                    <div class="container mt-4">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2>
                                <span class="badge badge-vendidos me-2">TOP <?php echo $limite; ?></span>
                                Libros Más Vendidos
                            </h2>
                            
                            <div class="dropdown">
                                <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="dropdownPeriodo" data-bs-toggle="dropdown" aria-expanded="false">
                                    <?php 
                                    echo match($periodo) {
                                        'total' => 'Todos los tiempos',
                                        'mes' => 'Último mes',
                                        'semana' => 'Última semana',
                                        default => 'Período'
                                    };
                                    ?>
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="dropdownPeriodo">
                                    <li><a class="dropdown-item" href="?accion=libros_mas_vendidos&periodo=total">Todos los tiempos</a></li>
                                    <li><a class="dropdown-item" href="?accion=libros_mas_vendidos&periodo=mes">Último mes</a></li>
                                    <li><a class="dropdown-item" href="?accion=libros_mas_vendidos&periodo=semana">Última semana</a></li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="card shadow">
                            <div class="card-body">
                                <?php if ($result->num_rows > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover table-ranking">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>#</th>
                                                    <th>Libro</th>
                                                    <th>Autor</th>
                                                    <th>Editorial</th>
                                                    <th class="text-end">Unidades Vendidas</th>
                                                    <th class="text-end">Ingresos Generados</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while ($row = $result->fetch_assoc()): ?>
                                                    <tr>
                                                        <td></td> <!-- El número se genera con CSS -->
                                                        <td>
                                                            <strong><?php echo htmlspecialchars($row['Titulo']); ?></strong><br>
                                                            <small class="text-muted">ISBN: <?php echo $row['ISBN']; ?></small>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($row['Autor']); ?></td>
                                                        <td><?php echo htmlspecialchars($row['Editorial']); ?></td>
                                                        <td class="text-end"><?php echo number_format($row['TotalVendido']); ?></td>
                                                        <td class="text-end">$<?php echo number_format($row['TotalIngresos'], 2); ?></td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    
                                    <div class="mt-3 d-flex justify-content-between align-items-center">
                                        <div>
                                            <a href="javascript:window.print()" class="btn btn-outline-primary">
                                                <i class="bi bi-printer-fill"></i> Imprimir Reporte
                                            </a>
                                        </div>
                                        <div class="btn-group">
                                            <a href="?accion=libros_mas_vendidos&limite=5&periodo=<?php echo $periodo; ?>" class="btn btn-sm <?php echo $limite == 5 ? 'btn-primary' : 'btn-outline-primary'; ?>">Top 5</a>
                                            <a href="?accion=libros_mas_vendidos&limite=10&periodo=<?php echo $periodo; ?>" class="btn btn-sm <?php echo $limite == 10 ? 'btn-primary' : 'btn-outline-primary'; ?>">Top 10</a>
                                            <a href="?accion=libros_mas_vendidos&limite=20&periodo=<?php echo $periodo; ?>" class="btn btn-sm <?php echo $limite == 20 ? 'btn-primary' : 'btn-outline-primary'; ?>">Top 20</a>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        No se encontraron ventas registradas para el período seleccionado.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <a href="index.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Volver al Menú
                            </a>
                        </div>
                    </div>
                    
                    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
                </body>
                </html>
                <?php
                break;
            
                        
                case 'clientes_frecuentes':
                    // Configurar límite de resultados
                    $limite = isset($_GET['limite']) ? (int)$_GET['limite'] : 10;
                    $periodo = isset($_GET['periodo']) ? $_GET['periodo'] : 'total'; // 'total', 'mes', 'semana'
                    
                    // Construir la consulta según el período seleccionado
                    $sql = "SELECT c.ID_Cliente, c.Nombre, c.Email, c.Telefono,
                                   COUNT(v.ID_Venta) AS TotalCompras, 
                                   SUM(v.Monto_Total) AS TotalGastado
                            FROM cliente c
                            JOIN venta v ON c.ID_Cliente = v.ID_Cliente";
                    
                    // Añadir filtro de período si es necesario
                    if ($periodo === 'mes') {
                        $sql .= " WHERE v.Fecha >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
                    } elseif ($periodo === 'semana') {
                        $sql .= " WHERE v.Fecha >= DATE_SUB(CURDATE(), INTERVAL 1 WEEK)";
                    }
                    
                    $sql .= " GROUP BY c.ID_Cliente, c.Nombre, c.Email, c.Telefono
                             ORDER BY TotalCompras DESC, TotalGastado DESC
                             LIMIT ?";
                    
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $limite);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    // Verificar si hay resultados
                    if ($result === false) {
                        die("Error en la consulta: " . $conn->error);
                    }
                ?>
                <!DOCTYPE html>
                <html lang="es">
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title>Clientes Frecuentes</title>
                    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
                    <style>
                        .badge-frecuentes {
                            background-color: #0d6efd;
                            color: white;
                            font-size: 1em;
                        }
                        .table-ranking {
                            counter-reset: ranking;
                        }
                        .table-ranking tbody tr::before {
                            counter-increment: ranking;
                            content: counter(ranking);
                            display: inline-block;
                            width: 2em;
                            margin-right: 0.5em;
                            font-weight: bold;
                            text-align: center;
                        }
                        .table-ranking tbody tr:nth-child(1)::before {
                            color: #ffc107;
                            font-weight: bold;
                        }
                        .table-ranking tbody tr:nth-child(2)::before {
                            color: #6c757d;
                            font-weight: bold;
                        }
                        .table-ranking tbody tr:nth-child(3)::before {
                            color: #cd7f32;
                            font-weight: bold;
                        }
                        .customer-avatar {
                            width: 40px;
                            height: 40px;
                            border-radius: 50%;
                            background-color: #0d6efd;
                            color: white;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            font-weight: bold;
                            margin-right: 10px;
                        }
                    </style>
                </head>
                <body>
                    <div class="container mt-4">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2>
                                <span class="badge badge-frecuentes me-2">TOP <?= htmlspecialchars($limite) ?></span>
                                Clientes Frecuentes
                            </h2>
                            
                            <div class="dropdown">
                                <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="dropdownPeriodo" data-bs-toggle="dropdown" aria-expanded="false">
                                    <?php 
                                    switch($periodo) {
                                        case 'mes': echo 'Último mes'; break;
                                        case 'semana': echo 'Última semana'; break;
                                        default: echo 'Todos los tiempos'; break;
                                    }
                                    ?>
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="dropdownPeriodo">
                                    <li><a class="dropdown-item" href="?accion=clientes_frecuentes&periodo=total">Todos los tiempos</a></li>
                                    <li><a class="dropdown-item" href="?accion=clientes_frecuentes&periodo=mes">Último mes</a></li>
                                    <li><a class="dropdown-item" href="?accion=clientes_frecuentes&periodo=semana">Última semana</a></li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="card shadow">
                            <div class="card-body">
                                <?php if ($result->num_rows > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover table-ranking">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>#</th>
                                                    <th>Cliente</th>
                                                    <th>Contacto</th>
                                                    <th class="text-end">Compras</th>
                                                    <th class="text-end">Total Gastado</th>
                                                    <th class="text-end">Ticket Promedio</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while ($row = $result->fetch_assoc()): 
                                                    $iniciales = '';
                                                    $nombres = explode(' ', $row['Nombre']);
                                                    foreach($nombres as $n) {
                                                        if(!empty($n)) {
                                                            $iniciales .= strtoupper(substr($n, 0, 1));
                                                        }
                                                    }
                                                    $iniciales = substr($iniciales, 0, 2);
                                                    $ticket_promedio = $row['TotalGastado'] / $row['TotalCompras'];
                                                ?>
                                                    <tr>
                                                        <td></td>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <div class="customer-avatar">
                                                                    <?= htmlspecialchars($iniciales) ?>
                                                                </div>
                                                                <div>
                                                                    <strong><?= htmlspecialchars($row['Nombre']) ?></strong><br>
                                                                    <small class="text-muted">ID: <?= htmlspecialchars($row['ID_Cliente']) ?></small>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <?= htmlspecialchars($row['Email']) ?><br>
                                                            <?php if (!empty($row['Telefono'])): ?>
                                                                <small class="text-muted"><?= htmlspecialchars($row['Telefono']) ?></small>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td class="text-end"><?= number_format($row['TotalCompras']) ?></td>
                                                        <td class="text-end">$<?= number_format($row['TotalGastado'], 2) ?></td>
                                                        <td class="text-end">$<?= number_format($ticket_promedio, 2) ?></td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    
                                    <div class="mt-3 d-flex justify-content-between align-items-center">
                                        <div>
                                            <button onclick="window.print()" class="btn btn-outline-primary">
                                                <i class="bi bi-printer"></i> Imprimir
                                            </button>
                                        </div>
                                        <div class="btn-group">
                                            <a href="?accion=clientes_frecuentes&limite=5&periodo=<?= urlencode($periodo) ?>" class="btn btn-sm <?= $limite == 5 ? 'btn-primary' : 'btn-outline-primary' ?>">Top 5</a>
                                            <a href="?accion=clientes_frecuentes&limite=10&periodo=<?= urlencode($periodo) ?>" class="btn btn-sm <?= $limite == 10 ? 'btn-primary' : 'btn-outline-primary' ?>">Top 10</a>
                                            <a href="?accion=clientes_frecuentes&limite=20&periodo=<?= urlencode($periodo) ?>" class="btn btn-sm <?= $limite == 20 ? 'btn-primary' : 'btn-outline-primary' ?>">Top 20</a>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        No se encontraron clientes frecuentes para el período seleccionado.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <a href="index.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Volver al Menú
                            </a>
                        </div>
                    </div>
                    
                    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
                    <script src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css"></script>
                </body>
                </html>
                <?php
                break;
                
                case 'gestion_editoriales':
    // Consulta base
    $sql = "SELECT * FROM editorial";
    
    // Procesar búsqueda si existe
    $busqueda = '';
    if(isset($_GET['busqueda']) && !empty($_GET['busqueda'])) {
        $busqueda = trim($_GET['busqueda']);
        $sql .= " WHERE ID_Editorial LIKE '%".$conn->real_escape_string($busqueda)."%' 
                 OR Nombre LIKE '%".$conn->real_escape_string($busqueda)."%'";
    }
    
    $sql .= " ORDER BY Nombre";
    $result = $conn->query($sql);
    
    if ($result === false) {
        die("Error en la consulta: " . $conn->error);
    }
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Gestión de Editoriales</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    </head>
    <body>
        <div class="container mt-4">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h2 class="mb-0"><i class="fas fa-book-publisher me-2"></i>Gestión de Editoriales</h2>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <a href="acciones.php?accion=nueva_editorial" class="btn btn-success">
                                <i class="fas fa-plus-circle me-1"></i> Nueva Editorial
                            </a>
                        </div>
                        <div class="col-md-6">
                            <form method="GET" action="acciones.php" class="d-flex">
                                <input type="hidden" name="accion" value="gestion_editoriales">
                                <div class="input-group">
                                    <input type="text" name="busqueda" class="form-control" 
                                           placeholder="Buscar por ID o Nombre..." 
                                           value="<?php echo htmlspecialchars($busqueda); ?>">
                                    <button class="btn btn-outline-secondary" type="submit">
                                        <i class="fas fa-search"></i>
                                    </button>
                                    <?php if(!empty($busqueda)): ?>
                                        <a href="acciones.php?accion=gestion_editoriales" class="btn btn-outline-danger">
                                            <i class="fas fa-times"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    </div>

                    <?php if ($result->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover table-bordered">
                                <thead class="table-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Nombre</th>
                                        <th>País</th>
                                        <th>Teléfono</th>
                                        <th>Email</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['ID_Editorial']); ?></td>
                                            <td><?php echo htmlspecialchars($row['Nombre']); ?></td>
                                            <td><?php echo htmlspecialchars($row['Pais']); ?></td>
                                            <td><?php echo htmlspecialchars($row['Telefono']); ?></td>
                                            <td><?php echo htmlspecialchars($row['Email']); ?></td>
                                            <td class="text-center">
                                                <div class="btn-group" role="group">
                                                    <a href="acciones.php?accion=editar_editorial&id=<?php echo $row['ID_Editorial']; ?>" 
                                                       class="btn btn-warning btn-sm" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="acciones.php?accion=eliminar_editorial&id=<?php echo $row['ID_Editorial']; ?>" 
                                                       class="btn btn-danger btn-sm" 
                                                       onclick="return confirm('¿Estás seguro de eliminar esta editorial?')"
                                                       title="Eliminar">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <?php echo empty($busqueda) ? 'No hay editoriales registradas' : 'No se encontraron resultados para "'.htmlspecialchars($busqueda).'"'; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <a href="index.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Volver al Menú
                            </a>
    </body>
    </html>
    <?php
    break;

case 'nueva_editorial':
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Nueva Editorial</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    </head>
    <body>
        <div class="container mt-4">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h2 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Nueva Editorial</h2>
                </div>
                <div class="card-body">
                    <form action="acciones.php?accion=guardar_editorial" method="post">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">ID Editorial</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                    <input type="text" name="id_editorial" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nombre</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-signature"></i></span>
                                    <input type="text" name="nombre" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">País</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-globe"></i></span>
                                    <input type="text" name="pais" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Teléfono</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                    <input type="text" name="telefono" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Email</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" name="email" class="form-control">
                                </div>
                            </div>
                            <div class="col-12 mt-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> Guardar
                                </button>
                                <a href="acciones.php?accion=gestion_editoriales" class="btn btn-secondary">
                                    <i class="fas fa-times me-1"></i> Cancelar
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    break;
                
                case 'guardar_editorial':
                    $id_editorial = $_POST['id_editorial'];
                    $nombre = $_POST['nombre'];
                    $pais = $_POST['pais'] ?? '';
                    $telefono = $_POST['telefono'] ?? '';
                    $email = $_POST['email'] ?? '';
                    
                    $sql = "INSERT INTO editorial (ID_Editorial, Nombre, Pais, Telefono, Email) VALUES (?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("sssss", $id_editorial, $nombre, $pais, $telefono, $email);
                    
                    if ($stmt->execute()) {
                        echo "<script>alert('Editorial creada correctamente'); window.location.href='acciones.php?accion=gestion_editoriales';</script>";
                    } else {
                        echo "<script>alert('Error al crear editorial'); window.history.back();</script>";
                    }
                    break;
                
                case 'editar_editorial':
                    $id = $_GET['id'];
                    $sql = "SELECT * FROM editorial WHERE ID_Editorial = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("s", $id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $editorial = $result->fetch_assoc();
                    
                    if (!$editorial) {
                        echo "<script>alert('Editorial no encontrada'); window.location.href='acciones.php?accion=gestion_editoriales';</script>";
                        exit();
                    }
                    ?>
                    <!DOCTYPE html>
                    <html lang="es">
                    <head>
                        <meta charset="UTF-8">
                        <meta name="viewport" content="width=device-width, initial-scale=1.0">
                        <title>Editar Editorial</title>
                        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
                    </head>
                    <body>
                        <div class="container mt-4">
                            <h2>Editar Editorial</h2>
                            <form action="acciones.php?accion=actualizar_editorial" method="post">
                                <input type="hidden" name="id_editorial" value="<?php echo $editorial['ID_Editorial']; ?>">
                                <div class="mb-3">
                                    <label class="form-label">Nombre</label>
                                    <input type="text" name="nombre" class="form-control" value="<?php echo htmlspecialchars($editorial['Nombre']); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">País</label>
                                    <input type="text" name="pais" class="form-control" value="<?php echo htmlspecialchars($editorial['Pais']); ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Teléfono</label>
                                    <input type="text" name="telefono" class="form-control" value="<?php echo htmlspecialchars($editorial['Telefono']); ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($editorial['Email']); ?>">
                                </div>
                                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                                <a href="acciones.php?accion=gestion_editoriales" class="btn btn-secondary">Cancelar</a>
                            </form>
                        </div>
                    </body>
                    </html>
                    <?php
                    break;
                
                case 'actualizar_editorial':
                    $id_editorial = $_POST['id_editorial'];
                    $nombre = $_POST['nombre'];
                    $pais = $_POST['pais'] ?? '';
                    $telefono = $_POST['telefono'] ?? '';
                    $email = $_POST['email'] ?? '';
                    
                    $sql = "UPDATE editorial SET Nombre = ?, Pais = ?, Telefono = ?, Email = ? WHERE ID_Editorial = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("sssss", $nombre, $pais, $telefono, $email, $id_editorial);
                    
                    if ($stmt->execute()) {
                        echo "<script>alert('Editorial actualizada correctamente'); window.location.href='acciones.php?accion=gestion_editoriales';</script>";
                    } else {
                        echo "<script>alert('Error al actualizar editorial'); window.history.back();</script>";
                    }
                    break;
                
                case 'eliminar_editorial':
                    $id = $_GET['id'];
                    
                    // Verificar si hay libros asociados
                    $sql_check = "SELECT COUNT(*) AS total FROM libro WHERE ID_Editorial = ?";
                    $stmt_check = $conn->prepare($sql_check);
                    $stmt_check->bind_param("s", $id);
                    $stmt_check->execute();
                    $result_check = $stmt_check->get_result();
                    $row_check = $result_check->fetch_assoc();
                    
                    if ($row_check['total'] > 0) {
                        echo "<script>alert('No se puede eliminar, hay libros asociados'); window.location.href='acciones.php?accion=gestion_editoriales';</script>";
                        exit();
                    }
                    
                    $sql = "DELETE FROM editorial WHERE ID_Editorial = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("s", $id);
                    
                    if ($stmt->execute()) {
                        echo "<script>alert('Editorial eliminada correctamente'); window.location.href='acciones.php?accion=gestion_editoriales';</script>";
                    } else {
                        echo "<script>alert('Error al eliminar editorial'); window.history.back();</script>";
                    }
                    break;
    }
?>