<?php

namespace App\Commands;

use App\Models\MCobranza;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Procesa la cola de mensajes de cobranza pendientes de envío.
 *
 * IMPORTANTE: no envía mensajes reales todavía. Aquí es donde debes conectar
 * tu proveedor de WhatsApp Business API (Meta Cloud API, Twilio, etc.) usando
 * las credenciales que contrates.
 *
 * Cron recomendado (cada 15-30 min):
 *   15 * * * * cd /ruta/del/proyecto && php spark cobranza:procesar
 */
class ProcesarMensajesCobranza extends BaseCommand
{
    protected $group       = 'Crédito Fácil';
    protected $name        = 'cobranza:procesar';
    protected $description = 'Procesa la cola de recordatorios y mensajes de cobranza pendientes.';

    public function run(array $params)
    {
        $modelo = new MCobranza();
        $pendientes = $modelo->getPendientesParaEnviar();

        if (empty($pendientes)) {
            CLI::write('No hay mensajes de cobranza pendientes.', 'green');
            return;
        }

        foreach ($pendientes as $mensaje) {
            $texto = $mensaje->texto_final ?: ($mensaje->plantilla_cuerpo ?? '');
            $texto = str_replace('{nombre}', $mensaje->nombre_completo, $texto);

            // TODO: conectar aquí tu proveedor real, por ejemplo:
            //   $whatsapp->enviar($mensaje->telefono, $texto);
            CLI::write("Enviar a {$mensaje->telefono} ({$mensaje->tipo}): {$texto}", 'yellow');

            $modelo->marcarEnviado($mensaje->id_mensaje);
        }

        CLI::write(count($pendientes) . ' mensaje(s) procesado(s).', 'green');
    }
}
