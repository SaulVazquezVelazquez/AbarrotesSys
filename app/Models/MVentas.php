<?php

namespace App\Models;

use CodeIgniter\Model;

class MVentas extends Model
{
    protected $table      = 'ventas';
    protected $primaryKey = 'id_venta';
    protected $allowedFields = [
        'id_cliente', 'id_usuario', 'fecha_limite_pago', 'subtotal', 'total',
        'saldo_pendiente', 'estatus', 'metodo_confirmacion', 'confirmacion_valor', 'ip_registro',
    ];

    function __construct()
    {
        parent::__construct();
    }

    /** Registra un pedido web autenticado a crédito y reserva inventario de forma atómica. */
    public function crearPedidoPickup(int $idCliente, array $items, string $pickupLocal, string $notas, string $ip): array
    {
        if (!$items || count($items) > 100) return [false, 'El carrito está vacío o contiene demasiados productos.'];
        $normalizedItems = [];
        foreach ($items as $item) {
            if (!is_array($item)) return [false, 'Hay un producto inválido en el carrito.'];
            $productId = filter_var($item['id_producto'] ?? null, FILTER_VALIDATE_INT);
            $quantity = filter_var($item['cantidad'] ?? null, FILTER_VALIDATE_FLOAT);
            if (!$productId || $quantity === false || !is_finite((float) $quantity) || (float) $quantity <= 0 || (float) $quantity > 99999) {
                return [false, 'Hay un producto o cantidad inválidos en el carrito.'];
            }
            $normalizedItems[$productId] = ($normalizedItems[$productId] ?? 0) + (float) $quantity;
        }
        $zone = new \DateTimeZone(config('App')->appTimezone);
        $pickup = \DateTimeImmutable::createFromFormat('!Y-m-d\\TH:i', $pickupLocal, $zone);
        $dateErrors = \DateTimeImmutable::getLastErrors();
        if (!$pickup || ($dateErrors && ($dateErrors['warning_count'] || $dateErrors['error_count']))) {
            return [false, 'Elige una fecha y hora de recogida válidas.'];
        }
        $now = new \DateTimeImmutable('now', $zone);
        if ($pickup < $now->modify('+30 minutes') || $pickup > $now->modify('+7 days')) {
            return [false, 'La recogida debe programarse con al menos 30 minutos y dentro de los próximos 7 días.'];
        }

        $db = $this->db;
        $db->transBegin();
        try {
            $cliente = $db->query('SELECT * FROM clientes WHERE id_cliente = ? FOR UPDATE', [$idCliente])->getRow();
            if (!$cliente || $cliente->estatus !== 'ACTIVO') throw new \RuntimeException('Solo los clientes registrados activos pueden hacer pedidos.');

            $prepared = []; $total = 0.0;
            foreach ($normalizedItems as $idProducto => $qty) {
                $producto = $db->query('SELECT * FROM productos WHERE id_producto = ? AND activo = 1 FOR UPDATE', [$idProducto])->getRow();
                if (!$producto) throw new \RuntimeException('Uno de los productos ya no está disponible. Actualiza el catálogo.');
                $qty = (float) $qty;
                if (abs($qty - round($qty, 3)) > 0.00001) throw new \RuntimeException('La cantidad de ' . $producto->nombre . ' admite hasta 3 decimales.');
                if (strtoupper((string) ($producto->unidad_base ?? 'PZA')) === 'PZA' && abs($qty - round($qty)) > 0.00001) {
                    throw new \RuntimeException('La cantidad de ' . $producto->nombre . ' debe ser un número entero.');
                }
                if ($producto->stock !== null && (float) $producto->stock + 0.00001 < $qty) {
                    throw new \RuntimeException('No hay existencias suficientes de ' . $producto->nombre . '.');
                }
                $price = (float) $producto->precio;
                $subtotal = round($qty * $price, 2);
                $total += $subtotal;
                $prepared[] = [$producto, $qty, $price, $subtotal];
            }
            $total = round($total, 2);
            if ($total <= 0) throw new \RuntimeException('El total del pedido debe ser mayor que cero.');

            $saldo = (float) $db->query("SELECT COALESCE(SUM(saldo_pendiente), 0) AS saldo FROM ventas WHERE id_cliente = ? AND estatus IN ('PENDIENTE','PARCIAL','VENCIDA')", [$idCliente])->getRow()->saldo;
            $disponible = max(0, (float) $cliente->limite_credito - $saldo);
            if ($total > $disponible + 0.00001) {
                throw new \RuntimeException('El pedido excede tu crédito disponible de $' . number_format($disponible, 2) . '.');
            }

            $fechaLimite = $now->modify('+' . max(1, (int) $cliente->plazo_dias) . ' days')->format('Y-m-d');
            $db->query("INSERT INTO ventas (id_cliente, id_usuario, fecha_limite_pago, subtotal, total, saldo_pendiente, estatus, metodo_confirmacion, ip_registro, pickup_at, pedido_estatus, notas_pedido) VALUES (?, NULL, ?, ?, ?, ?, 'PENDIENTE', 'NINGUNO', ?, ?, 'RECIBIDO', ?)", [
                $idCliente, $fechaLimite, $total, $total, $total, $ip, $pickup->format('Y-m-d H:i:s'), mb_substr(trim($notas), 0, 500),
            ]);
            $idVenta = (int) $db->insertID();
            foreach ($prepared as [$producto, $qty, $price, $subtotal]) {
                $db->query('INSERT INTO detalle_venta (id_venta, id_producto, descripcion_libre, cantidad, precio_unitario, subtotal) VALUES (?, ?, NULL, ?, ?, ?)', [$idVenta, $producto->id_producto, $qty, $price, $subtotal]);
                if ($producto->stock !== null) $db->query('UPDATE productos SET stock = stock - ? WHERE id_producto = ?', [$qty, $producto->id_producto]);
            }
            if ($db->transStatus() === false) throw new \RuntimeException('No se pudo guardar el pedido. No se descontó inventario.');
            $db->transCommit();
            return [true, 'Pedido #' . $idVenta . ' confirmado. Recógelo el ' . $pickup->format('d/m/Y H:i') . '.', $idVenta, $total, $disponible - $total];
        } catch (\Throwable $error) {
            $db->transRollback();
            return [false, $error->getMessage()];
        }
    }

