<?php
session_start();
require_once __DIR__ . '/conf/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['codigo_equipo'], $_POST['nombre'], $_POST['cedula'])) {
    $codigo_equipo = trim($_POST['codigo_equipo']);
    $nombre = trim($_POST['nombre']);
    $cedula = trim($_POST['cedula']);
    
    if (!validarCedula($cedula)) {
        mostrarAlerta('La cédula solo debe contener números.');
    }
    
    // Verificar que no exista ya la cédula
    if (usuarioExiste($cedula)) {
        mostrarAlerta('La cédula ya está registrada.');
    }
    
    // Verificar que el equipo exista
    $equipo = equipoExiste($codigo_equipo);
    if (!$equipo) {
        mostrarAlerta('El código de equipo no existe.');
    }
    
    // Verificar que el equipo no esté lleno
    $miembros = contarMiembrosEquipo($equipo['id']);
    if ($miembros >= 4) {
        mostrarAlerta('El equipo ya está completo (máximo 4 personas).');
    }
    
    // Registrar participante en el equipo
    if (registrarParticipante($nombre, $cedula, $equipo['id'])) {
        // IMPORTANTE: NO iniciar tiempo automáticamente al unirse
        // El tiempo solo se iniciará cuando el administrador active el hackathon
        // y los equipos accedan después de eso
        
        // Iniciar sesión del usuario
        $participante = usuarioExiste($cedula);
        iniciarSesion($participante);
        header("Location: index.php");
        exit;
    } else {
        mostrarAlerta('Error al registrar participante.');
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Unirse a Equipo - Hackathon</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <div class="text-center mb-3">
        <img src="img/img.jpg" alt="Logo Hackathon" style="max-width:800px;">
        <h1>Hackathon UPTPC</h1>
    </div>
    
    <div class="card w-50 mx-auto">
        <div class="card-header bg-primary text-white">
            <h2 class="mb-0">Unirse a Equipo Existente</h2>
        </div>
        <div class="card-body">
            <form method="post">
                <div class="mb-3">
                    <label for="codigo_equipo" class="form-label">Código del Equipo</label>
                    <input type="text" class="form-control" id="codigo_equipo" name="codigo_equipo" required 
                           placeholder="Ingresa el código de 6 caracteres" maxlength="6">
                    <div class="form-text">Pide el código al capitán del equipo.</div>
                </div>
                <div class="mb-3">
                    <label for="nombre" class="form-label">Nombre completo</label>
                    <input type="text" class="form-control" id="nombre" name="nombre" required>
                </div>
                <div class="mb-3">
                    <label for="cedula" class="form-label">Número de cédula</label>
                    <input type="text" class="form-control" id="cedula" name="cedula" required 
                           pattern="\d+" maxlength="20" inputmode="numeric">
                </div>
                <div class="d-flex justify-content-between">
                    <button type="submit" class="btn btn-success">Unirse al Equipo</button>
                    <a href="index.php" class="btn btn-secondary">Volver al Inicio</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Validación solo números para cédula
document.getElementById('cedula').addEventListener('input', function() {
    this.value = this.value.replace(/\D/g, '');
});
</script>
</body>
</html>