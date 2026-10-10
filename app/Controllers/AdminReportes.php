<?php

namespace App\Controllers;

use App\Models\MReportes;

class AdminReportes extends BaseController
{
    protected MReportes $Modelo;

    function __construct()
    {
        $this->Modelo = new MReportes();
    }

    public function index()
    {
        if ($check = $this->requireAdmin()) {
            return $check;
        }

        $data['nombre_completo']    = $this->session->get('nombre_completo');
        $data['rol']                = $this->session->get('rol');
        $data['resumen']            = $this->Modelo->getResumenGeneral();
        $data['flujo_caja']         = $this->Modelo->getFlujoCaja(30);

        return view('admin/reportes', $data);
    }

    // Exporta los saldos actuales de clientes a CSV (Excel los abre directamente).
    public function exportarCarteraCsv()
    {
        if ($check = $this->requireAdmin()) {
            return $check;
        }

        $filas = $this->Modelo->getCarteraDetalladaParaExportar();

        $this->response->setHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->response->setHeader('Content-Disposition', 'attachment; filename="cartera_' . date('Y-m-d') . '.csv"');

        $output = fopen('php://temp', 'w');
        fputcsv($output, ['Cliente', 'Teléfono', 'Saldo actual', 'Límite de crédito', 'Crédito disponible', 'Próxima fecha límite']);

        foreach ($filas as $f) {
            fputcsv($output, [
                $f->nombre_completo, $f->telefono, $f->saldo_actual, $f->limite_credito,
                $f->credito_disponible, $f->proxima_fecha_limite,
            ]);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $this->response->setBody($csv);
    }
}
