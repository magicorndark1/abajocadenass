<?php
// index.php
session_start();


// Si el usuario ya está logueado, redirigir al dashboard
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: dashboard.php");
    exit;
}

require_once 'db_config.php'; // Para futuras funcionalidades como mostrar libros destacados, etc.
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biblioteca Virtual</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .hero-section {
            background: linear-gradient(rgba(0,0,0,0.55), rgba(0,0,0,0.55)), #6366F1; /* Color de fondo índigo */            
            background-size: cover;
            background-position: center;
        }
        .login-card {
            background-color: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(8px);
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
        .btn-secondary {
            background-color: #6D28D9; /* Violet-700 */
        }
        .btn-secondary:hover {
            background-color: #5B21B6; /* Violet-800 */
        }
        .message-box {
            padding: 0.75rem; /* p-3 */
            margin-bottom: 1rem; /* mb-4 */
            border-radius: 0.375rem; /* rounded-md */
            font-size: 0.875rem; /* text-sm */
        }
        .message-box.error {
            background-color: #FEE2E2; /* red-100 */
            color: #B91C1C; /* red-700 */
            border: 1px solid #FCA5A5; /* red-300 */
        }
        .message-box.success { /* Estilo para mensajes de éxito */
            background-color: #D1FAE5; /* green-100 */
            color: #065F46; /* green-700 */
            border: 1px solid #6EE7B7; /* green-300 */
        }
    </style>
</head>
<body class="bg-gray-100 text-gray-800">

    <nav class="bg-white shadow-md fixed w-full z-10 top-0">
        <div class="container mx-auto px-6 py-3 flex justify-between items-center">
            <a href="index.php" class="flex items-center text-xl font-bold text-indigo-600">
                <img src="logo.png" alt="Logo Biblioteca Abajo Cadenas" class="h-10 mr-3"> <!-- Ajusta la altura (h-10) según necesites -->
                <span>Biblioteca Virtual Abajo Cadenas</span>
            </a>
            <div class="hidden md:flex space-x-4 items-center">
                <a href="index.php" class="text-gray-600 hover:text-indigo-600">Inicio</a>
                <a href="catalogo.php" class="text-gray-600 hover:text-indigo-600">Catálogo</a>
                <a href="#acerca" class="text-gray-600 hover:text-indigo-600">Acerca de</a>
                <a href="#contacto" class="text-gray-600 hover:text-indigo-600">Contáctanos</a>
                <a href="registro.php" class="text-gray-600 hover:text-indigo-600 px-3 py-2 rounded-md hover:bg-indigo-50 transition-colors">Registrarse</a>
                <button onclick="smoothScrollTo('#loginSection')" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 transition-colors">Iniciar Sesión</button>
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
            <a href="#acerca" class="block px-6 py-3 text-gray-600 hover:bg-indigo-50">Acerca de</a>
            <a href="#contacto" class="block px-6 py-3 text-gray-600 hover:bg-indigo-50">Contáctanos</a>
            <a href="registro.php" class="block px-6 py-3 text-gray-600 hover:bg-indigo-50">Registrarse</a>
            <button onclick="smoothScrollTo('#loginSection'); toggleMobileMenu();" class="w-full text-left block px-6 py-3 text-white bg-indigo-600 hover:bg-indigo-700">Iniciar Sesión</button>
        </div>
    </nav>

    <header class="hero-section pt-36 pb-24 md:pt-40 md:pb-28 text-white text-center">
        <div class="container mx-auto px-6">
            <h1 class="text-5xl md:text-6xl lg:text-7xl font-bold tracking-tight mb-6">
                Bienvenido a la Biblioteca Virtual
                <span class="block text-indigo-300 mt-2">Unidad Educativa "Abajo Cadenas"</span>
            </h1>
            <p class="text-xl md:text-2xl text-gray-200 mb-10 max-w-3xl mx-auto">
                Explora un universo de conocimiento y sumérgete en la lectura. Recursos ilimitados para tu aprendizaje y curiosidad.
            </p>
            <div class="w-full max-w-2xl mx-auto">
                <form action="catalogo.php" method="GET" class="flex items-center bg-white rounded-full shadow-xl overflow-hidden">
                    <input type="search" name="q" placeholder="Buscar libros por título, autor, categoría..." 
                           class="w-full px-6 py-4 text-gray-700 focus:outline-none text-lg"
                           aria-label="Buscar libros">
                    <button type="submit" class="bg-violet-600 hover:bg-violet-700 text-white px-6 py-5 transition-colors"> 
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>
        </div>
    </header>

    <section id="loginSection" class="py-16 bg-gray-50 scroll-mt-20 md:scroll-mt-24"> <!-- Margen para nav fija (5rem en móvil, 6rem en md y superior) -->
        <div class="container mx-auto px-6 flex justify-center">
            <div class="login-card w-full max-w-md p-8 space-y-6 rounded-xl shadow-2xl">
                <div class="text-center">
                    <i class="fas fa-sign-in-alt fa-3x text-indigo-600 mb-4"></i>
                    <h2 class="text-3xl font-bold tracking-tight text-gray-900">
                        Accede a tu Cuenta
                    </h2>
                </div>

                <div id="loginMessage" class="message-box" style="display: none;"></div>

                <form id="loginForm" action="login_handler.php" method="POST" class="space-y-6">
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700">Usuario o Email</label>
                        <input type="text" id="identifier" name="identifier" required
                         class="input-field mt-1 block w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none sm:text-sm"
                         placeholder="Tu nombre de usuario o correo">
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">Contraseña</label>
                        <input type="password" id="password" name="password" required
                               class="input-field mt-1 block w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none sm:text-sm"
                               placeholder="Tu contraseña">
                    </div>
                    <div>
                        <label for="role" class="block text-sm font-medium text-gray-700">Rol (Opcional si el sistema lo infiere)</label>
                        <select id="role" name="role"
                                class="input-field mt-1 block w-full px-3 py-2 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            <option value="" selected>Seleccionar si es necesario...</option>
                            <option value="administrador">Administrador</option>
                            <option value="docente">Docente</option>
                            <option value="estudiante">Estudiante</option>
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Si tu nombre de usuario es único para tu rol, puedes omitir esto.</p>
                    </div>


                    <div>
                        <button type="submit"
                                class="btn-primary w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Iniciar Sesión
                        </button>
                    </div>
                </form>
                <p class="text-sm text-center text-gray-600">
                    ¿No tienes cuenta?
                    <a href="registro.php" class="font-medium text-indigo-600 hover:text-indigo-500">
                        Regístrate aquí
                    </a>
                </p>
                 <p class="text-xs text-center text-gray-500">
                    <a href="solicitar_restablecimiento.php" class="font-medium text-indigo-600 hover:text-indigo-500">¿Olvidaste tu contraseña?</a>
                </p>
            </div>
        </div>
    </section>

    <section id="acerca" class="py-16 bg-gray-50 scroll-mt-20 md:scroll-mt-24"> <!-- Fondo suave y scroll margin -->
        <div class="container mx-auto px-6">
            <div class="text-center mb-12">
                <h2 class="text-4xl font-bold text-indigo-700 mb-4">
                    Acerca de Nosotros
                </h2>
                <!-- Un pequeño separador decorativo -->
                <div class="inline-block h-1 w-24 bg-indigo-500 rounded"></div>
            </div>
            <div class="max-w-3xl mx-auto bg-white p-8 md:p-10 rounded-xl shadow-xl text-gray-700">
                <p class="text-lg leading-relaxed mb-6">
                    En la <strong>Unidad Educativa Abajo Cadenas</strong>, nos dedicamos con pasión a proporcionar una educación integral y de la más alta calidad a cada uno de nuestros estudiantes. Creemos firmemente en el poder transformador del aprendizaje.
                </p>
                <p class="text-lg leading-relaxed mb-6">
                    Ubicados estratégicamente en Puerto La Cruz, Estado Anzoátegui, nuestra institución se enorgullece de ofrecer un completo abanico de programas educativos. Acompañamos a nuestros alumnos en su viaje formativo desde la <strong>educación inicial</strong>, sentando bases sólidas, hasta la <strong>secundaria</strong>, preparándolos para los desafíos del futuro.
                </p>
                <p class="text-lg leading-relaxed">
                    Nuestro compromiso primordial es fomentar el desarrollo armónico de cada estudiante, no solo en el ámbito <strong>académico</strong>, sino también en su crecimiento <strong>social y emocional</strong>. Buscamos formar ciudadanos conscientes, críticos, responsables y listos para contribuir positivamente a la sociedad.
                </p>
            </div>
        </div>
    </section>

    <section id="contacto" class="py-16 bg-gray-50 scroll-mt-20 md:scroll-mt-24"> <!-- Scroll margin añadido -->
        <div class="container mx-auto px-6 text-center">
            <h2 class="text-3xl font-bold text-gray-800 mb-8">Contáctanos</h2>
            <p class="max-w-xl mx-auto text-gray-600 mb-10">
                ¿Tienes preguntas o sugerencias? Nos encantaría escucharte.
            </p>
            <div class="max-w-3xl mx-auto grid md:grid-cols-3 gap-8 text-left">
                <div class="bg-white p-6 rounded-lg shadow-lg hover:shadow-xl transition-shadow">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <div class="flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 text-indigo-600">
                                <i class="fas fa-map-marker-alt fa-lg"></i>
                            </div>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-800">Ubicación</h3>
                            <p class="mt-1 text-sm text-gray-600">Calle Freites, Unidad Educativa Abajo Cadenas, Local n° 59, Puerto La Cruz, Anzoátegui, Venezuela.</p>
                        </div>
                    </div>

                </div>
                <div class="bg-white p-6 rounded-lg shadow-lg hover:shadow-xl transition-shadow">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <div class="flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 text-indigo-600">
                                <i class="fas fa-phone-alt fa-lg"></i>
                            </div>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-800">Teléfono</h3>
                            <p class="mt-1 text-sm text-gray-600"><a href="tel:+582812657656" class="hover:text-indigo-500">(0281) 265-7656</a></p>
                        </div>
                    </div>

                </div>
                <div class="bg-white p-6 rounded-lg shadow-lg hover:shadow-xl transition-shadow">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <div class="flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 text-indigo-600">
                                <i class="fas fa-envelope fa-lg"></i>
                            </div>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-800">Correo Electrónico</h3>
                            <p class="mt-1 text-sm text-gray-600"><a href="mailto:colegioabajocadenas@gmail.com" class="hover:text-indigo-500 break-all">colegioabajocadenas@gmail.com</a></p>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
    </section>

    <footer class="bg-gray-800 text-white py-8">
        <div class="container mx-auto px-6 text-center">
            <p>&copy; <?php echo date("Y"); ?> Biblioteca Virtual Abajo Cadenas. Todos los derechos reservados.</p>
            <p class="text-sm">Desarrollado con <i class="fas fa-heart text-red-500"></i> y PHP.</p>
        </div>
    </footer>

    <script>
        // Script para mostrar mensajes de error/éxito pasados por PHP a través de parámetros GET en el login
        document.addEventListener('DOMContentLoaded', function() {
            const loginMessageContainer = document.getElementById('loginMessage');
            const urlParams = new URLSearchParams(window.location.search);
            const errorLogin = urlParams.get('error_login');
            const successRegistro = urlParams.get('success_registro');
            const errorUnauthorized = urlParams.get('error_unauthorized');
            const errorRole = urlParams.get('error_role');
            const errorSession = urlParams.get('error_session');
            const logoutSuccess = urlParams.get('logout_success');

            let scrolledByMessage = false; // Flag para saber si el script de mensajes ya hizo scroll
            let messageText = '';
            let messageType = ''; // 'error' o 'success'

            if (errorLogin) {
                messageText = decodeURIComponent(errorLogin);
                messageType = 'error';
            } else if (successRegistro) {
                messageText = decodeURIComponent(successRegistro);
                messageType = 'success';
            } else if (errorUnauthorized) {
                messageText = "Debes iniciar sesión para acceder a esta página.";
                messageType = 'error';
            } else if (errorRole) {
                messageText = "Tu sesión fue cerrada debido a un rol inválido o un intento de acceso no autorizado. Por favor, inicia sesión de nuevo o contacta al administrador.";
                messageType = 'error';
            } else if (errorSession) {
                messageText = "Tu sesión fue cerrada debido a un problema. Por favor, inicia sesión de nuevo.";
                messageType = 'error';
            } else if (logoutSuccess) {
                messageText = "Has cerrado sesión exitosamente.";
                messageType = 'success';
            }

            if (messageText) {
                loginMessageContainer.textContent = messageText;
                loginMessageContainer.className = messageType === 'success' ? 'message-box success' : 'message-box error';
                loginMessageContainer.style.display = 'block';
                
                // Si el mensaje es relevante para la sección de login (error de login, éxito de registro, etc.)
                // y el ancla actual es #loginSection o no hay ancla, hacer scroll a #loginSection.
                const loginMessagesParams = ['error_login', 'success_registro', 'error_unauthorized', 'error_role', 'error_session', 'logout_success'];
                const hasLoginMessage = loginMessagesParams.some(param => urlParams.has(param));

                if (hasLoginMessage && (window.location.hash === '#loginSection' || !window.location.hash)) {
                    smoothScrollTo('#loginSection');
                    scrolledByMessage = true;
                }
            }

            // Manejar el scroll para cualquier ancla en la URL si no ha sido manejado ya por un mensaje específico
            // O si el ancla es diferente de #loginSection
            if (window.location.hash) {
                const targetId = window.location.hash;
                if (['#loginSection', '#acerca', '#contacto'].includes(targetId)) {
                    // Si el ancla es #loginSection, solo hacer scroll si no lo hizo ya el mensaje.
                    // Si el ancla es #acerca o #contacto, siempre hacer scroll.
                    if (targetId !== '#loginSection' || (targetId === '#loginSection' && !scrolledByMessage)) {
                        setTimeout(() => { smoothScrollTo(targetId); }, 50); // Timeout para asegurar que el DOM está listo y CSS aplicado
                    }
                }
            }

            // Menú móvil
            const mobileMenuButton = document.getElementById('mobileMenuButton');
            const mobileMenu = document.getElementById('mobileMenu');

            if (mobileMenuButton && mobileMenu) {
                mobileMenuButton.addEventListener('click', function() {
                    mobileMenu.classList.toggle('hidden');
                });
            }
        });

        function smoothScrollTo(selector) {
            const element = document.querySelector(selector);
            if (element) {
                element.scrollIntoView({
                    behavior: 'smooth'
                });
            }
        }
        function toggleMobileMenu() {
            const mobileMenu = document.getElementById('mobileMenu');
            if (mobileMenu) {
                mobileMenu.classList.toggle('hidden');
            }
        }
    </script>
</body>
</html>
