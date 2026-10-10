<?php
namespace App\Models;

use CodeIgniter\Model;

class MPOS extends Model
{
    protected $table = 'productos';
    protected $primaryKey = 'id_producto';

    public function buscarCodigo(string $codigo): ?object
    {
        $codigo = trim($codigo);
        if ($codigo === '') return null;
        $directo = $this->db->query("SELECT * FROM productos WHERE activo=1 AND codigo_barras=? LIMIT 1", [$codigo])->getRow();
        if ($directo) return $directo;

        // Las etiquetas de báscula EAN-13 son numéricas; los códigos QR
        // almacenados en codigo_barras pueden ser alfanuméricos.
        if (!preg_match('/^\d+$/', $codigo)) return null;
        // Etiqueta ponderada típica: prefijo + PLU de 5 dígitos + peso (5) o precio (5) + control.
        $cfg = $this->db->query("SELECT valor FROM configuracion WHERE clave='POS_ETIQUETA_PREFIJO'")->getRow();
        $prefix = (string) ($cfg->valor ?? '20');
        $prefixLength = strlen($prefix);
        if (strlen($codigo) !== $prefixLength + 11 || !str_starts_with($codigo, $prefix)) return null;
        $plu = substr($codigo, $prefixLength, 5);
        $producto = $this->db->query("SELECT * FROM productos WHERE activo=1 AND plu=? LIMIT 1", [$plu])->getRow();
        if (!$producto) return null;
        $tipo = $this->db->query("SELECT valor FROM configuracion WHERE clave='POS_ETIQUETA_TIPO'")->getRow();
        $valor = (int) substr($codigo, $prefixLength + 5, 5);
        if (($tipo->valor ?? 'PRECIO') === 'PESO') {
            $producto->cantidad_venta = $valor / 1000;
            $producto->precio_etiqueta = round($producto->cantidad_venta * (float) $producto->precio, 2);
        } else {
            $producto->precio_etiqueta = $valor / 100;
            $producto->cantidad_venta = round($producto->precio_etiqueta / max((float) $producto->precio, .01), 3);
        }
        $producto->etiqueta_peso = true;
        return $producto;
    }

