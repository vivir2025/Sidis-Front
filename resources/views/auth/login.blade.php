@extends('layouts.app')

@section('title', 'Iniciar Sesión - SIDIS')

@push('styles')
<style>
    body {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
    }

    .login-container {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .login-card {
        background: white;
        border-radius: 20px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        overflow: hidden;
        max-width: 420px;
        width: 100%;
        animation: slideUp 0.6s ease-out;
    }

    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .login-header {
        background: linear-gradient(135deg, var(--primary-color), #1e3d6f);
        color: white;
        padding: 40px 30px 30px;
        text-align: center;
        position: relative;
        overflow: hidden;
    }

    .login-header::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        animation: pulse 4s ease-in-out infinite;
    }

    @keyframes pulse {
        0%, 100% { transform: scale(0.8); opacity: 0.5; }
        50% { transform: scale(1.2); opacity: 0.8; }
    }

    .login-header h1 {
        font-size: 2.2rem;
        margin-bottom: 10px;
        font-weight: 600;
        position: relative;
        z-index: 1;
    }

    .login-header .subtitle {
        opacity: 0.95;
        font-size: 0.95rem;
        position: relative;
        z-index: 1;
    }

    .logo {
        font-size: 3.5rem;
        margin-bottom: 15px;
        color: rgba(255,255,255,0.95);
        position: relative;
        z-index: 1;
        animation: heartbeat 2s ease-in-out infinite;
    }

    @keyframes heartbeat {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.05); }
    }

    .login-body {
        padding: 40px 30px;
        background: linear-gradient(to bottom, #ffffff 0%, #f8f9fa 100%);
    }

    .form-floating {
        margin-bottom: 20px;
        position: relative;
    }

    .form-floating .form-control,
    .form-floating .form-select {
        border: 2px solid #e9ecef;
        border-radius: 12px;
        padding: 15px;
        font-size: 1rem;
        transition: all 0.3s ease;
        background-color: #fff;
    }

    .form-floating .form-control:focus,
    .form-floating .form-select:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 0.25rem rgba(44, 90, 160, 0.15);
        transform: translateY(-2px);
    }

    .form-floating label {
        color: #6c757d;
        font-weight: 500;
    }

    .btn-login {
        background: linear-gradient(135deg, var(--primary-color), #1e3d6f);
        border: none;
        border-radius: 12px;
        padding: 16px;
        font-size: 1.1rem;
        font-weight: 600;
        width: 100%;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .btn-login::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
        transition: left 0.5s;
    }

    .btn-login:hover::before {
        left: 100%;
    }

    .btn-login:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 35px rgba(44, 90, 160, 0.4);
    }

    .btn-login:active {
        transform: translateY(-1px);
    }

    .connection-indicator {
        text-align: center;
        margin-bottom: 25px;
        padding: 12px;
        border-radius: 12px;
        font-size: 0.9rem;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .connection-online {
        background: linear-gradient(135deg, #d4edda, #c3e6cb);
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .connection-offline {
        background: linear-gradient(135deg, #f8d7da, #f5c6cb);
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .sede-info {
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        border-radius: 12px;
        padding: 18px;
        margin-bottom: 20px;
        border-left: 4px solid var(--primary-color);
        transition: all 0.3s ease;
        animation: fadeIn 0.5s ease-out;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateX(-20px); }
        to { opacity: 1; transform: translateX(0); }
    }

    .sede-info small {
        color: #6c757d;
    }

    .form-check {
        margin-bottom: 25px;
    }

    .form-check-input:checked {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
    }

    .form-check-label {
        font-weight: 500;
        color: #495057;
    }

    .offline-notice {
        background: linear-gradient(135deg, #d1ecf1, #bee5eb);
        border: 1px solid #b6d4db;
        border-radius: 12px;
        padding: 15px;
        margin-top: 20px;
        color: #0c5460;
    }

    /* Loading spinner enhancement */
    .fa-spinner {
        animation: spin 1s linear infinite;
    }

    /* Mobile responsiveness */
    @media (max-width: 576px) {
        .login-card {
            margin: 10px;
            max-width: none;
        }
        
        .login-header {
            padding: 30px 20px 20px;
        }
        
        .login-body {
            padding: 30px 20px;
        }
        
        .logo {
            font-size: 2.5rem;
        }
        
        .login-header h1 {
            font-size: 1.8rem;
        }
    }
</style>
@endpush

@section('content')
<div class="login-container">
    <div class="login-card">
        <div class="login-header">
            <div class="logo">
                <i class="fas fa-heartbeat"></i>
            </div>
            <h1>SIDIS</h1>
            <p class="subtitle">Sistema Integral de Información en Salud</p>
        </div>

        <div class="login-body">
            <!-- Connection Status -->
            <div class="connection-indicator {{ ($isOnline ?? true) ? 'connection-online' : 'connection-offline' }}">
                <i class="fas fa-{{ ($isOnline ?? true) ? 'wifi' : 'exclamation-triangle' }}"></i>
                {{ ($isOnline ?? true) ? 'Conectado al servidor' : 'Modo offline disponible' }}
            </div>

            <form method="POST" action="{{ route('login') }}" id="loginForm">
                @csrf

                <!-- Sede Selection -->
                <div class="form-floating">
                    <select class="form-select @error('sede_id') is-invalid @enderror" 
                            id="sede_id" name="sede_id" required>
                        <option value="">Seleccione una sede...</option>
                        @foreach(($sedes ?? []) as $sede)
                            <option value="{{ $sede['id'] }}" 
                                    {{ old('sede_id') == $sede['id'] ? 'selected' : '' }}>
                                {{ $sede['nombre'] }}
                            </option>
                        @endforeach
                    </select>
                    <label for="sede_id">
                        <i class="fas fa-building me-2"></i>Sede
                    </label>
                    @error('sede_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Selected Sede Info -->
                <div id="sedeInfo" class="sede-info" style="display: none;">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-map-marker-alt text-primary me-2"></i>
                        <div>
                            <strong id="sedeNombre"></strong>
                            <br>
                            <small>Sede seleccionada</small>
                        </div>
                    </div>
                </div>

                <!-- Usuario -->
                <div class="form-floating">
                    <input type="text" 
                           class="form-control @error('login') is-invalid @enderror" 
                           id="login" 
                           name="login" 
                           value="{{ old('login') }}" 
                           required 
                           autocomplete="username"
                           placeholder="Usuario">
                    <label for="login">
                        <i class="fas fa-user me-2"></i>Usuario
                    </label>
                    @error('login')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Contraseña -->
                <div class="form-floating">
                    <input type="password" 
                           class="form-control @error('password') is-invalid @enderror" 
                           id="password" 
                           name="password" 
                           required 
                           autocomplete="current-password"
                           placeholder="Contraseña">
                    <label for="password">
                        <i class="fas fa-lock me-2"></i>Contraseña
                    </label>
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Remember Me -->
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="remember" name="remember">
                    <label class="form-check-label" for="remember">
                        <i class="fas fa-remember me-1"></i>
                        Recordar mis datos
                    </label>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn btn-primary btn-login" id="loginBtn">
                    <span id="loginBtnText">
                        <i class="fas fa-sign-in-alt me-2"></i>Iniciar Sesión
                    </span>
                    <span id="loginBtnLoading" style="display: none;">
                        <i class="fas fa-spinner fa-spin me-2"></i>Iniciando sesión...
                    </span>
                </button>
            </form>

            @if(!($isOnline ?? true))
                <div class="offline-notice">
                    <div class="d-flex align-items-start">
                        <i class="fas fa-info-circle me-2 mt-1"></i>
                        <div>
                            <strong>Modo Offline Activo</strong>
                            <br>
                            <small>Solo usuarios previamente autenticados pueden acceder sin conexión.</small>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const sedeSelect = document.getElementById('sede_id');
    const sedeInfo = document.getElementById('sedeInfo');
    const sedeNombre = document.getElementById('sedeNombre');
    const loginForm = document.getElementById('loginForm');
    const loginBtn = document.getElementById('loginBtn');
    const loginBtnText = document.getElementById('loginBtnText');
    const loginBtnLoading = document.getElementById('loginBtnLoading');

    // Handle sede selection
    sedeSelect.addEventListener('change', function() {
        if (this.value) {
            const selectedOption = this.options[this.selectedIndex];
            sedeNombre.textContent = selectedOption.text;
            sedeInfo.style.display = 'block';
        } else {
            sedeInfo.style.display = 'none';
        }
    });

    // Handle form submission
    loginForm.addEventListener('submit', function(e) {
        loginBtn.disabled = true;
        loginBtnText.style.display = 'none';
        loginBtnLoading.style.display = 'inline';
        
        // Prevent double submission
        setTimeout(() => {
            if (loginBtn.disabled) {
                loginBtn.disabled = false;
                loginBtnText.style.display = 'inline';
                loginBtnLoading.style.display = 'none';
            }
        }, 10000); // Reset after 10 seconds if no response
    });

    // Auto-focus on first empty field
    const firstEmptyField = document.querySelector('select:not([value]), input[type="text"]:not([value]), input[type="password"]:not([value])');
    if (firstEmptyField) {
        setTimeout(() => firstEmptyField.focus(), 500);
    }

    // Remember form data
    if (localStorage.getItem('remember_login') === 'true') {
        document.getElementById('remember').checked = true;
        document.getElementById('login').value = localStorage.getItem('saved_login') || '';
        document.getElementById('sede_id').value = localStorage.getItem('saved_sede_id') || '';
        
        // Trigger sede change event
        if (document.getElementById('sede_id').value) {
            sedeSelect.dispatchEvent(new Event('change'));
        }
    }

    // Save form data when remember is checked
    document.getElementById('remember').addEventListener('change', function() {
        if (this.checked) {
            localStorage.setItem('remember_login', 'true');
            localStorage.setItem('saved_login', document.getElementById('login').value);
            localStorage.setItem('saved_sede_id', document.getElementById('sede_id').value);
        } else {
            localStorage.removeItem('remember_login');
            localStorage.removeItem('saved_login');
            localStorage.removeItem('saved_sede_id');
        }
    });

    // Update saved data when fields change
    document.getElementById('login').addEventListener('input', function() {
        if (document.getElementById('remember').checked) {
            localStorage.setItem('saved_login', this.value);
        }
    });

    sedeSelect.addEventListener('change', function() {
        if (document.getElementById('remember').checked) {
            localStorage.setItem('saved_sede_id', this.value);
        }
    });

    // Add smooth transitions to form elements
    const formElements = document.querySelectorAll('.form-control, .form-select');
    formElements.forEach(element => {
        element.addEventListener('focus', function() {
            this.parentElement.style.transform = 'translateY(-2px)';
        });
        
        element.addEventListener('blur', function() {
            this.parentElement.style.transform = 'translateY(0)';
        });
    });
});
</script>
@endpush
