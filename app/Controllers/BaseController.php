<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

abstract class BaseController extends Controller
{
    protected $request;
    protected $helpers = ['url', 'form'];
    protected \CodeIgniter\Session\Session $session;
    protected \CodeIgniter\Database\BaseConnection $db;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->session = \Config\Services::session();
        $this->db      = \Config\Database::connect();
    }

    protected function jsonResponse(bool $respuesta, string $mensaje, array $extra = [])
    {
        return $this->response->setJSON(array_merge(
            ['respuesta' => $respuesta, 'mensaje' => $mensaje],
            $extra
        ));
    }

    /**
     * Verifica sesión de administrador/empleado activa.
     * $rolesPermitidos vacío = cualquier usuario logueado puede pasar.
     */
    protected function requireAdmin($esAjax = false, array $rolesPermitidos = [])
    {
        if ($this->session->get('logueado') != true) {
            return $esAjax
                ? $this->jsonResponse(false, 'Sesión finalizada, vuelve a iniciar sesión.')
                : redirect()->to(base_url('admin/login'));
        }

        if (!empty($rolesPermitidos) && !in_array($this->session->get('rol'), $rolesPermitidos)) {
            return $esAjax
                ? $this->jsonResponse(false, 'No tienes permiso para realizar esta acción.')
                : redirect()->to(base_url('admin/dashboard'));
        }

        return null;
    }

    /**
     * Registra una acción en la bitácora de auditoría.
     */
    protected function registrarAuditoria($accion, $entidad, $idEntidad, $detalle = null)
    {
        $this->db->query(
            "INSERT INTO auditoria (id_usuario, accion, entidad, id_entidad, detalle) VALUES (?, ?, ?, ?, ?)",
            [$this->session->get('id_usuario'), $accion, $entidad, $idEntidad, $detalle]
        );
    }
}
