{{-- resources/views/layouts/partials/sidebar.blade.php --}}

<div class="sidebar-content">
    <!-- User Profile Section -->
    @if(isset($usuario) && $usuario)
        <div class="user-profile-section">
            <div class="user-avatar-container">
                <div class="user-avatar">
                    @if(isset($usuario['avatar']) && $usuario['avatar'])
                        <img src="{{ asset('storage/avatars/' . $usuario['avatar']) }}" alt="Avatar" class="avatar-img">
                    @else
                        <i class="fas fa-user-circle"></i>
                    @endif
                </div>
                <div class="user-status-indicator {{ (($usuario['estado']['id'] ?? 0) == 1) ? 'status-online' : 'status-offline' }}"></div>
            </div>
            
            <div class="user-details">
                <h6 class="user-name">{{ $usuario['nombre_completo'] ?? 'Usuario' }}</h6>
                <div class="user-role">
                    <i class="fas fa-user-tag"></i>
                    {{ $usuario['rol']['nombre'] ?? 'Sin rol' }}
                </div>
                <div class="user-location">
                    <i class="fas fa-map-marker-alt"></i>
                    {{ $usuario['sede']['nombre'] ?? 'Sin sede' }}
                </div>
            </div>
        </div>
    @endif

    <!-- Navigation Menu -->
    <nav class="sidebar-nav">
        <!-- Dashboard -->
        <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
            <i class="fas fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>

        <!-- Pacientes -->
        @if(isset($usuario['tipo_usuario']) && (($usuario['tipo_usuario']['es_administrador'] ?? false) || ($usuario['tipo_usuario']['es_secretaria'] ?? false)))
            <a class="nav-link {{ request()->routeIs('pacientes.*') ? 'active' : '' }}" href="{{ route('pacientes.index') }}">
                <i class="fas fa-users"></i>
                <span>Pacientes</span>
            </a>

            <a class="nav-link {{ request()->routeIs('agendas.*') ? 'active' : '' }}" href="{{ route('agendas.index') }}">
                <i class="fas fa-calendar-alt"></i>
                <span>Agenda</span>
            </a>
        @endif

        {{-- ✅ TEMPORAL: Mostrar siempre para testing --}}
        @if(true)
            <a class="nav-link {{ request()->routeIs('historia-clinica.*') ? 'active' : '' }}" 
            href="{{ route('historia-clinica.index') }}">
                <i class="fas fa-file-medical"></i>
                <span>Historias Clínicas</span>
            </a>
        @endif  



        <!-- Reportes -->
        <a class="nav-link {{ request()->routeIs('reportes.*') ? 'active' : '' }}" href="#">
            <i class="fas fa-chart-bar"></i>
            <span>Reportes</span>
        </a>

        <!-- Administración (Solo Admin) -->
        @if(isset($usuario['tipo_usuario']) && ($usuario['tipo_usuario']['es_administrador'] ?? false))
            <div class="nav-section-title">Administración</div>
            
            <a class="nav-link {{ request()->routeIs('admin.usuarios.*') ? 'active' : '' }}" href="#">
                <i class="fas fa-user-cog"></i>
                <span>Usuarios</span>
            </a>

            <a class="nav-link {{ request()->routeIs('admin.especialidades.*') ? 'active' : '' }}" href="#">
                <i class="fas fa-stethoscope"></i>
                <span>Especialidades</span>
            </a>

            <a class="nav-link {{ request()->routeIs('admin.sedes.*') ? 'active' : '' }}" href="#">
                <i class="fas fa-building"></i>
                <span>Sedes</span>
            </a>

            {{-- ✅ BOTÓN ACTUALIZAR DATOS MAESTROS --}}
            <a class="nav-link nav-link-sync" href="#" onclick="sincronizarDatosMaestros(); return false;">
                <i class="fas fa-sync-alt"></i>
                <span>Actualizar Datos</span>
            </a>
        @endif

        <hr class="sidebar-divider">

        <!-- Mi Cuenta -->
        <div class="nav-section-title">Mi Cuenta</div>
        
        <a class="nav-link" href="#" onclick="showUserProfile()">
            <i class="fas fa-user"></i>
            <span>Mi Perfil</span>
        </a>

        <a class="nav-link" href="#" onclick="changePassword()">
            <i class="fas fa-key"></i>
            <span>Cambiar Contraseña</span>
        </a>

        <a class="nav-link" href="#" onclick="toggleTheme()">
            <i class="fas fa-moon" id="themeIconSidebar"></i>
            <span>Cambiar Tema</span>
        </a>
    </nav>

    <!-- Bottom Section -->
    <div class="sidebar-bottom">
        <!-- Sync Status -->
        <!-- <div class="sync-status {{ ($is_online ?? true) ? 'online' : 'offline' }}">
            <i class="fas {{ ($is_online ?? true) ? 'fa-wifi' : 'fa-wifi-slash' }}"></i>
            <span>{{ ($is_online ?? true) ? 'Conectado' : 'Sin conexión' }}</span>
        </div> -->

        @if(!($is_online ?? true) && ($pending_changes ?? 0) > 0)
            <div class="pending-changes">
                <i class="fas fa-clock"></i>
                {{ $pending_changes }} cambios pendientes
            </div>
        @endif

        <!-- Quick Actions -->
        <div class="quick-actions">
            <button class="btn btn-sm btn-outline-success" 
                    id="btnSyncAllUnified" 
                    onclick="ejecutarSincronizacionUnificadaV2()" 
                    title="Sincronizar Datos Offline">
                <i class="fas fa-sync-alt"></i>
                <span class="sync-text">Sincronizar</span>
            </button>
            <button class="btn btn-sm btn-outline-danger" onclick="confirmLogout()" title="Cerrar sesión">
                <i class="fas fa-sign-out-alt"></i>
            </button>
        </div>

        <!-- Version Info -->
        <div class="version-info">
            <small class="text-muted">
                SIDIS v2.1.0
                <br>
                <i class="fas fa-calendar"></i> {{ date('d/m/Y') }}
            </small>
        </div>
    </div>
