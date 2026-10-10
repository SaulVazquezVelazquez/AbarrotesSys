<?php

namespace App\Models;

use CodeIgniter\Model;

class MSolicitudes extends Model
{
    protected $table      = 'solicitudes_compra';
    protected $primaryKey = 'id_solicitud';
    protected $allowedFields = [
        'id_cliente', 'nombre_cliente', 'telefono', 'email', 'notas',
        'total_estimado', 'estatus', 'motivo_rechazo', 'id_venta', 'ip_origen', 'token_seguimiento',
    ];

    function __construct()
    {
        parent::__construct();
    }

    /**
     * Crea una solicitud de compra desde el catálogo público (carrito).
     * $items: [['id_producto'=>n, 'nombre_producto'=>str, 'cantidad'=>n, 'precio_unitario'=>n], ...]
     */
    public function crear($nombre, $telefono, $email, $notas, $items, $ip)
    {
        if (empty($items)) {
            return [false, 'Tu carrito está vacío.'];
        }

        $total = 0;
        foreach ($items as $item) {
            $total += $item['cantidad'] * $item['precio_unitario'];
        }

        // Si el teléfono ya pertenece a un cliente registrado, se liga automáticamente
        $cliente = $this->db->query("SELECT id_cliente FROM clientes WHERE telefono = ? LIMIT 1", [$telefono])->getRow();

        $this->db->transStart();

        $tokenSeguimiento = bin2hex(random_bytes(24));
        $this->insert([
            'id_cliente'     => $cliente->id_cliente ?? null,
            'nombre_cliente' => $nombre,
            'telefono'       => $telefono,
            'email'          => $email,
            'notas'          => $notas,
            'total_estimado' => $total,
            'estatus'        => 'PENDIENTE',
            'ip_origen'      => $ip,
            'token_seguimiento' => $tokenSeguimiento,
        ]);
        $idSolicitud = $this->db->insertID();

        foreach ($items as $item) {
            $subtotal = $item['cantidad'] * $item['precio_unitario'];
            $this->db->query(
                "INSERT INTO detalle_solicitud_compra (id_solicitud, id_producto, nombre_producto, cantidad, precio_unitario, subtotal)
                 VALUES (?, ?, ?, ?, ?, ?)",
                [$idSolicitud, $item['id_producto'] ?: null, $item['nombre_producto'], $item['cantidad'], $item['precio_unitario'], $subtotal]
            );
        }

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return [false, 'No se pudo registrar tu solicitud, intenta de nuevo.'];
        }

        return [true, '¡Listo! Tu solicitud fue enviada. La tienda la revisará y se pondrá en contacto contigo.', $idSolicitud, $tokenSeguimiento];
    }

    public function getEstadoPorToken(string $token)
    {
        return $this->db->table('solicitudes_compra')
            ->select('id_solicitud, estatus, fecha_creacion, fecha_resolucion, motivo_rechazo, id_venta')
            ->where('token_seguimiento', $token)->get()->getRow();
    }

    public function getPendientesCount()
    {
        return (int) $this->db->query("SELECT COUNT(*) AS total FROM solicitudes_compra WHERE estatus = 'PENDIENTE'")->getRow()->total;
    }

    public function getTodas($estatus = null)
    {
        $vsql = "SELECT * FROM solicitudes_compra WHERE 1=1 ";
        $params = [];
        if (!empty($estatus)) {
            $vsql .= "AND estatus = ? ";
            $params[] = $estatus;
        }
        $vsql .= "ORDER BY fecha_creacion DESC";

        return $this->db->query($vsql, $params)->getResult();
    }

    public function getDetalle($idSolicitud)
    {
        $solicitud = $this->find($idSolicitud);
        if (! $solicitud) {
            return null;
        }

        $items = $this->db->query("SELECT * FROM detalle_solicitud_compra WHERE id_solicitud = ?", [$idSolicitud])->getResult();

        return ['solicitud' => $solicitud, 'items' => $items];
    }

    public function rechazar($idSolicitud, $motivo)
    {
        $this->update($idSolicitud, [
            'estatus'          => 'RECHAZADA',
            'motivo_rechazo'   => $motivo,
            'fecha_resolucion' => date('Y-m-d H:i:s'),
        ]);

        return [true, 'Solicitud rechazada.'];
    }

    /**
     * Aprueba la solicitud y la convierte en una venta a crédito real,
     * reutilizando la validación de límite de MVentas/MClientes.
     */
    public function aprobarYConvertir($idSolicitud, $idCliente, $idUsuario, $plazoDias, $ip)
    {
        $data = $this->getDetalle($idSolicitud);
        if (! $data || ($data['solicitud']['estatus'] ?? null) !== 'PENDIENTE') {
            return [false, 'Esta solicitud ya no está pendiente.'];
        }

        $items = [];
        foreach ($data['items'] as $i) {
            $items[] = [
                'id_producto'       => $i->id_producto,
                'descripcion_libre' => $i->id_producto ? null : $i->nombre_producto,
                'cantidad'          => $i->cantidad,
                'precio_unitario'   => $i->precio_unitario,
            ];
        }

        $modeloVentas = new MVentas();
        [$ok, $mensaje, $idVenta] = $modeloVentas->crearVenta($idCliente, $idUsuario, $items, $plazoDias, 'NINGUNO', null, $ip);

        if (! $ok) {
            return [false, $mensaje]; // ej. "excede el crédito disponible"
        }

        $this->update($idSolicitud, [
            'id_cliente'       => $idCliente,
            'estatus'          => 'CONVERTIDA',
            'id_venta'         => $idVenta,
            'fecha_resolucion' => date('Y-m-d H:i:s'),
        ]);

        return [true, 'Solicitud aprobada y convertida en venta a crédito.', $idVenta];
    }
}
