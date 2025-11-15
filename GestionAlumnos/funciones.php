<?php

// Verifica si el usuario está logueado
function usuario_esta_logueado()
{
    return isset($_SESSION['usuario']);
}

function obtener_usuario_logueado()
{
    return $_SESSION['usuario'] ?? null;
}

// Verifica si el usuario es profesor de una clase
function usuario_es_profesor_de_clase(PDO $pdo, $idUsuario, $idClase)
{
    $consulta = $pdo->prepare("
        SELECT COUNT(*) 
        FROM clase c 
        JOIN usuario u ON u.ID_Profesor = c.ID_Profesor 
        WHERE u.ID_Usuario = ? AND c.ID_Clase = ?
    ");
    $consulta->execute([$idUsuario, $idClase]);
    return $consulta->fetchColumn() > 0;
}

// Verifica si el usuario es alumno de una clase
function usuario_es_alumno_de_clase(PDO $pdo, $idUsuario, $idClase)
{
    $consulta = $pdo->prepare("
        SELECT COUNT(*) 
        FROM alumno_clase ac 
        JOIN usuario u ON u.ID_Alumno = ac.ID_Alumno 
        WHERE u.ID_Usuario = ? AND ac.ID_Clase = ?
    ");
    $consulta->execute([$idUsuario, $idClase]);
    return $consulta->fetchColumn() > 0;
}

// El profesor puede editar datos de las clases que el da
function puede_editar_clase(PDO $pdo, $idUsuario, $idClase)
{
    return usuario_es_profesor_de_clase($pdo, $idUsuario, $idClase);
}

// Aca te permite ver los datos de un alumno en una clase especifica
function puede_ver_alumno_clase(PDO $pdo, $idUsuario, $id_alumno_clase)
{
    $consulta = $pdo->prepare("
        SELECT ID_Clase, ID_Alumno 
        FROM alumno_clase 
        WHERE ID_Alumno_Clase = ?
    ");
    $consulta->execute([$id_alumno_clase]);
    $fila = $consulta->fetch(PDO::FETCH_ASSOC);
    if (!$fila) return false;

    $idClase  = $fila['ID_Clase'];
    $idAlumno = $fila['ID_Alumno'];

    if (usuario_es_profesor_de_clase($pdo, $idUsuario, $idClase)) return true;

    $consulta2 = $pdo->prepare("SELECT ID_Alumno FROM usuario WHERE ID_Usuario = ?");
    $consulta2->execute([$idUsuario]);
    $usuario = $consulta2->fetch(PDO::FETCH_ASSOC);

    return ($usuario && $usuario['ID_Alumno'] == $idAlumno);
}

// ================================
// USUARIO 
// ================================
function obtener_clases_usuario(PDO $pdo, $idUsuario, $filtroRol = null)
{
    if ($filtroRol === 'Profesor') {
        $sql = "
            SELECT c.*
            FROM clase c 
            JOIN usuario u ON u.ID_Profesor = c.ID_Profesor 
            WHERE u.ID_Usuario = ?
        ";
        $consulta = $pdo->prepare($sql);
        $consulta->execute([$idUsuario]);
        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    if ($filtroRol === 'Alumno') {
        $sql = "
            SELECT c.*
            FROM clase c 
            JOIN alumno_clase ac ON ac.ID_Clase = c.ID_Clase 
            JOIN usuario u ON u.ID_Alumno = ac.ID_Alumno 
            WHERE u.ID_Usuario = ?
        ";
        $consulta = $pdo->prepare($sql);
        $consulta->execute([$idUsuario]);
        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Muestra los institutos donde el profesor da clases
function obtener_institutos_usuario(PDO $pdo, $idUsuario)
{
    $consulta = $pdo->prepare("
        SELECT DISTINCT i.ID_Instituto, i.Nombre
        FROM clase c
        JOIN instituto i ON i.ID_Instituto = c.ID_Instituto
        WHERE c.ID_Profesor = (
            SELECT ID_Profesor FROM usuario WHERE ID_Usuario = ?
        )
    ");
    $consulta->execute([$idUsuario]);
    return $consulta->fetchAll(PDO::FETCH_ASSOC);
}

// Muestra las materias que da el profesor en un colegio
function obtener_clases_profesor_instituto(PDO $pdo, $idUsuario, $idInstituto)
{
    $consulta = $pdo->prepare("
        SELECT c.* 
        FROM clase c
        WHERE c.ID_Instituto = ?
        AND c.ID_Profesor = (
            SELECT ID_Profesor FROM usuario WHERE ID_Usuario = ?
        )
    ");
    $consulta->execute([$idInstituto, $idUsuario]);
    return $consulta->fetchAll(PDO::FETCH_ASSOC);
}

// Muestra los alumnos de la clase
function obtener_alumnos_clase(PDO $pdo, $idClase)
{
    $consulta = $pdo->prepare("
        SELECT ac.ID_Alumno_Clase, al.Nombre, al.Apellido
        FROM alumno_clase ac
        JOIN alumno al ON ac.ID_Alumno = al.ID_Alumno
        WHERE ac.ID_Clase = ?
    ");
    $consulta->execute([$idClase]);
    return $consulta->fetchAll(PDO::FETCH_ASSOC);
}

// Funciones de renderizado

function renderizar_input_texto($nombre, $placeholder, $icono_svg = null)
{
    $html_icono = $icono_svg ? "<span class=\"input-icon\">$icono_svg</span>" : "";
    return "<div class=\"field\">$html_icono<input type=\"text\" name=\"" . htmlspecialchars($nombre) . "\" placeholder=\"" . htmlspecialchars($placeholder) . "\" required class=\"input\"></div>";
}

function renderizar_input_password($nombre, $placeholder, $icono_svg = null)
{
    $html_icono = $icono_svg ? "<span class=\"input-icon\">$icono_svg</span>" : "";
    return "<div class=\"field\">$html_icono<input type=\"password\" name=\"" . htmlspecialchars($nombre) . "\" placeholder=\"" . htmlspecialchars($placeholder) . "\" required class=\"input\"></div>";
}

function renderizar_boton_enviar($texto)
{
    return "<div class=\"field\"><button type=\"submit\" class=\"btn\">" . htmlspecialchars($texto) . "</button></div>";
}

function renderizar_formulario_nota_asistencia($periodo, $id_clase, $id_alumno_clase)
{
    return '
    <form method="post" class="note-form">
        <input type="hidden" name="id_clase" value="' . $id_clase . '">
        <input type="hidden" name="alumno_clase" value="' . $id_alumno_clase . '">
        <input type="hidden" name="tipo" value="' . $periodo . '">
        <label>Nota ' . $periodo . ': <input type="number" name="valor" step="0.01" required></label>
        <label>Total Clases: <input type="number" name="total" required></label>
        <label>Asistencias: <input type="number" name="asistencias" required></label>
        <label>Inasistencias: <input type="number" name="inasistencias" required></label>
        <button type="submit" name="add_nota_asistencia">Cargar ' . $periodo . '</button>
    </form>';
}

function renderizar_formulario_nota_final($tipo, $id_clase, $id_alumno_clase)
{
    return '
    <form method="post" class="note-form">
        <input type="hidden" name="id_clase" value="' . $id_clase . '">
        <input type="hidden" name="alumno_clase" value="' . $id_alumno_clase . '">
        <input type="hidden" name="tipo" value="' . $tipo . '">
        <label>Nota ' . $tipo . ': <input type="number" name="valor" step="0.01" required></label>
        <button type="submit" name="add_nota_asistencia">Cargar ' . $tipo . '</button>
    </form>';
}