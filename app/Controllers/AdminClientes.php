<?php

namespace App\Controllers;

use App\Models\MClientes;

class AdminClientes extends BaseController
{
    protected MClientes $Modelo;

    function __construct()
    {
        $this->Modelo = new MClientes();
    }

    public function index()
    {
        if ($check = $this->requireAdmin()) {
            return $check;
        }

        $termino = $this->request->getGet('q');
        $filtros = [
            'semaforo' => $this->request->getGet('semaforo'),
            'estatus' => $this->request->getGet('estatus'),
            'deuda' => $this->request->getGet('deuda'),
        ];

        $data['nombre_completo'] = $this->session->get('nombre_completo');
        $data['rol']             = $this->session->get('rol');
        $data['clientes']        = $this->Modelo->buscar($termino ?? '', $filtros);

        return view('admin/clientes', $data);
    }

    public function ficha($idCliente)
    {
        if ($check = $this->requireAdmin()) {
            return $check;
        }

        $cliente = $this->Modelo->getSaldo($idCliente);
        if (! $cliente) {
            return redirect()->to(base_url('admin/clientes'));
        }

        $data['nombre_completo'] = $this->session->get('nombre_completo');
        $data['rol']             = $this->session->get('rol');
        $data['cliente']         = $cliente;
        $data['compras']         = $this->Modelo->getHistorialCompras($idCliente);
        $data['pagos']           = $this->Modelo->getHistorialPagos($idCliente);
        $data['contactos']       = $this->Modelo->getContactos($idCliente);
        $data['scoring']         = $this->Modelo->getScoring($idCliente);
        $data['actividad']       = $this->Modelo->getTimeline((int) $idCliente);

        return view('admin/cliente_ficha', $data);
    }

    public function foto($idCliente)
    {
        if ($check = $this->requireAdmin()) {
            return $check;
        }

        $cliente = $this->Modelo->find($idCliente);
        if (!$cliente || empty($cliente['foto']) || !str_starts_with($cliente['foto'], 'clientes/')) {
            return $this->response->setStatusCode(404);
        }

        $ruta = WRITEPATH . 'uploads/clientes/' . basename($cliente['foto']);
        if (!is_file($ruta)) {
            return $this->response->setStatusCode(404);
        }

        $tipoMime = mime_content_type($ruta);
        if (!in_array($tipoMime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true)) {
            return $this->response->setStatusCode(404);
        }

        return $this->response
            ->setHeader('Content-Type', $tipoMime)
            ->setHeader('Cache-Control', 'private, no-store, max-age=0')
            ->setHeader('Pragma', 'no-cache')
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setBody(file_get_contents($ruta));
    }

    public function buscarJson()
    {
        if ($check = $this->requireAdmin(true)) {
            return $check;
        }

        $termino = $this->request->getGet('q');
        $resultados = $termino ? $this->Modelo->buscar($termino) : [];

        return $this->response->setJSON(['data' => $resultados]);
    }

