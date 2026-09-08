// CRUD de agenda
async function getCitas(usuarioId) {
    const { data, error } = await supabase
        .from('agenda')
        .select('id, fecha_ini, fecha_fin, resumen, detalle_evento, terminado, se_repite, notas')
        .eq('id_user', usuarioId)
        .eq('activo', true)
        .order('fecha_ini', { ascending: true });
    if (error) throw error;
    return data;
}

async function crearCita(usuarioId, fechaIni, fechaFin, resumen, detalle, notas) {
    const seRepite = fechaIni.slice(0, 10) !== fechaFin.slice(0, 10);
    const { error } = await supabase
        .from('agenda')
        .insert([{
            id_user: usuarioId,
            fecha_ini: fechaIni,
            fecha_fin: fechaFin,
            resumen,
            detalle_evento: detalle,
            notas,
            se_repite: seRepite,
            activo: true,
            terminado: false
        }]);
    if (error) throw error;
}

async function cambiarEstadoCita(idCita, usuarioId) {
    // Obtener estado actual
    const { data: cita } = await supabase
        .from('agenda')
        .select('terminado')
        .eq('id', idCita)
        .eq('id_user', usuarioId)
        .single();

    if (!cita) return;

    const { error } = await supabase
        .from('agenda')
        .update({ terminado: !cita.terminado })
        .eq('id', idCita)
        .eq('id_user', usuarioId);
    if (error) throw error;
}

async function eliminarCita(idCita, usuarioId) {
    const { error } = await supabase
        .from('agenda')
        .update({ activo: false })
        .eq('id', idCita)
        .eq('id_user', usuarioId);
    if (error) throw error;
}