</div>

<style>
/* ===== SIDEBAR CONTENT ===== */
.sidebar-content {
    display: flex;
    flex-direction: column;
    height: 100%;
    padding: 0;
}

/* ===== USER PROFILE SECTION ===== */
.user-profile-section {
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    color: white;
    padding: 10px 15px 15px 15px;
    text-align: center;
    margin-bottom: 5px;
    margin-top: 0;
    padding-top: 10px; /* Muy reducido para que ocupe desde arriba */
}

.user-avatar-container {
    position: relative;
    display: inline-block;
    margin-bottom: 6px; /* Reducido de 10px */
}

.user-avatar {
    width: 55px; /* Reducido de 65px */
    height: 55px;
    border-radius: 50%;
    background: rgba(255,255,255,0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem; /* Reducido de 2.3rem */
    border: 2px solid rgba(255,255,255,0.3); /* Reducido de 3px */
    transition: var(--transition);
}

.user-avatar:hover {
    transform: scale(1.05);
}

.avatar-img {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
}

.user-status-indicator {
    position: absolute;
    bottom: 2px;
    right: 2px;
    width: 14px;
    height: 14px;
    border-radius: 50%;
    border: 3px solid white;
}

.status-online {
    background-color: #28a745;
    animation: pulse-green 2s infinite;
}

.status-offline {
    background-color: #dc3545;
}

@keyframes pulse-green {
    0% { box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.7); }
    70% { box-shadow: 0 0 0 10px rgba(40, 167, 69, 0); }
    100% { box-shadow: 0 0 0 0 rgba(40, 167, 69, 0); }
}

.user-details {
    margin-top: 5px; /* Reducido de 8px */
}

.user-name {
    font-size: 0.95rem; /* Reducido de 1.05rem */
    font-weight: 600;
    margin-bottom: 4px; /* Reducido de 6px */
    text-shadow: 0 1px 2px rgba(0,0,0,0.1);
    line-height: 1.2;
}

.user-role,
.user-location {
    font-size: 0.8rem; /* Reducido de 0.85rem */
    opacity: 0.9;
    margin-bottom: 2px; /* Reducido de 3px */
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5px; /* Reducido de 6px */
    line-height: 1.2;
}

/* ===== NAVIGATION ===== */
.sidebar-nav {
    flex-grow: 1;
    padding: 20px 15px; /* Aumentado de 15px 10px */
    overflow-y: auto;
}

.nav-section-title {
    font-size: 0.8rem; /* Aumentado de 0.75rem */
    font-weight: 700;
    text-transform: uppercase;
    color: #6c757d;
    padding: 20px 15px 10px; /* Aumentado espaciado */
    letter-spacing: 0.5px;
}

.nav-link {
    display: flex;
    align-items: center;
    gap: 14px; /* Aumentado de 12px */
    padding: 14px 18px; /* Aumentado de 12px 15px */
    margin: 5px 0; /* Aumentado de 3px */
    border-radius: 10px;
    color: #495057;
    text-decoration: none;
    transition: var(--transition);
    font-weight: 500;
    position: relative;
    font-size: 0.95rem; /* Añadido tamaño de fuente */
}

.nav-link i {
    width: 20px;
    text-align: center;
    font-size: 1rem;
}

.nav-link:hover,
.nav-link.active {
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    color: white;
    transform: translateX(5px);
    box-shadow: 0 4px 15px rgba(44, 90, 160, 0.3);
}

.nav-link.active::before {
    content: '';
    position: absolute;
    left: 0;
    top: 50%;
    transform: translateY(-50%);
    width: 4px;
    height: 60%;
    background: white;
    border-radius: 0 4px 4px 0;
}

