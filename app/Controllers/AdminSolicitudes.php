<?php

namespace App\Controllers;

use App\Models\MSolicitudes;
use App\Models\MClientes;

class AdminSolicitudes extends BaseController
{
    protected MSolicitudes $Modelo;
    protected MClientes $ModeloClientes;

    function __construct()
    {
        $this->Modelo         = new MSolicitudes();
        $this->ModeloClientes = new MClientes();
    }

    public function index()
    {
        if ($check = $this->requireAdmin()) {
            return $check;
        }

        $data['nombre_completo'] = $this->session->get('nombre_completo');
        $data['rol']             = $this->session->get('rol');
        $data['solicitudes']     = $this->Modelo->getTodas();
        $data['clientes']        = $this->ModeloClientes->getTodosConSaldo();

        return view('admin/solicitudes', $data);
    }

    public function detalle($idSolicitud)
    {
        if ($check = $this->requireAdmin(true)) {
            return $check;
        }

        $data = $this->Modelo->getDetalle($idSolicitud);
        if (! $data) {
            return $this->jsonResponse(false, 'Solicitud no encontrada.');
        }

        return $this->response->setJSON(['respuesta' => true, 'data' => $data]);
    }

    // Aprobar: liga (o exige seleccionar) un cliente registrado y convierte la
    // solicitud en una venta a crédito real, validando el límite disponible.
    public function aprobar($idSolicitud)
    {
        if ($check = $this->requireAdmin(true)) {
            return $check;
        }

        $idCliente = $this->request->getPost('id_cliente');
        $plazoDias = $this->request->getPost('plazo_dias') ?: 15;

        if (empty($idCliente)) {
            return $this->jsonResponse(false, 'Selecciona a qué cliente registrado corresponde esta solicitud (o créalo primero en Clientes).');
        }

        [$ok, $mensaje, $idVenta] = $this->Modelo->aprobarYConvertir(
            $idSolicitud,
            $idCliente,
            $this->session->get('id_usuario'),
            $plazoDias,
            $this->request->getIPAddress()
        );

        if ($ok) {
            $this->registrarAuditoria('APROBAR_SOLICITUD', 'solicitudes_compra', $idSolicitud, 'Convertida en venta #' . $idVenta);
        }

        return $this->jsonResponse($ok, $mensaje, ['id_venta' => $idVenta ?? null]);
    }

    public function rechazar($idSolicitud)
    {
        if ($check = $this->requireAdmin(true)) {
            return $check;
        }

        $motivo = $this->request->getPost('motivo') ?: 'Sin motivo especificado';
        $resultado = $this->Modelo->rechazar($idSolicitud, $motivo);
        $this->registrarAuditoria('RECHAZAR_SOLICITUD', 'solicitudes_compra', $idSolicitud, $motivo);

        return $this->jsonResponse($resultado[0], $resultado[1]);
    }
}
