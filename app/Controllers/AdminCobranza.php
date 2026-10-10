<?php

namespace App\Controllers;

use App\Models\MCobranza;
use App\Models\MClientes;

class AdminCobranza extends BaseController
{
    protected MCobranza $Modelo;
    protected MClientes $ModeloClientes;

    function __construct()
    {
        $this->Modelo         = new MCobranza();
        $this->ModeloClientes = new MClientes();
    }

    public function index()
    {
        if ($check = $this->requireAdmin()) {
            return $check;
        }

        $data['nombre_completo'] = $this->session->get('nombre_completo');
        $data['rol']             = $this->session->get('rol');
        $data['aging']           = $this->Modelo->getAging();
        $data['resumen_aging']   = $this->Modelo->getResumenAging();
        $data['promesas']        = $this->Modelo->getPromesas('PENDIENTE');
        $data['seguimientos']    = $this->Modelo->getSeguimientosPendientes();
        $data['intenciones_pago'] = $this->Modelo->getIntencionesPago();
        $data['cola_mensajes']   = $this->Modelo->getCola();
        $data['clientes']        = $this->ModeloClientes->getTodosConSaldo();

        return view('admin/cobranza', $data);
    }

    public function guardarPromesa()
    {
        if ($check = $this->requireAdmin(true)) {
            return $check;
        }

        $idCliente = $this->request->getPost('id_cliente');
        $idVenta   = $this->request->getPost('id_venta');
        $monto     = $this->request->getPost('monto_prometido');
        $fecha     = $this->request->getPost('fecha_prometida');
        $notas     = $this->request->getPost('notas');

        if (empty($idCliente) || empty($monto) || empty($fecha)) {
            return $this->jsonResponse(false, 'Faltan datos de la promesa de pago.');
        }

        $resultado = $this->Modelo->guardarPromesa($idCliente, $idVenta ?: null, $monto, $fecha, $notas);

        return $this->jsonResponse($resultado[0], $resultado[1]);
    }

    public function actualizarPromesa($idPromesa)
    {
        if ($check = $this->requireAdmin(true)) {
            return $check;
        }

        $estatus = $this->request->getPost('estatus');
        $resultado = $this->Modelo->actualizarPromesa($idPromesa, $estatus);

        return $this->jsonResponse($resultado[0], $resultado[1]);
    }

    public function registrarSeguimiento()
    {
        if ($check = $this->requireAdmin(true)) {
            return $check;
        }

        $idCliente = (int) $this->request->getPost('id_cliente');
        $idVenta = $this->request->getPost('id_venta');
        $resultado = $this->request->getPost('resultado');
        $detalle = trim((string) $this->request->getPost('detalle'));
        $proximaFecha = $this->request->getPost('proxima_fecha');
        $resultadosPermitidos = ['CONTACTADO', 'SIN_RESPUESTA', 'PROMESA_PAGO', 'SEGUIMIENTO'];

        if (!$idCliente || !in_array($resultado, $resultadosPermitidos, true)) {
            return $this->jsonResponse(false, 'Selecciona el cliente y el resultado de la gestión.');
        }
        if ($proximaFecha && strtotime($proximaFecha) === false) {
            return $this->jsonResponse(false, 'La fecha del próximo seguimiento no es válida.');
        }

        $this->Modelo->registrarSeguimiento(
            $idCliente,
            $idVenta ? (int) $idVenta : null,
            (int) $this->session->get('id_usuario'),
            $resultado,
            $detalle ?: null,
            $proximaFecha ?: null
        );
        $this->registrarAuditoria('REGISTRAR_SEGUIMIENTO', 'clientes', $idCliente, $resultado . ($detalle ? ': ' . $detalle : ''));

        return $this->jsonResponse(true, 'Seguimiento guardado correctamente.');
    }

    public function enviarMensajeManual()
    {
        if ($check = $this->requireAdmin(true)) {
            return $check;
        }

        $idCliente = $this->request->getPost('id_cliente');
        $texto     = $this->request->getPost('texto');

        if (empty($idCliente) || empty($texto)) {
            return $this->jsonResponse(false, 'Selecciona un cliente y escribe el mensaje.');
        }

        $resultado = $this->Modelo->enviarManual($idCliente, $texto);

        return $this->jsonResponse($resultado[0], $resultado[1]);
    }

    public function marcarEnviado($idMensaje)
    {
        if ($check = $this->requireAdmin(true)) {
            return $check;
        }

        $resultado = $this->Modelo->marcarEnviado($idMensaje);

        return $this->jsonResponse($resultado[0], $resultado[1]);
    }
}