/* ===== BOTÓN DE SINCRONIZACIÓN ===== */
.nav-link-sync {
    background: linear-gradient(135deg, #28a745, #20c997) !important;
    color: white !important;
    font-weight: 600;
    margin-top: 10px;
    box-shadow: 0 2px 8px rgba(40, 167, 69, 0.3);
}

.nav-link-sync:hover {
    background: linear-gradient(135deg, #218838, #1fa084) !important;
    transform: translateX(5px) scale(1.02);
    box-shadow: 0 4px 15px rgba(40, 167, 69, 0.5);
}

.nav-link-sync i {
    animation: rotate-icon 2s linear infinite paused;
}

.nav-link-sync:hover i {
    animation-play-state: running;
}

@keyframes rotate-icon {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* ===== MODAL DE SINCRONIZACIÓN ===== */
.sync-progress-container {
    margin: 20px 0;
}

.sync-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 15px;
    margin: 8px 0;
    background: #f8f9fa;
    border-radius: 8px;
    border-left: 4px solid #6c757d;
    transition: all 0.3s ease;
}

.sync-item i:first-child {
    font-size: 20px;
    margin-right: 12px;
    color: #495057;
}

.sync-item span {
    flex: 1;
    font-weight: 600;
    color: #495057;
}

.sync-item .sync-status {
    font-size: 18px;
}

.sync-item .sync-status .fa-spinner {
    color: #007bff;
}

.sync-item .sync-status .fa-check-circle {
    color: #28a745;
}

.sync-item .sync-status .fa-clock {
    color: #6c757d;
}

#sync-current-task {
    font-weight: 600;
    color: #007bff;
    font-size: 14px;
}

/* ===== SIDEBAR DIVIDER ===== */
.sidebar-divider {
    border: none;
    height: 1px;
    background: linear-gradient(to right, transparent, #dee2e6, transparent);
    margin: 20px 0; /* Aumentado de 15px */
}

/* ===== BOTTOM SECTION ===== */
.sidebar-bottom {
    padding: 20px; /* Aumentado de 15px */
    border-top: 1px solid #e9ecef;
    background: rgba(0,0,0,0.02);
}

.sync-status {
    display: flex;
    align-items: center;
    gap: 10px; /* Aumentado de 8px */
    padding: 12px; /* Aumentado de 10px */
    border-radius: 8px;
    font-size: 0.9rem; /* Aumentado de 0.85rem */
    font-weight: 500;
    margin-bottom: 12px; /* Aumentado de 10px */
}

.sync-status.online {
    background: rgba(40, 167, 69, 0.1);
    color: #28a745;
    border: 1px solid rgba(40, 167, 69, 0.2);
}

.sync-status.offline {
    background: rgba(255, 193, 7, 0.1);
    color: #856404;
    border: 1px solid rgba(255, 193, 7, 0.2);
}

.pending-changes {
    font-size: 0.8rem; /* Aumentado de 0.75rem */
    color: #856404;
    padding: 8px 12px; /* Aumentado de 6px 10px */
    background: rgba(255, 193, 7, 0.1);
    border-radius: 8px; /* Aumentado de 6px */
    margin-bottom: 12px; /* Aumentado de 10px */
    display: flex;
    align-items: center;
    gap: 8px; /* Aumentado de 6px */
}

.quick-actions {
    display: flex;
    gap: 10px; /* Aumentado de 8px */
    margin-bottom: 15px; /* Aumentado de 12px */
}

.quick-actions .btn {
    flex: 1;
    padding: 10px; /* Aumentado de 8px */
    border-radius: 8px; /* Aumentado de 6px */
    font-size: 0.95rem; /* Añadido para mejor legibilidad */
}

.version-info {
    text-align: center;
    padding: 12px; /* Aumentado de 10px */
    background: rgba(0,0,0,0.03);
    border-radius: 8px; /* Aumentado de 6px */
    font-size: 0.8rem; /* Aumentado de 0.75rem */
}

/* ===== SCROLLBAR ===== */
.sidebar-nav::-webkit-scrollbar {
    width: 5px;
}

.sidebar-nav::-webkit-scrollbar-track {
    background: rgba(0,0,0,0.05);
}

.sidebar-nav::-webkit-scrollbar-thumb {
    background: rgba(44, 90, 160, 0.3);
    border-radius: 3px;
}

.sidebar-nav::-webkit-scrollbar-thumb:hover {
    background: rgba(44, 90, 160, 0.5);
}
</style>

<script>
// Funciones del sidebar
function showUserProfile() {
    const usuario = @json($usuario ?? []);
    
    Swal.fire({
        title: '<i class="fas fa-user-edit text-primary"></i> Mi Perfil',
        html: `
            <div class="text-start">
                <div class="card mb-3">
                    <div class="card-body">
                        <h6><i class="fas fa-user me-2"></i>Información Personal</h6>
                        <p><strong>Nombre:</strong> ${usuario.nombre_completo || 'No especificado'}</p>
                        <p><strong>Documento:</strong> ${usuario.documento || 'No especificado'}</p>
                        <p><strong>Correo:</strong> ${usuario.correo || 'No especificado'}</p>
                        <p><strong>Teléfono:</strong> ${usuario.telefono || 'No especificado'}</p>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body">
                        <h6><i class="fas fa-briefcase me-2"></i>Información Laboral</h6>
                        <p><strong>Rol:</strong> ${usuario.rol?.nombre || 'No asignado'}</p>
                        <p><strong>Sede:</strong> ${usuario.sede?.nombre || 'No asignada'}</p>
                    </div>
                </div>
            </div>
        `,
        width: '600px',
        showCloseButton: true,
        showConfirmButton: false
    });
}

/**
 * 🔍 Buscar historia clínica por documento
 */
function buscarHistoriaPorDocumento() {
    Swal.fire({
        title: '<i class="fas fa-search text-primary"></i> Buscar Historia Clínica',
        html: `
            <div class="text-start">
                <div class="mb-3">
                    <label class="form-label fw-bold">
                        <i class="fas fa-id-card me-2"></i>Número de Documento
                    </label>
                    <input type="text" 
                           class="form-control form-control-lg" 
                           id="documentoBusqueda" 
                           placeholder="Ej: 1234567890"
                           autocomplete="off"
                           required>
                    <small class="text-muted">Ingrese el documento del paciente sin puntos ni espacios</small>
                </div>
            </div>
        `,
        width: '500px',
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-search me-2"></i>Buscar',
        cancelButtonText: '<i class="fas fa-times me-2"></i>Cancelar',
        confirmButtonColor: '#2c5aa0',
        cancelButtonColor: '#6c757d',
        focusConfirm: false,
        didOpen: () => {
            // Enfocar el input al abrir
            document.getElementById('documentoBusqueda').focus();
            
            // Permitir buscar con Enter
            document.getElementById('documentoBusqueda').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    Swal.clickConfirm();
                }
            });
        },
        preConfirm: () => {
            const documento = document.getElementById('documentoBusqueda').value.trim();
            
            if (!documento) {
                Swal.showValidationMessage('Por favor ingrese un número de documento');
                return false;
            }
            
            if (!/^\d+$/.test(documento)) {
                Swal.showValidationMessage('El documento debe contener solo números');
                return false;
            }
            
            if (documento.length < 5 || documento.length > 15) {
                Swal.showValidationMessage('El documento debe tener entre 5 y 15 dígitos');
                return false;
            }
            
            return documento;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            buscarYMostrarHistoria(result.value);
        }
    });
}

