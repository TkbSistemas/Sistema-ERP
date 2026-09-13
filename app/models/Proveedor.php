<?php
require_once __DIR__ . '/../helpers/Database.php';

class Proveedor
{
    private const CATEGORIAS_PERMITIDAS = [
        'Ferretería',
        'Electrica',
        'CCTV',
        'Computación',
        'Papelería',
        'Material de Oficina',
        'Alimentos',
    ];

    public static function all(){
        $db = Database::getInstance()->getConnection();
        $proveedores = $db->query(
            'SELECT * FROM catalogo_proveedores WHERE activo = 1 ORDER BY nombre ASC'
        )->fetchAll(PDO::FETCH_ASSOC);

        return self::adjuntarAgentes($db, $proveedores);
    }

    public static function filtrar(array $filtros, int $limite, int $offset): array{
        $db = Database::getInstance()->getConnection();
        $condiciones = ['activo = 1'];
        $parametros = [];

        $buscar = trim((string) ($filtros['buscar'] ?? ''));
        if ($buscar !== '') {
            $condiciones[] = "CONCAT_WS(' ', nombre, categoria, razon_social, rfc,
                telefono, correo, ubicacion_fisica, url_tienda) LIKE :buscar";
            $parametros[':buscar'] = '%' . $buscar . '%';
        }

        $categoria = trim((string) ($filtros['categoria'] ?? ''));
        if ($categoria !== '') {
            $condiciones[] = 'categoria = :categoria';
            $parametros[':categoria'] = $categoria;
        }

        if (!empty($filtros['partner_activo'])) {
            $condiciones[] = 'partner_activo = 1';
        }

        $where = ' WHERE ' . implode(' AND ', $condiciones);

        $stmtTotal = $db->prepare('SELECT COUNT(*) FROM catalogo_proveedores' . $where);
        $stmtTotal->execute($parametros);
        $total = (int) $stmtTotal->fetchColumn();

        $stmt = $db->prepare(
            'SELECT id, nombre, categoria, partner_activo, url_tienda, correo, telefono,
                    ubicacion_fisica, rfc, razon_social
               FROM catalogo_proveedores' . $where . '
              ORDER BY nombre ASC
              LIMIT :limite OFFSET :offset'
        );
        foreach ($parametros as $nombre => $valor) {
            $stmt->bindValue($nombre, $valor, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limite', max(1, $limite), PDO::PARAM_INT);
        $stmt->bindValue(':offset', max(0, $offset), PDO::PARAM_INT);
        $stmt->execute();

        $proveedores = self::adjuntarAgentes($db, $stmt->fetchAll(PDO::FETCH_ASSOC));

        return [
            'proveedores' => $proveedores,
            'total' => $total,
        ];
    }

    private static function adjuntarAgentes(PDO $db, array $proveedores): array{
        if (!$proveedores) {
            return [];
        }

        $indices = [];
        foreach ($proveedores as $indice => &$proveedor) {
            $proveedor['agentes'] = [];
            $indices[(int) $proveedor['id']] = $indice;
        }
        unset($proveedor);

        $marcadores = implode(',', array_fill(0, count($indices), '?'));
        $stmt = $db->prepare(
            "SELECT id, proveedor_id, nombre, correo, telefono
               FROM agentes_proveedores
              WHERE activo = 1 AND proveedor_id IN ($marcadores)
              ORDER BY nombre ASC"
        );
        $stmt->execute(array_keys($indices));

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $agente) {
            $proveedorId = (int) $agente['proveedor_id'];
            if (isset($indices[$proveedorId])) {
                $proveedores[$indices[$proveedorId]]['agentes'][] = $agente;
            }
        }

        return $proveedores;
    }

    public static function categorias(): array{
        $db = Database::getInstance()->getConnection();
        return $db->query(
            "SELECT DISTINCT categoria
               FROM catalogo_proveedores
              WHERE activo = 1 AND categoria IS NOT NULL AND categoria <> ''
              ORDER BY categoria ASC"
        )->fetchAll(PDO::FETCH_COLUMN);
    }

    public static function categoriasPermitidas(): array
    {
        return self::CATEGORIAS_PERMITIDAS;
    }

    public static function delete(int $id): bool{
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("UPDATE catalogo_proveedores SET activo = 0 WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public static function find($id){
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM catalogo_proveedores WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public static function create($data){
        $db = Database::getInstance()->getConnection();
        $sql = "INSERT INTO catalogo_proveedores (nombre, categoria, rfc, telefono, correo, ubicacion_fisica,
                            partner_activo, url_tienda,razon_social)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        return $stmt->execute([
            $data['nombre'],
            $data['categoria'],
            $data['rfc'],
            $data['telefono'],
            $data['correo'],
            $data['ubicacion_fisica'],
            $data['partner_activo'],
            $data['url_tienda'],
            $data['razon_social']
        ]);
    }

    public static function update(int $id, array $data): bool
    {
        $db = Database::getInstance()->getConnection();
        $sql = "UPDATE catalogo_proveedores SET
                    nombre = ?, categoria = ?, rfc = ?, telefono = ?, correo = ?,
                    ubicacion_fisica = ?, partner_activo = ?, url_tienda = ?,
                    razon_social = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ? AND activo = 1";
        $stmt = $db->prepare($sql);
        return $stmt->execute([
            $data['nombre'],
            $data['categoria'],
            $data['rfc'],
            $data['telefono'],
            $data['correo'],
            $data['ubicacion_fisica'],
            $data['partner_activo'],
            $data['url_tienda'],
            $data['razon_social'],
            $id
        ]);
    }

    public static function crearAgente(int $proveedorId, array $data): bool
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare(
            'INSERT INTO agentes_proveedores (proveedor_id, nombre, correo, telefono, activo)
             VALUES (?, ?, ?, ?, 1)'
        );
        return $stmt->execute([
            $proveedorId,
            $data['nombre'],
            $data['correo'],
            $data['telefono'],
        ]);
    }

    public static function buscarAgente(int $agenteId, int $proveedorId): ?array
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare(
            'SELECT id, proveedor_id, nombre, correo, telefono
               FROM agentes_proveedores
              WHERE id = ? AND proveedor_id = ? AND activo = 1'
        );
        $stmt->execute([$agenteId, $proveedorId]);
        $agente = $stmt->fetch(PDO::FETCH_ASSOC);

        return $agente ?: null;
    }

    public static function actualizarAgente(int $agenteId, int $proveedorId, array $data): bool
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare(
            'UPDATE agentes_proveedores
                SET nombre = ?, correo = ?, telefono = ?, updated_at = CURRENT_TIMESTAMP
              WHERE id = ? AND proveedor_id = ? AND activo = 1'
        );
        return $stmt->execute([
            $data['nombre'],
            $data['correo'],
            $data['telefono'],
            $agenteId,
            $proveedorId,
        ]);
    }

    public static function eliminarAgente(int $agenteId, int $proveedorId): bool
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare(
            'UPDATE agentes_proveedores
                SET activo = 0, updated_at = CURRENT_TIMESTAMP
              WHERE id = ? AND proveedor_id = ? AND activo = 1'
        );
        $stmt->execute([$agenteId, $proveedorId]);

        return $stmt->rowCount() === 1;
    }
}
