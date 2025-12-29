@extends('layouts.app')

@section('title', 'Iniciar Sesión - SIDS')

@push('styles')
<style>
    :root {
        --npv-green: #6DB33F;
        --npv-dark-green: #4A9B2E;
        --npv-blue: #2C5AA0;
        --npv-dark-blue: #1e3d6f;
        --npv-light-blue: #4A7BC8;
    }

    /* Forzar que body ocupe toda la pantalla sin navbar */
    html, body {
        height: 100%;
        margin: 0;
        padding: 0;
        overflow-x: hidden;
    }

    body.login-page {
        background: linear-gradient(135deg, #e8f0f7 0%, #d4dfe9 50%, #c5d4e3 100%);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        overflow: hidden;
    }

    /* Fondo decorativo con patrón */
    body.login-page::before {
        content: '';
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-image: 
            repeating-linear-gradient(90deg, rgba(44, 90, 160, 0.02) 0px, transparent 1px, transparent 50px, rgba(44, 90, 160, 0.02) 51px),
            repeating-linear-gradient(0deg, rgba(44, 90, 160, 0.02) 0px, transparent 1px, transparent 50px, rgba(44, 90, 160, 0.02) 51px),
            radial-gradient(circle at 15% 20%, rgba(109, 179, 63, 0.15) 0%, transparent 40%),
            radial-gradient(circle at 85% 80%, rgba(44, 90, 160, 0.15) 0%, transparent 40%),
            radial-gradient(circle at 50% 50%, rgba(109, 179, 63, 0.08) 0%, transparent 60%);
        pointer-events: none;
        z-index: 0;
    }

    /* Elementos decorativos flotantes */
    body.login-page::after {
        content: '';
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-image: 
            url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%232C5AA0' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        opacity: 0.4;
        pointer-events: none;
        z-index: 0;
    }

    /* Círculos decorativos animados */
    .login-container::before,
    .login-container::after {
        content: '';
        position: fixed;
        border-radius: 50%;
        pointer-events: none;
        z-index: 0;
    }

    .login-container::before {
        width: 400px;
        height: 400px;
        background: radial-gradient(circle, rgba(109, 179, 63, 0.1) 0%, transparent 70%);
        top: -200px;
        left: -200px;
        animation: float-circle 20s ease-in-out infinite;
    }

    .login-container::after {
        width: 350px;
        height: 350px;
        background: radial-gradient(circle, rgba(44, 90, 160, 0.1) 0%, transparent 70%);
        bottom: -175px;
        right: -175px;
        animation: float-circle 25s ease-in-out infinite reverse;
    }

    @keyframes float-circle {
        0%, 100% {
            transform: translate(0, 0) scale(1);
        }
        25% {
            transform: translate(30px, 30px) scale(1.1);
        }
        50% {
            transform: translate(-20px, 40px) scale(0.95);
        }
        75% {
            transform: translate(40px, -30px) scale(1.05);
        }
    }

    .login-container {
        width: 100%;
        height: 100vh;
        display: flex;
        align-items: stretch;
        justify-content: center;
        padding: 0;
        position: relative;
        z-index: 1;
    }

    .login-wrapper {
        display: flex;
        background: white;
        border-radius: 0;
        box-shadow: none;
        overflow: hidden;
        width: 100%;
        height: 100%;
        animation: fadeIn 0.6s ease-out;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }
        to {
            opacity: 1;
        }
    }

    /* Left Panel - Branding */
    .login-brand {
        flex: 0 0 45%;
        background: linear-gradient(135deg, var(--npv-blue) 0%, var(--npv-dark-blue) 100%);
        padding: 60px 50px;
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
        margin-bottom: 30px;
        position: relative;
        z-index: 1;
        animation: float 3s ease-in-out infinite;
        background: white;
        border-radius: 20px;
        padding: 15px;
        box-shadow: 0 15px 40px rgba(0,0,0,0.3);
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
        font-size: 2.8rem;
        font-weight: 700;
        margin-bottom: 15px;
        text-shadow: 0 3px 15px rgba(0,0,0,0.3);
        letter-spacing: 2px;
    }

    .brand-content .subtitle {
        font-size: 1.1rem;
        opacity: 0.95;
        font-weight: 400;
        line-height: 1.5;
        margin-bottom: 40px;
    }

    .brand-features {
        margin-top: 40px;
        text-align: left;
        width: 100%;
        max-width: 350px;
    }

    .feature-item {
        display: flex;
        align-items: center;
        margin-bottom: 20px;
        opacity: 0.9;
        transition: all 0.3s ease;
    }

    .feature-item:hover {
        opacity: 1;
        transform: translateX(5px);
    }

    .feature-item i {
        font-size: 1.5rem;
        margin-right: 15px;
        color: var(--npv-green);
        background: rgba(255,255,255,0.2);
        width: 50px;
        height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
    }

    .feature-item span {
        font-size: 1rem;
        font-weight: 500;
    }

    /* Right Panel - Form */
    .login-form-panel {
        flex: 0 0 55%;
        padding: 60px 80px;
        background: white;
        display: flex;
        flex-direction: column;
        justify-content: center;
        overflow-y: auto;
    }

    .form-header {
        margin-bottom: 30px;
    }

    .form-header h2 {
        font-size: 2rem;
        font-weight: 700;
        color: var(--npv-dark-blue);
        margin-bottom: 8px;
    }

    .form-header p {
        color: #6c757d;
        font-size: 1rem;
        margin-bottom: 0;
    }

    /* Connection Status */
    .connection-indicator {
        display: inline-flex;
        align-items: center;
        padding: 10px 18px;
        border-radius: 50px;
        font-size: 0.9rem;
        font-weight: 600;
        margin-bottom: 25px;
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
        margin-bottom: 20px;
        position: relative;
    }

    .form-label {
        display: block;
        font-weight: 600;
        color: var(--npv-dark-blue);
        margin-bottom: 10px;
        font-size: 0.95rem;
    }

    .form-label i {
        margin-right: 8px;
        color: var(--npv-green);
        font-size: 0.95rem;
    }

    .form-control,
    .form-select {
        width: 100%;
        padding: 14px 18px;
        border: 2px solid #e9ecef;
        border-radius: 12px;
        font-size: 1rem;
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
        padding: 16px;
        background: linear-gradient(135deg, var(--npv-green), var(--npv-dark-green));
        border: none;
        border-radius: 12px;
        color: white;
        font-size: 1.1rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
        box-shadow: 0 6px 20px rgba(109, 179, 63, 0.4);
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
    /* Tablet (md y lg) */
    @media (max-width: 992px) {
        .login-wrapper {
            flex-direction: column;
        }

        .login-brand {
            flex: 0 0 auto;
            min-height: 40vh;
            padding: 40px 30px;
        }

        .brand-logo {
            width: 100px;
            height: 100px;
            margin-bottom: 20px;
        }

        .brand-content h1 {
            font-size: 2.2rem;
        }

        .brand-content .subtitle {
            font-size: 1rem;
            margin-bottom: 25px;
        }

        .brand-features {
            margin-top: 20px;
        }

        .feature-item {
            margin-bottom: 15px;
            font-size: 0.95rem;
        }

        .feature-item i {
            width: 42px;
            height: 42px;
            font-size: 1.3rem;
        }

        .login-form-panel {
            flex: 1;
            padding: 40px 35px;
        }

        .form-header h2 {
            font-size: 1.8rem;
        }

        .form-header p {
            font-size: 0.95rem;
        }

        .form-control,
        .form-select {
            padding: 12px 16px;
            font-size: 0.95rem;
        }
    }

    /* Móviles pequeños */
    @media (max-width: 576px) {
        body.login-page {
            align-items: stretch;
        }

        .login-container {
            padding: 0;
            height: 100vh;
        }

        .login-wrapper {
            border-radius: 0;
            flex-direction: column;
        }

        .login-brand {
            min-height: 35vh;
            padding: 30px 25px;
        }

        .brand-logo {
            width: 85px;
            height: 85px;
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
            padding: 30px 25px;
        }

        .form-header {
            margin-bottom: 25px;
        }

        .form-header h2 {
            font-size: 1.6rem;
        }

        .form-header p {
            font-size: 0.9rem;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-label {
            font-size: 0.85rem;
        }

        .form-control,
        .form-select {
            padding: 12px 14px;
            font-size: 0.9rem;
        }

        .connection-indicator {
            font-size: 0.8rem;
            padding: 8px 14px;
            margin-bottom: 20px;
        }

        .btn-login {
            padding: 14px;
            font-size: 1rem;
        }
    }

    /* Pantallas extra pequeñas */
    @media (max-width: 380px) {
        .login-brand {
            min-height: 30vh;
            padding: 25px 20px;
        }

        .brand-logo {
            width: 75px;
            height: 75px;
        }

        .brand-content h1 {
            font-size: 1.6rem;
        }

        .brand-content .subtitle {
            font-size: 0.85rem;
        }

        .login-form-panel {
            padding: 25px 20px;
        }

        .form-header h2 {
            font-size: 1.4rem;
        }
    }

    /* Altura mínima para pantallas muy pequeñas */
    @media (max-height: 700px) and (min-width: 993px) {
        .login-brand {
            padding: 40px 45px;
        }

        .brand-logo {
            width: 110px;
            height: 110px;
            margin-bottom: 20px;
        }

        .brand-content h1 {
            font-size: 2.2rem;
        }

        .brand-features {
            margin-top: 25px;
        }

        .feature-item {
            margin-bottom: 15px;
            font-size: 0.95rem;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-header {
            margin-bottom: 25px;
        }

        .login-form-panel {
            padding: 40px 60px;
        }
    }

    /* Iconos médicos decorativos en los laterales */
    .medical-decoration {
        position: fixed;
        color: rgba(44, 90, 160, 0.08);
        pointer-events: none;
        z-index: 0;
        font-size: 3rem;
        animation: float 4s ease-in-out infinite;
    }

    .medical-decoration:nth-child(1) { top: 10%; left: 5%; animation-delay: 0s; font-size: 4rem; }
    .medical-decoration:nth-child(2) { top: 25%; right: 8%; animation-delay: 1s; font-size: 3.5rem; }
    .medical-decoration:nth-child(3) { top: 45%; left: 3%; animation-delay: 2s; font-size: 3rem; }
    .medical-decoration:nth-child(4) { top: 60%; right: 5%; animation-delay: 1.5s; font-size: 3.8rem; }
    .medical-decoration:nth-child(5) { top: 80%; left: 7%; animation-delay: 0.5s; font-size: 3.2rem; color: rgba(109, 179, 63, 0.08); }
    .medical-decoration:nth-child(6) { top: 70%; right: 10%; animation-delay: 2.5s; font-size: 3.5rem; color: rgba(109, 179, 63, 0.08); }

    /* Ocultar decoraciones en pantallas pequeñas */
    @media (max-width: 1400px) {
        .medical-decoration {
            display: none;
        }
    }

    /* Landscape en móviles */
    @media (max-height: 500px) and (max-width: 992px) {
        .login-wrapper {
            flex-direction: row;
        }

        .login-brand {
            flex: 0 0 40%;
            min-height: 100vh;
            padding: 30px 25px;
        }

        .brand-logo {
            width: 70px;
            height: 70px;
            margin-bottom: 12px;
        }

        .brand-content h1 {
            font-size: 1.5rem;
        }

        .brand-content .subtitle {
            font-size: 0.8rem;
        }

        .brand-features {
            display: none;
        }

        .login-form-panel {
            flex: 0 0 60%;
            padding: 25px 30px;
        }

        .form-group {
            margin-bottom: 12px;
        }

        .form-header {
            margin-bottom: 18px;
        }

        .form-header h2 {
            font-size: 1.4rem;
        }

        .form-control,
        .form-select {
            padding: 10px 14px;
            font-size: 0.85rem;
        }

        .btn-login {
            padding: 12px;
            font-size: 0.95rem;
        }
    }
</style>
@endpush

@section('content')
<!-- Iconos médicos decorativos flotantes -->
<i class="fas fa-heartbeat medical-decoration"></i>
<i class="fas fa-stethoscope medical-decoration"></i>
<i class="fas fa-user-md medical-decoration"></i>
<i class="fas fa-hospital medical-decoration"></i>
<i class="fas fa-notes-medical medical-decoration"></i>
<i class="fas fa-hand-holding-heart medical-decoration"></i>

<div class="login-container">
    <div class="login-wrapper">
        <!-- Left Panel - Branding -->
        <div class="login-brand">
            <div class="brand-logo">
                <img src="{{ asset('images/logo-fundacion.png') }}" alt="Fundación Nacer Para Vivir">
            </div>
            <div class="brand-content">
                <h1>SIDS</h1>
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