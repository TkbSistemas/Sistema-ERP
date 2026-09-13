<?php
require_once __DIR__ . '/../models/categoria.php';
require_once __DIR__ . '/../helpers/Session.php';
require_once __DIR__ . '/../helpers/ActivityLogger.php';

class CategoriaController
{
    public function index(): void
    {
        Session::requireLogin(['Administrador', 'Almacen']);
        $categorias = Categoria::all();
        include __DIR__ . '/../views/categorias/index.php';
    }

    public function create(): void
    {
        Session::requireLogin(['Administrador', 'Almacen']);
        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (! Session::checkCsrf($_POST['csrf'] ?? '')) {
                $error = 'Token CSRF inválido.';
            } elseif (empty(trim($_POST['nombre'] ?? ''))) {
                $error = 'El nombre es obligatorio.';
            } else {
                Categoria::create($_POST);
                ActivityLogger::registrarAlta('configuracion', 'categoria_inventario', null, 'Categoría de Inventario Registrada', [
                    'nombre' => trim((string) ($_POST['nombre'] ?? '')),
                ]);
                header('Location: categorias.php?success=1');
                exit();
            }
        }

        include __DIR__ . '/../views/categorias/create.php';
    }

    public function edit($id): void
    {
        Session::requireLogin(['Administrador', 'Almacen']);
        $categoria = Categoria::find($id);
        $error     = '';

        if (! $categoria) {
            die('Categoría no encontrada.');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (! Session::checkCsrf($_POST['csrf'] ?? '')) {
                $error = 'Token CSRF inválido.';
            } elseif (empty(trim($_POST['nombre'] ?? ''))) {
                $error = 'El nombre es obligatorio.';
            } else {
                Categoria::update($id, $_POST);
                ActivityLogger::registrarActualizacion('configuracion', 'categoria_inventario', $id, 'Categoría de Inventario Actualizada', [
                    'nombre' => trim((string) ($_POST['nombre'] ?? '')),
                ]);
                header('Location: categorias.php?success=2');
                exit();
            }
        }

        include __DIR__ . '/../views/categorias/edit.php';
    }

    public function delete($id): void
    {
        Session::requireLogin(['Administrador', 'Almacen']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ! Session::checkCsrf($_POST['csrf'] ?? '')) {
            header('Location: categorias.php?error=csrf');
            exit();
        }

        $categoriaId = (int) ($id ?: ($_POST['id'] ?? 0));
        if ($categoriaId > 0) {
            Categoria::delete($categoriaId);
            ActivityLogger::registrarBaja('configuracion', 'categoria_inventario', $categoriaId, 'Categoría de Inventario Eliminada');
            header('Location: categorias.php?deleted=1');
            exit();
        }

        header('Location: categorias.php?error=not_found');
        exit();
    }
}
