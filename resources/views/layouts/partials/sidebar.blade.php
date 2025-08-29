<div class="d-flex flex-column h-100">
    <!-- User Info -->
    @if(isset($usuario) && $usuario)
        <div class="user-info">
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-user-circle fa-2x"></i>
                </div>
                <div class="flex-grow-1 ms-3">
                    <h6 class="mb-0">{{ $usuario['nombre_completo'] ?? 'Usuario' }}</h6>
                    <small class="opacity-75">{{ $usuario['rol']['nombre'] ?? 'Sin rol' }}</small>
                    <br>
                    <small class="opacity-75">
                        <i class="fas fa-building"></i> {{ $usuario['sede']['nombre'] ?? 'Sin sede' }}
                    </small>
                </div>
            </div>
        </div>
    @endif

    <!-- Navigation Menu -->
    <nav class="nav flex-column">
        <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a>

        @if(isset($usuario['tipo_usuario']) && ($usuario['tipo_usuario']['es_administrador'] ?? false) || ($usuario['tipo_usuario']['es_secretaria'] ?? false))
            <a class="nav-link {{ request()->routeIs('pacientes.*') ? 'active' : '' }}" href="#">
                <i class="fas fa-users"></i> Pacientes
            </a>

            <a class="nav-link {{ request()->routeIs('citas.*') ? 'active' : '' }}" href="#">
                <i class="fas fa-calendar-alt"></i> Citas
            </a>
        @endif

        @if(isset($usuario['tipo_usuario']) && ($usuario['tipo_usuario']['es_medico'] ?? false) || ($usuario['tipo_usuario']['es_enfermero'] ?? false))
            <a class="nav-link {{ request()->routeIs('agenda.*') ? 'active' : '' }}" href="#">
                <i class="fas fa-calendar-check"></i> Mi Agenda
            </a>

            <a class="nav-link {{ request()->routeIs('historias.*') ? 'active' : '' }}" href="#">
                <i class="fas fa-file-medical"></i> Historias Clínicas
            </a>
        @endif

        @if(isset($usuario['tipo_usuario']) && ($usuario['tipo_usuario']['es_administrador'] ?? false))
            <div class="nav-item">
                <a class="nav-link" data-bs-toggle="collapse" href="#adminMenu" role="button">
                    <i class="fas fa-cogs"></i> Administración
                    <i class="fas fa-chevron-down float-end mt-1"></i>
                </a>
                <div class="collapse {{ request()->routeIs('admin.*') ? 'show' : '' }}" id="adminMenu">
                    <div class="nav flex-column ms-3">
                        <a class="nav-link {{ request()->routeIs('admin.usuarios.*') ? 'active' : '' }}" href="#">
                            <i class="fas fa-user-cog"></i> Usuarios
                        </a>
                        <a class="nav-link {{ request()->routeIs('admin.especialidades.*') ? 'active' : '' }}" href="#">
                            <i class="fas fa-stethoscope"></i> Especialidades
                        </a>
                        <a class="nav-link {{ request()->routeIs('admin.contratos.*') ? 'active' : '' }}" href="#">
                            <i class="fas fa-file-contract"></i> Contratos
                        </a>
                        <a class="nav-link {{ request()->routeIs('admin.cups.*') ? 'active' : '' }}" href="#">
                            <i class="fas fa-list-alt"></i> CUPS
                        </a>
                    </div>
                </div>
            </div>
        @endif

        <!-- Sync Status -->
        <div class="mt-auto p-3">
            @if(($is_online ?? true))
                <div class="alert alert-success alert-sm mb-0">
                    <i class="fas fa-check-circle"></i> En línea
                </div>
            @else
                <div class="alert alert-warning alert-sm mb-0">
                    <i class="fas fa-exclamation-triangle"></i> Modo offline
                </div>
            @endif
        </div>
    </nav>
</div>
