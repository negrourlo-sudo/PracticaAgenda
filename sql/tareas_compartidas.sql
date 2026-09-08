-- ============================================
-- TABLAS PARA SISTEMA DE TAREAS COMPARTIDAS
-- Ejecuta esto en: Supabase Dashboard > SQL Editor
-- ============================================

-- Tabla: tareas_compartidas
-- Vincula una cita existente de agenda para compartirla con otros usuarios
CREATE TABLE IF NOT EXISTS tareas_compartidas (
  id BIGSERIAL PRIMARY KEY,
  id_cita BIGINT NOT NULL REFERENCES agenda(id) ON DELETE CASCADE,
  id_propietario BIGINT NOT NULL REFERENCES usuarios(id) ON DELETE CASCADE,
  created_at TIMESTAMPTZ DEFAULT now()
);

-- Tabla: participantes_tarea
-- Cada fila es una invitación de un usuario a una tarea compartida
CREATE TABLE IF NOT EXISTS participantes_tarea (
  id BIGSERIAL PRIMARY KEY,
  id_tarea_compartida BIGINT NOT NULL REFERENCES tareas_compartidas(id) ON DELETE CASCADE,
  id_usuario BIGINT NOT NULL REFERENCES usuarios(id) ON DELETE CASCADE,
  estado TEXT NOT NULL DEFAULT 'pendiente' CHECK (estado IN ('pendiente', 'aceptada', 'rechazada')),
  responded_at TIMESTAMPTZ,
  UNIQUE(id_tarea_compartida, id_usuario)
);

-- RLS (Row Level Security)
ALTER TABLE tareas_compartidas ENABLE ROW LEVEL SECURITY;
ALTER TABLE participantes_tarea ENABLE ROW LEVEL SECURITY;

-- Política: el propietario puede ver y gestionar sus tareas compartidas
CREATE POLICY "propietario gestiona sus tareas compartidas"
  ON tareas_compartidas FOR ALL
  USING (id_propietario = (SELECT id FROM usuarios WHERE email = auth.uid()::text));

-- Política: el propietario puede insertar participantes en sus tareas
CREATE POLICY "propietario inserta participantes"
  ON participantes_tarea FOR INSERT
  WITH CHECK (
    id_tarea_compartida IN (
      SELECT id FROM tareas_compartidas
      WHERE id_propietario = (SELECT id FROM usuarios WHERE email = auth.uid()::text)
    )
  );

-- Política: el propietario puede ver participantes de sus tareas
CREATE POLICY "propietario ve participantes"
  ON participantes_tarea FOR SELECT
  USING (
    id_tarea_compartida IN (
      SELECT id FROM tareas_compartidas
      WHERE id_propietario = (SELECT id FROM usuarios WHERE email = auth.uid()::text)
    )
  );

-- Política: cualquier usuario puede ver las invitaciones dirigidas a él
CREATE POLICY "usuario ve sus invitaciones"
  ON participantes_tarea FOR SELECT
  USING (
    id_usuario = (SELECT id FROM usuarios WHERE email = auth.uid()::text)
  );

-- Política: el usuario invitado puede actualizar su estado (aceptar/rechazar)
CREATE POLICY "usuario responde su invitacion"
  ON participantes_tarea FOR UPDATE
  USING (
    id_usuario = (SELECT id FROM usuarios WHERE email = auth.uid()::text)
  );

-- Política: cualquier usuario autenticado puede listar usuarios (para seleccionar con quién compartir)
CREATE POLICY "usuarios autenticados listan usuarios"
  ON usuarios FOR SELECT
  USING (true);

-- Política: cualquier usuario autenticado puede ver tareas compartidas donde participa
CREATE POLICY "usuario ve tareas donde participa"
  ON tareas_compartidas FOR SELECT
  USING (
    id IN (
      SELECT id_tarea_compartida FROM participantes_tarea
      WHERE id_usuario = (SELECT id FROM usuarios WHERE email = auth.uid()::text)
        AND estado = 'aceptada'
    )
  );
