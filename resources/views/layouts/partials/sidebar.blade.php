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
            <button class="btn btn-sm btn-outline-success" onclick="syncAllPendingData()" title="Sincronizar">
                <i class="fas fa-sync-alt"></i>
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
    padding: 20px;
    text-align: center;
}

.user-avatar-container {
    position: relative;
    display: inline-block;
    margin-bottom: 15px;
}

.user-avatar {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    background: rgba(255,255,255,0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    border: 3px solid rgba(255,255,255,0.3);
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
    margin-top: 10px;
}

.user-name {
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 8px;
    text-shadow: 0 1px 2px rgba(0,0,0,0.1);
}

.user-role,
.user-location {
    font-size: 0.85rem;
    opacity: 0.9;
    margin-bottom: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}

/* ===== NAVIGATION ===== */
.sidebar-nav {
    flex-grow: 1;
    padding: 15px 10px;
    overflow-y: auto;
}

.nav-section-title {
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    color: #6c757d;
    padding: 15px 15px 8px;
    letter-spacing: 0.5px;
}

.nav-link {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 15px;
    margin: 3px 0;
    border-radius: 10px;
    color: #495057;
    text-decoration: none;
    transition: var(--transition);
    font-weight: 500;
    position: relative;
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

/* ===== SIDEBAR DIVIDER ===== */
.sidebar-divider {
    border: none;
    height: 1px;
    background: linear-gradient(to right, transparent, #dee2e6, transparent);
    margin: 15px 0;
}

/* ===== BOTTOM SECTION ===== */
.sidebar-bottom {
    padding: 15px;
    border-top: 1px solid #e9ecef;
    background: rgba(0,0,0,0.02);
}

.sync-status {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px;
    border-radius: 8px;
    font-size: 0.85rem;
    font-weight: 500;
    margin-bottom: 10px;
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
    font-size: 0.75rem;
    color: #856404;
    padding: 6px 10px;
    background: rgba(255, 193, 7, 0.1);
    border-radius: 6px;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.quick-actions {
    display: flex;
    gap: 8px;
    margin-bottom: 12px;
}

.quick-actions .btn {
    flex: 1;
    padding: 8px;
    border-radius: 6px;
}

.version-info {
    text-align: center;
    padding: 10px;
    background: rgba(0,0,0,0.03);
    border-radius: 6px;
    font-size: 0.75rem;
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

// Inicializar tema al cargar
document.addEventListener('DOMContentLoaded', function() {
    const savedTheme = localStorage.getItem('theme');
    const themeIcon = document.getElementById('themeIconSidebar');
    
    if (savedTheme === 'dark') {
        document.body.classList.add('dark-theme');
        if (themeIcon) themeIcon.className = 'fas fa-sun';
    }
});
</script>
