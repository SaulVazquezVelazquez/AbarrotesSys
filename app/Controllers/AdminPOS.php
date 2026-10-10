<?php
namespace App\Controllers;
use App\Models\MPOS;
use App\Models\MProductos;

class AdminPOS extends BaseController
{
    private MPOS $pos;
    public function __construct(){ $this->pos=new MPOS(); }
    private function auth($ajax=true){ return $this->requireAdmin($ajax); }
    public function index(){ if($c=$this->auth(false))return $c; $data=['nombre_completo'=>$this->session->get('nombre_completo'),'rol'=>$this->session->get('rol'),'productos'=>(new MProductos())->getActivos()]; return view('admin/pos',$data); }
    public function buscar(){
        if($c=$this->auth()) return $c;
        $code=trim((string)$this->request->getGet('codigo'));
        $q=trim((string)$this->request->getGet('q'));
        $p=$code!==''?$this->pos->buscarCodigo($code):null;
        if(!$p && $q!==''){
            $normalizado=strtr(mb_strtolower($q),['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n']);
            $patron='%'.$normalizado.'%';
            $p=db_connect()->query("SELECT * FROM productos WHERE activo=1 AND (TRANSLATE(LOWER(nombre), 'áéíóúüñ', 'aeiouun') LIKE ? OR codigo_barras ILIKE ?) ORDER BY nombre ASC LIMIT 30",[$patron,'%'.$q.'%'])->getResult();
        }
        if(!$p && $q==='') $p=db_connect()->table('productos')->where('activo',1)->orderBy('nombre')->limit(200)->get()->getResult();
        if($p && !is_array($p)) $p=[$p];
        return $this->jsonResponse(true,'Consulta lista.',['productos'=>$p]);
    }
    public function cobrar(){ if($c=$this->auth())return $c; $items=json_decode((string)$this->request->getPost('items'),true)??[]; [$ok,$msg,$id]=(new MPOS())->registrarVenta((int)$this->session->get('id_usuario'),$items,(string)$this->request->getPost('metodo_pago'),$this->request->getPost('id_cliente')?(int)$this->request->getPost('id_cliente'):null,(string)$this->request->getPost('recibido'),json_decode((string)$this->request->getPost('pagos'),true)??[]); if($ok)$this->registrarAuditoria('POS_VENTA','pos_ventas',$id,$msg); return $this->jsonResponse($ok,$msg,['id_venta'=>$id]); }
    public function ticket(int $idVenta)
    {
        if ($check = $this->auth(false)) return $check;
        $db = db_connect();
        $venta = $db->query("SELECT pv.*, COALESCE(c.nombre_completo, 'Venta mostrador') AS cliente,
                                    c.telefono, COALESCE(u.nombre_completo, '—') AS atendio
                             FROM pos_ventas pv LEFT JOIN clientes c ON c.id_cliente = pv.id_cliente
                             LEFT JOIN usuarios u ON u.id_usuario = pv.id_usuario
                             WHERE pv.id_venta = ? AND pv.metodo_pago <> 'CREDITO'", [$idVenta])->getRow();
        if (!$venta) return redirect()->to(base_url('admin/ventas'));
        $items = $db->query('SELECT pd.cantidad, pd.precio_unitario, pd.subtotal, p.nombre AS nombre_producto, pd.departamento FROM pos_detalle pd JOIN productos p ON p.id_producto = pd.id_producto WHERE pd.id_venta = ? ORDER BY pd.id_detalle', [$idVenta])->getResult();
        $pagos = $db->query('SELECT metodo, monto FROM pos_pagos WHERE id_venta = ? ORDER BY id_pago', [$idVenta])->getResult();
        return view('admin/ticket', ['venta'=>$venta, 'items'=>$items, 'pagos'=>$pagos, 'tipo'=>'MOSTRADOR']);
    }
    public function pausar(){ if($c=$this->auth())return $c; $items=json_decode((string)$this->request->getPost('items'),true)??[]; if(!$items)return $this->jsonResponse(false,'La cuenta está vacía.'); $db=db_connect(); $db->table('pos_esperas')->insert(['id_usuario'=>$this->session->get('id_usuario'),'referencia'=>substr((string)$this->request->getPost('referencia'),0,80),'items_json'=>json_encode($items),'fecha_creacion'=>date('Y-m-d H:i:s')]); return $this->jsonResponse(true,'Cuenta puesta en espera.'); }
    public function esperas(){if($c=$this->auth())return $c;return $this->jsonResponse(true,'',['esperas'=>db_connect()->table('pos_esperas')->orderBy('fecha_creacion')->get()->getResult()]);}
    public function recuperar($id){if($c=$this->auth())return $c;$db=db_connect();$r=$db->table('pos_esperas')->where('id_espera',(int)$id)->get()->getRow();if(!$r)return $this->jsonResponse(false,'Cuenta no encontrada.');$db->table('pos_esperas')->where('id_espera',(int)$id)->delete();return $this->jsonResponse(true,'Cuenta recuperada.',['items'=>json_decode($r->items_json,true),'referencia'=>$r->referencia]);}
    public function eliminarEspera($id){if($c=$this->auth())return $c;db_connect()->table('pos_esperas')->where('id_espera',(int)$id)->delete();return $this->jsonResponse(true,'Cuenta eliminada.');}
    public function merma()
    {
        if ($check = $this->auth()) return $check;

        $id = (int) $this->request->getPost('id_producto');
        $cantidad = (float) $this->request->getPost('cantidad');
        if ($id <= 0 || $cantidad <= 0) return $this->jsonResponse(false, 'Producto y cantidad de merma son obligatorios.');

        $db = db_connect();
        $db->transBegin();
        try {
            $producto = $db->query('SELECT * FROM productos WHERE id_producto = ? FOR UPDATE', [$id])->getRow();
            if (!$producto || $producto->stock === null || (float) $producto->stock < $cantidad) {
                throw new \RuntimeException('Producto no válido o inventario insuficiente.');
            }

            $db->query('UPDATE productos SET stock = stock - ? WHERE id_producto = ?', [$cantidad, $id]);
            $db->table('pos_mermas')->insert([
                'id_producto' => $id,
                'id_usuario' => $this->session->get('id_usuario'),
                'cantidad' => $cantidad,
                'motivo' => trim((string) $this->request->getPost('motivo')),
            ]);
            if (!$db->transStatus()) throw new \RuntimeException('No se pudo guardar la merma.');
            $db->transCommit();
            return $this->jsonResponse(true, 'Merma registrada.');
        } catch (\Throwable $error) {
            $db->transRollback();
            return $this->jsonResponse(false, $error->getMessage());
        }
    }

    public function entrada()
    {
        if ($check = $this->auth()) return $check;

        $id = (int) $this->request->getPost('id_producto');
        $empaques = (float) $this->request->getPost('empaques');
        $unidadesPorEmpaque = (float) $this->request->getPost('unidades_por_empaque');
        if ($id <= 0 || $empaques <= 0 || $unidadesPorEmpaque <= 0) {
            return $this->jsonResponse(false, 'Selecciona un producto e indica empaques y unidades por empaque.');
        }

        $cantidadBase = $empaques * $unidadesPorEmpaque;
        $db = db_connect();
        $db->transBegin();
        try {
            $producto = $db->query('SELECT id_producto FROM productos WHERE id_producto = ? FOR UPDATE', [$id])->getRow();
            if (!$producto) throw new \RuntimeException('El producto seleccionado ya no existe.');
            $db->query('UPDATE productos SET stock = COALESCE(stock, 0) + ? WHERE id_producto = ?', [$cantidadBase, $id]);
            $db->table('pos_entradas')->insert([
                'id_producto' => $id,
                'id_usuario' => $this->session->get('id_usuario'),
                'empaques' => $empaques,
                'unidades_por_empaque' => $unidadesPorEmpaque,
                'cantidad_base' => $cantidadBase,
            ]);
            if (!$db->transStatus()) throw new \RuntimeException('No se pudo guardar la entrada.');
            $db->transCommit();
            return $this->jsonResponse(true, 'Entrada registrada: ' . rtrim(rtrim(number_format($cantidadBase, 3, '.', ''), '0'), '.') . ' unidades base.');
        } catch (\Throwable $error) {
            $db->transRollback();
            return $this->jsonResponse(false, $error->getMessage());
        }
    }
    public function cliente(){if($c=$this->auth())return $c;$n=trim((string)$this->request->getPost('nombre_completo'));$tel=trim((string)$this->request->getPost('telefono'));$lim=(float)$this->request->getPost('limite_credito');if($n===''||$tel==='')return $this->jsonResponse(false,'Nombre y teléfono son obligatorios.');$m=new \App\Models\MClientes();$id=$m->insert(['nombre_completo'=>$n,'telefono'=>$tel,'limite_credito'=>$lim,'plazo_dias'=>15,'estatus'=>'ACTIVO'],true);return $this->jsonResponse((bool)$id,'Cliente registrado.',['id_cliente'=>$id]);}
    public function abono()
    {
        if ($check = $this->auth()) return $check;

        $id = (int) $this->request->getPost('id_cliente');
        $monto = (float) $this->request->getPost('monto');
        if ($id <= 0 || $monto <= 0) return $this->jsonResponse(false, 'Selecciona un cliente e indica un monto válido.');

        $db = db_connect();
        $db->transBegin();
        try {
            $cliente = $db->query('SELECT * FROM clientes WHERE id_cliente = ? FOR UPDATE', [$id])->getRow();
            if (!$cliente) throw new \RuntimeException('Cliente no encontrado.');

            $pendiente = (float) $db->query(
                "SELECT COALESCE(SUM(saldo_pendiente), 0) AS total FROM ventas WHERE id_cliente = ? AND estatus IN ('PENDIENTE','PARCIAL','VENCIDA')",
                [$id]
            )->getRow()->total;
            if ($monto > $pendiente) throw new \RuntimeException('El abono supera el saldo pendiente.');

            $metodo = (string) ($this->request->getPost('metodo_pago') ?: 'EFECTIVO');
            if (!in_array($metodo, ['EFECTIVO', 'TRANSFERENCIA', 'TARJETA'], true)) {
                throw new \RuntimeException('Forma de pago inválida.');
            }

            $db->query('INSERT INTO pagos_abonos (id_cliente, monto, metodo_pago, id_usuario) VALUES (?, ?, ?, ?)', [
                $id, $monto, $metodo, $this->session->get('id_usuario'),
            ]);
            $pagoId = (int) $db->insertID();
            $restante = $monto;
            $ventas = $db->query(
                "SELECT * FROM ventas WHERE id_cliente = ? AND saldo_pendiente > 0 AND estatus IN ('PENDIENTE','PARCIAL','VENCIDA') ORDER BY fecha_venta FOR UPDATE",
                [$id]
            )->getResult();

            foreach ($ventas as $venta) {
                $aplicado = min($restante, (float) $venta->saldo_pendiente);
                $nuevoSaldo = (float) $venta->saldo_pendiente - $aplicado;
                $db->query('UPDATE ventas SET saldo_pendiente = ?, estatus = ? WHERE id_venta = ?', [
                    $nuevoSaldo, $nuevoSaldo <= 0 ? 'PAGADA' : 'PARCIAL', $venta->id_venta,
                ]);
                $restante -= $aplicado;
                if ($restante <= 0) break;
            }

            if (!$db->transStatus()) throw new \RuntimeException('No se pudo guardar el abono.');
            $db->transCommit();
            return $this->jsonResponse(true, 'Abono registrado.', [
                'id_pago' => $pagoId,
                'saldo_anterior' => $pendiente,
                'monto' => $monto,
                'saldo_nuevo' => $pendiente - $monto,
            ]);
        } catch (\Throwable $error) {
            $db->transRollback();
            return $this->jsonResponse(false, $error->getMessage());
        }
    }
    public function corte()
    {
        if ($check = $this->auth()) return $check;
        $real = $this->request->getPost('efectivo_contado');
        if (!is_numeric($real) || (float) $real < 0) return $this->jsonResponse(false, 'El efectivo contado debe ser un monto válido y no negativo.');
        $real = (float) $real;
        $db = db_connect();
        // Agrega cada pedido web primero por folio para no multiplicar su total por el número de renglones.
        $web = $db->query("WITH pedidos AS (SELECT v.id_venta, v.total, COALESCE(SUM(d.subtotal) FILTER (WHERE COALESCE(p.departamento, 'ABARROTES') = 'ABARROTES'), 0) AS abarrotes, COALESCE(SUM(d.subtotal) FILTER (WHERE p.departamento = 'VERDULERIA'), 0) AS verduleria FROM ventas v LEFT JOIN detalle_venta d ON d.id_venta = v.id_venta LEFT JOIN productos p ON p.id_producto = d.id_producto WHERE DATE(v.fecha_venta) = CURRENT_DATE AND v.pickup_at IS NOT NULL AND v.estatus <> 'CANCELADA' GROUP BY v.id_venta, v.total) SELECT COALESCE(SUM(total),0) AS total, COALESCE(SUM(abarrotes),0) AS abarrotes, COALESCE(SUM(verduleria),0) AS verduleria FROM pedidos")->getRow();
        $r = $db->query("SELECT COALESCE(SUM(total),0) + ? AS total, COALESCE(SUM(subtotal_abarrotes),0) + ? AS abarrotes, COALESCE(SUM(subtotal_verduleria),0) + ? AS verduleria FROM pos_ventas WHERE DATE(fecha_venta)=CURRENT_DATE", [$web->total, $web->abarrotes, $web->verduleria])->getRow();
        $pagos = $db->query("SELECT pp.metodo,COALESCE(SUM(pp.monto),0) monto FROM pos_pagos pp JOIN pos_ventas v USING(id_venta) WHERE DATE(v.fecha_venta)=CURRENT_DATE GROUP BY pp.metodo ORDER BY pp.metodo")->getResultArray();
        if ((float) $web->total > 0) $pagos[] = ['metodo' => 'CREDITO', 'monto' => (float) $web->total];
        $efectivo = 0.0;
        foreach ($pagos as $pago) if ($pago['metodo'] === 'EFECTIVO') $efectivo = (float) $pago['monto'];
        $db->table('pos_cortes')->insert([
            'id_usuario' => $this->session->get('id_usuario'), 'efectivo_esperado' => $efectivo,
            'efectivo_contado' => $real, 'total' => $r->total, 'abarrotes' => $r->abarrotes,
            'verduleria' => $r->verduleria, 'desglose_json' => json_encode($pagos),
        ]);
        return $this->jsonResponse(true, 'Corte realizado.', [
            'total' => $r->total, 'abarrotes' => $r->abarrotes, 'verduleria' => $r->verduleria,
            'pagos' => $pagos, 'efectivo_esperado' => $efectivo, 'efectivo_contado' => $real,
            'diferencia' => $real - $efectivo,
        ]);
    }
}