/**
 * 🔎 Realizar búsqueda y mostrar resultado
 */
function buscarYMostrarHistoria(documento) {
    // Mostrar loader
    Swal.fire({
        title: 'Buscando...',
        html: '<i class="fas fa-spinner fa-spin fa-3x text-primary"></i><br><br>Buscando historia clínica...',
        showConfirmButton: false,
        allowOutsideClick: false
    });
    
    // Realizar petición AJAX
    fetch(`{{ route('historia-clinica.buscar-documento') }}?documento=${documento}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (data.historias && data.historias.length > 0) {
                mostrarResultadosHistorias(data.historias, documento);
            } else {
                // No se encontraron historias
                Swal.fire({
                    icon: 'info',
                    title: 'Sin Resultados',
                    html: `
                        <p>No se encontraron historias clínicas para el documento <strong>${documento}</strong></p>
                        <p class="text-muted">¿Desea crear una nueva historia clínica?</p>
                    `,
                    showCancelButton: true,
                    confirmButtonText: '<i class="fas fa-plus me-2"></i>Crear Historia',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#28a745'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = `{{ route('historia-clinica.index') }}?crear=true&documento=${documento}`;
                    }
                });
            }
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message || 'Error al buscar la historia clínica'
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error de Conexión',
            text: 'No se pudo conectar con el servidor. Intente nuevamente.'
        });
    });
}

/**
 * 📋 Mostrar resultados de búsqueda
 */
function mostrarResultadosHistorias(historias, documento) {
    let html = `
        <div class="text-start">
            <p class="mb-3">Se encontraron <strong>${historias.length}</strong> historia(s) para el documento <strong>${documento}</strong></p>
            <div class="list-group">
    `;
    
    historias.forEach(historia => {
        const fecha = new Date(historia.fecha_atencion).toLocaleDateString('es-CO');
        const especialidad = historia.especialidad?.nombre || 'Sin especialidad';
        const profesional = historia.profesional?.nombre_completo || 'Sin profesional';
        
        html += `
            <a href="{{ url('historia-clinica') }}/${historia.uuid}" 
               class="list-group-item list-group-item-action">
                <div class="d-flex w-100 justify-content-between">
                    <h6 class="mb-1">
                        <i class="fas fa-file-medical text-primary me-2"></i>
                        ${historia.paciente?.nombre_completo || 'Paciente'}
                    </h6>
                    <small class="text-muted">${fecha}</small>
                </div>
                <p class="mb-1">
                    <i class="fas fa-stethoscope me-2"></i>${especialidad}
                </p>
                <small class="text-muted">
                    <i class="fas fa-user-md me-2"></i>${profesional}
                </small>
            </a>
        `;
    });
    
    html += `
            </div>
        </div>
    `;
    
    Swal.fire({
        title: '<i class="fas fa-list-alt text-success"></i> Historias Encontradas',
        html: html,
        width: '700px',
        showCloseButton: true,
        showConfirmButton: false,
        customClass: {
            popup: 'swal-wide'
        }
    });
}


function changePassword() {
    Swal.fire({
        title: 'Cambiar Contraseña',
        html: `
            <div class="text-start">
                <div class="mb-3">
                    <label class="form-label">Contraseña actual</label>
                    <input type="password" class="form-control" id="currentPassword" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Nueva contraseña</label>
                    <input type="password" class="form-control" id="newPassword" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Confirmar contraseña</label>
                    <input type="password" class="form-control" id="confirmPassword" required>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Cambiar',
        cancelButtonText: 'Cancelar',
        preConfirm: () => {
            const current = document.getElementById('currentPassword').value;
            const newPass = document.getElementById('newPassword').value;
            const confirm = document.getElementById('confirmPassword').value;
            
            if (!current || !newPass || !confirm) {
                Swal.showValidationMessage('Todos los campos son obligatorios');
                return false;
            }
            
            if (newPass !== confirm) {
                Swal.showValidationMessage('Las contraseñas no coinciden');
                return false;
            }
            
            if (newPass.length < 8) {
                Swal.showValidationMessage('La contraseña debe tener al menos 8 caracteres');
                return false;
            }
            
            return { current, newPass };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            mostrarAlerta('Contraseña actualizada correctamente', 'success');
        }
    });
}

function toggleTheme() {
    const body = document.body;
    const themeIcon = document.getElementById('themeIconSidebar');
    
    if (body.classList.contains('dark-theme')) {
        body.classList.remove('dark-theme');
        localStorage.setItem('theme', 'light');
        if (themeIcon) themeIcon.className = 'fas fa-moon';
        mostrarAlerta('Tema claro activado', 'success');
    } else {
        body.classList.add('dark-theme');
        localStorage.setItem('theme', 'dark');
        if (themeIcon) themeIcon.className = 'fas fa-sun';
        mostrarAlerta('Tema oscuro activado', 'success');
    }
}

function confirmLogout() {
    Swal.fire({
        title: '¿Cerrar Sesión?',
        text: '¿Está seguro que desea cerrar su sesión actual?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Sí, cerrar sesión',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("logout") }}';
            
            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = document.querySelector('meta[name="csrf-token"]').content;
            
            form.appendChild(csrfToken);
            document.body.appendChild(form);
            form.submit();
        }
    });
}

/**
 * 🔄 Sincronizar datos maestros desde el sidebar
 */
