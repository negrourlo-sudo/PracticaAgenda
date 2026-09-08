<?php
session_start();
require 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'cerrar_sesion') {
        $_SESSION = [];
        session_destroy();
        header('Location: index.php');
        exit;
}

if (!isset($_SESSION['id_user'])) {
    header('Location: index.php');
    exit;
}  

$id_user = $_SESSION['usuarios']['id'];
$nombre = $_SESSION['usuarios']['nombre'];
$dia_actual = date('j');
$fecha_hoy = date('Y-m-d');
$conexion = conectar();
$error = '';
$mensaje = '';

function convertirFecha(string $fecha): string
{
    return str_replace('T', ' ', $fecha) . (strlen($fecha) === 16 ? ':00' : '');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'crear_cita') {
        $fecha_ini = $_POST['fecha_ini'] ?? '';
        $fecha_fin = $_POST['fecha_fin'] ?? '';
        $resumen = trim($_POST['resumen'] ?? '');
        $detalle_evento = trim($_POST['detalle_evento'] ?? '');
        $notas = trim($_POST['notas'] ?? '');

        if ($fecha_ini === '' || $fecha_fin === '' || $resumen === '') {
            $error = 'La fecha de inicio, la fecha de fin y el resumen son obligatorios.';
        } elseif (strtotime($fecha_fin) <= strtotime($fecha_ini)) {
            $error = 'La fecha de fin debe ser posterior a la fecha de inicio.';
        } else {
            $se_repite = date('Y-m-d', strtotime($fecha_ini)) !== date('Y-m-d', strtotime($fecha_fin)) ? 1 : 0;
            $stmt = $conexion->prepare(
                'INSERT INTO agenda (fecha_reg, fecha_ini, fecha_fin, resumen, detalle_evento, activo, terminado, se_repite, notas, id_user)
                 VALUES (NOW(), ?, ?, ?, ?, 1, 0, ?, ?, ?)'
            );
            $stmt->execute([
                convertirFecha($fecha_ini),
                convertirFecha($fecha_fin),
                $resumen,
                $detalle_evento,
                $se_repite,
                $notas,
                $id_user
            ]);
            $mensaje = 'Cita creada correctamente.';
        }
    } elseif ($accion === 'cambiar_estado') {
        $id_cita = (int) ($_POST['id_cita'] ?? 0);
        $stmt = $conexion->prepare('UPDATE agenda SET terminado = NOT terminado WHERE id = ? AND id_user = ? AND activo = 1');
        $stmt->execute([$id_cita, $id_user]);
        $mensaje = 'Estado de la cita actualizado.';
    } elseif ($accion === 'eliminar_cita') {
        $id_cita = (int) ($_POST['id_cita'] ?? 0);
        $stmt = $conexion->prepare('DELETE FROM agenda WHERE id = ? AND id_user = ?');
        $stmt->execute([$id_cita, $id_user]);
        $mensaje = 'Cita eliminada.';
    }
}

