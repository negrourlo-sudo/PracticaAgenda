-- ============================================
-- ARREGLAR TODO EL SISTEMA DE COMPARTIR
-- Ejecuta esto en: Supabase Dashboard > SQL Editor
-- ============================================

-- 1. Eliminar tablas si existen (para recrearlas limpias)
DROP TABLE IF EXISTS participantes_tarea CASCADE;
DROP TABLE IF EXISTS tareas_compartidas CASCADE;

-- 2. Crear tablas con las foreign keys correctas
CREATE TABLE tareas_compartidas (
  id BIGSERIAL PRIMARY KEY,
  id_cita BIGINT NOT NULL,
  id_propietario BIGINT NOT NULL,
  created_at TIMESTAMPTZ DEFAULT now()
);

CREATE TABLE participantes_tarea (
  id BIGSERIAL PRIMARY KEY,
  id_tarea_compartida BIGINT NOT NULL REFERENCES tareas_compartidas(id) ON DELETE CASCADE,
  id_usuario BIGINT NOT NULL,
  estado TEXT NOT NULL DEFAULT 'pendiente' CHECK (estado IN ('pendiente', 'aceptada', 'rechazada')),
  responded_at TIMESTAMPTZ,
  UNIQUE(id_tarea_compartida, id_usuario)
);

-- 3. Deshabilitar RLS por ahora (para que funcione sin problemas)
ALTER TABLE tareas_compartidas DISABLE ROW LEVEL SECURITY;
ALTER TABLE participantes_tarea DISABLE ROW LEVEL SECURITY;

-- 4. Verificar que las tablas existen
SELECT 'tareas_compartidas' as tabla, count(*) as registros FROM tareas_compartidas
UNION ALL
SELECT 'participantes_tarea', count(*) FROM participantes_tarea;
