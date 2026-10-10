<?php

namespace App\Controllers;

use App\Models\MVentas;
use App\Models\MClientes;
use App\Models\MProductos;

class AdminVentas extends BaseController
{
    protected MVentas $Modelo;
    protected MClientes $ModeloClientes;
    protected MProductos $ModeloProductos;

    function __construct()
    {
        $this->Modelo          = new MVentas();
        $this->ModeloClientes  = new MClientes();
        $this->ModeloProductos = new MProductos();
    }

    public function index()
    {
        if ($check = $this->requireAdmin()) {
            return $check;
        }

        $data['nombre_completo'] = $this->session->get('nombre_completo');
        $data['rol']             = $this->session->get('rol');
        $data['productos']       = $this->ModeloProductos->getActivos();
        $data['filtros'] = [
            'cliente' => trim((string) $this->request->getGet('cliente')),
            'desde' => trim((string) $this->request->getGet('desde')),
            'hasta' => trim((string) $this->request->getGet('hasta')),
            'tipo' => in_array($this->request->getGet('tipo'), ['CREDITO', 'MOSTRADOR'], true) ? $this->request->getGet('tipo') : '',
            'metodo_pago' => in_array($this->request->getGet('metodo_pago'), ['CREDITO', 'EFECTIVO', 'TRANSFERENCIA', 'TARJETA', 'MIXTO'], true) ? $this->request->getGet('metodo_pago') : '',
            'estatus' => in_array($this->request->getGet('estatus'), ['PENDIENTE', 'PARCIAL', 'PAGADA', 'VENCIDA', 'CANCELADA'], true) ? $this->request->getGet('estatus') : '',
        ];
        foreach (['desde', 'hasta'] as $campoFecha) {
            if ($data['filtros'][$campoFecha] !== '' && !$this->fechaHoraValida($data['filtros'][$campoFecha])) $data['filtros'][$campoFecha] = '';
        }
        $data['ventas'] = $data['filtros']['tipo'] === 'MOSTRADOR' ? [] : $this->Modelo->getListado($data['filtros']);
        $data['ventasMostrador'] = $data['filtros']['tipo'] === 'CREDITO' ? [] : $this->getVentasMostradorFiltradas($data['filtros']);

        return view('admin/ventas', $data);
    }

    private function fechaHoraValida(string $value): bool
    {
        $fecha = \DateTimeImmutable::createFromFormat('!Y-m-d\\TH:i', $value);
        $errores = \DateTimeImmutable::getLastErrors();
        return $fecha !== false && $fecha->format('Y-m-d\\TH:i') === $value && (!$errores || ($errores['warning_count'] === 0 && $errores['error_count'] === 0));
    }

    private function getVentasMostradorFiltradas(array $filtros): array
    {
        $sql = "SELECT pv.id_venta, pv.fecha_venta, pv.total, pv.metodo_pago, pv.efectivo_recibido, pv.cambio,
                       COALESCE(u.nombre_completo, '—') AS cajero,
                       COALESCE(c.nombre_completo, 'Venta mostrador') AS cliente,
                       COALESCE(string_agg(p.nombre || ' × ' || pd.cantidad, ', ' ORDER BY p.nombre), '—') AS productos
                FROM pos_ventas pv LEFT JOIN usuarios u ON u.id_usuario = pv.id_usuario
                LEFT JOIN clientes c ON c.id_cliente = pv.id_cliente
                LEFT JOIN pos_detalle pd ON pd.id_venta = pv.id_venta
                LEFT JOIN productos p ON p.id_producto = pd.id_producto
                WHERE pv.metodo_pago <> 'CREDITO'";
        $params = [];
        if ($filtros['cliente'] !== '') { $sql .= ' AND (COALESCE(c.nombre_completo, \'Venta mostrador\') ILIKE ? OR COALESCE(c.telefono, \'\') ILIKE ?)'; $params[] = '%' . $filtros['cliente'] . '%'; $params[] = '%' . $filtros['cliente'] . '%'; }
        if ($filtros['desde'] !== '') { $sql .= ' AND pv.fecha_venta >= ?'; $params[] = str_replace('T', ' ', $filtros['desde']); }
        if ($filtros['hasta'] !== '') { $sql .= ' AND pv.fecha_venta <= ?'; $params[] = str_replace('T', ' ', $filtros['hasta']); }
        if ($filtros['metodo_pago'] !== '' && $filtros['metodo_pago'] !== 'CREDITO') { $sql .= ' AND pv.metodo_pago = ?'; $params[] = $filtros['metodo_pago']; }
        if ($filtros['metodo_pago'] === 'CREDITO') return [];
        $sql .= ' GROUP BY pv.id_venta, pv.fecha_venta, pv.total, pv.metodo_pago, pv.efectivo_recibido, pv.cambio, u.nombre_completo, c.nombre_completo ORDER BY pv.fecha_venta DESC LIMIT 500';
        return $this->db->query($sql, $params)->getResult();
    }