    /**
     * Crea una venta a crédito completa (cabecera + detalle) dentro de una
     * transacción. $items es un arreglo de:
     *   ['id_producto' => n|null, 'descripcion_libre' => str|null, 'cantidad' => n, 'precio_unitario' => n]
     */
    public function crearVenta($idCliente, $idUsuario, $items, $plazoDias, $metodoConfirmacion, $confirmacionValor, $ip)
    {
        if (empty($items)) {
            return [false, 'La venta necesita al menos un artículo.'];
        }

        $total = 0;
        foreach ($items as $item) {
            $total += $item['cantidad'] * $item['precio_unitario'];
        }

        // Validación de límite de crédito en tiempo real (regla de negocio central)
        $modeloClientes = new MClientes();
        [$ok, $mensaje] = $modeloClientes->validarLimite($idCliente, $total);
        if (! $ok) {
            return [false, $mensaje];
        }

        $this->db->transStart();

        $fechaLimite = date('Y-m-d', strtotime('+' . (int) $plazoDias . ' days'));

        $this->insert([
            'id_cliente'          => $idCliente,
            'id_usuario'          => $idUsuario,
            'fecha_limite_pago'   => $fechaLimite,
            'subtotal'            => $total,
            'total'               => $total,
            'saldo_pendiente'     => $total,
            'estatus'             => 'PENDIENTE',
            'metodo_confirmacion' => $metodoConfirmacion,
            'confirmacion_valor'  => $confirmacionValor,
            'ip_registro'         => $ip,
        ]);
        $idVenta = $this->db->insertID();

        $modeloProductos = new MProductos();

        foreach ($items as $item) {
            $subtotalItem = $item['cantidad'] * $item['precio_unitario'];
            $this->db->query(
                "INSERT INTO detalle_venta (id_venta, id_producto, descripcion_libre, cantidad, precio_unitario, subtotal)
                 VALUES (?, ?, ?, ?, ?, ?)",
                [$idVenta, $item['id_producto'] ?: null, $item['descripcion_libre'] ?? null, $item['cantidad'], $item['precio_unitario'], $subtotalItem]
            );

            if (!empty($item['id_producto'])) {
                $modeloProductos->descontarStock($item['id_producto'], $item['cantidad']);
            }
        }

        // Programa el recordatorio previo al vencimiento (usando DIAS_AVISO_AMARILLO)
        $diasAviso = (int) ($this->db->query("SELECT valor FROM configuracion WHERE clave = 'DIAS_AVISO_AMARILLO'")->getRow()->valor ?? 3);
        $fechaAviso = date('Y-m-d H:i:s', strtotime($fechaLimite . " -{$diasAviso} days"));
        $this->db->query(
            "INSERT INTO mensajes_cobranza (id_cliente, id_venta, tipo, canal, fecha_programada, estatus)
             VALUES (?, ?, 'RECORDATORIO_PREVIO', 'WHATSAPP', ?, 'PENDIENTE')",
            [$idCliente, $idVenta, $fechaAviso]
        );

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return [false, 'No se pudo registrar la venta, intenta de nuevo.'];
        }

