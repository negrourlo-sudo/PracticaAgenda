// Auth: login, registro, logout, sesión
async function login(email, password) {
    const { data, error } = await supabase.auth.signInWithPassword({ email, password });
    if (error) throw error;
    return data;
}

async function registro(nombre, email, movil, password) {
    // 1. Crear usuario en auth
    const { data: authData, error: authError } = await supabase.auth.signUp({ email, password });
    if (authError) throw authError;

    // 2. Insertar en tabla usuarios
    const { error: dbError } = await supabase
        .from('usuarios')
        .insert([{ nombre, email, movil, clave: 'AUTH_USER', activo: true }]);
    if (dbError) throw dbError;

    return authData;
}

async function logout() {
    const { error } = await supabase.auth.signOut();
    if (error) throw error;
}

async function getSesion() {
    const { data: { session } } = await supabase.auth.getSession();
    return session;
}

async function getUsuarioActual() {
    const session = await getSesion();
    if (!session) return null;

    const { data } = await supabase
        .from('usuarios')
        .select('id, nombre, email, movil')
        .eq('email', session.user.email)
        .single();

    return data;
}
