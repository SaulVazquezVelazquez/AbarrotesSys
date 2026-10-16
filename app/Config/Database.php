<?php

namespace Config;

use CodeIgniter\Database\Config;

class Database extends Config
{
    public string $filesPath = APPPATH . 'Database' . DIRECTORY_SEPARATOR;
    public string $defaultGroup = 'default';
    public array $default = [];

    public function __construct()
    {
        parent::__construct();

        // En producción la base elegida es Supabase; no caer silenciosamente en la URL
        // de PostgreSQL que Render inyecta como DATABASE_URL.
        $databaseUrl = getenv('SUPABASE_DATABASE_URL') ?: ($_ENV['SUPABASE_DATABASE_URL'] ?? null);
        if (ENVIRONMENT === 'production' && empty($databaseUrl)) {
            throw new \RuntimeException('Falta configurar SUPABASE_DATABASE_URL en el entorno de producción.');
        }
        $databaseUrl = $databaseUrl ?: (getenv('DATABASE_URL') ?: ($_ENV['DATABASE_URL'] ?? null));

        if (!empty($databaseUrl)) {
            $params = parse_url($databaseUrl);
            $query  = [];
            parse_str($params['query'] ?? '', $query);

            $this->default = [
                'DSN'          => '',
                'hostname'     => $params['host'] ?? '127.0.0.1',
                'username'     => rawurldecode($params['user'] ?? 'postgres'),
                'password'     => rawurldecode($params['pass'] ?? ''),
                'database'     => isset($params['path']) ? ltrim($params['path'], '/') : 'tienda_abarrotes',
                'DBDriver'     => 'Postgre',
                'DBPrefix'     => '',
                'pConnect'     => false,
                'DBDebug'      => (ENVIRONMENT !== 'production'),
                'charset'      => 'utf8',
                'DBCollat'     => 'en_US.utf8',
                'swapPre'      => '',
                'encrypt'      => false,
                'compress'     => false,
                'strictOn'     => false,
                'failover'     => [],
                'port'         => $params['port'] ?? 5432,
                'sslmode'      => $query['sslmode'] ?? 'prefer',
                'numberNative' => false,
            ];
        } else {
            // ---- Credenciales locales (MAMP) — ajusta a tu entorno ----
            $this->default = [
                'DSN'          => '',
                'hostname'     => getenv('DB_HOSTNAME') ?: '127.0.0.1',
                'username'     => getenv('DB_USERNAME') ?: 'postgres',
                'password'     => getenv('DB_PASSWORD') ?: 'root',
                'database'     => getenv('DB_DATABASE') ?: 'tienda_abarrotes',
                'DBDriver'     => 'Postgre',
                'DBPrefix'     => '',
                'pConnect'     => false,
                'DBDebug'      => true,
                'charset'      => 'utf8',
                'DBCollat'     => 'en_US.utf8',
                'swapPre'      => '',
                'encrypt'      => false,
                'compress'     => false,
                'strictOn'     => false,
                'failover'     => [],
                'port'         => (int) (getenv('DB_PORT') ?: 5432),
                'numberNative' => false,
            ];
        }

        if (ENVIRONMENT === 'testing') {
            $this->defaultGroup = 'tests';
        }
    }
}
