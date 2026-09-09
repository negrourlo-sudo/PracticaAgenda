-- ============================================
-- POLICÍAS RLS CORREGIDAS
-- Ejecuta esto en: Supabase Dashboard > SQL Editor
-- ============================================

-- Primero eliminar políticas anteriores si existen
DROP POLICY IF EXISTS "propietario gestiona sus tareas compartidas" ON tareas_compartidas;
DROP POLICY IF EXISTS "propietario inserta participantes" ON participantes_tarea;
DROP POLICY IF EXISTS "propietario ve participantes" ON participantes_tarea;
DROP POLICY IF EXISTS "usuario ve sus invitaciones" ON participantes_tarea;
DROP POLICY IF EXISTS "usuario responde su invitacion" ON participantes_tarea;
DROP POLICY IF EXISTS "usuarios autenticados listan usuarios" ON usuarios;
DROP POLICY IF EXISTS "usuario ve tareas donde participa" ON tareas_compartidas;

-- Políticas simplificadas para tareas_compartidas
CREATE POLICY "authenticated_full_access_tareas_compartidas"
  ON tareas_compartidas FOR ALL
  USING (auth.uid() IS NOT NULL);

-- Políticas simplificadas para participantes_tarea
CREATE POLICY "authenticated_full_access_participantes"
  ON participantes_tarea FOR ALL
  USING (auth.uid() IS NOT NULL);

-- Política para listar usuarios (cualquier autenticado)
CREATE POLICY "authenticated_list_users"
  ON usuarios FOR SELECT
  USING (auth.uid() IS NOT NULL);