        return [true, 'Venta a crédito registrada correctamente.', $idVenta];
    }

    public function getDetalle($idVenta)
    {
        $venta = $this->db->query(
            "SELECT v.*, c.nombre_completo AS cliente, c.telefono, u.nombre_completo AS atendio
             FROM ventas v
             INNER JOIN clientes c ON c.id_cliente = v.id_cliente
             LEFT JOIN usuarios u ON u.id_usuario = v.id_usuario
             WHERE v.id_venta = ?",
            [$idVenta]
        )->getRow();

        if (! $venta) {
            return null;
        }

        $items = $this->db->query(
            "SELECT d.*, p.nombre AS nombre_producto
             FROM detalle_venta d
             LEFT JOIN productos p ON p.id_producto = d.id_producto
             WHERE d.id_venta = ?",
            [$idVenta]
        )->getResult();

        $pagos = $this->db->query("SELECT * FROM pagos_abonos WHERE id_venta = ? ORDER BY fecha_pago ASC", [$idVenta])->getResult();

        return ['venta' => $venta, 'items' => $items, 'pagos' => $pagos];
    }

    public function getListado(array $filtros = [])
    {
        $vsql = "SELECT v.id_venta, v.total, v.saldo_pendiente, v.estatus, v.fecha_venta, v.fecha_limite_pago,
                        v.pickup_at, v.pedido_estatus,
                        c.nombre_completo AS cliente, c.telefono
                 FROM ventas v
                 INNER JOIN clientes c ON c.id_cliente = v.id_cliente
                 WHERE 1=1 ";
        $params = [];
        if (!empty($filtros['cliente'])) { $vsql .= 'AND (c.nombre_completo ILIKE ? OR c.telefono ILIKE ?) '; $params[] = '%' . $filtros['cliente'] . '%'; $params[] = '%' . $filtros['cliente'] . '%'; }
        if (!empty($filtros['desde'])) { $vsql .= 'AND v.fecha_venta >= ? '; $params[] = str_replace('T', ' ', $filtros['desde']); }
        if (!empty($filtros['hasta'])) { $vsql .= 'AND v.fecha_venta <= ? '; $params[] = str_replace('T', ' ', $filtros['hasta']); }
        if (!empty($filtros['estatus'])) { $vsql .= 'AND v.estatus = ? '; $params[] = $filtros['estatus']; }
        if (!empty($filtros['metodo_pago']) && $filtros['metodo_pago'] !== 'CREDITO') return [];
        $vsql .= "ORDER BY v.fecha_venta DESC LIMIT 300";

        return $this->db->query($vsql, $params)->getResult();
    }

    public function cancelar($idVenta, $motivo)
    {
        $this->update($idVenta, ['estatus' => 'CANCELADA', 'saldo_pendiente' => 0]);
        return [true, 'Venta cancelada correctamente.'];
    }

    // Marca automáticamente como VENCIDA cualquier venta cuya fecha límite ya pasó
    public function actualizarVencidas()
    {
        $this->db->query(
            "UPDATE ventas SET estatus = 'VENCIDA'
             WHERE estatus IN ('PENDIENTE','PARCIAL') AND fecha_limite_pago < CURRENT_DATE AND saldo_pendiente > 0"
        );
    }
}
