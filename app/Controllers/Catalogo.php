<?php

namespace App\Controllers;

use App\Models\MProductos;
use App\Models\MSolicitudes;
use App\Models\MVentas;
use App\Models\MClientes;

class Catalogo extends BaseController
{
    protected MProductos $ModeloProductos;
    protected MSolicitudes $ModeloSolicitudes;

    function __construct()
    {
        $this->ModeloProductos   = new MProductos();
        $this->ModeloSolicitudes = new MSolicitudes();
    }

    // Página pública: catálogo con carrito (no requiere login)
    public function index()
    {
        $this->response->setHeader('Cache-Control', 'private, no-store, no-cache, must-revalidate');
        $cliente = null;
        if ($this->session->get('cliente_logueado')) {
            $cliente = (new MClientes())->getSaldo((int) $this->session->get('id_cliente'));
            if (!$cliente || $cliente->estatus !== 'ACTIVO') {
                $this->session->remove(['cliente_logueado', 'id_cliente']);
                $cliente = null;
            }
        }
        $data['productos'] = $this->ModeloProductos->getActivos();
        $data['cliente'] = $cliente;
        $data['imagenesPorProducto'] = $this->ModeloProductos->getImagenesPorProducto(array_map(
            static fn($producto) => (int) $producto->id_producto,
            $data['productos']
        ));

        return view('catalogo/index', $data);
    }

    // Confirma un pedido pickup únicamente para un cliente autenticado y lo carga a su cuenta.
    public function solicitar()
    {
        if (!$this->session->get('cliente_logueado')) {
            return $this->jsonResponse(false, 'Inicia sesión con tu cuenta de cliente para realizar el pedido.');
        }
        $items = json_decode((string) $this->request->getPost('items'), true);
        if (!is_array($items)) $items = [];
        [$ok, $mensaje, $idVenta, $total, $disponible] = (new MVentas())->crearPedidoPickup(
            (int) $this->session->get('id_cliente'),
            $items,
            trim((string) $this->request->getPost('pickup_at')),
            (string) $this->request->getPost('notas'),
            $this->request->getIPAddress()
        );
        return $this->jsonResponse($ok, $mensaje, [
            'id_venta' => $idVenta ?? null,
            'total' => $total ?? null,
            'credito_disponible' => $disponible ?? null,
        ]);
    }

    public function estadoSolicitud()
    {
        $token = strtolower(trim((string) $this->request->getPost('token')));
        if (!preg_match('/^[a-f0-9]{48}$/', $token)) {
            return $this->jsonResponse(false, 'Escribe el código de seguimiento que recibiste al enviar tu solicitud.');
        }

        $solicitud = $this->ModeloSolicitudes->getEstadoPorToken($token);
        if (!$solicitud) {
            return $this->jsonResponse(false, 'No encontramos una solicitud con ese código. Revisa que esté completo.');
        }

        $mensajes = [
            'PENDIENTE' => 'La tienda recibió tu solicitud y la revisará pronto.',
            'CONVERTIDA' => 'Tu solicitud fue aprobada y se convirtió en una compra a crédito.',
            'RECHAZADA' => 'La tienda no pudo aprobar esta solicitud.',
        ];
        return $this->jsonResponse(true, 'Estado consultado.', [
            'solicitud' => [
                'folio' => $solicitud->id_solicitud,
                'estatus' => $solicitud->estatus,
                'mensaje' => $mensajes[$solicitud->estatus] ?? 'La solicitud fue actualizada.',
                'fecha' => $solicitud->fecha_resolucion ?: $solicitud->fecha_creacion,
                'motivo' => $solicitud->estatus === 'RECHAZADA' ? $solicitud->motivo_rechazo : null,
            ],
        ]);
    }
}
