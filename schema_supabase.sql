-- Schema para Supabase (PostgreSQL)
-- Ejecutar en el SQL Editor de Supabase

CREATE TABLE usuarios (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    activo BOOLEAN DEFAULT true,
    email VARCHAR(255) UNIQUE NOT NULL,
    movil VARCHAR(255),
    clave VARCHAR(255) NOT NULL
);

CREATE TABLE agenda (
    id SERIAL PRIMARY KEY,
    fecha_reg TIMESTAMP DEFAULT NOW(),
    fecha_ini TIMESTAMP NOT NULL,
    fecha_fin TIMESTAMP NOT NULL,
    resumen VARCHAR(255) NOT NULL,
    detalle_evento VARCHAR(255),
    activo BOOLEAN DEFAULT true,
    terminado BOOLEAN DEFAULT false,
    se_repite BOOLEAN DEFAULT false,
    notas VARCHAR(255),
    id_user INTEGER REFERENCES usuarios(id)
);

-- Habilitar acceso desde el cliente (para desarrollo)
ALTER TABLE usuarios ENABLE ROW LEVEL SECURITY;
ALTER TABLE agenda ENABLE ROW LEVEL SECURITY;

CREATE POLICY "Acceso total usuarios" ON usuarios FOR ALL USING (true) WITH CHECK (true);
CREATE POLICY "Acceso total agenda" ON agenda FOR ALL USING (true) WITH CHECK (true);
