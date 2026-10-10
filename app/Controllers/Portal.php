<?php

namespace App\Controllers;

use App\Models\MClientes;
use App\Models\MPagos;

class Portal extends BaseController
{
    protected MClientes $ModeloClientes;
    protected MPagos $ModeloPagos;

    function __construct()
    {
        $this->ModeloClientes = new MClientes();
        $this->ModeloPagos    = new MPagos();
    }

    public function index()
    {
        if ($this->session->get('cliente_logueado')) {
            return redirect()->to(base_url('portal/cuenta'));
        }

        return view('portal/login');
    }

    public function entrar()
    {
        $telefono = $this->request->getPost('telefono');
        $pin      = $this->request->getPost('pin');

        $cliente = $this->ModeloClientes->getPorTelefono($telefono);
        /* var_dump($this->ModeloClientes->verificarPin($cliente->id_cliente, $pin)); */

        if (! $cliente || $cliente->estatus !== 'ACTIVO' || ! $this->ModeloClientes->verificarPin($cliente->id_cliente, $pin)) {
            return $this->jsonResponse(false, 'Teléfono o PIN incorrectos. Si no tienes un PIN asignado, pide a la tienda que te lo configure.');
        }

        $this->session->regenerate(true);
        $this->session->set([
            'cliente_logueado' => true,
            'id_cliente'       => $cliente->id_cliente,
        ]);

        return $this->jsonResponse(true, 'Bienvenido.');
    }

    public function cuenta()
    {
        if (! $this->session->get('cliente_logueado')) {
            return redirect()->to(base_url());
        }

        $idCliente = $this->session->get('id_cliente');

        $data['cliente']  = $this->ModeloClientes->getSaldo($idCliente);
        if (!$data['cliente'] || $data['cliente']->estatus !== 'ACTIVO') {
            $this->session->remove(['cliente_logueado', 'id_cliente']);
            return redirect()->to(base_url('portal'));
        }
        $this->response->setHeader('Cache-Control', 'private, no-store, no-cache, must-revalidate');
        $data['compras']  = $this->ModeloClientes->getHistorialCompras($idCliente);
        $data['pagos']    = $this->ModeloClientes->getHistorialPagos($idCliente);

        return view('portal/cuenta', $data);
    }

    public function logout()
    {
        $this->session->destroy();
        return redirect()->to(base_url());
    }

    public function descargarComprobante($idPago)
    {
        if (! $this->session->get('cliente_logueado')) {
            return redirect()->to(base_url());
        }

        $ruta = WRITEPATH . 'uploads/comprobantes/comprobante_' . $idPago . '.pdf';
        if (! is_file($ruta)) {
            return redirect()->to(base_url('portal/cuenta'));
        }

        return $this->response->download($ruta, null);
    }
}
