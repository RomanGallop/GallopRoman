<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/funciones.php';

if (!usuario_esta_logueado()) {
    header("Location: index.php");
    exit;
}

$usuario = obtener_usuario_logueado();
$idUsuario = $usuario['id'];
$rol = $usuario['rol'];

$mensajes = [];

// ACA ES LA PARTE DONDE SE CARGAN LOS DATOS DE LOS ALUMNOS QUE PONEN LOS PROFESORES

if (isset($_POST['add_nota_asistencia']) && $rol === 'Profesor') {

    $id_alumno_clase = (int)$_POST['alumno_clase'];
    $tipo = $_POST['tipo'];
    $valor = floatval($_POST['valor']);
    $fecha = date('Y-m-d');
    $periodo = $_POST['tipo'];
    $total_clases = (int)$_POST['total'];
    $asistencias = (int)$_POST['asistencias'];
    $inasistencias = max(0, $total_clases - $asistencias);

    // Verifica si el profesor puede poner los datos del alumno
    if (puede_editar_clase($pdo, $idUsuario, $_POST['id_clase'])) {
        $consulta_existencia_nota = $pdo->prepare("SELECT COUNT(*) FROM nota WHERE ID_Alumno_Clase = ? AND Tipo = ?");
        $consulta_existencia_nota->execute([$id_alumno_clase, $tipo]);
        $nota_existe = $consulta_existencia_nota->fetchColumn() > 0;

        $consulta_existencia_asistencia = $pdo->prepare("SELECT COUNT(*) FROM alumno_asistencia WHERE ID_Alumno_Clase = ? AND Periodo = ?");
        $consulta_existencia_asistencia->execute([$id_alumno_clase, $periodo]);
        $asistencia_existe = $consulta_existencia_asistencia->fetchColumn() > 0;

        // Verificar si ya hay notas o si las asistencias estan cargadas
        if ($nota_existe || $asistencia_existe) {
            $mensajes[] = "Ya existe nota y/o asistencia para este cuatrimestre.";
        } else {
            $consulta_insertar_nota = $pdo->prepare("INSERT INTO nota (ID_Alumno_Clase, Tipo, Valor, Fecha) VALUES (?, ?, ?, ?)");
            $consulta_insertar_nota->execute([$id_alumno_clase, $tipo, $valor, $fecha]);

            $consulta_insertar_asistencia = $pdo->prepare("INSERT INTO alumno_asistencia (ID_Alumno_Clase, Periodo, Total_Clases, Asistencias, Inasistencias) VALUES (?, ?, ?, ?, ?)");
            $consulta_insertar_asistencia->execute([$id_alumno_clase, $periodo, $total_clases, $asistencias, $inasistencias]);

            $mensajes[] = "Nota y asistencia cargadas.";
        }
    } else {
        $mensajes[] = "No tenés permiso para esta clase.";
    }
}

// DATOS DEL PROFESORES

$institutos = [];
$clases = [];
$alumnos = [];

$idInstitutoSeleccionado = $_GET['instituto'] ?? null;
$idClaseSeleccionada = $_GET['clase'] ?? null;

if ($rol === 'Profesor') {
    $institutos = obtener_institutos_usuario($pdo, $idUsuario);

    if ($idInstitutoSeleccionado) {
        $clases = obtener_clases_profesor_instituto($pdo, $idUsuario, $idInstitutoSeleccionado);
    }

    if ($idClaseSeleccionada) {
        $alumnos = obtener_alumnos_clase($pdo, $idClaseSeleccionada);
    }
}

// DATOS DE LOS ALUMNOS

$clases_alumno = [];
$detalle_clase = null;
$institutos_alumno = [];

