<?php
// registro.php
session_start();

// Si el usuario ya está logueado, redirigir al dashboard
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Usuario - Biblioteca Virtual</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f0f4f8; /* Un fondo suave */
        }
        .register-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .register-card {
            background-color: white;
            width: 100%;
            max-width: 600px; /* Más ancho para más campos */
            padding: 2rem; /* p-8 */
            border-radius: 0.75rem; /* rounded-xl */
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05); /* shadow-2xl */
        }
        .input-field {
            border-color: #D1D5DB; /* Gray-300 */
        }
        .input-field:focus {
            border-color: #4F46E5; /* Indigo-600 */
            box-shadow: 0 0 0 2px #A5B4FC; /* Indigo-200 */
        }
        .btn-primary {
            background-color: #4F46E5; /* Indigo-600 */
        }
        .btn-primary:hover {
            background-color: #4338CA; /* Indigo-700 */
        }
        .message-box {
            padding: 0.75rem;
            margin-bottom: 1rem;
            border-radius: 0.375rem;
            font-size: 0.875rem;
        }
        .message-box.error {
            background-color: #FEE2E2;
            color: #B91C1C;
            border: 1px solid #FCA5A5;
        }
         .message-box.success {
            background-color: #D1FAE5;
            color: #065F46;
            border: 1px solid #6EE7B7;
        }
    </style>