function sincronizarDatosMaestros() {
    Swal.fire({
        title: '¿Actualizar Datos?',
        html: `
            <p>Se sincronizarán todos los datos maestros del sistema:</p>
            <ul class="text-start" style="list-style: none; padding-left: 0;">
                <li><i class="fas fa-check-circle text-success me-2"></i>Departamentos y Municipios</li>
                <li><i class="fas fa-check-circle text-success me-2"></i>Empresas y Regímenes</li>
                <li><i class="fas fa-check-circle text-success me-2"></i>Procesos y Brigadas</li>
                <li><i class="fas fa-check-circle text-success me-2"></i>Especialidades</li>
                <li><i class="fas fa-check-circle text-success me-2"></i>Tipos de Documento</li>
                <li><i class="fas fa-check-circle text-success me-2"></i>Medicamentos</li>
                <li><i class="fas fa-check-circle text-success me-2"></i>Diagnósticos</li>
                <li><i class="fas fa-check-circle text-success me-2"></i>Remisiones</li>
                <li><i class="fas fa-check-circle text-success me-2"></i>CUPS</li>
                <li><i class="fas fa-check-circle text-success me-2"></i>CUPS Contratados</li>
            </ul>
            <p class="text-muted mt-3"><i class="fas fa-info-circle"></i> Este proceso puede tardar unos segundos</p>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-sync-alt me-2"></i>Actualizar',
        cancelButtonText: '<i class="fas fa-times me-2"></i>Cancelar',
        confirmButtonColor: '#2c5aa0',
        cancelButtonColor: '#6c757d'
    }).then((result) => {
        if (result.isConfirmed) {
            ejecutarSincronizacionDatosMaestros();
        }
    });
}

/**
 * ⚙️ Ejecutar sincronización con barra de progreso animada
 */
function ejecutarSincronizacionDatosMaestros() {
    // Mostrar barra de progreso con animación
    Swal.fire({
        title: 'Sincronizando Datos...',
        html: `
            <div class="sync-progress-container">
                <div class="sync-item" id="sync-maestros">
                    <i class="fas fa-database"></i>
                    <span>Datos Maestros</span>
                    <div class="sync-status"><i class="fas fa-spinner fa-spin"></i></div>
                </div>
                <div class="sync-item" id="sync-medicamentos">
                    <i class="fas fa-pills"></i>
                    <span>Medicamentos</span>
                    <div class="sync-status"><i class="fas fa-clock text-muted"></i></div>
                </div>
                <div class="sync-item" id="sync-diagnosticos">
                    <i class="fas fa-file-medical"></i>
                    <span>Diagnósticos</span>
                    <div class="sync-status"><i class="fas fa-clock text-muted"></i></div>
                </div>
                <div class="sync-item" id="sync-remisiones">
                    <i class="fas fa-file-invoice"></i>
                    <span>Remisiones</span>
                    <div class="sync-status"><i class="fas fa-clock text-muted"></i></div>
                </div>
                <div class="sync-item" id="sync-cups">
                    <i class="fas fa-clipboard-list"></i>
                    <span>CUPS</span>
                    <div class="sync-status"><i class="fas fa-clock text-muted"></i></div>
                </div>
                <div class="sync-item" id="sync-cups-contratados">
                    <i class="fas fa-file-contract"></i>
                    <span>CUPS Contratados</span>
                    <div class="sync-status"><i class="fas fa-clock text-muted"></i></div>
                </div>
            </div>
            <div class="progress mt-3" style="height: 25px;">
                <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" 
                     id="sync-progress-bar" 
                     role="progressbar" 
                     style="width: 16%">
                     16%
                </div>
            </div>
            <p class="text-muted mt-3" id="sync-current-task">Sincronizando datos maestros...</p>
            <p class="text-warning"><i class="fas fa-hourglass-half"></i> Este proceso puede tardar varios minutos</p>
        `,
        allowOutsideClick: false,
        showConfirmButton: false,
        width: '600px',
        customClass: {
            popup: 'sync-modal'
        }
    });
    
    // Realizar petición AJAX
    fetch('{{ route("offline.sync-master-data") }}', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Marcar todo como completado
            document.querySelectorAll('.sync-item').forEach(item => {
                item.querySelector('.sync-status').innerHTML = '<i class="fas fa-check-circle text-success"></i>';
            });
            document.getElementById('sync-progress-bar').style.width = '100%';
            document.getElementById('sync-progress-bar').textContent = '100%';
            
            // Mostrar resultado exitoso
            setTimeout(() => {
                Swal.fire({
                    icon: 'success',
                    title: '¡Actualización Exitosa!',
                    html: `
                        <p>${data.message || 'Datos sincronizados correctamente'}</p>
                        ${data.stats ? `
                            <div class="alert alert-info mt-3">
                                <strong>📊 Registros sincronizados:</strong>
                                <div class="row mt-3">
                                    <div class="col-6 text-start">
                                        ${data.stats.departamentos ? `<div><i class="fas fa-map-marked-alt me-2 text-primary"></i>Departamentos: <strong>${data.stats.departamentos}</strong></div>` : ''}
                                        ${data.stats.municipios ? `<div><i class="fas fa-city me-2 text-primary"></i>Municipios: <strong>${data.stats.municipios}</strong></div>` : ''}
                                        ${data.stats.medicamentos ? `<div><i class="fas fa-pills me-2 text-success"></i>Medicamentos: <strong>${data.stats.medicamentos}</strong></div>` : ''}
                                        ${data.stats.diagnosticos ? `<div><i class="fas fa-file-medical me-2 text-info"></i>Diagnósticos: <strong>${data.stats.diagnosticos}</strong></div>` : ''}
                                        ${data.stats.remisiones ? `<div><i class="fas fa-file-invoice me-2 text-warning"></i>Remisiones: <strong>${data.stats.remisiones}</strong></div>` : ''}
                                    </div>
                                    <div class="col-6 text-start">
                                        ${data.stats.cups ? `<div><i class="fas fa-clipboard-list me-2 text-danger"></i>CUPS: <strong>${data.stats.cups}</strong></div>` : ''}
                                        ${data.stats.cups_contratados ? `<div><i class="fas fa-file-contract me-2 text-secondary"></i>CUPS Contratados: <strong>${data.stats.cups_contratados}</strong></div>` : ''}
                                        ${data.stats.empresas ? `<div><i class="fas fa-building me-2"></i>Empresas: <strong>${data.stats.empresas}</strong></div>` : ''}
                                        ${data.stats.procesos ? `<div><i class="fas fa-tasks me-2"></i>Procesos: <strong>${data.stats.procesos}</strong></div>` : ''}
                                        ${data.stats.brigadas ? `<div><i class="fas fa-users me-2"></i>Brigadas: <strong>${data.stats.brigadas}</strong></div>` : ''}
                                    </div>
                                </div>
                            </div>
                        ` : ''}
                    `,
                    confirmButtonText: 'Aceptar',
                    confirmButtonColor: '#28a745',
                    width: '700px'
                });
            }, 500);
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error en la Sincronización',
                text: data.error || 'No se pudieron actualizar los datos',
                confirmButtonText: 'Cerrar',
                confirmButtonColor: '#dc3545'
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error de Conexión',
            html: `
                <p>No se pudo conectar con el servidor</p>
                <p class="text-muted">Por favor verifique su conexión e intente nuevamente</p>
            `,
            confirmButtonText: 'Cerrar',
            confirmButtonColor: '#dc3545'
        });
    });
}

// Inicializar tema al cargar
document.addEventListener('DOMContentLoaded', function() {
    const savedTheme = localStorage.getItem('theme');
    const themeIcon = document.getElementById('themeIconSidebar');
    
    if (savedTheme === 'dark') {
        document.body.classList.add('dark-theme');
        if (themeIcon) themeIcon.className = 'fas fa-sun';
    }
});

/**
 * 🔄 SINCRONIZACIÓN UNIFICADA V2 - NUEVA FUNCIÓN
 * Ejecuta sincronización secuencial: Pacientes → Agendas → Citas → Historias Clínicas
 * Luego pregunta si desea sincronizar datos maestros
 */
async function ejecutarSincronizacionUnificadaV2() {
    console.log('🔄 Iniciando sincronización unificada V2...');
    
    // Preguntar confirmación
    const result = await Swal.fire({
        title: '¿Sincronizar Datos Offline?',
        html: `
            <div class="text-start">
                <p class="mb-3">Se sincronizarán los <strong>datos offline pendientes</strong> en el siguiente orden:</p>
                <ul class="sync-order-list">
                    <li><i class="fas fa-users text-primary me-2"></i><strong>1. Pacientes</strong></li>
                    <li><i class="fas fa-calendar-alt text-info me-2"></i><strong>2. Agendas</strong></li>
                    <li><i class="fas fa-calendar-check text-success me-2"></i><strong>3. Citas</strong></li>
                    <li><i class="fas fa-file-medical text-danger me-2"></i><strong>4. Historias Clínicas</strong></li>
                </ul>
                <div class="alert alert-info mt-3 mb-2">
                    <i class="fas fa-info-circle me-2"></i>
                    Después podrá sincronizar los datos maestros si lo desea
                </div>
                <div class="alert alert-warning mb-0">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Si un paso falla, la sincronización se detendrá
                </div>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-sync-alt me-2"></i>Sincronizar Datos Offline',
        cancelButtonText: '<i class="fas fa-times me-2"></i>Cancelar',
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        width: '650px'
    });

    if (!result.isConfirmed) {
        return;
    }

    // Deshabilitar botón
    const btnSync = document.getElementById('btnSyncAllUnified');
    if (btnSync) {
        btnSync.disabled = true;
        btnSync.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    }

    // Mostrar modal de progreso
    Swal.fire({
        title: 'Sincronizando Datos Offline...',
        html: `
            <div class="sync-progress-container">
                <div class="sync-step" id="sync-step-pacientes">
                    <div class="step-icon"><i class="fas fa-users"></i></div>
                    <div class="step-content">
                        <div class="step-title">1. Pacientes</div>
                        <div class="step-status" id="status-pacientes">
                            <i class="fas fa-spinner fa-spin text-primary"></i> Procesando...
                        </div>
                    </div>
                </div>
                
                <div class="sync-step" id="sync-step-agendas">
                    <div class="step-icon"><i class="fas fa-calendar-alt"></i></div>
                    <div class="step-content">
                        <div class="step-title">2. Agendas</div>
                        <div class="step-status" id="status-agendas">
                            <i class="fas fa-clock text-muted"></i> En espera...
                        </div>
                    </div>
                </div>
                
                <div class="sync-step" id="sync-step-citas">
                    <div class="step-icon"><i class="fas fa-calendar-check"></i></div>
                    <div class="step-content">
                        <div class="step-title">3. Citas</div>
                        <div class="step-status" id="status-citas">
                            <i class="fas fa-clock text-muted"></i> En espera...
                        </div>
                    </div>
                </div>
                
                <div class="sync-step" id="sync-step-historias">
                    <div class="step-icon"><i class="fas fa-file-medical"></i></div>
                    <div class="step-content">
                        <div class="step-title">4. Historias Clínicas</div>
                        <div class="step-status" id="status-historias">
                            <i class="fas fa-clock text-muted"></i> En espera...
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="progress mt-4" style="height: 25px;">
                <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" 
                     id="sync-global-progress" 
                     role="progressbar" 
                     style="width: 0%">
                     0%
                </div>
            </div>
            
            <p class="text-muted mt-3 mb-0" id="sync-message">
                <i class="fas fa-info-circle me-2"></i>Iniciando sincronización...
            </p>
        `,
        allowOutsideClick: false,
        showConfirmButton: false,
        width: '700px',
        customClass: {
            popup: 'sync-unified-modal'
        }
    });

    try {
        console.log('📡 Llamando a endpoint /sync-all...');
        
        // Ejecutar sincronización
        const response = await fetch('{{ route("sync.all") }}', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });

        console.log('📨 Respuesta recibida:', response.status);
        
        const data = await response.json();
        
        console.log('📊 Datos recibidos:', data);

        if (data.success) {
            // Marcar todos como completados
            actualizarEstadoSincronizacion('pacientes', 'success', data.details?.pacientes);
            actualizarEstadoSincronizacion('agendas', 'success', data.details?.agendas);
            actualizarEstadoSincronizacion('citas', 'success', data.details?.citas);
            actualizarEstadoSincronizacion('historias', 'success', data.details?.historias);

            // Actualizar barra de progreso
            const progressBar = document.getElementById('sync-global-progress');
            if (progressBar) {
                progressBar.style.width = '100%';
                progressBar.textContent = '100%';
            }

            // Mostrar resultado exitoso con opción de sincronizar datos maestros
            setTimeout(() => {
                Swal.fire({
                    icon: 'success',
                    title: '¡Sincronización de Datos Offline Completada!',
                    html: generarResumenSincronizacion(data) + `
                        <div class="alert alert-info mt-4 mb-0">
                            <h6><i class="fas fa-database me-2"></i>Sincronizar Datos Maestros</h6>
                            <p class="mb-2">¿Desea actualizar también los datos maestros del sistema?</p>
                            <small class="text-muted">
                                (Departamentos, Municipios, Medicamentos, Diagnósticos, CUPS, etc.)
                            </small>
                        </div>
                    `,
                    showDenyButton: true,
                    confirmButtonText: '<i class="fas fa-sync-alt me-2"></i>Sí, sincronizar datos maestros',
                    denyButtonText: '<i class="fas fa-times me-2"></i>No, terminar',
                    confirmButtonColor: '#2c5aa0',
                    denyButtonColor: '#6c757d',
                    width: '750px'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Usuario quiere sincronizar datos maestros
                        sincronizarDatosMaestros();
                    }
                });
            }, 1000);

        } else {
            // Error en algún paso
            const failedModule = data.module || 'desconocido';
            const failedStep = data.step || 0;
            
            // Marcar pasos completados y el que falló
            if (failedStep >= 1) {
                actualizarEstadoSincronizacion('pacientes', 
                    failedModule === 'pacientes' ? 'error' : 'success', 
                    data.results?.pacientes);
            }
            if (failedStep >= 2) {
                actualizarEstadoSincronizacion('agendas', 
                    failedModule === 'agendas' ? 'error' : 'success', 
                    data.results?.agendas);
            }
            if (failedStep >= 3) {
                actualizarEstadoSincronizacion('citas', 
                    failedModule === 'citas' ? 'error' : 'success', 
                    data.results?.citas);
            }
            if (failedStep >= 4) {
                actualizarEstadoSincronizacion('historias', 
                    failedModule === 'historias' ? 'error' : 'success', 
                    data.results?.historias);
            }

            setTimeout(() => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error en la Sincronización de Datos Offline',
                    html: `
                        <div class="alert alert-danger">
                            <strong>Módulo:</strong> ${getNombreModulo(failedModule)}<br>
                            <strong>Error:</strong> ${data.error || 'Error desconocido'}
                        </div>
                        ${data.results ? generarResumenParcial(data.results) : ''}
                        <div class="alert alert-warning mt-3 mb-0">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            La sincronización se detuvo. Por favor, corrija el error e intente nuevamente.
                        </div>
                    `,
                    confirmButtonText: 'Cerrar',
                    confirmButtonColor: '#dc3545',
                    width: '700px'
                });
            }, 500);
        }

    } catch (error) {
        console.error('Error en sincronización:', error);
        
        Swal.fire({
            icon: 'error',
            title: 'Error de Conexión',
            html: `
                <p>No se pudo conectar con el servidor</p>
                <p class="text-muted">Por favor verifique su conexión e intente nuevamente</p>
                <code class="d-block mt-3 p-2 bg-light text-danger">${error.message}</code>
            `,
            confirmButtonText: 'Cerrar',
            confirmButtonColor: '#dc3545'
        });
    } finally {
        // Rehabilitar botón
        if (btnSync) {
            btnSync.disabled = false;
            btnSync.innerHTML = '<i class="fas fa-sync-alt"></i><span class="sync-text">Sincronizar</span>';
        }
    }
}

