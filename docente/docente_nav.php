<?php
// Este archivo es incluido por páginas que están en la carpeta /admin/
// por lo que las rutas deben ser relativas desde /admin/

// Las variables $pagina_actual, $clase_activa, $clase_hover
// deben estar definidas en el archivo que incluye este nav.
?>
<nav class="bg-indigo-700 text-white shadow-lg">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <div class="flex items-center">
                <a href="../docente/dashboard_docente.php" class="flex-shrink-0 flex items-center text-white">
                    <img src="../logo.png" alt="Logo" class="h-8 w-auto mr-2">
                    <span class="font-semibold text-xl">Panel Docente</span>
                </a>
            </div>
            <div class="hidden md:flex items-center space-x-4">
                <a href="../docente/dashboard_docente.php" class="px-3 py-2 rounded-md text-sm font-medium <?php echo ($pagina_actual == 'dashboard_docente.php') ? $clase_activa : 'text-indigo-100 hover:bg-indigo-600 hover:text-white'; ?>">Dashboard</a>
                <a href="gestionar_categorias.php" class="px-3 py-2 rounded-md text-sm font-medium <?php echo ($pagina_actual == 'gestionar_categorias.php') ? $clase_activa : 'text-indigo-100 hover:bg-indigo-600 hover:text-white'; ?>">Gestionar Categorías</a>
                <a href="upload_libro.php" class="px-3 py-2 rounded-md text-sm font-medium <?php echo ($pagina_actual == 'upload_libro.php') ? $clase_activa : 'text-indigo-100 hover:bg-indigo-600 hover:text-white'; ?>">Subir Libro</a>
                <a href="../docente/ver_estudiantes.php" class="px-3 py-2 rounded-md text-sm font-medium <?php echo ($pagina_actual == 'ver_estudiantes.php') ? $clase_activa : 'text-indigo-100 hover:bg-indigo-600 hover:text-white'; ?>">Ver Estudiantes</a>
                <a href="gestionar_libros.php" class="px-3 py-2 rounded-md text-sm font-medium <?php echo ($pagina_actual == 'gestionar_libros.php') ? $clase_activa : 'text-indigo-100 hover:bg-indigo-600 hover:text-white'; ?>">Gestionar Libros</a>
                <a href="../catalogo.php" class="px-3 py-2 rounded-md text-sm font-medium text-indigo-100 hover:bg-indigo-600 hover:text-white" target="_blank">Ver Catálogo</a>
                <a href="../logout.php" class="px-3 py-2 rounded-md text-sm font-medium bg-red-500 hover:bg-red-600 text-white">Cerrar Sesión <i class="fas fa-sign-out-alt ml-1"></i></a>
            </div>
            <!-- Botón de menú móvil podría ir aquí si se necesita para estas páginas -->
        </div>
    </div>
</nav>