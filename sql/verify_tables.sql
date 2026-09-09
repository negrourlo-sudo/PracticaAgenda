-- ============================================
-- VERIFICAR QUE LAS TABLAS EXISTEN
-- Ejecuta esto en: Supabase Dashboard > SQL Editor
-- ============================================

-- Verificar tablas
SELECT table_name FROM information_schema.tables
WHERE table_schema = 'public'
AND table_name IN ('tareas_compartidas', 'participantes_tarea', 'agenda', 'usuarios');

-- Verificar columnas de tareas_compartidas
SELECT column_name, data_type, is_nullable
FROM information_schema.columns
WHERE table_name = 'tareas_compartidas'
ORDER BY ordinal_position;

-- Verificar columnas de participantes_tarea
SELECT column_name, data_type, is_nullable
FROM information_schema.columns
WHERE table_name = 'participantes_tarea'
ORDER BY ordinal_position;

-- Verificar foreign keys
SELECT
    tc.table_name,
    kcu.column_name,
    ccu.table_name AS foreign_table_name,
    ccu.column_name AS foreign_column_name
FROM information_schema.table_constraints AS tc
JOIN information_schema.key_column_usage AS kcu
    ON tc.constraint_name = kcu.constraint_name
JOIN information_schema.constraint_column_usage AS ccu
    ON ccu.constraint_name = tc.constraint_name
WHERE tc.constraint_type = 'FOREIGN KEY'
AND tc.table_name IN ('tareas_compartidas', 'participantes_tarea');
