<h2>Eliminar Producto</h2>
<p>¿Estás seguro que deseas eliminar el producto <b><?= htmlspecialchars($producto['nombre']) ?></b> (ID: <?= $producto['id'] ?>)?<br>
<b>Esta acción no se puede deshacer.</b></p>
<form method="post">
    <input type="hidden" name="confirmar" value="1">
    <button type="submit">Sí, eliminar</button>
    <a href="productos_view.php?id=<?= $producto['id'] ?>">Cancelar</a>
</form>