    // Validación de límite en tiempo real, usada por el POS antes de dejar cobrar (AJAX)
    public function validarLimite()
    {
        if ($check = $this->requireAdmin(true)) {
            return $check;
        }

        $idCliente = $this->request->getGet('id_cliente');
        $monto     = (float) $this->request->getGet('monto');

        $saldo = $this->ModeloClientes->getSaldo($idCliente);
        if (! $saldo) {
            return $this->jsonResponse(false, 'Cliente no encontrado.');
        }

        [$ok, $mensaje] = $this->ModeloClientes->validarLimite($idCliente, $monto);

        return $this->jsonResponse($ok, $mensaje, [
            'credito_disponible' => $saldo->credito_disponible,
        ]);
    }

    public function guardar()
    {
        if ($check = $this->requireAdmin(true)) {
            return $check;
        }

        $idCliente          = $this->request->getPost('id_cliente');
        $itemsJson          = $this->request->getPost('items'); // JSON: [{id_producto, descripcion_libre, cantidad, precio_unitario}]
        $plazoDias          = $this->request->getPost('plazo_dias') ?: 15;
        $metodoConfirmacion = $this->request->getPost('metodo_confirmacion') ?: 'NINGUNO';
        $confirmacionValor  = $this->request->getPost('confirmacion_valor'); // PIN capturado o firma base64

        $items = json_decode($itemsJson, true);

        if (empty($idCliente) || empty($items)) {
            return $this->jsonResponse(false, 'Selecciona un cliente y al menos un artículo.');
        }

        // Si la confirmación es por PIN, se valida contra el PIN guardado del cliente
        if ($metodoConfirmacion === 'PIN') {
            if (! $this->ModeloClientes->verificarPin($idCliente, $confirmacionValor)) {
                return $this->jsonResponse(false, 'El PIN no es correcto. No se puede confirmar la entrega.');
            }
        }

        [$ok, $mensaje, $idVenta] = $this->Modelo->crearVenta(
            $idCliente,
            $this->session->get('id_usuario'),
            $items,
            $plazoDias,
            $metodoConfirmacion,
            $confirmacionValor,
            $this->request->getIPAddress()
        );

        if ($ok) {
            $this->registrarAuditoria('CREAR_CREDITO', 'ventas', $idVenta, 'Cliente #' . $idCliente);
        }

        return $this->jsonResponse($ok, $mensaje, ['id_venta' => $idVenta]);
    }

    public function actualizarPedido(int $idVenta)
    {
        if ($check = $this->requireAdmin(true)) return $check;
        $estado = strtoupper(trim((string) $this->request->getPost('pedido_estatus')));
        $allowed = ['PREPARANDO', 'LISTO', 'ENTREGADO'];
        if (!in_array($estado, $allowed, true)) return $this->jsonResponse(false, 'Estado de recogida no válido.');
        $db = $this->db;
        $db->transBegin();
        try {
            $pedido = $db->query('SELECT pickup_at, pedido_estatus FROM ventas WHERE id_venta = ? FOR UPDATE', [$idVenta])->getRow();
            if (!$pedido || empty($pedido->pickup_at)) throw new \RuntimeException('No encontramos ese pedido de recogida.');
            $steps = ['RECIBIDO' => 0, 'PREPARANDO' => 1, 'LISTO' => 2, 'ENTREGADO' => 3];
            if (($steps[$estado] ?? -1) !== ($steps[$pedido->pedido_estatus] ?? -2) + 1 && $estado !== $pedido->pedido_estatus) {
                throw new \RuntimeException('Actualiza el pedido en orden: recibido, preparando, listo y entregado.');
            }
            $db->table('ventas')->where('id_venta', $idVenta)->update(['pedido_estatus' => $estado]);
            if (!$db->transStatus()) throw new \RuntimeException('No se pudo actualizar el pedido.');
            $db->transCommit();
            return $this->jsonResponse(true, 'Pedido actualizado: ' . $estado . '.');
        } catch (\Throwable $error) {
            $db->transRollback();
            return $this->jsonResponse(false, $error->getMessage());
        }
    }

    public function detalle($idVenta)
    {
        if ($check = $this->requireAdmin()) {
            return $check;
        }

        $data = $this->Modelo->getDetalle($idVenta);
        if (! $data) {
            return redirect()->to(base_url('admin/ventas'));
        }

        $data['nombre_completo'] = $this->session->get('nombre_completo');
        $data['rol']             = $this->session->get('rol');

        return view('admin/venta_detalle', $data);
    }

    public function ticket(int $idVenta)
    {
        if ($check = $this->requireAdmin()) return $check;
        $data = $this->Modelo->getDetalle($idVenta);
        if (!$data) return redirect()->to(base_url('admin/ventas'));
        $data['tipo'] = 'CREDITO';
        $data['pagos'] = array_map(static fn($p) => (object) ['metodo' => $p->metodo_pago, 'monto' => $p->monto], $data['pagos']);
        return view('admin/ticket', $data);
    }

    public function cancelar($idVenta)
    {
        // Cancelar una deuda es sensible: solo DUENO/ADMIN
        if ($check = $this->requireAdmin(true, ['DUENO', 'ADMIN'])) {
            return $check;
        }

        $motivo = $this->request->getPost('motivo');
        $resultado = $this->Modelo->cancelar($idVenta, $motivo);
        $this->registrarAuditoria('CANCELAR_DEUDA', 'ventas', $idVenta, $motivo);

        return $this->jsonResponse($resultado[0], $resultado[1]);
    }
}
