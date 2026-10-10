<?php

namespace App\Controllers;

use App\Models\MReportes;
use App\Models\MVentas;
use App\Models\MSolicitudes;

class AdminDashboard extends BaseController
{
    protected MReportes $ModeloReportes;
    protected MVentas $ModeloVentas;
    protected MSolicitudes $ModeloSolicitudes;

    function __construct()
    {
        $this->ModeloReportes    = new MReportes();
        $this->ModeloVentas      = new MVentas();
        $this->ModeloSolicitudes = new MSolicitudes();
    }

    public function index()
    {
        if ($check = $this->requireAdmin()) {
            return $check;
        }

        // Refresca automáticamente el estatus de ventas vencidas cada vez que se abre el dashboard
        $this->ModeloVentas->actualizarVencidas();

        $data['nombre_completo']       = $this->session->get('nombre_completo');
        $data['rol']                   = $this->session->get('rol');
        $data['resumen']               = $this->ModeloReportes->getResumenGeneral();
        $data['flujo_caja']            = $this->ModeloReportes->getFlujoCaja(30);
        $data['solicitudes_pendientes'] = $this->ModeloSolicitudes->getPendientesCount();

        return view('admin/dashboard', $data);
    }
}