/**
 * Actualizar estado visual de un módulo en el modal de sincronización
 */
function actualizarEstadoSincronizacion(modulo, estado, detalles) {
    const statusElement = document.getElementById(`status-${modulo}`);
    const stepElement = document.getElementById(`sync-step-${modulo}`);
    
    if (!statusElement || !stepElement) return;

    if (estado === 'success') {
        const synced = detalles?.synced_count || 0;
        const failed = detalles?.failed_count || 0;
        
        statusElement.innerHTML = `
            <i class="fas fa-check-circle text-success me-2"></i>
            ${synced} sincronizados${failed > 0 ? `, ${failed} fallidos` : ''}
        `;
        stepElement.classList.add('step-completed');
        
    } else if (estado === 'error') {
        statusElement.innerHTML = `
            <i class="fas fa-times-circle text-danger me-2"></i>
            Error al sincronizar
        `;
        stepElement.classList.add('step-error');
    } else if (estado === 'processing') {
        statusElement.innerHTML = `
            <i class="fas fa-spinner fa-spin text-primary me-2"></i>
            Procesando...
        `;
    }

    // Actualizar progreso global
    const progressBar = document.getElementById('sync-global-progress');
    if (progressBar) {
        const completedSteps = document.querySelectorAll('.step-completed, .step-error').length;
        const totalSteps = 4;
        const percentage = (completedSteps / totalSteps) * 100;
        
        progressBar.style.width = `${percentage}%`;
        progressBar.textContent = `${Math.round(percentage)}%`;
    }
}