    public function registrarVenta(int $usuario, array $items, string $metodo, ?int $cliente, string $recibido, array $pagos = []): array
    {
        $db = $this->db; $db->transBegin();
        try {
            $total = 0; $sub = ['ABARROTES'=>0.0,'VERDULERIA'=>0.0]; $stockNeeded = [];
            if ($metodo === 'CREDITO') {
                if (!$cliente) throw new \RuntimeException('Selecciona el cliente que llevará la compra a cuenta.');
                $clienteBloqueado = $db->query('SELECT estatus FROM clientes WHERE id_cliente = ? FOR UPDATE', [$cliente])->getRow();
                if (!$clienteBloqueado || $clienteBloqueado->estatus !== 'ACTIVO') throw new \RuntimeException('Cliente no disponible.');
            }
            foreach ($items as &$i) {
                $p = $db->query('SELECT * FROM productos WHERE id_producto=? AND activo=1 FOR UPDATE', [(int)$i['id_producto']])->getRow();
                $qty=(float)$i['cantidad']; if (!$p || $qty<=0) throw new \RuntimeException('Producto o cantidad inválidos.');
                $price = (float)$p->precio; $line=round($qty*$price,2);
                if (!empty($i['codigo_escaneado'])) { $scanned=$this->buscarCodigo((string)$i['codigo_escaneado']); if(!$scanned || (int)$scanned->id_producto!==(int)$p->id_producto)throw new \RuntimeException('Etiqueta de báscula inválida.'); $qty=(float)$scanned->cantidad_venta; $line=(float)$scanned->precio_etiqueta; $price=$qty>0?round($line/$qty,4):$price; } $dept=$p->departamento ?: 'ABARROTES'; $sub[$dept]=($sub[$dept]??0)+$line; $total += $line;
                if (abs($qty - round($qty, 3)) > 0.00001 || (strtoupper((string) ($p->unidad_base ?? 'PZA')) === 'PZA' && abs($qty - round($qty)) > 0.00001)) throw new \RuntimeException('La cantidad de ' . $p->nombre . ' no es válida para su unidad de venta.');
                $stockNeeded[$p->id_producto] = ($stockNeeded[$p->id_producto] ?? 0) + $qty;
                if ($qty <= 0 || ($p->stock !== null && (float) $p->stock + 0.00001 < $stockNeeded[$p->id_producto])) throw new \RuntimeException('Existencia insuficiente o cantidad inválida de ' . $p->nombre . '.');
                $i=['producto'=>$p,'cantidad'=>$qty,'precio'=>$price,'subtotal'=>$line];
            } unset($i);
            if ($total<=0) throw new \RuntimeException('Agrega artículos a la venta.');
            if ($metodo==='CREDITO') {
                if (!$cliente) throw new \RuntimeException('Selecciona el cliente que llevará la compra a cuenta.');
                $c=$db->query('SELECT * FROM clientes WHERE id_cliente=? FOR UPDATE',[$cliente])->getRow();
                if (!$c || $c->estatus!=='ACTIVO') throw new \RuntimeException('Cliente no disponible.');
                $saldo=$db->query("SELECT COALESCE(SUM(saldo_pendiente),0) n FROM ventas WHERE id_cliente=? AND estatus IN ('PENDIENTE','PARCIAL','VENCIDA')",[$cliente])->getRow()->n;
                if ((float)$saldo+$total>(float)$c->limite_credito) throw new \RuntimeException('La compra supera el crédito disponible.');
                $db->query("INSERT INTO ventas(id_cliente,id_usuario,fecha_limite_pago,subtotal,total,saldo_pendiente,estatus,metodo_confirmacion) VALUES(?,?,CURRENT_DATE + CAST(? AS INTEGER),?,?,?,'PENDIENTE','NINGUNO')",[$cliente,$usuario,$c->plazo_dias,$total,$total,$total]);
                $ventaId=(int)$db->insertID();
                foreach($items as $i) { $p=$i['producto']; $db->query('INSERT INTO detalle_venta(id_venta,id_producto,cantidad,precio_unitario,subtotal) VALUES(?,?,?,?,?)',[$ventaId,$p->id_producto,$i['cantidad'],$i['precio'],$i['subtotal']]); if($p->stock!==null)$db->query('UPDATE productos SET stock=stock-? WHERE id_producto=?',[$i['cantidad'],$p->id_producto]); }
                $db->query('INSERT INTO pos_ventas(id_usuario,id_cliente,total,subtotal_abarrotes,subtotal_verduleria,metodo_pago,efectivo_recibido,cambio) VALUES(?,?,?,?,?,?,0,0)',[$usuario,$cliente,$total,$sub['ABARROTES'],$sub['VERDULERIA'],'CREDITO']);$ticketId=(int)$db->insertID();
                foreach($items as $i){$p=$i['producto'];$db->query('INSERT INTO pos_detalle(id_venta,id_producto,cantidad,precio_unitario,subtotal,departamento) VALUES(?,?,?,?,?,?)',[$ticketId,$p->id_producto,$i['cantidad'],$i['precio'],$i['subtotal'],$p->departamento ?: 'ABARROTES']);} $db->query('INSERT INTO pos_pagos(id_venta,metodo,monto) VALUES(?,?,?)',[$ticketId,'CREDITO',$total]);$ventaId=$ticketId;
            } else {
                $validos=['EFECTIVO','TRANSFERENCIA','TARJETA']; if($metodo!=='MIXTO' && !in_array($metodo,$validos,true))throw new \RuntimeException('Forma de pago inválida.');
                $pagado = $metodo==='MIXTO' ? array_sum(array_map(fn($x)=>(float)($x['monto']??0),$pagos)) : (float)$recibido;
                if ($metodo==='MIXTO' && (abs($pagado-$total)>0.01 || empty($pagos))) throw new \RuntimeException('Los pagos combinados deben sumar exactamente el total.');
                if ($metodo!=='MIXTO' && $pagado+0.001<$total) throw new \RuntimeException('El pago recibido no cubre el total.');
                foreach($pagos as $pago)if(!in_array($pago['metodo']??'', $validos, true)||(float)($pago['monto']??0)<=0)throw new \RuntimeException('Desglose de pago inválido.');
                $change=max(0,$pagado-$total);
                $db->query('INSERT INTO pos_ventas(id_usuario,id_cliente,total,subtotal_abarrotes,subtotal_verduleria,metodo_pago,efectivo_recibido,cambio) VALUES(?,?,?,?,?,?,?,?)',[$usuario,$cliente,$total,$sub['ABARROTES'],$sub['VERDULERIA'],$metodo,$pagado,$change]);
                $ventaId=(int)$db->insertID();
                foreach($items as $i) {
                    $p=$i['producto'];
                    $db->query('INSERT INTO pos_detalle(id_venta,id_producto,cantidad,precio_unitario,subtotal,departamento) VALUES(?,?,?,?,?,?)',[$ventaId,$p->id_producto,$i['cantidad'],$i['precio'],$i['subtotal'],$p->departamento ?: 'ABARROTES']);
                    if($p->stock!==null) $db->query('UPDATE productos SET stock=stock-? WHERE id_producto=?',[$i['cantidad'],$p->id_producto]);
                }
                $metodos=$metodo==='MIXTO'?$pagos:[['metodo'=>$metodo,'monto'=>$total]];
                foreach($metodos as $pago) $db->query('INSERT INTO pos_pagos(id_venta,metodo,monto) VALUES(?,?,?)',[$ventaId,$pago['metodo'],$pago['monto']]);
            }
            if($db->transStatus()===false) throw new \RuntimeException('No se pudo guardar la venta.');
            $db->transCommit(); return [true,'Venta registrada. Ticket #'.$ventaId,$ventaId];
        } catch(\Throwable $e) { $db->transRollback(); return [false,$e->getMessage(),null]; }
    }
}
