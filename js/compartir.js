// ============================================
// SISTEMA DE TAREAS COMPARTIDAS
// ============================================

// Obtener todos los usuarios excepto el actual
async function listarUsuarios(usuarioActualId) {
    const { data, error } = await sb
        .from('usuarios')
        .select('id, nombre, email')
        .neq('id', usuarioActualId)
        .eq('activo', true)
        .order('nombre');
    if (error) throw error;
    return data;
}

// Compartir una cita con uno o varios usuarios
async function compartirCita(idCita, idPropietario, idsUsuarios) {
    // 1. Crear registro en tareas_compartidas
    const { data: compartida, error: err1 } = await sb
        .from('tareas_compartidas')
        .insert([{
            id_cita: idCita,
            id_propietario: idPropietario
        }])
        .select()
        .single();
    if (err1) throw err1;

    // 2. Crear invitaciones para cada usuario seleccionado
    const invitaciones = idsUsuarios.map(idUsu => ({
        id_tarea_compartida: compartida.id,
        id_usuario: idUsu,
        estado: 'pendiente'
    }));

    const { error: err2 } = await sb
        .from('participantes_tarea')
        .insert(invitaciones);
    if (err2) throw err2;

    return compartida;
}

// Obtener solicitudes pendientes de un usuario
async function obtenerSolicitudesPendientes(usuarioId) {
    const { data, error } = await sb
        .from('participantes_tarea')
        .select(`
            id,
            estado,
            responded_at,
            id_tarea_compartida,
            tareas_compartidas (
                id,
                id_cita,
                id_propietario,
                agenda (
                    id,
                    resumen,
                    fecha_ini,
                    fecha_fin,
                    detalle_evento
                ),
                usuarios: id_propietario (
                    id,
                    nombre
                )
            )
        `)
        .eq('id_usuario', usuarioId)
        .order('id', { ascending: false });
    if (error) throw error;
    return data;
}

// Obtener todas las solicitudes (pendientes + respondidas)
async function obtenerTodasSolicitudes(usuarioId) {
    const { data, error } = await sb
        .from('participantes_tarea')
        .select(`
            id,
            estado,
            responded_at,
            id_tarea_compartida,
            tareas_compartidas (
                id,
                id_cita,
                id_propietario,
                agenda (
                    id,
                    resumen,
                    fecha_ini,
                    fecha_fin,
                    detalle_evento
                ),
                usuarios: id_propietario (
                    id,
                    nombre
                )
            )
        `)
        .eq('id_usuario', usuarioId)
        .order('id', { ascending: false });
    if (error) throw error;
    return data;
}

// Responder a una solicitud (aceptar o rechazar)
async function responderSolicitud(idSolicitud, estado) {
    const { error } = await sb
        .from('participantes_tarea')
        .update({
            estado,
            responded_at: new Date().toISOString()
        })
        .eq('id', idSolicitud);
    if (error) throw error;
}

// Obtener participantes de una tarea compartida
async function obtenerParticipantes(idTareaCompartida) {
    const { data, error } = await sb
        .from('participantes_tarea')
        .select(`
            id,
            estado,
            usuarios (
                id,
                nombre
            )
        `)
        .eq('id_tarea_compartida', idTareaCompartida);
    if (error) throw error;
    return data;
}

// Verificar si una cita ya está compartida por el usuario
async function citaYaCompartida(idCita) {
    const { data, error } = await sb
        .from('tareas_compartidas')
        .select('id')
        .eq('id_cita', idCita)
        .maybeSingle();
    if (error) throw error;
    return data;
}

// Obtener tareas compartidas donde el usuario es participante aceptado
async function obtenerTareasAceptadas(usuarioId) {
    const { data, error } = await sb
        .from('participantes_tarea')
        .select(`
            id,
            id_tarea_compartida,
            tareas_compartidas (
                id,
                id_cita,
                id_propietario,
                agenda (
                    id,
                    resumen,
                    fecha_ini,
                    fecha_fin,
                    detalle_evento,
                    notas,
                    terminado
                ),
                usuarios: id_propietario (
                    id,
                    nombre
                )
            )
        `)
        .eq('id_usuario', usuarioId)
        .eq('estado', 'aceptada');
    if (error) throw error;
    return data;
}

// Eliminar una tarea compartida (el propietario puede eliminar la invitación)
async function eliminarTareaCompartida(idTareaCompartida) {
    const { error } = await sb
        .from('tareas_compartidas')
        .delete()
        .eq('id', idTareaCompartida);
    if (error) throw error;
}

// Eliminar invitación individual
async function eliminarInvitacion(idInvitacion) {
    const { error } = await sb
        .from('participantes_tarea')
        .delete()
        .eq('id', idInvitacion);
    if (error) throw error;
}
