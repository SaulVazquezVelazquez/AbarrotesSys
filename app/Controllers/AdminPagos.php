<?php

namespace App\Controllers;

use App\Models\MPagos;
use App\Models\MClientes;
use Dompdf\Dompdf;
use Dompdf\Options;

class AdminPagos extends BaseController
{
    protected MPagos $Modelo;
    protected MClientes $ModeloClientes;

    function __construct()
    {
        $this->Modelo         = new MPagos();
        $this->ModeloClientes = new MClientes();
    }

    public function index()
    {
        if ($check = $this->requireAdmin()) {
            return $check;
        }

        $data['nombre_completo'] = $this->session->get('nombre_completo');
        $data['rol']             = $this->session->get('rol');
        $data['pagos']           = $this->Modelo->getListado();
        $data['clientes']        = $this->ModeloClientes->getTodosConSaldo();
        $data['cliente_preseleccionado'] = $this->request->getGet('cliente');
        $data['monto_preseleccionado'] = $this->request->getGet('monto');
        $data['intento_preseleccionado'] = $this->request->getGet('intento');

        return view('admin/pagos', $data);
    }

    public function guardar()
    {
        if ($check = $this->requireAdmin(true)) {
            return $check;
        }

        $idCliente  = $this->request->getPost('id_cliente');
        $idVenta    = $this->request->getPost('id_venta');
        $monto      = $this->request->getPost('monto');
        $metodoPago = $this->request->getPost('metodo_pago') ?: 'EFECTIVO';
        $referencia = $this->request->getPost('referencia_pago');
        $idIntento = $this->request->getPost('id_intento');

        if (empty($idCliente) || empty($monto) || $monto <= 0) {
            return $this->jsonResponse(false, 'Selecciona un cliente e indica un monto válido.');
        }

        [$ok, $mensaje, $idPago] = $this->Modelo->registrarAbono(
            $idCliente,
            $monto,
            $metodoPago,
            $referencia,
            $this->session->get('id_usuario'),
            $idVenta ?: null
        );

        if ($ok) {
            if ($idIntento) {
                $this->db->table('mensajes_cobranza')->where('id_mensaje', (int) $idIntento)
                    ->where('id_cliente', (int) $idCliente)->where('tipo', 'INTENCION_PAGO')
                    ->where('estatus', 'PENDIENTE')->update(['estatus' => 'ENVIADO', 'fecha_envio' => date('Y-m-d H:i:s')]);
            }
            $this->registrarAuditoria('REGISTRAR_ABONO', 'pagos_abonos', $idPago, 'Cliente #' . $idCliente . ' - $' . $monto);
            $rutaPdf = $this->generarComprobante($idPago, $idCliente, $monto, $metodoPago, $referencia);
            if ($rutaPdf) {
                $this->Modelo->guardarRutaComprobante($idPago, $rutaPdf);
            }
        }

        return $this->jsonResponse($ok, $mensaje, ['id_pago' => $idPago]);
    }

    private function generarComprobante($idPago, $idCliente, $monto, $metodoPago, $referencia)
    {
        if (! class_exists(Dompdf::class)) {
            return null;
        }

        $cliente = $this->ModeloClientes->getSaldo($idCliente);

        $options = new Options();
        $options->set('isRemoteEnabled', false);

        $html = view('portal/comprobante_pdf', [
            'idPago'     => $idPago,
            'cliente'    => $cliente,
            'monto'      => $monto,
            'metodoPago' => $metodoPago,
            'referencia' => $referencia,
            'fecha'      => date('d/m/Y H:i'),
        ]);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper([0, 0, 300, 500], 'portrait'); // tamaño tipo "ticket"
        $dompdf->render();

        $nombreArchivo = 'comprobante_' . $idPago . '.pdf';
        file_put_contents(WRITEPATH . 'uploads/comprobantes/' . $nombreArchivo, $dompdf->output());

        return 'comprobantes/' . $nombreArchivo;
    }

    public function comprobante($idPago)
    {
        if ($check = $this->requireAdmin()) {
            return $check;
        }

        $ruta = WRITEPATH . 'uploads/comprobantes/comprobante_' . $idPago . '.pdf';
        if (! is_file($ruta)) {
            return redirect()->to(base_url('admin/pagos'));
        }

        return $this->response->download($ruta, null);
    }
}