if ($rol === 'Alumno') {
    $clases_alumno = obtener_clases_usuario($pdo, $idUsuario, 'Alumno');

    // Te busca a que escuela pertenece el alumno logueado
    $consulta_institutos = $pdo->prepare(
        "SELECT DISTINCT i.ID_Instituto, i.Nombre
        FROM clase c
        JOIN instituto i ON i.ID_Instituto = c.ID_Instituto
        JOIN alumno_clase ac ON ac.ID_Clase = c.ID_Clase
        JOIN usuario u ON u.ID_Alumno = ac.ID_Alumno
        WHERE u.ID_Usuario = ?"
    );

    $consulta_institutos->execute([$idUsuario]);
    $institutos_alumno = $consulta_institutos->fetchAll(PDO::FETCH_ASSOC);

    // Busca la clase del alumno
    if (isset($_GET['clase'])) {
        $idClase = (int)$_GET['clase'];

        $consulta_clase = $pdo->prepare("
            SELECT c.Nombre AS NombreClase, ac.ID_Alumno_Clase
            FROM alumno_clase ac
            JOIN clase c ON c.ID_Clase = ac.ID_Clase
            JOIN usuario u ON u.ID_Alumno = ac.ID_Alumno
            WHERE u.ID_Usuario = ? AND c.ID_Clase = ?
        ");
        $consulta_clase->execute([$idUsuario, $idClase]);
        $detalle_clase = $consulta_clase->fetch(PDO::FETCH_ASSOC);

        // Aca muestra en detalle las notas y las asistencias
        if ($detalle_clase) {
            $id_alumno_clase = $detalle_clase['ID_Alumno_Clase'];

            // Notas
            $consulta_notas = $pdo->prepare("SELECT Tipo, Valor, Fecha FROM nota WHERE ID_Alumno_Clase = ?");
            $consulta_notas->execute([$id_alumno_clase]);
            $detalle_clase['Notas'] = $consulta_notas->fetchAll(PDO::FETCH_ASSOC);

            // Asistencias 
            $consulta_asistencias = $pdo->prepare("
                SELECT Periodo, Total_Clases, Asistencias, Inasistencias
                FROM alumno_asistencia
                WHERE ID_Alumno_Clase = ? ");
            $consulta_asistencias->execute([$id_alumno_clase]);
            $detalle_clase['Asistencias'] = $consulta_asistencias->fetchAll(PDO::FETCH_ASSOC);

            // ACA SE CALCULA EL ESTADO DEL ALUMNO
            $notas = array_column($detalle_clase['Notas'], 'Valor', 'Tipo');
            $totalAsistencias = 0;
            $totalInasistencias = 0;
            $totalClases = 0;

            foreach ($detalle_clase['Asistencias'] as $asistencia) {
                $totalAsistencias += $asistencia['Asistencias'];
                $totalInasistencias += $asistencia['Inasistencias'];
                $totalClases += $asistencia['Total_Clases'];
            }

            $motivo = '';
            $estado = 'Pendiente';

            // aca se calcula el porcentaje de asistencia
            $asistencia = ($totalClases > 0) ? ($totalAsistencias / $totalClases) : 0;

            // Se pide al menos 70% de asistencia (si tiene el 30% o mas de las inasistencias queda libre)
            if ($asistencia < 0.7) {
                $estado = "Libre";
                $motivo = 'inasistencias >30%';
            } else {

                // Si hay ambas parciales registradas, evaluamos las notas
                if (isset($notas['1C']) && isset($notas['2C'])) {
                    $nota1 = floatval($notas['1C']);
                    $nota2 = floatval($notas['2C']);

                    // Si no aprueba alguno de los parciales queda libre
                    if ($nota1 < 6 || $nota2 < 6) {
                        $estado = "Libre";
                        $motivo = 'no aprobó alguno de los parciales';
                    } elseif ($nota1 >= 7 && $nota2 >= 7) {
                        $estado = "Promocionado"; // Si aprueba ambos parciales con nota mayor o igual a 7
                    } elseif ($nota1 = 6 && $nota2 = 6) {
                        $estado = "Regular"; // Si aprueba ambos parciales con nota 6
                    }
                } else {
                    // Si no se carga alguna de las dos notas aparece como pendiente, hasta que se suba la otra nota
                    $estado = 'Pendiente';
                    $motivo = 'Faltan parciales para evaluar';
                }
            }
            $detalle_clase['PorcentajeAsistencia'] = round($asistencia * 100, 2) . '%';
            $detalle_clase['EstadoFinal'] = $estado;
            $detalle_clase['MotivoEstado'] = $motivo;

            // ACA SE CALCULA EL TOTAL DE LOS DATOS DE LA ASISTENCIA
            $totalAsistencias = 0;
            $totalInasistencias = 0;
            $totalClases = 0;

            foreach ($detalle_clase['Asistencias'] as $asistencia) {
                $totalAsistencias += $asistencia['Asistencias'];
                $totalInasistencias += $asistencia['Inasistencias'];
                $totalClases += $asistencia['Total_Clases'];
            }
            $detalle_clase['TotalAsistencias'] = $totalAsistencias;
            $detalle_clase['TotalInasistencias'] = $totalInasistencias;
            $detalle_clase['TotalClases'] = $totalClases;
        }
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Sistema de Gestión de Alumnos y Profesores</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="icon" type="image/png" href="icons/user-group-man-man.png">
</head>

<body>
    <div class="dashboard-header">
        <h1>Bienvenido, <?= htmlspecialchars($usuario['username']) ?> (<?= $rol ?>)</h1>
        <?php if ($rol === 'Alumno' && !empty($institutos_alumno)): ?>
            <p style="margin: 5px 0; font-size: 0.9em; color: #666;">
                Escuela: <?= htmlspecialchars(implode(', ', array_column($institutos_alumno, 'Nombre'))) ?>
            </p>
        <?php endif; ?>
        <a href="logout.php">Cerrar sesión</a>
    </div>
    <?php foreach ($mensajes as $mensaje): ?>
        <p class="success"><?= $mensaje ?></p>
    <?php endforeach; ?>

    <?php if ($rol === 'Profesor'): ?>
        <?php if (!$idInstitutoSeleccionado): ?>
            <h2>Seleccionar Instituto</h2>
            <ul>
                <?php foreach ($institutos as $instituto): ?>
                    <li>
                        <a href="?instituto=<?= $instituto['ID_Instituto'] ?>"><?= htmlspecialchars($instituto['Nombre']) ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php elseif (!$idClaseSeleccionada): ?>
            <h2>Materias / Clases</h2>
            <ul>
                <?php foreach ($clases as $clase): ?>
                    <li>
                        <a href="?instituto=<?= $idInstitutoSeleccionado ?>&clase=<?= $clase['ID_Clase'] ?>">
                            <?= htmlspecialchars($clase['Nombre']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if ($idClaseSeleccionada): ?>
            <div class="change-links">
                <a class="change-link" href="dashboard.php">Cambiar escuela</a>
                <a class="change-link" href="dashboard.php?instituto=<?= $idInstitutoSeleccionado ?>">Cambiar materia</a>
            </div>
            <h2>Alumnos de la Clase</h2>
            <?php foreach ($alumnos as $alumno): ?>
                <div class="card">
                    <p class="student-name"> <?= $alumno['Nombre'] . " " . $alumno['Apellido'] ?></p>
                    <?php
                    $id_alumno_clase = $alumno['ID_Alumno_Clase'];

                    // Estado de cada instancia
                    $consulta_tipos_nota = $pdo->prepare("SELECT Tipo FROM nota WHERE ID_Alumno_Clase = ?");
                    $consulta_tipos_nota->execute([$id_alumno_clase]);
                    $tipos_nota_cargados = array_column($consulta_tipos_nota->fetchAll(PDO::FETCH_ASSOC), 'Tipo');

                    $consulta_periodos_asistencia = $pdo->prepare("SELECT Periodo FROM alumno_asistencia WHERE ID_Alumno_Clase = ?");
                    $consulta_periodos_asistencia->execute([$id_alumno_clase]);
                    $periodos_asistencia_cargados = array_column($consulta_periodos_asistencia->fetchAll(PDO::FETCH_ASSOC), 'Periodo');

                    $ya_cargo_1C = in_array('1C', $tipos_nota_cargados) && in_array('1C', $periodos_asistencia_cargados);
                    $ya_cargo_2C = in_array('2C', $tipos_nota_cargados) && in_array('2C', $periodos_asistencia_cargados);
                    $ya_cargo_Final = in_array('Final', $tipos_nota_cargados);
                    ?>
                    <div style="display:flex; flex-wrap:wrap; gap:18px 32px;">

                        <!-- Datos del 1C -->
                        <div class="period-card">
                            <?php if (!$ya_cargo_1C): ?>
                                <form method="post" class="note-form">
                                    <input type="hidden" name="id_clase" value="<?= $idClaseSeleccionada ?>">
                                    <input type="hidden" name="alumno_clase" value="<?= $alumno['ID_Alumno_Clase'] ?>">
                                    <input type="hidden" name="tipo" value="1C">
                                    <label>Nota 1C: <input type="number" name="valor" step="0.01" min="0" max="10" required></label>
                                    <label>Total Clases: <input type="number" name="total" required></label>
                                    <label>Asistencias: <input type="number" name="asistencias" required></label>
                                    <button type="submit" name="add_nota_asistencia">Cargar 1C</button>
                                </form>
                            <?php else: ?>
                                <div class="success" style="margin:10px 0;"> 1C cargado</div>
                            <?php endif; ?>
                        </div>

                        <!-- Datos del 2C -->
                        <div class="period-card">
                            <?php if (!$ya_cargo_2C): ?>
                                <form method="post" class="note-form">
                                    <input type="hidden" name="id_clase" value="<?= $idClaseSeleccionada ?>">
                                    <input type="hidden" name="alumno_clase" value="<?= $alumno['ID_Alumno_Clase'] ?>">
                                    <input type="hidden" name="tipo" value="2C">
                                    <label>Nota 2C: <input type="number" name="valor" step="0.01" min="0" max="10" required></label>
                                    <label>Total Clases: <input type="number" name="total" required></label>
                                    <label>Asistencias: <input type="number" name="asistencias" required></label>
                                    <button type="submit" name="add_nota_asistencia">Cargar 2C</button>
                                </form>
                            <?php else: ?>
                                <div class="success" style="margin:10px 0;"> 2C cargado</div>
                            <?php endif; ?>
                        </div>

                        <!-- Datos del final -->
                        <div class="period-card">
                            <?php if (!$ya_cargo_Final): ?>
                                <form method="post" class="note-form">
                                    <input type="hidden" name="id_clase" value="<?= $idClaseSeleccionada ?>">
                                    <input type="hidden" name="alumno_clase" value="<?= $alumno['ID_Alumno_Clase'] ?>">
                                    <input type="hidden" name="tipo" value="Final">
                                    <label>Nota Final: <input type="number" name="valor" step="0.01" min="0" max="10" required></label>
                                    <button type="submit" name="add_nota_asistencia">Cargar Final</button>
                                </form>
                            <?php else: ?>
                                <div class="success" style="margin:10px 0;"> Final cargado</div>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    <?php endif; ?>

    <!-- PANTALLA CUANDO ENTRAS COMO ROL DE ALUMNO -->

    <?php if ($rol === 'Alumno'): ?>
        <h2>Mis Clases</h2>
        <ul>
            <?php foreach ($clases_alumno as $clase): ?>
                <li>
                    <a href="?clase=<?= $clase['ID_Clase'] ?>">
                        <?= htmlspecialchars($clase['Nombre']) ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>

        <?php if ($detalle_clase): ?>
            <div class="card">
                <h3><?= $detalle_clase['NombreClase'] ?></h3>
                <h4>Notas</h4>
                <ul>
                    <?php foreach ($detalle_clase['Notas'] as $nota): ?>
                        <li><?= $nota['Tipo'] ?>: <?= $nota['Valor'] ?> (<?= $nota['Fecha'] ?>)</li>
                    <?php endforeach; ?>
                </ul>
                <h4>Asistencias (Resumen)</h4>
                <ul>
                    <?php foreach ($detalle_clase['Asistencias'] as $asistencia): ?>
                        <li><?= $asistencia['Periodo'] ?> →
                            Asistencias: <?= $asistencia['Asistencias'] ?>/<?= $asistencia['Total_Clases'] ?>
                            (Inasistencias: <?= $asistencia['Inasistencias'] ?>)
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="totales">
                    <h4>Totales del Año</h4>
                    <p>
                        Total Clases: <?= $detalle_clase['TotalClases'] ?><br>
                        Total Asistencias: <?= $detalle_clase['TotalAsistencias'] ?><br>
                        Total Inasistencias: <?= $detalle_clase['TotalInasistencias'] ?>
                        <br>Porcentaje de Asistencia: <?= isset($detalle_clase['PorcentajeAsistencia']) ? $detalle_clase['PorcentajeAsistencia'] : '0%' ?>
                    </p>
                </div>
                <h4>Estado Final: <?= $detalle_clase['EstadoFinal'] ?></h4>
                <?php if (!empty($detalle_clase['MotivoEstado'])): ?>
                    <div class="alert" style="margin-top:8px; background:#fef3c7; color:#92400e; border:1px solid #fde68a;">
                        Motivo: <?= htmlspecialchars($detalle_clase['MotivoEstado']) ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</body>

</html>