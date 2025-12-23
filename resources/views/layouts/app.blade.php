<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SIDIS - Sistema de Información')</title>

    <!-- Bootstrap 5 CSS (Local para modo offline) -->
    <link href="{{ asset('css/vendor/bootstrap/bootstrap.min.css') }}" rel="stylesheet">
    
    <!-- Font Awesome (Local para modo offline) -->
    <link rel="stylesheet" href="{{ asset('css/vendor/fontawesome/css/all.min.css') }}">
    
    <!-- SweetAlert2 (Local para modo offline) -->
    <link rel="stylesheet" href="{{ asset('css/vendor/sweetalert2/sweetalert2.min.css') }}">
    
    <!-- Select2 CSS (Local para modo offline) -->
    <link href="{{ asset('css/vendor/select2/select2.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('css/vendor/select2/select2-bootstrap-5-theme.min.css') }}" rel="stylesheet" />

    <!-- Estilos personalizados -->
    <style>
                :root {
            --primary-color: #2c5aa0;
            --primary-dark: #1e3d6f;
            --secondary-color: #f8f9fa;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
            --dark-bg: #1a1a1a;
            --dark-card: #2d3748;
            --dark-border: #4b5563;
            --border-radius: 12px;
            --box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            --transition: all 0.3s ease;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--secondary-color);
            line-height: 1.6;
            transition: var(--transition);
        }

        /* ===== NAVBAR ===== */
        .navbar {
            background: linear-gradient(135deg, #ffffff, #f8f9fa) !important;
            border-bottom: 1px solid #e9ecef;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            padding: 0.75rem 0;
        }

        
        .sede-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 10px;
            background: #003366;
            color: #ffffff;
            border-radius: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 51, 102, 0.3);
            font-size: 11px;
            max-width: 35px;
            overflow: hidden;
        }

        .sede-badge:hover {
            background: #004080;
            box-shadow: 0 3px 6px rgba(0, 51, 102, 0.4);
            max-width: 200px;
            padding: 5px 12px;
        }

        .sede-badge i {
            font-size: 12px;
            min-width: 12px;
        }

        .sede-badge span {
            white-space: nowrap;
            font-weight: 600;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .sede-badge:hover span {
            opacity: 1;
        }


        /* ===== MODAL HEADER PROFESIONAL ===== */
        .modal-header-professional {
            background: linear-gradient(to right, #0d47a1, #1976d2);
            border-bottom: none;
            padding: 1.5rem;
            box-shadow: 0 4px 12px rgba(13, 71, 161, 0.15);
        }

        .modal-header-professional .modal-icon-wrapper {
            width: 42px;
            height: 42px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .modal-header-professional .modal-icon-wrapper i {
            color: white;
            font-size: 1.3rem;
        }

        .modal-header-professional .modal-title {
            color: white;
            font-weight: 600;
            font-size: 1.25rem;
            letter-spacing: 0.5px;
        }

        .modal-header-professional .btn-close {
            filter: brightness(0) invert(1);
            opacity: 0.9;
            transition: all 0.3s ease;
        }

        .modal-header-professional .btn-close:hover {
            opacity: 1;
            transform: scale(1.1);
        }


        /* ===== USER AVATAR BUTTON ===== */
        .user-avatar-small {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            border: 2px solid #ffffff;
            box-shadow: 0 2px 8px rgba(44, 90, 160, 0.25);
            transition: all 0.3s ease;
            position: relative;
            cursor: pointer;
        }

        .user-avatar-small img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }

        .user-avatar-small i {
            font-size: 1.6rem;
            color: white;
            transition: all 0.3s ease;
        }

        /* Hover suave */
        .user-avatar-small:hover {
            box-shadow: 0 4px 12px rgba(44, 90, 160, 0.4);
            border-color: var(--primary-color);
        }

        .user-avatar-small:hover i {
            color: #ffffff;
        }

        /* ✅ EVITAR DISTORSIÓN AL HACER CLIC */
        .btn-link:focus,
        .btn-link:active,
        .btn-link:focus-visible {
            box-shadow: none !important;
            outline: none !important;
        }

        #userDropdown {
            padding: 0 !important;
            margin: 0 !important;
            border: none !important;
            background: transparent !important;
        }

        #userDropdown:focus,
        #userDropdown:active,
        #userDropdown:focus-visible {
            box-shadow: none !important;
            outline: none !important;
            transform: none !important;
        }

        /* Evitar que el avatar cambie al hacer clic */
        #userDropdown .user-avatar-small:active,
        #userDropdown.show .user-avatar-small {
            transform: none !important;
        }

        /* Badge de notificaciones (opcional) */
        .user-avatar-small .notification-badge {
            position: absolute;
            top: -3px;
            right: -3px;
            width: 16px;
            height: 16px;
            background: linear-gradient(135deg, #dc3545, #e74c3c);
            border-radius: 50%;
            border: 2px solid white;
            font-size: 0.6rem;
            color: white;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: pulse-notification 2s infinite;
        }

        @keyframes pulse-notification {
            0%, 100% {
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7);
            }
            50% {
                transform: scale(1.1);
                box-shadow: 0 0 0 4px rgba(220, 53, 69, 0);
            }
        }

        /* ===== RESPONSIVE USER AVATAR ===== */
        @media (max-width: 768px) {
            .user-avatar-small {
                width: 36px;
                height: 36px;
                border-width: 2px;
            }
            
            .user-avatar-small i {
                font-size: 1.5rem;
            }
        }

        @media (max-width: 576px) {
            .user-avatar-small {
                width: 34px;
                height: 34px;
            }
            
            .user-avatar-small i {
                font-size: 1.4rem;
            }
        }

        /* ===== DARK THEME USER AVATAR ===== */
        .dark-theme .user-avatar-small {
            border-color: var(--dark-card);
            box-shadow: 0 2px 8px rgba(44, 90, 160, 0.4);
        }

        .dark-theme .user-avatar-small:hover {
            border-color: var(--primary-color);
            box-shadow: 0 4px 12px rgba(44, 90, 160, 0.6);
        }


        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: var(--primary-color) !important;
            transition: var(--transition);
        }

        .navbar-brand:hover {
            transform: scale(1.05);
        }

        .navbar-brand i {
            margin-right: 8px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        /* ===== CONNECTION STATUS ===== */
        .connection-status {
            position: fixed;
            top: 15px;
            right: 15px;
            z-index: 1050;
            padding: 10px 16px;
            border-radius: 25px;
            font-size: 0.875rem;
            font-weight: 600;
            box-shadow: var(--box-shadow);
            transition: var(--transition);
            backdrop-filter: blur(10px);
        }

        .connection-online {
            background: linear-gradient(135deg, var(--success-color), #20c997);
            color: white;
        }

        .connection-offline {
            background: linear-gradient(135deg, var(--danger-color), #e74c3c);
            color: white;
        }

        .connection-syncing {
            background: linear-gradient(135deg, var(--warning-color), #f39c12);
            color: #212529;
        }

        /* ===== SIDEBAR ===== */
        .sidebar {
            min-height: calc(100vh - 76px);
            background: linear-gradient(180deg, #ffffff, #f8f9fa);
            box-shadow: 2px 0 15px rgba(0,0,0,0.1);
            border-right: 1px solid #e9ecef;
        }

        .sidebar .nav-link {
            color: #495057;
            padding: 14px 20px;
            margin: 4px 8px;
            border-radius: var(--border-radius);
            transition: var(--transition);
            font-weight: 500;
            position: relative;
            overflow: hidden;
        }

        .sidebar .nav-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            transition: left 0.5s;
        }

        .sidebar .nav-link:hover::before {
            left: 100%;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            transform: translateX(5px);
            box-shadow: 0 4px 15px rgba(44, 90, 160, 0.3);
        }

        .sidebar .nav-link i {
            width: 20px;
            margin-right: 12px;
            text-align: center;
        }

        /* ===== MAIN CONTENT ===== */
        .main-content {
            padding: 25px;
            background-color: var(--secondary-color);
            min-height: calc(100vh - 76px);
        }

        /* ===== CARDS ===== */
        .card {
            border: none;
            box-shadow: var(--box-shadow);
            border-radius: var(--border-radius);
            transition: var(--transition);
            overflow: hidden;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            border: none;
            padding: 1.25rem;
            font-weight: 600;
        }

        .card-body {
            padding: 1.5rem;
        }

        /* ===== BUTTONS ===== */
        .btn {
            border-radius: 8px;
            font-weight: 500;
            padding: 0.625rem 1.25rem;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255,255,255,0.3);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.3s, height 0.3s;
        }

        .btn:hover::before {
            width: 300px;
            height: 300px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            border: none;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--primary-dark), #0f2347);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(44, 90, 160, 0.4);
        }

        /* ===== BOTÓN VERIFICAR CONEXIÓN COMPACTO ===== */
        .btn-check-connection {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            border: 2px solid #6c757d;
            background: transparent;
            color: #6c757d;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            padding: 0;
            margin-right: 0.5rem;
            position: relative;
        }

        .btn-check-connection i {
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        /* Hover */
        .btn-check-connection:hover {
            background: #6c757d;
            color: white;
            border-color: #5a6268;
            box-shadow: 0 2px 8px rgba(108, 117, 125, 0.3);
        }

        .btn-check-connection:hover i {
            transform: rotate(180deg);
        }

        /* Focus (sin distorsión) */
        .btn-check-connection:focus,
        .btn-check-connection:active {
            outline: none !important;
            box-shadow: 0 0 0 3px rgba(108, 117, 125, 0.2) !important;
            transform: none !important;
        }

        /* Estado activo (al hacer clic) */
        .btn-check-connection:active {
            background: #5a6268;
            border-color: #545b62;
        }

        /* Animación de verificación */
        .btn-check-connection.checking i {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from {
                transform: rotate(0deg);
            }
            to {
                transform: rotate(360deg);
            }
        }

        /* Estado de conexión exitosa */
        .btn-check-connection.success {
            border-color: #28a745;
            color: #28a745;
            background: rgba(40, 167, 69, 0.1);
        }

        /* Estado de conexión fallida */
        .btn-check-connection.error {
            border-color: #dc3545;
            color: #dc3545;
            background: rgba(220, 53, 69, 0.1);
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .btn-check-connection {
                width: 36px;
                height: 36px;
            }
            
            .btn-check-connection i {
                font-size: 0.95rem;
            }
        }

        @media (max-width: 576px) {
            .btn-check-connection {
                width: 34px;
                height: 34px;
            }
            
            .btn-check-connection i {
                font-size: 0.9rem;
            }
        }

        /* ===== DARK THEME ===== */
        .dark-theme .btn-check-connection {
            border-color: #adb5bd;
            color: #adb5bd;
        }

        .dark-theme .btn-check-connection:hover {
            background: #495057;
            color: white;
            border-color: #6c757d;
        }

        .dark-theme .btn-check-connection.success {
            border-color: #28a745;
            color: #28a745;
        }

        .dark-theme .btn-check-connection.error {
            border-color: #dc3545;
            color: #dc3545;
        }


        /* ===== SEDE SELECTOR ===== */
        .sede-selector-container {
            background: linear-gradient(135deg, #ffffff, #f8f9fa);
            border-radius: var(--border-radius);
            padding: 20px;
            margin-bottom: 25px;
            border-left: 4px solid var(--primary-color);
            box-shadow: var(--box-shadow);
            position: relative;
            overflow: hidden;
        }

        .sede-selector-container::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: radial-gradient(circle, rgba(44, 90, 160, 0.1), transparent);
            border-radius: 50%;
            transform: translate(30px, -30px);
        }

        .sede-actual-info {
            transition: var(--transition);
            position: relative;
            z-index: 1;
        }

        .sede-actual-info:hover {
            transform: translateY(-2px);
        }

        #btnCambiarSede {
            border-radius: 20px;
            transition: var(--transition);
            font-size: 0.875rem;
            padding: 0.5rem 1rem;
        }

        #btnCambiarSede:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 15px rgba(var(--bs-primary-rgb), 0.3);
        }

        /* ===== MODALS ===== */
        .modal-content {
            border-radius: var(--border-radius);
            border: none;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            border: none;
            padding: 1.5rem;
        }

        .modal-body {
            padding: 2rem;
        }

        .modal-footer {
            border: none;
            padding: 1.5rem 2rem;
            background-color: #f8f9fa;
        }

        /* ===== FORMS ===== */
        .form-control,
        .form-select {
            border-radius: 8px;
            border: 2px solid #e9ecef;
            padding: 0.75rem 1rem;
            transition: var(--transition);
            font-size: 0.95rem;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(44, 90, 160, 0.15);
            transform: translateY(-1px);
        }

        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.75rem;
        }

        /* ===== ALERTS ===== */
        .alert {
            border-radius: var(--border-radius);
            border: none;
            padding: 1rem 1.25rem;
            font-weight: 500;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .alert-info {
            background: linear-gradient(135deg, #d1ecf1, #bee5eb);
            color: #0c5460;
            border-left: 4px solid var(--info-color);
        }

        .offline-indicator {
            background: linear-gradient(135deg, #fff3cd, #ffeaa7);
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px 20px;
            margin-bottom: 25px;
            border-radius: var(--border-radius);
            border-left: 4px solid var(--warning-color);
            box-shadow: var(--box-shadow);
        }

        /* ===== SYNC BUTTON ===== */
        .sync-button {
            position: fixed;
            bottom: 25px;
            right: 25px;
            z-index: 1040;
            border-radius: 50%;
            width: 65px;
            height: 65px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.3);
            border: none;
            background: linear-gradient(135deg, var(--warning-color), #f39c12);
            color: #212529;
            font-size: 1.25rem;
            transition: var(--transition);
        }

        .sync-button:hover {
            transform: scale(1.1);
            box-shadow: 0 8px 25px rgba(0,0,0,0.4);
        }

        .sync-button.spinning {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* ===== USER INFO ===== */
        .user-info {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 20px;
            border-radius: var(--border-radius);
            margin-bottom: 25px;
            box-shadow: var(--box-shadow);
        }

        /* ===== DROPDOWN ===== */
        .dropdown-menu {
            border: none;
            box-shadow: var(--box-shadow);
            border-radius: var(--border-radius);
            padding: 0.5rem 0;
        }

        .dropdown-item {
            padding: 0.75rem 1.25rem;
            transition: var(--transition);
            font-weight: 500;
        }

        .dropdown-item:hover {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            transform: translateX(5px);
        }

        .dropdown-item i {
            width: 20px;
            margin-right: 10px;
        }

        /* ===== DARK THEME ===== */
        .dark-theme {
            background-color: var(--dark-bg) !important;
            color: #e9ecef !important;
        }

        .dark-theme .navbar {
            background: linear-gradient(135deg, var(--dark-card), #374151) !important;
            border-bottom-color: var(--dark-border);
        }

        .dark-theme .sidebar {
            background: linear-gradient(180deg, var(--dark-card), #374151);
            border-right-color: var(--dark-border);
        }

        .dark-theme .sidebar .nav-link {
            color: #e9ecef !important;
        }

        .dark-theme .sidebar .nav-link:hover,
        .dark-theme .sidebar .nav-link.active {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark)) !important;
            color: white !important;
        }

        .dark-theme .card {
            background-color: var(--dark-card) !important;
            color: #e9ecef !important;
        }

        .dark-theme .form-control,
        .dark-theme .form-select {
            background-color: #374151 !important;
            border-color: var(--dark-border) !important;
            color: #e9ecef !important;
        }

        .dark-theme .form-control:focus,
        .dark-theme .form-select:focus {
            background-color: #374151 !important;
            border-color: var(--primary-color) !important;
            color: #e9ecef !important;
        }

        .dark-theme .sede-selector-container {
            background: linear-gradient(135deg, var(--dark-card), #374151);
        }

        .dark-theme .modal-content {
            background-color: var(--dark-card);
            color: #e9ecef;
        }

        .dark-theme .modal-footer {
            background-color: #374151;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .sidebar {
                min-height: auto;
            }
            
            .connection-status {
                position: relative;
                top: auto;
                right: auto;
                margin-bottom: 15px;
                display: inline-block;
            }
            
            .main-content {
                padding: 15px;
            }
            
            .sede-selector-container {
                padding: 15px;
                margin-bottom: 20px;
            }
            
            .sync-button {
                width: 55px;
                height: 55px;
                bottom: 20px;
                right: 20px;
                font-size: 1.1rem;
            }
            
            .card-body {
                padding: 1.25rem;
            }
        }

        @media (max-width: 576px) {
            .navbar-brand {
                font-size: 1.25rem;
            }
            
            .main-content {
                padding: 10px;
            }
            
            .sede-selector-container {
                padding: 12px;
            }
            
            .btn {
                padding: 0.5rem 1rem;
                font-size: 0.875rem;
            }
        }

        /* ===== ANIMATIONS ===== */
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .slide-in-right {
            animation: slideInRight 0.3s ease-out;
        }

        @keyframes slideInRight {
            from { transform: translateX(100%); }
            to { transform: translateX(0); }
        }

        /* ===== SCROLLBAR ===== */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary-dark);
        }

        /* ===== LOADING STATES ===== */
        .loading {
            position: relative;
            pointer-events: none;
        }

        .loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 20px;
            height: 20px;
            margin: -10px 0 0 -10px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

                /* ===== SIDEBAR TRIGGER ZONE (Zona invisible para activar hover) ===== */
        .sidebar-trigger-zone {
            position: fixed;
            left: 0;
            top: 70px; /* Altura del navbar */
            width: 20px;
            height: calc(100vh - 70px);
            z-index: 1040;
            background: transparent;
        }

        /* Indicador visual opcional (línea de color en el borde) */
        .sidebar-trigger-zone::after {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 3px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            border-radius: 0 3px 3px 0;
            opacity: 0.6;
            transition: var(--transition);
        }

        .sidebar-trigger-zone:hover::after {
            opacity: 1;
            height: 100px;
        }

        /* ===== SIDEBAR CONTAINER ===== */
        .sidebar-container {
            position: fixed;
            left: -280px;
            top: 70px; /* Altura del navbar */
            width: 280px;
            height: calc(100vh - 70px);
            background: linear-gradient(180deg, #ffffff, #f8f9fa);
            box-shadow: 4px 0 15px rgba(0, 0, 0, 0.1);
            border-right: 1px solid #e9ecef;
            z-index: 1050;
            overflow-y: auto;
            overflow-x: hidden;
            transition: left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Mostrar sidebar al hacer hover en la zona trigger o en el sidebar mismo */
        .sidebar-trigger-zone:hover ~ .sidebar-container,
        .sidebar-container:hover {
            left: 0;
        }

        /* ===== SIDEBAR NAVIGATION ===== */
        .sidebar-container .nav {
            padding: 1rem 0;
        }

        .sidebar-container .nav-link {
            color: #495057;
            padding: 14px 20px;
            margin: 4px 8px;
            border-radius: var(--border-radius);
            transition: var(--transition);
            font-weight: 500;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
        }

        .sidebar-container .nav-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            transition: left 0.5s;
        }

        .sidebar-container .nav-link:hover::before {
            left: 100%;
        }

        .sidebar-container .nav-link:hover,
        .sidebar-container .nav-link.active {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white !important;
            transform: translateX(5px);
            box-shadow: 0 4px 15px rgba(44, 90, 160, 0.3);
        }

        .sidebar-container .nav-link i {
            width: 20px;
            margin-right: 12px;
            text-align: center;
            font-size: 1.1rem;
        }

        /* Overlay (solo móvil) */
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1040;
            opacity: 0;
            visibility: hidden;
            transition: var(--transition);
        }

        .sidebar-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        /* ===== SCROLLBAR SIDEBAR ===== */
        .sidebar-container::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar-container::-webkit-scrollbar-track {
            background: rgba(0,0,0,0.05);
        }

        .sidebar-container::-webkit-scrollbar-thumb {
            background: rgba(44, 90, 160, 0.3);
            border-radius: 3px;
        }

        .sidebar-container::-webkit-scrollbar-thumb:hover {
            background: rgba(44, 90, 160, 0.5);
        }

        /* ===== RESPONSIVE SIDEBAR ===== */
        @media (max-width: 768px) {
            /* En móvil, desactivar el hover automático */
            .sidebar-trigger-zone {
                display: none;
            }
            
            .sidebar-container {
                left: -100%;
                width: 85%;
                max-width: 320px;
            }
            
            /* En móvil, solo se abre con clase active (click manual) */
            .sidebar-container.active {
                left: 0;
            }
            
            /* Desactivar hover en móvil */
            .sidebar-trigger-zone:hover ~ .sidebar-container,
            .sidebar-container:hover {
                left: -100%;
            }
            
            .sidebar-container.active:hover {
                left: 0;
            }
        }

        /* ===== DARK THEME SIDEBAR ===== */
        .dark-theme .sidebar-container {
            background: linear-gradient(180deg, var(--dark-card), #374151);
            border-right-color: var(--dark-border);
        }

        .dark-theme .sidebar-container .nav-link {
            color: #e9ecef !important;
        }

        .dark-theme .sidebar-container .nav-link:hover,
        .dark-theme .sidebar-container .nav-link.active {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark)) !important;
            color: white !important;
        }

    </style>

    </style>

    @stack('styles')
</head>
<body class="{{ request()->routeIs('login') ? 'login-page' : '' }}">
    @unless(request()->routeIs('login'))
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container-fluid">
            <!-- Logo -->
            <a class="navbar-brand d-flex align-items-center" href="{{ route('dashboard') }}">
                <img src="{{ asset('images/listo.png') }}" alt="SIDIS Logo" style="height: 40px;" class="me-2">
                <span class="fw-bold text-primary">SIDS</span>
            </a>


            <!-- Right Side -->
            <div class="d-flex align-items-center gap-3">
                <!-- Connection Status -->
                <!-- <div id="connectionStatus" class="connection-status {{ ($is_online ?? true) ? 'connection-online' : 'connection-offline' }}">
                    <i class="fas {{ ($is_online ?? true) ? 'fa-wifi' : 'fa-wifi-slash' }}"></i>
                    <span id="statusText">{{ ($is_online ?? true) ? 'Conectado' : 'Sin conexión' }}</span>
                </div> -->

                <!-- Sede Actual -->
                @if(isset($usuario['sede']))
                    <div class="sede-badge" data-bs-toggle="modal" data-bs-target="#modalCambiarSede" title="Cambiar sede">
                        <i class="fas fa-building"></i>
                        <span id="sedeActualNombre">{{ $usuario['sede']['nombre'] }}</span>
                    </div>
                @endif

                                    <!-- Estado de Conexión -->
                    @if(($is_offline ?? false) || !($is_online ?? true))
                        <span class="badge bg-warning me-2">
                            <i class="fas fa-wifi-slash"></i> Modo Offline
                        </span>
                    @else
                        <span class="badge bg-success me-2">
                            <i class="fas fa-wifi"></i> Conectado
                        </span>
                    @endif
                    
                    <!-- Verificar Conexión -->
                    <button type="button" class="btn-check-connection" onclick="checkConnection()" title="Verificar Conexión">
                        <i class="fas fa-sync-alt"></i>
                    </button>


                <!-- User Dropdown -->
                <div class="dropdown">
                    <button class="btn btn-link text-decoration-none p-0" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="user-avatar-small">
                            @if(isset($usuario['avatar']) && $usuario['avatar'])
                                <img src="{{ asset('storage/avatars/' . $usuario['avatar']) }}" alt="Avatar">
                            @else
                                <i class="fas fa-user-circle"></i>
                            @endif
                        </div>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li>
                            <a class="dropdown-item" href="#" onclick="showUserProfile()">
                                <i class="fas fa-user me-2"></i>Mi Perfil
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="#" onclick="showUserSettings()">
                                <i class="fas fa-cog me-2"></i>Configuración
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="#" onclick="confirmLogout()">
                                <i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Sidebar Trigger Zone (Zona invisible para activar el hover) -->
    <div class="sidebar-trigger-zone" id="sidebarTriggerZone"></div>

    <!-- Sidebar Container -->
    <div class="sidebar-container" id="sidebarContainer">
        @include('layouts.partials.sidebar')
    </div>

    <!-- Overlay (solo para móvil cuando se abre manualmente) -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>
    @endunless

    <!-- Main Content -->
    <main class="main-content">
        @yield('content')
    </main>

    <!-- Modal Cambiar Sede -->
    @unless(request()->routeIs('login'))
    <div class="modal fade" id="modalCambiarSede" tabindex="-1" aria-labelledby="modalCambiarSedeLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header modal-header-professional">
                    <div class="d-flex align-items-center">
                        <div class="modal-icon-wrapper">
                            <i class="fas fa-building"></i>
                        </div>
                        <h5 class="modal-title mb-0" id="modalCambiarSedeLabel">Cambiar Sede</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="nuevaSedeSelect" class="form-label">Selecciona una sede:</label>
                        <select class="form-select" id="nuevaSedeSelect">
                            <option value="">Cargando sedes...</option>
                        </select>
                    </div>
                    <div class="alert alert-info d-flex align-items-center" role="alert">
                        <i class="fas fa-info-circle me-2"></i>
                        <small>Al cambiar de sede, se actualizarán los datos disponibles según la ubicación seleccionada.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancelar
                    </button>
                    <button type="button" class="btn btn-primary" id="btnConfirmarCambio">
                        <i class="fas fa-check me-1"></i>Confirmar Cambio
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endunless

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <!-- Scripts principales -->
    <script>
        // ===== VARIABLES GLOBALES =====
        let isOnline = {{ ($is_online ?? true) ? 'true' : 'false' }};
        let checkInterval;
        let syncInProgress = false;

        // ===== FUNCIONES DE SIDEBAR (Solo para móvil) =====
        function closeSidebar() {
            const sidebar = document.getElementById('sidebarContainer');
            const overlay = document.getElementById('sidebarOverlay');
            
            if (sidebar && overlay) {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
                document.body.style.overflow = '';
            }
        }

        // Para abrir manualmente en móvil (si lo necesitas desde algún botón)
        function openSidebarMobile() {
            if (window.innerWidth <= 768) {
                const sidebar = document.getElementById('sidebarContainer');
                const overlay = document.getElementById('sidebarOverlay');
                
                if (sidebar && overlay) {
                    sidebar.classList.add('active');
                    overlay.classList.add('active');
                    document.body.style.overflow = 'hidden';
                }
            }
        }

        // ===== FUNCIONES DE CONEXIÓN =====
        function updateConnectionStatus(online) {
            const statusEl = document.getElementById('connectionStatus');
            const textEl = document.getElementById('statusText');
            
            if (statusEl && textEl) {
                if (online) {
                    statusEl.className = 'connection-status connection-online';
                    statusEl.querySelector('i').className = 'fas fa-wifi';
                    textEl.textContent = 'Conectado';
                } else {
                    statusEl.className = 'connection-status connection-offline';
                    statusEl.querySelector('i').className = 'fas fa-wifi-slash';
                    textEl.textContent = 'Sin conexión';
                }
                
                isOnline = online;
            }
        }

        function checkConnection() {
            fetch('/check-connection', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.online !== isOnline) {
                    updateConnectionStatus(data.online);
                    
                    if (data.online && !isOnline) {
                        Swal.fire({
                            title: '¡Conexión restablecida!',
                            text: '¿Desea sincronizar los datos pendientes?',
                            icon: 'success',
                            showCancelButton: true,
                            confirmButtonText: 'Sincronizar',
                            cancelButtonText: 'Más tarde',
                            confirmButtonColor: '#2c5aa0',
                            cancelButtonColor: '#6c757d'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                syncAllPendingData();
                            }
                        });
                    }
                }
            })
            .catch(() => {
                updateConnectionStatus(false);
            });
        }

        function syncAllPendingData() {
            if (syncInProgress) {
                mostrarAlerta('Sincronización en progreso', 'warning');
                return;
            }

            syncInProgress = true;
            
            Swal.fire({
                title: 'Sincronizando...',
                html: 'Sincronizando datos pendientes',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            fetch('/sync-pacientes', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Sincronización Completada',
                        text: `${data.synced_count || 0} pacientes sincronizados`,
                        icon: 'success',
                        timer: 3000,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: data.error || 'Error en la sincronización',
                        icon: 'error',
                        confirmButtonColor: '#2c5aa0'
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    title: 'Error de Conexión',
                    text: 'No se pudo conectar con el servidor',
                    icon: 'error',
                    confirmButtonColor: '#2c5aa0'
                });
            })
            .finally(() => {
                syncInProgress = false;
            });
        }

        // ===== FUNCIONES DE SEDE =====
        function cargarSedesDisponibles() {
            const nuevaSedeSelect = document.getElementById('nuevaSedeSelect');
            if (!nuevaSedeSelect) return;
            
            nuevaSedeSelect.innerHTML = '<option value="">Cargando...</option>';
            
            fetch('/sedes-disponibles', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    nuevaSedeSelect.innerHTML = '<option value="">Selecciona una sede...</option>';
                    
                    data.data.forEach(sede => {
                        const option = document.createElement('option');
                        option.value = sede.id;
                        option.textContent = sede.nombre;
                        
                        if (sede.id === data.sede_actual) {
                            option.textContent += ' (Actual)';
                            option.disabled = true;
                            option.selected = true;
                        }
                        
                        nuevaSedeSelect.appendChild(option);
                    });
                }
            })
            .catch(error => {
                nuevaSedeSelect.innerHTML = '<option value="">Error de conexión</option>';
                mostrarAlerta('Error cargando sedes', 'error');
            });
        }

        function cambiarSede(nuevaSedeId) {
            const btnConfirmarCambio = document.getElementById('btnConfirmarCambio');
            if (!btnConfirmarCambio) return;
            
            const originalText = btnConfirmarCambio.innerHTML;
            
            btnConfirmarCambio.disabled = true;
            btnConfirmarCambio.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Cambiando...';

            fetch('/cambiar-sede', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    sede_id: parseInt(nuevaSedeId)
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const sedeNombreEl = document.getElementById('sedeActualNombre');
                    if (sedeNombreEl) {
                        sedeNombreEl.textContent = data.usuario.sede.nombre;
                    }
                    
                    const modalElement = document.getElementById('modalCambiarSede');
                    if (modalElement) {
                        const modalCambiarSede = bootstrap.Modal.getInstance(modalElement);
                        if (modalCambiarSede) {
                            modalCambiarSede.hide();
                        }
                    }
                    
                    Swal.fire({
                        title: '¡Sede cambiada!',
                        text: data.message,
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    mostrarAlerta(data.error || 'Error cambiando sede', 'error');
                }
            })
            .catch(error => {
                mostrarAlerta('Error de conexión', 'error');
            })
            .finally(() => {
                btnConfirmarCambio.disabled = false;
                btnConfirmarCambio.innerHTML = originalText;
            });
        }

        // ===== FUNCIONES DE USUARIO =====
        function showUserProfile() {
                Swal.fire({
                title: '<i class="fas fa-user-edit text-primary"></i> Mi Perfil',
                html: `
                    <div class="text-start">
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-body">
                                        <h6 class="card-title"><i class="fas fa-user me-2"></i>Información Personal</h6>
                                        <p><strong>Nombre:</strong> {{ $usuario['nombre_completo'] ?? 'No especificado' }}</p>
                                        <p><strong>Documento:</strong> {{ $usuario['documento'] ?? 'No especificado' }}</p>
                                        <p><strong>Correo:</strong> {{ $usuario['correo'] ?? 'No especificado' }}</p>
                                        <p><strong>Teléfono:</strong> {{ $usuario['telefono'] ?? 'No especificado' }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-body">
                                        <h6 class="card-title"><i class="fas fa-briefcase me-2"></i>Información Laboral</h6>
                                        <p><strong>Rol:</strong> {{ $usuario['rol']['nombre'] ?? 'No asignado' }}</p>
                                        <p><strong>Sede:</strong> {{ $usuario['sede']['nombre'] ?? 'No asignada' }}</p>
                                        <p><strong>Estado:</strong> 
                                            <span class="badge bg-{{ (($usuario['estado']['id'] ?? 0) == 1) ? 'success' : 'danger' }}">
                                                {{ $usuario['estado']['nombre'] ?? 'No definido' }}
                                            </span>
                                        </p>
                                        @if(isset($usuario['especialidad']) && $usuario['especialidad'])
                                        <p><strong>Especialidad:</strong> {{ $usuario['especialidad']['nombre'] }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `,
                width: '600px',
                showCloseButton: true,
                showConfirmButton: false
            });
        }

        function showUserSettings() {
            Swal.fire({
            title: '<i class="fas fa-cog text-primary"></i> Configuración',
            html: `
                <div class="text-start">
                    <div class="list-group">
                        <a href="#" class="list-group-item list-group-item-action" onclick="changeTheme()">
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
                    </div>
                </div>
            `,
            width: '500px',
            showCloseButton: true,
            showConfirmButton: false
        });
        }

        function changePassword() {
            Swal.close();
            Swal.fire({
                title: 'Cambiar Contraseña',
                html: `
                    <form id="passwordForm">
                        <div class="mb-3">
                            <input type="password" class="form-control" id="currentPassword" placeholder="Contraseña actual" required>
                        </div>
                        <div class="mb-3">
                            <input type="password" class="form-control" id="newPassword" placeholder="Nueva contraseña" required>
                        </div>
                        <div class="mb-3">
                            <input type="password" class="form-control" id="confirmPassword" placeholder="Confirmar contraseña" required>
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
                    
                    if (newPass.length < 6) {
                                        Swal.showValidationMessage('La contraseña debe tener al menos 6 caracteres');
                        return false;
                    }
                    
                    return { current, newPass };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    showAlert('success', 'Contraseña actualizada correctamente');
                }
            });
        }

        function showNotificationSettings() {
            mostrarAlerta('Configuración de notificaciones próximamente', 'info');
        }

        function toggleTheme() {
            document.body.classList.toggle('dark-theme');
            const isDark = document.body.classList.contains('dark-theme');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
            mostrarAlerta(`Tema ${isDark ? 'oscuro' : 'claro'} activado`, 'success');
        }

        function confirmLogout() {
            Swal.fire({
                title: '¿Cerrar Sesión?',
                text: '¿Estás seguro de que deseas salir?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, salir',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '{{ route("logout") }}';
                }
            });
        }

        // ===== UTILIDADES =====
        function mostrarAlerta(mensaje, tipo = 'info') {
            if (typeof Swal !== 'undefined') {
                const iconMap = {
                    'success': 'success',
                    'error': 'error',
                    'warning': 'warning',
                    'info': 'info'
                };

                Swal.fire({
                    title: mensaje,
                    icon: iconMap[tipo] || 'info',
                    timer: 3000,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end',
                    timerProgressBar: true
                });
            }
        }

        // ===== EVENT LISTENERS =====
        document.addEventListener('DOMContentLoaded', function() {
            // Solo ejecutar si NO estamos en login
            if (!document.body.classList.contains('login-page')) {
                // Verificar conexión cada 30 segundos
                checkInterval = setInterval(checkConnection, 30000);
                
                // Cerrar sidebar con ESC (solo móvil)
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape' && window.innerWidth <= 768) {
                        closeSidebar();
                    }
                });
                
                // Event listener para modal de cambiar sede
                const modalCambiarSede = document.getElementById('modalCambiarSede');
                if (modalCambiarSede) {
                    modalCambiarSede.addEventListener('show.bs.modal', function() {
                        cargarSedesDisponibles();
                    });
                }

                // Event listener para botón confirmar cambio de sede
                const btnConfirmarCambio = document.getElementById('btnConfirmarCambio');
                if (btnConfirmarCambio) {
                    btnConfirmarCambio.addEventListener('click', function() {
                        const nuevaSedeId = document.getElementById('nuevaSedeSelect').value;
                        
                        if (!nuevaSedeId) {
                            mostrarAlerta('Por favor selecciona una sede', 'warning');
                            return;
                        }

                        cambiarSede(nuevaSedeId);
                    });
                }
                
                // Handle online/offline events
                window.addEventListener('online', () => {
                    updateConnectionStatus(true);
                    checkConnection();
                    mostrarAlerta('Conexión restablecida', 'success');
                });

                window.addEventListener('offline', () => {
                    updateConnectionStatus(false);
                    mostrarAlerta('Conexión perdida - Trabajando offline', 'warning');
                });
            }

            // Cargar tema guardado (funciona en todas las páginas)
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme === 'dark') {
                document.body.classList.add('dark-theme');
            }

            // Inicializar tooltips de Bootstrap
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // Inicializar Select2 si existe
            if (typeof $.fn.select2 !== 'undefined') {
                $('.select2').select2({
                    theme: 'bootstrap-5',
                    width: '100%'
                });
            }
        });

        // Limpiar interval al salir
        window.addEventListener('beforeunload', function() {
            if (checkInterval) {
                clearInterval(checkInterval);
            }
        });
    </script>

    @stack('scripts')
</body>
</html>
