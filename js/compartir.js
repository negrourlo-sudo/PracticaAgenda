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
    const { data: compartida, error: err1 } = await sb
        .from('tareas_compartidas')
        .insert([{
            id_cita: idCita,
            id_propietario: idPropietario
        }])
        .select()
        .single();
    if (err1) throw err1;

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

// Obtener solicitudes de un usuario (sin joins, consultas separadas)
async function obtenerTodasSolicitudes(usuarioId) {
    console.log('[COMPARTIR] Buscando solicitudes para usuario:', usuarioId, typeof usuarioId);

    // 1. Obtener mis participaciones
    const { data: participaciones, error: err1 } = await sb
        .from('participantes_tarea')
        .select('id, estado, responded_at, id_tarea_compartida')
        .eq('id_usuario', usuarioId)
        .order('id', { ascending: false });
    if (err1) { console.error('[COMPARTIR] Error participaciones:', err1); throw err1; }
    console.log('[COMPARTIR] Participaciones encontradas:', participaciones);
    if (!participaciones || participaciones.length === 0) return [];

    // 2. Obtener las tareas compartidas
    const idsCompartidas = [...new Set(participaciones.map(p => p.id_tarea_compartida))];
    const { data: compartidas, error: err2 } = await sb
        .from('tareas_compartidas')
        .select('id, id_cita, id_propietario')
        .in('id', idsCompartidas);
    if (err2) throw err2;

    // 3. Obtener las citas (agenda)
    const idsCitas = [...new Set(compartidas.map(c => c.id_cita))];
    const { data: citas, error: err3 } = await sb
        .from('agenda')
        .select('id, resumen, fecha_ini, fecha_fin, detalle_evento')
        .in('id', idsCitas);
    if (err3) throw err3;

    // 4. Obtener los propietarios (usuarios)
    const idsPropietarios = [...new Set(compartidas.map(c => c.id_propietario))];
    const { data: propietarios, error: err4 } = await sb
        .from('usuarios')
        .select('id, nombre')
        .in('id', idsPropietarios);
    if (err4) throw err4;

    // 5. Combinar todo
    const compartidasMap = {};
    compartidas.forEach(c => { compartidasMap[c.id] = c; });

    const citasMap = {};
    citas.forEach(c => { citasMap[c.id] = c; });

    const propietariosMap = {};
    propietarios.forEach(p => { propietariosMap[p.id] = p; });

    return participaciones.map(p => {
        const comp = compartidasMap[p.id_tarea_compartida] || {};
        const cita = citasMap[comp.id_cita] || {};
        const prop = propietariosMap[comp.id_propietario] || {};
        return {
            id: p.id,
            estado: p.estado,
            responded_at: p.responded_at,
            id_tarea_compartida: p.id_tarea_compartida,
            tarea_compartida: {
                id_cita: comp.id_cita,
                id_propietario: comp.id_propietario
            },
            cita,
            propietario: prop
        };
    });
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

// Obtener tareas aceptadas donde el usuario participa
async function obtenerTareasAceptadas(usuarioId) {
    const { data: participaciones, error: err1 } = await sb
        .from('participantes_tarea')
        .select('id, id_tarea_compartida')
        .eq('id_usuario', usuarioId)
        .eq('estado', 'aceptada');
    if (err1) throw err1;
    if (!participaciones || participaciones.length === 0) return [];

    const idsCompartidas = [...new Set(participaciones.map(p => p.id_tarea_compartida))];
    const { data: compartidas, error: err2 } = await sb
        .from('tareas_compartidas')
        .select('id, id_cita, id_propietario')
        .in('id', idsCompartidas);
    if (err2) throw err2;

    const idsCitas = [...new Set(compartidas.map(c => c.id_cita))];
    const { data: citas, error: err3 } = await sb
        .from('agenda')
        .select('id, resumen, fecha_ini, fecha_fin, detalle_evento, notas, terminado')
        .in('id', idsCitas);
    if (err3) throw err3;

    const idsPropietarios = [...new Set(compartidas.map(c => c.id_propietario))];
    const { data: propietarios, error: err4 } = await sb
        .from('usuarios')
        .select('id, nombre')
        .in('id', idsPropietarios);
    if (err4) throw err4;

    const compartidasMap = {};
    compartidas.forEach(c => { compartidasMap[c.id] = c; });
    const citasMap = {};
    citas.forEach(c => { citasMap[c.id] = c; });
    const propietariosMap = {};
    propietarios.forEach(p => { propietariosMap[p.id] = p; });

    return participaciones.map(p => {
        const comp = compartidasMap[p.id_tarea_compartida] || {};
        const cita = citasMap[comp.id_cita] || {};
        const prop = propietariosMap[comp.id_propietario] || {};
        return { cita, propietario: prop };
    });
}
