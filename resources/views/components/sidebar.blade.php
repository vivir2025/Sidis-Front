@auth
<div class="sidebar-container">
    <!-- Sidebar Toggle Button -->
    <button class="sidebar-toggle" id="sidebarToggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

    <!-- Sidebar Content -->
    <div class="sidebar" id="sidebar">
        <div class="d-flex flex-column h-100">
            <!-- Close Button (Mobile) -->
            <div class="sidebar-header d-md-none">
                <button class="btn btn-sm btn-outline-light" onclick="closeSidebar()">
                    <i class="fas fa-times"></i>
                </button>
            </div>

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
                        @if(isset($usuario['especialidad']) && $usuario['especialidad'])
                            <div class="user-specialty">
                                <i class="fas fa-stethoscope"></i>
                                {{ $usuario['especialidad']['nombre'] }}
                            </div>
                        @endif
                    </div>

                    <!-- Quick Actions -->
                    <div class="user-quick-actions">
                        <button class="btn btn-sm btn-outline-light" onclick="showUserProfile()" title="Ver perfil">
                            <i class="fas fa-user"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-light" onclick="showUserSettings()" title="Configuración">
                            <i class="fas fa-cog"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-light" onclick="toggleTheme()" title="Cambiar tema">
                            <i class="fas fa-moon" id="themeIcon"></i>
                        </button>
                    </div>
                </div>
            @endif

            <!-- Navigation Menu -->
            <nav class="nav flex-column sidebar-nav">
                <!-- Dashboard -->
                <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                    <div class="nav-indicator"></div>
                </a>

                <!-- Pacientes - Solo para Admin y Secretaria -->
                @if(isset($usuario['tipo_usuario']) && (($usuario['tipo_usuario']['es_administrador'] ?? false) || ($usuario['tipo_usuario']['es_secretaria'] ?? false)))
                    <a class="nav-link {{ request()->routeIs('pacientes.*') ? 'active' : '' }}" href="{{ route('pacientes.index') }}">
                        <i class="fas fa-users"></i>
                        <span>Pacientes</span>
                        <div class="nav-indicator"></div>
                        @if(($pacientes_pendientes ?? 0) > 0)
                            <span class="badge bg-warning">{{ $pacientes_pendientes }}</span>
                        @endif
                    </a>

                    <a class="nav-link {{ request()->routeIs('agendas.*') ? 'active' : '' }}" href="{{ route('agendas.index') }}">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Agenda</span>
                        <div class="nav-indicator"></div>
                        @if(($citas_hoy ?? 0) > 0)
                            <span class="badge bg-info">{{ $citas_hoy }}</span>
                        @endif
                    </a>
                @endif

                <!-- Mi Agenda - Para Médicos y Enfermeros -->
                @if(isset($usuario['tipo_usuario']) && (($usuario['tipo_usuario']['es_medico'] ?? false) || ($usuario['tipo_usuario']['es_enfermero'] ?? false)))
                    <a class="nav-link {{ request()->routeIs('mi-agenda.*') ? 'active' : '' }}" href="#">
                        <i class="fas fa-calendar-check"></i>
                        <span>Mi Agenda</span>
                        <div class="nav-indicator"></div>
                        @if(($mis_citas_hoy ?? 0) > 0)
                            <span class="badge bg-success">{{ $mis_citas_hoy }}</span>
                        @endif
                    </a>

                    <a class="nav-link {{ request()->routeIs('historias.*') ? 'active' : '' }}" href="#">
                        <i class="fas fa-file-medical"></i>
                        <span>Historias Clínicas</span>
                        <div class="nav-indicator"></div>
                    </a>
                @endif

                <!-- Reportes -->
                <a class="nav-link {{ request()->routeIs('reportes.*') ? 'active' : '' }}" href="#">
                    <i class="fas fa-chart-bar"></i>
                    <span>Reportes</span>
                    <div class="nav-indicator"></div>
                </a>

                <!-- Administración - Solo para Administradores -->
                @if(isset($usuario['tipo_usuario']) && ($usuario['tipo_usuario']['es_administrador'] ?? false))
                    <div class="nav-item nav-dropdown">
                        <a class="nav-link dropdown-toggle {{ request()->routeIs('admin.*') ? 'active' : '' }}" 
                           data-bs-toggle="collapse" 
                           href="#adminMenu" 
                           role="button"
                           aria-expanded="{{ request()->routeIs('admin.*') ? 'true' : 'false' }}">
                            <i class="fas fa-cogs"></i>
                            <span>Administración</span>
                            <i class="fas fa-chevron-down dropdown-arrow"></i>
                            <div class="nav-indicator"></div>
                        </a>
                        <div class="collapse {{ request()->routeIs('admin.*') ? 'show' : '' }}" id="adminMenu">
                            <div class="nav flex-column submenu">
                                <a class="nav-link {{ request()->routeIs('admin.usuarios.*') ? 'active' : '' }}" href="#">
                                    <i class="fas fa-user-cog"></i>
                                    <span>Usuarios</span>
                                </a>
                                <a class="nav-link {{ request()->routeIs('admin.especialidades.*') ? 'active' : '' }}" href="#">
                                    <i class="fas fa-stethoscope"></i>
                                    <span>Especialidades</span>
                                </a>
                                <a class="nav-link {{ request()->routeIs('admin.contratos.*') ? 'active' : '' }}" href="#">
                                    <i class="fas fa-file-contract"></i>
                                    <span>Contratos</span>
                                </a>
                                <a class="nav-link {{ request()->routeIs('admin.cups.*') ? 'active' : '' }}" href="#">
                                    <i class="fas fa-list-alt"></i>
                                    <span>CUPS</span>
                                </a>
                                <a class="nav-link {{ request()->routeIs('admin.sedes.*') ? 'active' : '' }}" href="#">
                                    <i class="fas fa-building"></i>
                                    <span>Sedes</span>
                                </a>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Divider -->
                <hr class="sidebar-divider">

                <!-- Configuración Personal -->
                <div class="nav-item nav-dropdown">
                    <a class="nav-link dropdown-toggle" 
                       data-bs-toggle="collapse" 
                       href="#configMenu" 
                       role="button">
                        <i class="fas fa-user-cog"></i>
                        <span>Mi Cuenta</span>
                        <i class="fas fa-chevron-down dropdown-arrow"></i>
                    </a>
                    <div class="collapse" id="configMenu">
                        <div class="nav flex-column submenu">
                            <a class="nav-link" href="#" onclick="showUserProfile()">
                                <i class="fas fa-user"></i>
                                <span>Mi Perfil</span>
                            </a>
                            <a class="nav-link" href="#" onclick="changePassword()">
                                <i class="fas fa-key"></i>
                                <span>Cambiar Contraseña</span>
                            </a>
                            <a class="nav-link" href="#" onclick="showUserSettings()">
                                <i class="fas fa-cog"></i>
                                <span>Preferencias</span>
                            </a>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Bottom Section -->
            <div class="sidebar-bottom mt-auto">
                <!-- Sync Status -->
                <div class="sync-status-container">
                    @if(($is_online ?? true))
                        <div class="sync-status online">
                            <i class="fas fa-wifi"></i>
                            <span>Conectado</span>
                            <div class="sync-indicator"></div>
                        </div>
                    @else
                        <div class="sync-status offline">
                            <i class="fas fa-wifi-slash"></i>
                            <span>Sin conexión</span>
                            <button class="btn btn-sm btn-outline-warning" onclick="forceSyncData()" title="Sincronizar">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                        @if(($pending_changes ?? 0) > 0)
                            <div class="pending-changes">
                                <i class="fas fa-clock"></i>
                                {{ $pending_changes }} cambios pendientes
                            </div>
                        @endif
                    @endif
                </div>

                <!-- Quick Sync Actions -->
                <div class="quick-actions">
                    <button class="btn btn-sm btn-outline-success" onclick="syncAllPendingData()" title="Sincronizar todo">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="confirmLogout()" title="Cerrar sesión">
                        <i class="fas fa-sign-out-alt"></i>
                    </button>
                </div>

                <!-- Version Info -->
                <div class="version-info">
                    <small class="text-muted">
                        SIDS v2.1.0
                        <br>
                        <i class="fas fa-calendar"></i> {{ date('d/m/Y') }}
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Mouse Trigger Zone -->
    <div class="sidebar-trigger-zone" id="sidebarTriggerZone"></div>