/**
 * Generar resumen HTML de la sincronización de datos offline
 */
function generarResumenSincronizacion(data) {
    const details = data.details || {};
    
    return `
        <p class="mb-3">${data.message || 'Todos los datos offline fueron sincronizados correctamente'}</p>
        
        <div class="row mt-4">
            <div class="col-12">
                <div class="alert alert-success mb-0">
                    <h5><i class="fas fa-check-circle me-2"></i>Resumen de Datos Offline Sincronizados</h5>
                    <hr>
                    <div class="row">
                        <div class="col-6 text-start">
                            ${generarItemResumen('Pacientes', 'users', details.pacientes)}
                            ${generarItemResumen('Agendas', 'calendar-alt', details.agendas)}
                        </div>
                        <div class="col-6 text-start">
                            ${generarItemResumen('Citas', 'calendar-check', details.citas)}
                            ${generarItemResumen('Historias Clínicas', 'file-medical', details.historias)}
                        </div>
                    </div>
                    <hr>
                    <div class="text-center">
                        <strong>Total sincronizado: ${data.total_synced || 0} registros</strong>
                        ${(data.total_failed || 0) > 0 ? `<br><span class="text-warning">Fallidos: ${data.total_failed}</span>` : ''}
                    </div>
                </div>
            </div>
        </div>
    `;
}