$stmt = $conexion->prepare('SELECT id, fecha_ini, fecha_fin, resumen, detalle_evento, terminado, se_repite, notas FROM agenda WHERE id_user = ? AND activo = 1 ORDER BY fecha_ini ASC');
$stmt->execute([$id_user]);
$citas = $stmt->fetchAll();
$dias_con_citas = [];
$citas_calendario = [];
foreach ($citas as $cita) {
    $dia_inicio = date('Y-m-d', strtotime($cita['fecha_ini']));
    $dia_fin = date('Y-m-d', strtotime($cita['fecha_fin']));
    $dias_a_marcar = [$dia_inicio];

    if ($cita['se_repite']) {
        $fecha = new DateTime($dia_inicio);
        $fechaFinal = new DateTime($dia_fin);
        while ($fecha < $fechaFinal) {
            $fecha->modify('+1 day');
            $dias_a_marcar[] = $fecha->format('Y-m-d');
        }
    }

    foreach ($dias_a_marcar as $dia_cita) {
        $dias_con_citas[$dia_cita] = true;
        $citas_calendario[$dia_cita][] = [
            'id' => (int) $cita['id'],
            'resumen' => $cita['resumen'],
            'color' => (int) $cita['id'] % 6
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi agenda</title>
    <link rel="stylesheet" href="styles.css?v=4">
</head>
<body class="pagina-agenda">
    <header>
        <div class="encabezado-agenda">
            <div>
                <h1>AGENDA PERSONAL</h1>
                <p>Bienvenido, <?= htmlspecialchars($nombre) ?></p>
            </div>
            <div class="icono-calendario" aria-label="Día actual: <?= $dia_actual ?>" title="Día actual">
                <span class="icono-calendario-mes">HOY</span>
                <span class="icono-calendario-dia"><?= $dia_actual ?></span>
            </div>
        </div>
    </header>

    <nav class="navegacion" aria-label="Navegación principal">
        <a class="boton" href="agenda.php">Mi agenda</a>
        <form method="post" action="agenda.php">
            <input type="hidden" name="accion" value="cerrar_sesion">
            <button class="boton boton-cerrar" type="submit">Cerrar sesión</button>
        </form>
    </nav>

    <main>
        <div class="encabezado-seccion">
            <div>
                <p class="etiqueta-seccion">ORGANIZA TU TIEMPO</p>
                <h2>Mis citas</h2>
            </div>
            <p id="fecha-seleccionada" class="fecha-seleccionada" aria-live="polite"></p>
        </div>

        <section class="panel-calendario" aria-labelledby="titulo-calendario">
            <div class="cabecera-calendario">
                <div>
                    <p class="etiqueta-seccion">VISTA DIARIA</p>
                    <h3 id="titulo-calendario"></h3>
                </div>
                <div class="controles-calendario">
                    <button type="button" class="boton-calendario" id="mes-anterior" aria-label="Mes anterior">&#8249;</button>
                    <button type="button" class="boton-calendario" id="mes-siguiente" aria-label="Mes siguiente">&#8250;</button>
                </div>
            </div>
            <div class="dias-semana" aria-hidden="true">
                <span>LUN</span><span>MAR</span><span>MIÉ</span><span>JUE</span><span>VIE</span><span>SÁB</span><span>DOM</span>
            </div>
            <div class="rejilla-calendario" id="rejilla-calendario" role="grid" aria-label="Calendario"></div>
            <button type="button" class="boton-hoy" id="volver-hoy">Volver a hoy</button>
        </section>

        <?php if ($mensaje): ?>
            <p class="exito"><?= htmlspecialchars($mensaje) ?></p>
        <?php endif; ?>

        <?php if ($error): ?>
            <p class="errores"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <form method="post" action="agenda.php" class="formulario-cita" id="formulario-cita">
            <input type="hidden" name="accion" value="crear_cita">

            <label for="resumen">TAREA A INSCRIBIR</label>
            <input type="text" id="resumen" name="resumen" maxlength="255" required>

            <input type="hidden" id="fecha_ini" name="fecha_ini" required>
            <input type="hidden" id="fecha_fin" name="fecha_fin" required>

            <fieldset class="selector-fechas">
                <legend>Elige los días de la tarea</legend>
                <p class="ayuda-selector">Selecciona una fecha en el calendario para el inicio y otra para el fin.</p>
                <div class="modos-fecha">
                    <button type="button" class="modo-fecha activo" id="modo-inicio" data-ayuda="Pincha aquí en Inicio y elige el primer día en el calendario." title="Activa Inicio y elige el primer día en el calendario.">Inicio: <strong id="texto-fecha-inicio">Sin elegir</strong></button>
                    <button type="button" class="modo-fecha" id="modo-fin" data-ayuda="Pincha aquí en Fin y elige el último día en el calendario." title="Activa Fin y elige el último día en el calendario.">Fin: <strong id="texto-fecha-fin">Sin elegir</strong></button>
                </div>
            </fieldset>

            <details class="selector-horas">
                <summary>Elegir horas</summary>
                <div class="horas-grid">
                    <label for="hora_inicio">Hora de inicio</label>
                    <input type="time" id="hora_inicio" value="09:00" required>
                    <label for="hora_fin">Hora de fin</label>
                    <input type="time" id="hora_fin" value="10:00" required>
                </div>
            </details>

            <label for="detalle_evento">Detalle del evento</label>
            <input type="text" id="detalle_evento" name="detalle_evento" maxlength="255">

            <label for="notas">Notas</label>
            <input type="text" id="notas" name="notas" maxlength="255">

            <div class="estado-repeticion" id="estado-repeticion" aria-live="polite">
                <span class="indicador-repeticion" aria-hidden="true"></span>
                <span>
                    <strong id="titulo-repeticion">Cita de un día</strong>
                    <small id="texto-repeticion">La tarea no se repetirá en otros días.</small>
                </span>
            </div>

            <button type="submit">Guardar cita</button>
        </form>

        <?php if ($citas): ?>
            <div class="tabla-contenedor">
                <table>
                    <thead>
                        <tr>
                            <th>Resumen</th>
                            <th>Inicio</th>
                            <th>Fin</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($citas as $cita): ?>
                            <tr class="<?= $cita['terminado'] ? 'cita-terminada' : '' ?> tarea-color-<?= (int) $cita['id'] % 6 ?>" data-fecha="<?= date('Y-m-d', strtotime($cita['fecha_ini'])) ?>" data-fecha-fin="<?= date('Y-m-d', strtotime($cita['fecha_fin'])) ?>" data-repetida="<?= (int) $cita['se_repite'] ?>">
                                <td><span class="marcador-tarea" aria-hidden="true"></span><?= htmlspecialchars($cita['resumen']) ?></td>
                                <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($cita['fecha_ini']))) ?></td>
                                <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($cita['fecha_fin']))) ?></td>
                                <td><?= $cita['terminado'] ? 'Terminada' : 'Pendiente' ?></td>
                                <td class="acciones">
                                    <form method="post" action="agenda.php">
                                        <input type="hidden" name="accion" value="cambiar_estado">
                                        <input type="hidden" name="id_cita" value="<?= (int) $cita['id'] ?>">
                                        <button type="submit"><?= $cita['terminado'] ? 'Reabrir' : 'Terminar' ?></button>
                                    </form>
                                    <form method="post" action="agenda.php">
                                        <input type="hidden" name="accion" value="eliminar_cita">
                                        <input type="hidden" name="id_cita" value="<?= (int) $cita['id'] ?>">
                                        <button class="boton-cerrar" type="submit">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="sin-citas">No tienes citas guardadas.</p>
        <?php endif; ?>
        <p class="sin-citas sin-citas-dia" id="sin-citas-dia" hidden>No hay tareas para este día.</p>
    </main>
    <script>
        const fechaHoy = '<?= $fecha_hoy ?>';
        const diasConCitas = <?= json_encode(array_keys($dias_con_citas), JSON_UNESCAPED_UNICODE) ?>;
        const citasCalendario = <?= json_encode($citas_calendario, JSON_UNESCAPED_UNICODE) ?>;
        const filasCitas = [...document.querySelectorAll('tbody tr[data-fecha]')];
        const rejillaCalendario = document.getElementById('rejilla-calendario');
        const tituloCalendario = document.getElementById('titulo-calendario');
        const fechaSeleccionada = document.getElementById('fecha-seleccionada');
        const sinCitasDia = document.getElementById('sin-citas-dia');
        const fechaInicio = document.getElementById('fecha_ini');
        const fechaFin = document.getElementById('fecha_fin');
        const horaInicio = document.getElementById('hora_inicio');
        const horaFin = document.getElementById('hora_fin');
        const modoInicio = document.getElementById('modo-inicio');
        const modoFin = document.getElementById('modo-fin');
        const textoFechaInicio = document.getElementById('texto-fecha-inicio');
        const textoFechaFin = document.getElementById('texto-fecha-fin');
        const tituloRepeticion = document.getElementById('titulo-repeticion');
        const textoRepeticion = document.getElementById('texto-repeticion');
        const estadoRepeticion = document.getElementById('estado-repeticion');
        let fechaActiva = fechaHoy;
        let mesVisible = new Date(`${fechaHoy}T12:00:00`);
        let modoFecha = 'inicio';

        const formatearFecha = fecha => new Intl.DateTimeFormat('es-ES', {
            weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'
        }).format(new Date(`${fecha}T12:00:00`));

        function actualizarEstadoRepeticion() {
            const inicio = fechaInicio.value.slice(0, 10);
            const fin = fechaFin.value.slice(0, 10);
            const esRepetida = inicio !== '' && fin !== '' && inicio !== fin;
            tituloRepeticion.textContent = esRepetida ? 'Cita de varios días' : 'Cita de un día';
            textoRepeticion.textContent = esRepetida
                ? 'Se mostrará en cada día desde el inicio hasta el fin.'
                : 'La tarea no se repetirá en otros días.';
            estadoRepeticion.classList.toggle('activa', esRepetida);
        }

        function actualizarFechaFormulario(tipo, fecha) {
            const campo = tipo === 'inicio' ? fechaInicio : fechaFin;
            const hora = tipo === 'inicio' ? horaInicio.value : horaFin.value;
            campo.value = `${fecha}T${hora}`;
            const texto = tipo === 'inicio' ? textoFechaInicio : textoFechaFin;
            texto.textContent = formatearFecha(fecha);
        }

        function cambiarModoFecha(modo) {
            modoFecha = modo;
            modoInicio.classList.toggle('activo', modo === 'inicio');
            modoFin.classList.toggle('activo', modo === 'fin');
        }

        function seleccionarFecha(fecha) {
            fechaActiva = fecha;
            mesVisible = new Date(`${fecha}T12:00:00`);
            fechaSeleccionada.textContent = formatearFecha(fecha);
            filasCitas.forEach(fila => {
                const perteneceAlDia = fila.dataset.fecha === fecha;
                const esRepetida = fila.dataset.repetida === '1';
                const estaDentroDelIntervalo = esRepetida && fecha >= fila.dataset.fecha && fecha <= fila.dataset.fechaFin;
                fila.hidden = !perteneceAlDia && !estaDentroDelIntervalo;
            });
            sinCitasDia.hidden = filasCitas.some(fila => !fila.hidden);
            actualizarEstadoRepeticion();
            renderizarCalendario();
        }

        function elegirFechaDesdeCalendario(fecha) {
            actualizarFechaFormulario(modoFecha, fecha);
            seleccionarFecha(fecha);
            if (modoFecha === 'inicio') {
                cambiarModoFecha('fin');
            }
        }

        function renderizarCalendario() {
            const año = mesVisible.getFullYear();
            const mes = mesVisible.getMonth();
            const primerDia = new Date(año, mes, 1);
            const diasDelMes = new Date(año, mes + 1, 0).getDate();
            const desplazamiento = (primerDia.getDay() + 6) % 7;
            tituloCalendario.textContent = new Intl.DateTimeFormat('es-ES', { month: 'long', year: 'numeric' }).format(primerDia);
            rejillaCalendario.innerHTML = '';

            for (let i = 0; i < desplazamiento; i += 1) {
                rejillaCalendario.append(document.createElement('span'));
            }

            for (let dia = 1; dia <= diasDelMes; dia += 1) {
                const fecha = `${año}-${String(mes + 1).padStart(2, '0')}-${String(dia).padStart(2, '0')}`;
                const boton = document.createElement('button');
                boton.type = 'button';
                boton.className = 'dia-calendario';
                boton.textContent = dia;
                boton.dataset.fecha = fecha;
                boton.setAttribute('aria-label', formatearFecha(fecha));
                boton.setAttribute('aria-pressed', fecha === fechaActiva);
                if (fecha === fechaActiva) boton.classList.add('seleccionado');
                if (fecha === fechaHoy) boton.classList.add('hoy');
                if (diasConCitas.includes(fecha)) {
                    boton.classList.add('tiene-citas');
                    const marcadores = document.createElement('span');
                    marcadores.className = 'marcadores-dia';
                    citasCalendario[fecha].slice(0, 4).forEach(cita => {
                        const marcador = document.createElement('span');
                        marcador.className = `marcador-dia color-${cita.color}`;
                        marcador.title = cita.resumen;
                        marcadores.append(marcador);
                    });
                    boton.append(marcadores);
                    boton.title = citasCalendario[fecha].map(cita => cita.resumen).join(', ');
                }
                boton.addEventListener('click', () => elegirFechaDesdeCalendario(fecha));
                rejillaCalendario.append(boton);
            }
        }

        document.getElementById('mes-anterior').addEventListener('click', () => {
            mesVisible.setMonth(mesVisible.getMonth() - 1);
            renderizarCalendario();
        });
        document.getElementById('mes-siguiente').addEventListener('click', () => {
            mesVisible.setMonth(mesVisible.getMonth() + 1);
            renderizarCalendario();
        });
        document.getElementById('volver-hoy').addEventListener('click', () => {
            mesVisible = new Date(`${fechaHoy}T12:00:00`);
            seleccionarFecha(fechaHoy);
        });
        modoInicio.addEventListener('click', () => cambiarModoFecha('inicio'));
        modoFin.addEventListener('click', () => cambiarModoFecha('fin'));
        horaInicio.addEventListener('change', () => { if (fechaInicio.value) actualizarFechaFormulario('inicio', fechaInicio.value.slice(0, 10)); });
        horaFin.addEventListener('change', () => { if (fechaFin.value) actualizarFechaFormulario('fin', fechaFin.value.slice(0, 10)); });
        seleccionarFecha(fechaHoy);
    </script>
</body>
</html>