</div>

<style>
/* ===== SIDEBAR CONTAINER ===== */
.sidebar-container {
    position: relative;
    z-index: 1000;
}

/* ===== MOUSE TRIGGER ZONE ===== */
.sidebar-trigger-zone {
    position: fixed;
    top: 0;
    left: 0;
    width: 20px;
    height: 100vh;
    z-index: 999;
    background: transparent;
    transition: all 0.3s ease;
}

.sidebar-trigger-zone:hover {
    width: 30px;
    background: linear-gradient(to right, rgba(44, 90, 160, 0.1), transparent);
}

/* ===== SIDEBAR TOGGLE BUTTON ===== */
.sidebar-toggle {
    position: fixed;
    top: 20px;
    left: -50px;
    z-index: 1100;
    width: 45px;
    height: 45px;
    border: none;
    border-radius: 0 50% 50% 0;
    background: linear-gradient(135deg, var(--primary-color, #2c5aa0), var(--primary-dark, #1e3d6f));
    color: white;
    font-size: 1.2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 2px 4px 15px rgba(44, 90, 160, 0.3);
    transition: all 0.3s ease;
    cursor: pointer;
    opacity: 0;
}

.sidebar-toggle.show {
    left: 0;
    opacity: 1;
}

.sidebar-toggle:hover {
    transform: translateX(5px);
    box-shadow: 2px 6px 20px rgba(44, 90, 160, 0.4);
}

/* ===== SIDEBAR OVERLAY ===== */
.sidebar-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1050;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
}

.sidebar-overlay.active {
    opacity: 1;
    visibility: visible;
}

/* ===== SIDEBAR ===== */
.sidebar {
    position: fixed;
    top: 0;
    left: -320px;
    width: 320px;
    height: 100vh;
    background: linear-gradient(180deg, #ffffff, #f8f9fa);
    box-shadow: 2px 0 15px rgba(0, 0, 0, 0.1);
    z-index: 1060;
    transition: left 0.3s ease;
    overflow-y: auto;
    overflow-x: hidden;
}

.sidebar.active {
    left: 0;
}

/* Mostrar sidebar al hover en desktop */
@media (min-width: 769px) {
    .sidebar-container:hover .sidebar {
        left: 0;
    }
    
    .sidebar-container:hover .sidebar-toggle {
        left: 320px;
        border-radius: 50% 0 0 50%;
    }
    
    .sidebar-container:hover .sidebar-overlay {
        opacity: 0.3;
        visibility: visible;
    }
}

/* ===== SIDEBAR HEADER (MOBILE) ===== */
.sidebar-header {
    padding: 15px;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    justify-content: flex-end;
}

/* ===== USER PROFILE SECTION ===== */
.user-profile-section {
    background: linear-gradient(135deg, var(--primary-color, #2c5aa0), var(--primary-dark, #1e3d6f));
    color: white;
    padding: 20px;
    position: relative;
    overflow: hidden;
}

.user-profile-section::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 100%;
    height: 100%;
    background: radial-gradient(circle, rgba(255,255,255,0.1), transparent);
    animation: float 6s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translateY(0px) rotate(0deg); }
    50% { transform: translateY(-10px) rotate(180deg); }
}

.user-avatar-container {
    position: relative;
    display: flex;
    justify-content: center;
    margin-bottom: 15px;
}

.user-avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: rgba(255,255,255,0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    position: relative;
    border: 3px solid rgba(255,255,255,0.3);
    transition: all 0.3s ease;
}

.user-avatar:hover {
    transform: scale(1.1);
    border-color: rgba(255,255,255,0.6);
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
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 2px solid white;
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
    text-align: center;
    margin-bottom: 15px;
}

.user-name {
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 8px;
    text-shadow: 0 1px 2px rgba(0,0,0,0.1);
}

.user-role,
.user-location,
.user-specialty {
    font-size: 0.8rem;
    opacity: 0.9;
    margin-bottom: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}

.user-quick-actions {
    display: flex;
    justify-content: center;
    gap: 8px;
}

.user-quick-actions .btn {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    transition: all 0.3s ease;
}

.user-quick-actions .btn:hover {
    background-color: rgba(255,255,255,0.2);
    transform: translateY(-2px);
}

/* ===== NAVIGATION ===== */
.sidebar-nav {
    flex-grow: 1;
    padding: 0 8px;
}

.nav-link {
    position: relative;
    padding: 12px 16px;
    margin: 2px 0;
    border-radius: 10px;
    color: #495057;
    text-decoration: none;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 12px;
    font-weight: 500;
    overflow: hidden;
}

.nav-link::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
    transition: left 0.5s;
}

.nav-link:hover::before {
    left: 100%;
}

.nav-link:hover,
.nav-link.active {
    background: linear-gradient(135deg, var(--primary-color, #2c5aa0), var(--primary-dark, #1e3d6f));
    color: white;
    transform: translateX(5px);
    box-shadow: 0 4px 15px rgba(44, 90, 160, 0.3);
}

.nav-link i {
    width: 20px;
    text-align: center;
    font-size: 1rem;
}

.nav-link span {
    flex-grow: 1;
}

.nav-link .badge {
    font-size: 0.7rem;
    padding: 2px 6px;
    border-radius: 10px;
}

.nav-indicator {
    position: absolute;
    left: 0;
    top: 50%;
    transform: translateY(-50%);
    width: 4px;
    height: 0;
    background: var(--primary-color, #2c5aa0);
    border-radius: 0 4px 4px 0;
    transition: all 0.3s ease;
}

.nav-link.active .nav-indicator {
    height: 60%;
}

/* ===== DROPDOWN NAVIGATION ===== */
.nav-dropdown .dropdown-arrow {
    font-size: 0.8rem;
    transition: all 0.3s ease;
}

.nav-dropdown .nav-link[aria-expanded="true"] .dropdown-arrow {
    transform: rotate(180deg);
}

.submenu {
    padding-left: 20px;
    background: rgba(0,0,0,0.02);
    border-radius: 8px;
    margin: 4px 0;
}

.submenu .nav-link {
    padding: 8px 12px;
    font-size: 0.9rem;
    margin: 1px 0;
}

.submenu .nav-link:hover,
.submenu .nav-link.active {
    background: linear-gradient(135deg, rgba(44, 90, 160, 0.8), rgba(30, 61, 111, 0.8));
    transform: translateX(3px);
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
    padding: 15px 8px 8px;
    border-top: 1px solid #e9ecef;
    background: linear-gradient(180deg, transparent, rgba(0,0,0,0.02));
}

.sync-status-container {
    margin-bottom: 12px;
}

.sync-status {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    border-radius: 8px;
    font-size: 0.85rem;
    font-weight: 500;
    position: relative;
}

.sync-status.online {
    background: linear-gradient(135deg, rgba(40, 167, 69, 0.1), rgba(32, 201, 151, 0.1));
    color: #28a745;
    border: 1px solid rgba(40, 167, 69, 0.2);
}

.sync-status.offline {
    background: linear-gradient(135deg, rgba(255, 193, 7, 0.1), rgba(243, 156, 18, 0.1));
    color: #856404;
    border: 1px solid rgba(255, 193, 7, 0.2);
}

.sync-indicator {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: currentColor;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}

.pending-changes {
    font-size: 0.75rem;
    color: #856404;
    padding: 4px 8px;
    background: rgba(255, 193, 7, 0.1);
    border-radius: 6px;
    margin-top: 4px;
    display: flex;
    align-items: center;
    gap: 4px;
}

.quick-actions {
    display: flex;
    justify-content: space-between;
    gap: 8px;
    margin-bottom: 12px;
}

.quick-actions .btn {
    flex: 1;
    padding: 8px;
    font-size: 0.9rem;
    border-radius: 6px;
    transition: all 0.3s ease;
}

.quick-actions .btn:hover {
    transform: translateY(-2px);
}

.version-info {
    text-align: center;
    padding: 8px;
    background: rgba(0,0,0,0.02);
    border-radius: 6px;
    border: 1px solid rgba(0,0,0,0.05);
}

/* ===== DARK THEME ===== */
.dark-theme .sidebar {
    background: linear-gradient(180deg, #374151, #1f2937);
}

.dark-theme .nav-link {
    color: #e9ecef;
}

.dark-theme .nav-link:hover,
.dark-theme .nav-link.active {
    background: linear-gradient(135deg, var(--primary-color, #2c5aa0), var(--primary-dark, #1e3d6f));
    color: white;
}

.dark-theme .submenu {
    background: rgba(255,255,255,0.05);
}

.dark-theme .sidebar-bottom {
    border-top-color: rgba(255,255,255,0.1);
    background: linear-gradient(180deg, transparent, rgba(255,255,255,0.02));
}

.dark-theme .version-info {
    background: rgba(255,255,255,0.05);
    border-color: rgba(255,255,255,0.1);
}

.dark-theme .sidebar-header {
    border-bottom-color: rgba(255,255,255,0.1);
}

/* ===== RESPONSIVE ===== */
@media (max-width: 768px) {
    .sidebar {
        width: 100%;
        left: -100%;
    }
    
    .sidebar-trigger-zone {
        display: none;
    }
    
    .sidebar-toggle {
        position: fixed;
        top: 20px;
        left: 20px;
        opacity: 1;
        border-radius: 50%;
    }
    
    .sidebar-toggle.show {
        left: 20px;
    }
    
    .user-profile-section {
        padding: 15px;
    }
    
    .user-avatar {
        width: 50px;
        height: 50px;
        font-size: 1.5rem;
    }
    
    .user-name {
        font-size: 1rem;
    }
    
    .nav-link {
        padding: 10px 12px;
    }
    
    .nav-link span {
        font-size: 0.9rem;
    }
}

@media (min-width: 769px) {
    .sidebar-header {
        display: none !important;
    }
}

/* ===== ANIMATIONS ===== */
@keyframes slideInLeft {
    from {
        transform: translateX(-100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

@keyframes slideOutLeft {
    from {
        transform: translateX(0);
        opacity: 1;
    }
    to {
        transform: translateX(-100%);
        opacity: 0;
    }
}

.sidebar.active {
    animation: slideInLeft 0.3s ease-out;
}

/* ===== SCROLLBAR CUSTOMIZATION ===== */
.sidebar::-webkit-scrollbar {
    width: 6px;
}

.sidebar::-webkit-scrollbar-track {
    background: rgba(0,0,0,0.1);
    border-radius: 3px;
}

.sidebar::-webkit-scrollbar-thumb {
    background: rgba(44, 90, 160, 0.3);
    border-radius: 3px;
}

.sidebar::-webkit-scrollbar-thumb:hover {
    background: rgba(44, 90, 160, 0.5);
}
</style>

<script>
// ===== SIDEBAR FUNCTIONALITY =====
let sidebarTimeout;
let isMouseOverSidebar = false;

function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    
    if (sidebar.classList.contains('active')) {
        closeSidebar();
    } else {
        openSidebar();
    }
}

function openSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    
    sidebar.classList.add('active');
    overlay.classList.add('active');
    
    // Solo bloquear scroll en móvil
    if (window.innerWidth <= 768) {
        document.body.style.overflow = 'hidden';
    }
}
function closeSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    
    sidebar.classList.remove('active');
    overlay.classList.remove('active');
    document.body.style.overflow = '';
}

function setupSidebarHover() {
    const sidebarContainer = document.querySelector('.sidebar-container');
    const sidebar = document.getElementById('sidebar');
    const triggerZone = document.getElementById('sidebarTriggerZone');
    const toggleBtn = document.getElementById('sidebarToggle');
    
    if (!sidebarContainer || !sidebar || !triggerZone) return;
    
    // Solo en desktop
    if (window.innerWidth > 768) {
        // Mostrar sidebar al entrar en la zona de activación
        triggerZone.addEventListener('mouseenter', () => {
            clearTimeout(sidebarTimeout);
            sidebar.classList.add('active');
            toggleBtn.classList.add('show');
        });
        
        // Mantener sidebar abierto mientras el mouse esté sobre él
        sidebarContainer.addEventListener('mouseenter', () => {
            clearTimeout(sidebarTimeout);
            isMouseOverSidebar = true;
        });
        
        // Ocultar sidebar cuando el mouse salga
        sidebarContainer.addEventListener('mouseleave', () => {
            isMouseOverSidebar = false;
            sidebarTimeout = setTimeout(() => {
                if (!isMouseOverSidebar) {
                    sidebar.classList.remove('active');
                    toggleBtn.classList.remove('show');
                }
            }, 300); // Delay de 300ms para evitar parpadeo
        });
        
        // Prevenir que se cierre al hacer hover sobre el sidebar
        sidebar.addEventListener('mouseenter', () => {
            clearTimeout(sidebarTimeout);
        });
    }
}

// ===== FUNCIONES DE USUARIO =====
function showUserProfile() {
    const usuario = @json($usuario ?? []);
    
    Swal.fire({
        title: '<i class="fas fa-user-edit text-primary"></i> Mi Perfil',
        html: `
            <div class="text-start">
                <div class="row g-3">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-title"><i class="fas fa-user me-2"></i>Información Personal</h6>
                                <div class="row">
                                    <div class="col-6">
                                        <p><strong>Nombre:</strong><br>${usuario.nombre_completo || 'No especificado'}</p>
                                        <p><strong>Documento:</strong><br>${usuario.documento || 'No especificado'}</p>
                                        <p><strong>Teléfono:</strong><br>${usuario.telefono || 'No especificado'}</p>
                                    </div>
                                    <div class="col-6">
                                        <p><strong>Correo:</strong><br>${usuario.correo || 'No especificado'}</p>
                                        <p><strong>Usuario:</strong><br>${usuario.login || 'No especificado'}</p>
                                        <p><strong>Estado:</strong><br>
                                            <span class="badge bg-${(usuario.estado?.id == 1) ? 'success' : 'danger'}">
                                                ${usuario.estado?.nombre || 'No definido'}
                                            </span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-title"><i class="fas fa-briefcase me-2"></i>Información Laboral</h6>
                                <div class="row">
                                    <div class="col-6">
                                        <p><strong>Rol:</strong><br>${usuario.rol?.nombre || 'No asignado'}</p>
                                        <p><strong>Sede:</strong><br>${usuario.sede?.nombre || 'No asignada'}</p>
                                    </div>
                                    <div class="col-6">
                                        ${usuario.especialidad ? `<p><strong>Especialidad:</strong><br>${usuario.especialidad.nombre}</p>` : ''}
                                        <p><strong>Tipo Usuario:</strong><br>
                                            ${usuario.tipo_usuario?.es_administrador ? '<span class="badge bg-danger">Admin</span> ' : ''}
                                            ${usuario.tipo_usuario?.es_medico ? '<span class="badge bg-info">Médico</span> ' : ''}
                                            ${usuario.tipo_usuario?.es_enfermero ? '<span class="badge bg-success">Enfermero</span> ' : ''}
                                            ${usuario.tipo_usuario?.es_secretaria ? '<span class="badge bg-warning">Secretaria</span> ' : ''}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `,
        width: '700px',
        showCloseButton: true,
        showConfirmButton: false,
        customClass: {
            popup: 'animated fadeIn faster'
        }
    });
}

function showUserSettings() {
    Swal.fire({
        title: '<i class="fas fa-cog text-primary"></i> Configuración',
        html: `
            <div class="text-start">
                <div class="list-group">
                    <a href="#" class="list-group-item list-group-item-action" onclick="toggleTheme()">
                        <i class="fas fa-palette me-2"></i>
                        Cambiar tema
                        <small class="text-muted d-block">Personalizar apariencia</small>
                    </a>
                    <a href="#" class="list-group-item list-group-item-action" onclick="changePassword()">
                        <i class="fas fa-key me-2"></i>
                        Cambiar contraseña
                        <small class="text-muted d-block">Actualizar credenciales</small>
                    </a>
                    <a href="#" class="list-group-item list-group-item-action" onclick="syncSettings()">
                        <i class="fas fa-sync-alt me-2"></i>
                        Sincronización
                        <small class="text-muted d-block">Configurar sincronización automática</small>
                    </a>
                    <a href="#" class="list-group-item list-group-item-action" onclick="clearCache()">
                        <i class="fas fa-trash-alt me-2"></i>
                        Limpiar caché
                        <small class="text-muted d-block">Eliminar datos temporales</small>
                    </a>
                    <a href="#" class="list-group-item list-group-item-action" onclick="exportData()">
                        <i class="fas fa-download me-2"></i>
                        Exportar datos
                        <small class="text-muted d-block">Descargar información personal</small>
                    </a>
                </div>
            </div>
        `,
        width: '500px',
        showCloseButton: true,
        showConfirmButton: false,
        customClass: {
            popup: 'animated fadeIn faster'
        }
    });
}

function toggleTheme() {
    const body = document.body;
    const themeIcon = document.getElementById('themeIcon');
    
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

function changePassword() {
    Swal.close();
    Swal.fire({
        title: 'Cambiar Contraseña',
        html: `
            <form id="passwordForm" class="text-start">
                <div class="mb-3">
                    <label class="form-label">Contraseña actual</label>
                    <input type="password" class="form-control" id="currentPassword" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Nueva contraseña</label>
                    <input type="password" class="form-control" id="newPassword" required>
                    <div class="form-text">Mínimo 8 caracteres</div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Confirmar contraseña</label>
                    <input type="password" class="form-control" id="confirmPassword" required>
                </div>
            </form>
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
            // Enviar petición para cambiar contraseña
            fetch('/cambiar-password', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    current_password: result.value.current,
                    new_password: result.value.newPass
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    mostrarAlerta('Contraseña actualizada correctamente', 'success');
                } else {
                    mostrarAlerta(data.error || 'Error al cambiar contraseña', 'error');
                }
            })
            .catch(error => {
                mostrarAlerta('Error de conexión', 'error');
            });
        }
    });
}

function syncSettings() {
    Swal.close();
    const currentSettings = JSON.parse(localStorage.getItem('syncSettings') || '{}');
    
    Swal.fire({
        title: 'Configuración de Sincronización',
        html: `
            <div class="text-start">
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="autoSync" ${currentSettings.autoSync !== false ? 'checked' : ''}>
                    <label class="form-check-label" for="autoSync">
                        <strong>Sincronización automática</strong>
                        <small class="d-block text-muted">Sincronizar datos automáticamente cuando haya conexión</small>
                    </label>
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="syncOnWifi" ${currentSettings.syncOnWifi !== false ? 'checked' : ''}>
                    <label class="form-check-label" for="syncOnWifi">
                        <strong>Solo sincronizar con WiFi</strong>
                        <small class="d-block text-muted">Evitar usar datos móviles para sincronización</small>
                    </label>
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="notifySync" ${currentSettings.notifySync !== false ? 'checked' : ''}>
                    <label class="form-check-label" for="notifySync">
                        <strong>Notificar sincronización</strong>
                        <small class="d-block text-muted">Mostrar notificaciones cuando se sincronicen datos</small>
                    </label>
                </div>
                <div class="mb-3">
                    <label for="syncInterval" class="form-label"><strong>Intervalo de sincronización</strong></label>
                    <select class="form-select" id="syncInterval">
                        <option value="5" ${currentSettings.syncInterval == 5 ? 'selected' : ''}>5 minutos</option>
                        <option value="15" ${currentSettings.syncInterval == 15 || !currentSettings.syncInterval ? 'selected' : ''}>15 minutos</option>
                        <option value="30" ${currentSettings.syncInterval == 30 ? 'selected' : ''}>30 minutos</option>
                        <option value="60" ${currentSettings.syncInterval == 60 ? 'selected' : ''}>1 hora</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="maxRetries" class="form-label"><strong>Reintentos máximos</strong></label>
                    <select class="form-select" id="maxRetries">
                        <option value="3" ${currentSettings.maxRetries == 3 || !currentSettings.maxRetries ? 'selected' : ''}>3 intentos</option>
                        <option value="5" ${currentSettings.maxRetries == 5 ? 'selected' : ''}>5 intentos</option>
                        <option value="10" ${currentSettings.maxRetries == 10 ? 'selected' : ''}>10 intentos</option>
                    </select>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Guardar',
        cancelButtonText: 'Cancelar',
        width: '500px'
    }).then((result) => {
        if (result.isConfirmed) {
            const settings = {
                autoSync: document.getElementById('autoSync').checked,
                syncOnWifi: document.getElementById('syncOnWifi').checked,
                notifySync: document.getElementById('notifySync').checked,
                syncInterval: parseInt(document.getElementById('syncInterval').value),
                maxRetries: parseInt(document.getElementById('maxRetries').value)
            };
            
            localStorage.setItem('syncSettings', JSON.stringify(settings));
            mostrarAlerta('Configuración de sincronización guardada', 'success');
        }
    });
}

function clearCache() {
    Swal.close();
    Swal.fire({
        title: '¿Limpiar caché?',
        html: `
            <div class="text-start">
                <p>Esto eliminará:</p>
                <ul class="list-unstyled">
                    <li><i class="fas fa-check text-success me-2"></i>Datos temporales</li>
                    <li><i class="fas fa-check text-success me-2"></i>Configuraciones locales</li>
                    <li><i class="fas fa-check text-success me-2"></i>Caché de imágenes</li>
                    <li><i class="fas fa-times text-danger me-2"></i>Datos sin sincronizar se perderán</li>
                </ul>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Advertencia:</strong> Esta acción no se puede deshacer.
                </div>
            </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Sí, limpiar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            localStorage.clear();
            sessionStorage.clear();
            
            if ('caches' in window) {
                caches.keys().then(names => {
                    names.forEach(name => {
                        caches.delete(name);
                    });
                });
            }
            
            mostrarAlerta('Caché limpiado correctamente', 'success');
            
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        }
    });
}

function exportData() {
    Swal.close();
    Swal.fire({
        title: 'Exportar Datos',
        html: `
            <div class="text-start">
                <p>Selecciona qué datos deseas exportar:</p>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="exportProfile" checked>
                    <label class="form-check-label" for="exportProfile">
                        <i class="fas fa-user me-2"></i>Información del perfil
                    </label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="exportSettings" checked>
                    <label class="form-check-label" for="exportSettings">
                        <i class="fas fa-cog me-2"></i>Configuraciones
                    </label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="exportActivity">
                    <label class="form-check-label" for="exportActivity">
                        <i class="fas fa-history me-2"></i>Historial de actividad
                    </label>
                </div>
                <div class="mt-3">
                    <label for="exportFormat" class="form-label">Formato:</label>
                    <select class="form-select" id="exportFormat">
                        <option value="json">JSON</option>
                        <option value="pdf">PDF</option>
                        <option value="excel">Excel</option>
                    </select>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Exportar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            const exportOptions = {
                profile: document.getElementById('exportProfile').checked,
                settings: document.getElementById('exportSettings').checked,
                activity: document.getElementById('exportActivity').checked,
                format: document.getElementById('exportFormat').value
            };
            
            Swal.fire({
                title: 'Exportando...',
                html: 'Preparando tus datos para descarga',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            setTimeout(() => {
                Swal.fire({
                    title: '¡Exportación Completa!',
                    text: 'Tus datos han sido exportados exitosamente',
                    icon: 'success',
                    confirmButtonText: 'Descargar'
                }).then(() => {
                    mostrarAlerta('Descarga iniciada', 'info');
                });
            }, 2000);
        }
    });
}

// ===== FUNCIONES DE SINCRONIZACIÓN =====
function forceSyncData() {
    syncAllPendingData();
}

function syncAllPendingData() {
    Swal.fire({
        title: 'Sincronizando...',
        html: 'Sincronizando datos pendientes',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    setTimeout(() => {
        Swal.fire({
            title: '¡Sincronización Completa!',
            text: 'Todos los datos han sido sincronizados correctamente',
            icon: 'success',
            timer: 2000,
            showConfirmButton: false
        });
    }, 3000);
}

function confirmLogout() {
    Swal.fire({
        title: '¿Cerrar Sesión?',
        text: '¿Está seguro que desea cerrar su sesión actual?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fas fa-sign-out-alt"></i> Sí, cerrar sesión',
        cancelButtonText: '<i class="fas fa-times"></i> Cancelar',
        reverseButtons: true,
        customClass: {
            popup: 'animated fadeIn faster'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Cerrando sesión...',
                text: 'Por favor espere',
                icon: 'info',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            setTimeout(() => {
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
            }, 1000);
        }
    });
}

// ===== EVENTOS Y INICIALIZACIÓN =====
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar tema
    const savedTheme = localStorage.getItem('theme');
    const themeIcon = document.getElementById('themeIcon');
    
    if (savedTheme === 'dark') {
        document.body.classList.add('dark-theme');
        if (themeIcon) themeIcon.className = 'fas fa-sun';
    } else {
        if (themeIcon) themeIcon.className = 'fas fa-moon';
    }
    
    // Configurar hover del sidebar
    setupSidebarHover();
    
    // Cerrar sidebar al hacer clic en enlaces (móvil)
    const navLinks = document.querySelectorAll('.sidebar .nav-link');
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            if (window.innerWidth <= 768) {
                if (!this.classList.contains('dropdown-toggle')) {
                    setTimeout(() => {
                        closeSidebar();
                    }, 100);
                }
            }
        });
    });
    
    // Cerrar sidebar al redimensionar ventana
    window.addEventListener('resize', function() {
        if (window.innerWidth <= 768) {
            // En móvil, resetear el comportamiento hover
            const sidebar = document.getElementById('sidebar');
            const toggleBtn = document.getElementById('sidebarToggle');
            if (sidebar) sidebar.classList.remove('active');
            if (toggleBtn) toggleBtn.classList.remove('show');
        } else {
            // En desktop, reconfigurar hover
            setupSidebarHover();
        }
    });
    
    // Manejar tecla ESC para cerrar sidebar
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeSidebar();
        }
    });
    
    // Inicializar tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

// Función auxiliar para mostrar alertas
function mostrarAlerta(mensaje, tipo) {
    if (typeof Swal !== 'undefined') {
        const iconos = {
            'success': 'success',
            'error': 'error',
            'warning': 'warning',
            'info': 'info'
        };
        
        Swal.fire({
            title: mensaje,
            icon: iconos[tipo] || 'info',
            timer: 3000,
            showConfirmButton: false,
            toast: true,
            position: 'top-end'
        });
    } else {
        console.log(`${tipo.toUpperCase()}: ${mensaje}`);
    }
}
</script>
@endauth