</head>
<body>
    <nav class="bg-white shadow-md fixed w-full z-10 top-0">
        <div class="container mx-auto px-6 py-3 flex justify-between items-center">
            <a href="index.php" class="flex items-center text-xl font-bold text-indigo-600">
                <img src="logo.png" alt="Logo Biblioteca Abajo Cadenas" class="h-10 mr-3"> <!-- Ajusta la altura (h-10) según necesites -->
                <span>Biblioteca Virtual Abajo Cadenas</span>
            </a>
            <div class="hidden md:flex space-x-4 items-center">
                <a href="index.php" class="text-gray-600 hover:text-indigo-600">Inicio</a>
                <a href="catalogo.php" class="text-gray-600 hover:text-indigo-600">Catálogo</a>
                <a href="index.php#acerca" class="text-gray-600 hover:text-indigo-600">Acerca de</a>
                <a href="index.php#contacto" class="text-gray-600 hover:text-indigo-600">Contáctanos</a>
                <a href="registro.php" class="text-indigo-600 font-semibold border-b-2 border-indigo-500 px-3 py-2">Registrarse</a>
                <a href="index.php#loginSection" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 transition-colors">Iniciar Sesión</a>
            </div>
            <div class="md:hidden">
                <button id="mobileMenuButton" class="text-gray-600 focus:outline-none">
                    <i class="fas fa-bars fa-lg"></i>
                </button>
            </div>
        </div>
        <div id="mobileMenu" class="md:hidden hidden bg-white shadow-lg">
            <a href="index.php" class="block px-6 py-3 text-gray-600 hover:bg-indigo-50">Inicio</a>
            <a href="catalogo.php" class="block px-6 py-3 text-gray-600 hover:bg-indigo-50">Catálogo</a>
            <a href="index.php#acerca" class="block px-6 py-3 text-gray-600 hover:bg-indigo-50">Acerca de</a>
            <a href="index.php#contacto" class="block px-6 py-3 text-gray-600 hover:bg-indigo-50">Contáctanos</a>
            <a href="registro.php" class="block px-6 py-3 text-indigo-600 bg-indigo-50 font-semibold">Registrarse</a>
            <a href="index.php#loginSection" class="block px-6 py-3 text-white bg-indigo-600 hover:bg-indigo-700">Iniciar Sesión</a>
        </div>
    </nav>

    <div class="register-container pt-24 pb-12">
        <div class="register-card">
            <div class="text-center mb-8">
                <i class="fas fa-user-plus fa-3x text-indigo-600 mb-4"></i>
                <h2 class="text-3xl font-bold tracking-tight text-gray-900">
                    Crear Nueva Cuenta
                </h2>
                <p class="mt-2 text-sm text-gray-600">
                    Completa el formulario para registrarte. Tu cuenta requerirá aprobación administrativa.
                </p>
            </div>

            <div id="registerMessage" class="message-box" style="display: none;"></div>

            <form id="registerForm" action="registro_handler.php" method="POST" class="space-y-5">
                <div>
                    <label for="nombre_completo" class="block text-sm font-medium text-gray-700">Nombre Completo</label>
                    <input type="text" id="nombre_completo" name="nombre_completo" required pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+"
                           title="Solo letras y espacios"
                           class="input-field mt-1 block w-full px-3 py-2 border rounded-md shadow-sm"
                           placeholder="Ej: Ana Pérez">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="cedula" class="block text-sm font-medium text-gray-700">Cédula (V/E)</label>
                        <input type="text" id="cedula" name="cedula" required pattern="[VEJPGvejpg]-\d{7,8}"
                               title="Formato: V-12345678 o E-12345678"
                               class="input-field mt-1 block w-full px-3 py-2 border rounded-md shadow-sm"
                               placeholder="Ej: V-12345678">
                        <p class="mt-1 text-xs text-gray-500">Incluye V, E, J, P o G seguido de 7 u 8 números.</p>
                    </div>
                    <div>
                        <label for="telefono" class="block text-sm font-medium text-gray-700">Teléfono</label>
                        <input type="tel" id="telefono" name="telefono"
                               pattern="\+?\d{10,15}" title="Número de teléfono válido"
                               class="input-field mt-1 block w-full px-3 py-2 border rounded-md shadow-sm"
                               placeholder="Ej: 0412-3456789">
                    </div>
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Correo Electrónico</label>
                    <input type="email" id="email" name="email" required
                           class="input-field mt-1 block w-full px-3 py-2 border rounded-md shadow-sm"
                           placeholder="tu.correo@ejemplo.com">
                </div>

                <div>
                    <label for="direccion" class="block text-sm font-medium text-gray-700">Dirección</label>
                    <textarea id="direccion" name="direccion" rows="3"
                              class="input-field mt-1 block w-full px-3 py-2 border rounded-md shadow-sm"
                              placeholder="Tu dirección completa"></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700">Nombre de Usuario</label>
                        <input type="text" id="username" name="username" required pattern="[a-zA-Z0-9_]{4,20}"
                               title="4-20 caracteres, solo letras, números y guion bajo"
                               class="input-field mt-1 block w-full px-3 py-2 border rounded-md shadow-sm"
                               placeholder="Elige un nombre de usuario">
                    </div>
                    <div>
                        <label for="role" class="block text-sm font-medium text-gray-700">Rol Solicitado</label>
                        <select id="role" name="role" required
                                class="input-field mt-1 block w-full px-3 py-2 border bg-white rounded-md shadow-sm">
                            <option value="estudiante" selected>Estudiante</option>
                            <option value="docente">Docente</option>
                            </select>
                    </div>
                </div>


                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">Contraseña</label>
                        <input type="password" id="password" name="password" required minlength="8"
                               class="input-field mt-1 block w-full px-3 py-2 border rounded-md shadow-sm"
                               placeholder="Mínimo 8 caracteres">
                    </div>
                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirmar Contraseña</label>
                        <input type="password" id="confirm_password" name="confirm_password" required
                               class="input-field mt-1 block w-full px-3 py-2 border rounded-md shadow-sm"
                               placeholder="Repite tu contraseña">
                    </div>
                </div>
                 <div id="passwordError" class="text-xs text-red-500 mt-1" style="display:none;">Las contraseñas no coinciden o son muy cortas.</div>


                <div>
                    <button type="submit"
                            class="btn-primary w-full flex justify-center py-2.5 px-4 border rounded-md shadow-sm text-sm font-medium text-white">
                        Registrarme
                    </button>
                </div>
            </form>
            <p class="mt-6 text-sm text-center text-gray-600">
                ¿Ya tienes una cuenta?
                <a href="index.php#loginSection" class="font-medium text-indigo-600 hover:text-indigo-500">
                    Inicia Sesión aquí
                </a>
            </p>
        </div>
    </div>
    <footer class="bg-gray-100 text-gray-600 py-6 text-center">
        <p>&copy; <?php echo date("Y"); ?> Biblioteca Virtual Abajo Cadenas. Todos los derechos reservados.</p>
        <p class="text-sm">Desarrollado con <i class="fas fa-heart text-red-500"></i> y PHP.</p>
    </footer>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const registerMessageContainer = document.getElementById('registerMessage');
            const urlParams = new URLSearchParams(window.location.search);
            const error = urlParams.get('error_registro');
            const success = urlParams.get('success_registro'); // Este es más probable que se muestre en index.php

            if (error) {
                registerMessageContainer.textContent = decodeURIComponent(error);
                registerMessageContainer.className = 'message-box error';
                registerMessageContainer.style.display = 'block';
            } else if (success) {
                // Normalmente el mensaje de éxito del registro se mostraría en la página de login
                // Pero si se quiere mostrar aquí también (ej. antes de una redirección manual)
                registerMessageContainer.textContent = decodeURIComponent(success);
                registerMessageContainer.className = 'message-box success';
                registerMessageContainer.style.display = 'block';
            }

            const form = document.getElementById('registerForm');
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirm_password');
            const passwordError = document.getElementById('passwordError');

            form.addEventListener('submit', function(event) {
                if (password.value !== confirmPassword.value) {
                    passwordError.textContent = 'Las contraseñas no coinciden.';
                    passwordError.style.display = 'block';
                    confirmPassword.focus();
                    event.preventDefault(); // Detiene el envío del formulario
                    return false;
                }
                if (password.value.length < 8) {
                     passwordError.textContent = 'La contraseña debe tener al menos 8 caracteres.';
                     passwordError.style.display = 'block';
                     password.focus();
                     event.preventDefault();
                     return false;
                }
                // Limpiar el mensaje de error si las validaciones pasan
                passwordError.style.display = 'none';
                return true;
            });

            // Menú móvil (copiado de index.php para consistencia)
            const mobileMenuButton = document.getElementById('mobileMenuButton');
            const mobileMenu = document.getElementById('mobileMenu');

            if (mobileMenuButton && mobileMenu) {
                mobileMenuButton.addEventListener('click', function() {
                    mobileMenu.classList.toggle('hidden');
                });
            }
        });
    </script>
</body>
</html>