/**
 * Generar item individual del resumen
 */
function generarItemResumen(nombre, icono, detalles) {
    if (!detalles) return '';
    
    const synced = detalles.synced_count || 0;
    const failed = detalles.failed_count || 0;
    
    // Si tiene detalle de enviados/descargados (aplica a todos los módulos)
    if (detalles.enviados !== undefined || detalles.descargados !== undefined) {
        return `
            <div class="mb-2">
                <i class="fas fa-${icono} me-2"></i>
                <strong>${nombre}:</strong><br>
                <span class="text-success ms-4">↑ ${detalles.enviados || 0} enviados</span><br>
                <span class="text-info ms-4">↓ ${detalles.descargados || 0} descargados</span>
                ${failed > 0 ? `<br><span class="text-danger ms-4">✗ ${failed} errores</span>` : ''}
            </div>
        `;
    }
    
    return `
        <div class="mb-2">
            <i class="fas fa-${icono} me-2"></i>
            <strong>${nombre}:</strong> 
            <span class="text-success">${synced} ✓</span>
            ${failed > 0 ? `<span class="text-danger"> / ${failed} ✗</span>` : ''}
        </div>
    `;
}

/**
 * Generar resumen parcial cuando falla
 */
function generarResumenParcial(results) {
    let html = '<div class="alert alert-info mt-3"><h6>Datos sincronizados antes del error:</h6>';
    
    if (results.pacientes?.success) {
        html += generarItemResumen('Pacientes', 'users', results.pacientes);
    }
    if (results.agendas?.success) {
        html += generarItemResumen('Agendas', 'calendar-alt', results.agendas);
    }
    if (results.citas?.success) {
        html += generarItemResumen('Citas', 'calendar-check', results.citas);
    }
    
    html += '</div>';
    return html;
}

/**
 * Obtener nombre amigable del módulo
 */
function getNombreModulo(modulo) {
    const nombres = {
        'pacientes': 'Pacientes',
        'agendas': 'Agendas',
        'citas': 'Citas',
        'historias': 'Historias Clínicas',
        'conectividad': 'Conectividad',
        'sistema': 'Sistema'
    };
    return nombres[modulo] || modulo;
}
</script>

<style>
/* Estilos para el modal de sincronización unificada */
.sync-unified-modal {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.sync-progress-container {
    display: flex;
    flex-direction: column;
    gap: 15px;
    margin: 20px 0;
}

.sync-step {
    display: flex;
    align-items: center;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
    border-left: 4px solid #dee2e6;
    transition: all 0.3s ease;
}

.sync-step.step-completed {
    background: #d4edda;
    border-left-color: #28a745;
}

.sync-step.step-error {
    background: #f8d7da;
    border-left-color: #dc3545;
}

.step-icon {
    font-size: 1.8rem;
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: white;
    border-radius: 50%;
    margin-right: 15px;
    color: #6c757d;
}

.sync-step.step-completed .step-icon {
    color: #28a745;
}

.sync-step.step-error .step-icon {
    color: #dc3545;
}

.step-content {
    flex: 1;
}

.step-title {
    font-weight: 600;
    font-size: 1.1rem;
    margin-bottom: 5px;
    color: #2c5aa0;
}

.step-status {
    font-size: 0.9rem;
    color: #6c757d;
}

.sync-order-list {
    list-style: none;
    padding-left: 0;
}

.sync-order-list li {
    padding: 8px 0;
    border-bottom: 1px solid #e9ecef;
}

.sync-order-list li:last-child {
    border-bottom: none;
}

.quick-actions .sync-text {
    margin-left: 5px;
    font-size: 0.85rem;
}

@media (max-width: 768px) {
    .quick-actions .sync-text {
        display: none;
    }
}
</style>