    public function guardar()
    {
        if ($check = $this->requireAdmin(true)) {
            return $check;
        }

        $id = $this->request->getPost('id_cliente');

        $data = [
            'nombre_completo' => $this->request->getPost('nombre_completo'),
            'telefono'        => $this->request->getPost('telefono'),
            'direccion'       => $this->request->getPost('direccion'),
            'limite_credito'  => $this->request->getPost('limite_credito') ?: 0,
            'plazo_dias'      => $this->request->getPost('plazo_dias') ?: 15,
            'notas'           => $this->request->getPost('notas'),
        ];

        if (empty($data['nombre_completo']) || empty($data['telefono'])) {
            return $this->jsonResponse(false, 'Nombre y teléfono son obligatorios.');
        }

        $pin = $this->request->getPost('pin');
        $archivoFoto = $this->request->getFile('foto_cliente');
        $fotoAnterior = null;

        if ($archivoFoto && $archivoFoto->getError() !== UPLOAD_ERR_NO_FILE) {
            if (!$archivoFoto->isValid()) {
                return $this->jsonResponse(false, 'No se pudo recibir la foto. Inténtalo de nuevo.');
            }
            if (!in_array($archivoFoto->getMimeType(), ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true)) {
                return $this->jsonResponse(false, 'La foto debe ser JPG, PNG, GIF o WebP.');
            }
        }

        if ($id) {
            $clienteAnterior = $this->Modelo->find($id);
            $fotoAnterior = $clienteAnterior['foto'] ?? null;
            // Solo DUENO/ADMIN pueden modificar el límite de crédito de un cliente existente
            if ($this->session->get('rol') === 'CAJERO') {
                unset($data['limite_credito']);
            }
            if ($archivoFoto && $archivoFoto->getError() !== UPLOAD_ERR_NO_FILE) {
                $nombreFoto = $archivoFoto->getRandomName();
                $archivoFoto->move(WRITEPATH . 'uploads/clientes', $nombreFoto);
                $data['foto'] = 'clientes/' . $nombreFoto;
            }
            $this->Modelo->update($id, $data);
            if (!empty($pin)) {
                $this->Modelo->establecerPin($id, $pin);
            }
            $this->registrarAuditoria('MODIFICAR_CLIENTE', 'clientes', $id, json_encode($data));
        } else {
            if ($archivoFoto && $archivoFoto->getError() !== UPLOAD_ERR_NO_FILE) {
                $nombreFoto = $archivoFoto->getRandomName();
                $archivoFoto->move(WRITEPATH . 'uploads/clientes', $nombreFoto);
                $data['foto'] = 'clientes/' . $nombreFoto;
            }
            $this->Modelo->insert($data);
            $id = $this->db->insertID();
            if (!empty($pin)) {
                $this->Modelo->establecerPin($id, $pin);
            }
            $this->registrarAuditoria('CREAR_CLIENTE', 'clientes', $id, json_encode($data));
        }

        if ($fotoAnterior && !empty($data['foto'])) {
            $rutaAnterior = WRITEPATH . 'uploads/clientes/' . basename($fotoAnterior);
            if (str_starts_with($fotoAnterior, 'clientes/') && is_file($rutaAnterior)) {
                unlink($rutaAnterior);
            }
        }

        return $this->jsonResponse(true, 'Cliente guardado correctamente.', ['id_cliente' => $id]);
    }

    public function bloquear($idCliente)
    {
        if ($check = $this->requireAdmin(true)) {
            return $check;
        }

        $motivo = $this->request->getPost('motivo') ?: 'Bloqueo manual';
        $resultado = $this->Modelo->bloquear($idCliente, $motivo);
        $this->registrarAuditoria('BLOQUEAR_CLIENTE', 'clientes', $idCliente, $motivo);

        return $this->jsonResponse($resultado[0], $resultado[1]);
    }

    public function desbloquear($idCliente)
    {
        // Solo DUENO/ADMIN pueden desbloquear (requiere autorización manual, según el requerimiento)
        if ($check = $this->requireAdmin(true, ['DUENO', 'ADMIN'])) {
            return $check;
        }

        $resultado = $this->Modelo->desbloquear($idCliente);
        $this->registrarAuditoria('DESBLOQUEAR_CLIENTE', 'clientes', $idCliente, null);

        return $this->jsonResponse($resultado[0], $resultado[1]);
    }

    public function guardarContacto()
    {
        if ($check = $this->requireAdmin(true)) {
            return $check;
        }

        $idCliente = $this->request->getPost('id_cliente');
        $nombre    = $this->request->getPost('nombre');
        $telefono  = $this->request->getPost('telefono');
        $relacion  = $this->request->getPost('relacion');

        if (empty($idCliente) || empty($nombre)) {
            return $this->jsonResponse(false, 'Faltan datos del contacto.');
        }

        $resultado = $this->Modelo->agregarContacto($idCliente, $nombre, $telefono, $relacion);

        return $this->jsonResponse($resultado[0], $resultado[1]);
    }
}
