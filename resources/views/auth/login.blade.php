@extends('layouts.app')

@section('title', 'Iniciar Sesión - SIDIS')

@push('styles')
<style>
    :root {
        --npv-green: #6DB33F;
        --npv-dark-green: #4A9B2E;
        --npv-blue: #2C5AA0;
        --npv-dark-blue: #1e3d6f;
        --npv-light-blue: #4A7BC8;
    }

    body {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        height: 100vh;
        margin: 0;
        padding: 0;
        overflow: hidden;
        position: relative;
    }

    body::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-image: 
            radial-gradient(circle at 20% 50%, rgba(109, 179, 63, 0.1) 0%, transparent 50%),
            radial-gradient(circle at 80% 80%, rgba(44, 90, 160, 0.1) 0%, transparent 50%);
        pointer-events: none;
    }

    .login-container {
        height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        position: relative;
        z-index: 1;
    }

    .login-wrapper {
        display: flex;
        background: white;
        border-radius: 20px;
        box-shadow: 
            0 20px 60px rgba(0,0,0,0.1),
            0 0 0 1px rgba(0,0,0,0.05);
        overflow: hidden;
        max-width: 1100px;
        width: 100%;
        max-height: 90vh;
        animation: slideUp 0.8s cubic-bezier(0.16, 1, 0.3, 1);
    }

    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(40px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Left Panel - Branding */
    .login-brand {
        flex: 0 0 45%;
        background: linear-gradient(135deg, var(--npv-blue) 0%, var(--npv-dark-blue) 100%);
        padding: 40px 35px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        position: relative;
        overflow: hidden;
        color: white;
    }

    .login-brand::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(109, 179, 63, 0.15) 0%, transparent 70%);
        animation: pulse 8s ease-in-out infinite;
    }

    @keyframes pulse {
        0%, 100% { transform: scale(1) rotate(0deg); }
        50% { transform: scale(1.1) rotate(5deg); }
    }

    .brand-logo {
        width: 140px;
        height: 140px;
        margin-bottom: 25px;
        position: relative;
        z-index: 1;
        animation: float 3s ease-in-out infinite;
        background: white;
        border-radius: 16px;
        padding: 15px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    }

    .brand-logo img {
        width: 100%;
        height: 100%;
        object-fit: contain;
    }

    @keyframes float {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-8px); }
    }

    .brand-content {
        text-align: center;
        position: relative;
        z-index: 1;
    }

    .brand-content h1 {
        font-size: 2.2rem;
        font-weight: 700;
        margin-bottom: 12px;
        text-shadow: 0 2px 10px rgba(0,0,0,0.2);
    }

    .brand-content .subtitle {
        font-size: 1rem;
        opacity: 0.95;
        font-weight: 400;
        line-height: 1.5;
        margin-bottom: 25px;
    }

    .brand-features {
        margin-top: 30px;
        text-align: left;
    }

    .feature-item {
        display: flex;
        align-items: center;
        margin-bottom: 16px;
        opacity: 0.9;
    }

    .feature-item i {
        font-size: 1.3rem;
        margin-right: 12px;
        color: var(--npv-green);
        background: rgba(255,255,255,0.2);
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
    }

    .feature-item span {
        font-size: 0.95rem;
    }

    /* Right Panel - Form */
    .login-form-panel {
        flex: 0 0 55%;
        padding: 50px 60px;
        background: white;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .form-header {
        margin-bottom: 22px;
    }

    .form-header h2 {
        font-size: 1.6rem;
        font-weight: 700;
        color: var(--npv-dark-blue);
        margin-bottom: 6px;
    }

    .form-header p {
        color: #6c757d;
        font-size: 0.85rem;
        margin-bottom: 0;
    }

    /* Connection Status */
    .connection-indicator {
        display: inline-flex;
        align-items: center;
        padding: 7px 14px;
        border-radius: 50px;
        font-size: 0.75rem;
        font-weight: 600;
        margin-bottom: 18px;
        transition: all 0.3s ease;
    }

    .connection-online {
        background: linear-gradient(135deg, #d4edda, #c3e6cb);
        color: #155724;
    }

    .connection-online i {
        color: #28a745;
        margin-right: 6px;
        font-size: 0.8rem;
    }

    .connection-offline {
        background: linear-gradient(135deg, #fff3cd, #ffeaa7);
        color: #856404;
    }

    .connection-offline i {
        color: #ffc107;
        margin-right: 6px;
        font-size: 0.8rem;
    }

    /* Form Styles */
    .form-group {
        margin-bottom: 16px;
        position: relative;
    }

    .form-label {
        display: block;
        font-weight: 600;
        color: var(--npv-dark-blue);
        margin-bottom: 7px;
        font-size: 0.8rem;
    }

    .form-label i {
        margin-right: 5px;
        color: var(--npv-green);
        font-size: 0.8rem;
    }

    .form-control,
    .form-select {
        width: 100%;
        padding: 10px 14px;
        border: 2px solid #e9ecef;
        border-radius: 10px;
        font-size: 0.85rem;
        transition: all 0.3s ease;
        background-color: #f8f9fa;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: var(--npv-green);
        box-shadow: 0 0 0 3px rgba(109, 179, 63, 0.1);
        background-color: white;
        outline: none;
    }

    .form-control.is-invalid,
    .form-select.is-invalid {
        border-color: #dc3545;
    }

    .invalid-feedback {
        display: block;
        color: #dc3545;
        font-size: 0.75rem;
        margin-top: 5px;
    }

    /* Sede Info */
    .sede-info {
        background: linear-gradient(135deg, #e8f5e9, #f1f8f4);
        border-radius: 10px;
        padding: 10px 14px;
        margin-bottom: 16px;
        border-left: 3px solid var(--npv-green);
        display: flex;
        align-items: center;
        animation: fadeInSlide 0.5s ease-out;
    }

    @keyframes fadeInSlide {
        from { 
            opacity: 0; 
            transform: translateX(-20px); 
        }
        to { 
            opacity: 1; 
            transform: translateX(0); 
        }
    }

    .sede-info i {
        font-size: 1.1rem;
        color: var(--npv-green);
        margin-right: 10px;
    }

    .sede-info strong {
        color: var(--npv-dark-blue);
        display: block;
        font-size: 0.8rem;
    }

    .sede-info small {
        color: #6c757d;
        font-size: 0.7rem;
    }

    /* Checkbox */
    .form-check {
        margin-bottom: 18px;
        display: flex;
        align-items: center;
    }

    .form-check-input {
        width: 16px;
        height: 16px;
        margin-right: 8px;
        border: 2px solid #dee2e6;
        border-radius: 5px;
        cursor: pointer;
    }

    .form-check-input:checked {
        background-color: var(--npv-green);
        border-color: var(--npv-green);
    }

    .form-check-label {
        font-weight: 500;
        color: #495057;
        cursor: pointer;
        user-select: none;
        font-size: 0.8rem;
    }

    /* Submit Button */
    .btn-login {
        width: 100%;
        padding: 12px;
        background: linear-gradient(135deg, var(--npv-green), var(--npv-dark-green));
        border: none;
        border-radius: 10px;
        color: white;
        font-size: 0.9rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(109, 179, 63, 0.3);
    }

    .btn-login::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
        transition: left 0.5s;
    }

    .btn-login:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(109, 179, 63, 0.4);
    }

    .btn-login:hover::before {
        left: 100%;
    }

    .btn-login:active {
        transform: translateY(0);
    }

    .btn-login:disabled {
        opacity: 0.7;
        cursor: not-allowed;
        transform: none;
    }

    .btn-login i {
        font-size: 0.85rem;
        margin-right: 6px;
    }

    /* Offline Notice */
    .offline-notice {
        background: linear-gradient(135deg, #fff3cd, #ffeaa7);
        border: 1px solid #ffc107;
        border-radius: 10px;
        padding: 10px 14px;
        margin-top: 16px;
        display: flex;
        align-items-start;
    }

    .offline-notice i {
        color: #856404;
        font-size: 0.9rem;
        margin-right: 10px;
        margin-top: 2px;
    }

    .offline-notice strong {
        color: #856404;
        display: block;
        margin-bottom: 3px;
        font-size: 0.8rem;
    }

    .offline-notice small {
        color: #856404;
        opacity: 0.9;
        font-size: 0.7rem;
    }

    /* Loading Animation */
    .fa-spinner {
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }

    /* Responsive Design */
    @media (max-width: 992px) {
        .login-wrapper {
            flex-direction: column;
            max-width: 500px;
            max-height: 95vh;
            overflow-y: auto;
        }

        .login-brand {
            flex: 0 0 auto;
            padding: 30px 25px;
        }

        .brand-logo {
            width: 100px;
            height: 100px;
            margin-bottom: 15px;
        }

        .brand-content h1 {
            font-size: 1.8rem;
        }

        .brand-content .subtitle {
            font-size: 0.9rem;
        }

        .brand-features {
            display: none;
        }

        .login-form-panel {
            flex: 0 0 auto;
            padding: 30px 25px;
        }

        .form-header h2 {
            font-size: 1.5rem;
        }
    }

    @media (max-width: 576px) {
        .login-container {
            padding: 10px;
        }

        .login-wrapper {
            border-radius: 16px;
        }

        .login-brand {
            padding: 25px 20px;
        }

        .brand-logo {
            width: 90px;
            height: 90px;
        }

        .brand-content h1 {
            font-size: 1.6rem;
        }

        .login-form-panel {
            padding: 25px 20px;
        }

        .form-header h2 {
            font-size: 1.4rem;
        }

        .form-group {
            margin-bottom: 14px;
        }
    }

    /* Altura mínima para pantallas muy pequeñas */
    @media (max-height: 700px) {
        .login-wrapper {
            max-height: 95vh;
        }

        .brand-logo {
            width: 100px;
            height: 100px;
        }

        .brand-features {
            margin-top: 20px;
        }

        .feature-item {
            margin-bottom: 12px;
        }

        .form-group {
            margin-bottom: 14px;
        }

        .form-header {
            margin-bottom: 18px;
        }

        .login-form-panel {
            padding: 40px 50px;
        }
    }
</style>
@endpush

@section('content')
<div class="login-container">
    <div class="login-wrapper">
        <!-- Left Panel - Branding -->
        <div class="login-brand">
            <div class="brand-logo">
                <img src="{{ asset('images/logo-fundacion.png') }}" alt="Fundación Nacer Para Vivir">
            </div>
            <div class="brand-content">
                <h1>SIDIS</h1>
                <p class="subtitle">Organización Comunitaria Llegando a Tu Vida </p>
                
                <div class="brand-features">
                    <div class="feature-item">
                        <i class="fas fa-shield-alt"></i>
                        <span>Acceso seguro y protegido</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-heartbeat"></i>
                        <span>Gestión integral de salud</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-users"></i>
                        <span>Atención personalizada</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Panel - Form -->
        <div class="login-form-panel">
            <div class="form-header">
                <h2>Bienvenido</h2>
                <p>Ingresa tus credenciales para continuar</p>
            </div>

            <!-- Connection Status -->
            <div class="connection-indicator {{ ($isOnline ?? true) ? 'connection-online' : 'connection-offline' }}">
                <i class="fas fa-{{ ($isOnline ?? true) ? 'wifi' : 'exclamation-triangle' }}"></i>
                {{ ($isOnline ?? true) ? 'Conectado al servidor' : 'Modo offline disponible' }}
            </div>

            <form method="POST" action="{{ route('login') }}" id="loginForm">
                @csrf

                <!-- Sede Selection -->
                <div class="form-group">
                    <label for="sede_id" class="form-label">
                        <i class="fas fa-building"></i>Sede
                    </label>
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
                    @error('sede_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Selected Sede Info -->
                <div id="sedeInfo" class="sede-info" style="display: none;">
                    <i class="fas fa-map-marker-alt"></i>
                    <div>
                        <strong id="sedeNombre"></strong>
                        <small>Sede seleccionada</small>
                    </div>
                </div>

                <!-- Usuario -->
                <div class="form-group">
                    <label for="login" class="form-label">
                        <i class="fas fa-user"></i>Usuario
                    </label>
                    <input type="text" 
                           class="form-control @error('login') is-invalid @enderror" 
                           id="login" 
                           name="login" 
                           value="{{ old('login') }}" 
                           required 
                           autocomplete="username"
                           placeholder="Ingresa tu usuario">
                    @error('login')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Contraseña -->
                <div class="form-group">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock"></i>Contraseña
                    </label>
                    <input type="password" 
                           class="form-control @error('password') is-invalid @enderror" 
                           id="password" 
                           name="password" 
                           required 
                           autocomplete="current-password"
                           placeholder="Ingresa tu contraseña">
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Remember Me -->
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="remember" name="remember">
                    <label class="form-check-label" for="remember">
                        Recordar mis datos
                    </label>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn btn-login" id="loginBtn">
                    <span id="loginBtnText">
                        <i class="fas fa-sign-in-alt"></i>Iniciar Sesión
                    </span>
                    <span id="loginBtnLoading" style="display: none;">
                        <i class="fas fa-spinner fa-spin"></i>Iniciando sesión...
                    </span>
                </button>
            </form>

            @if(!($isOnline ?? true))
                <div class="offline-notice">
                    <i class="fas fa-info-circle"></i>
                    <div>
                        <strong>Modo Offline Activo</strong>
                        <small>Solo usuarios previamente autenticados pueden acceder sin conexión.</small>
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
            sedeInfo.style.display = 'flex';
        } else {
            sedeInfo.style.display = 'none';
        }
    });

    // Handle form submission
    loginForm.addEventListener('submit', function(e) {
        loginBtn.disabled = true;
        loginBtnText.style.display = 'none';
        loginBtnLoading.style.display = 'inline';
        
        setTimeout(() => {
            if (loginBtn.disabled) {
                loginBtn.disabled = false;
                loginBtnText.style.display = 'inline';
                loginBtnLoading.style.display = 'none';
            }
        }, 10000);
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
});
</script>
@endpush