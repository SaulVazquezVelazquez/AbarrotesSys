<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class App extends BaseConfig
{
    public string $baseURL = '';

    /** @var list<string> */
    public array $allowedHostnames = [];

    public string $indexPage = '';
    public string $uriProtocol = 'REQUEST_URI';
    public string $permittedURIChars = 'a-z 0-9~%.:_\-';

    public string $defaultLocale = 'es';
    public bool $negotiateLocale = false;
    /** @var list<string> */
    public array $supportedLocales = ['es'];

    public string $appTimezone = 'America/Mexico_City';
    public string $charset = 'UTF-8';
    public bool $forceGlobalSecureRequests = false;

    /** @var array<string,string> */
    public array $proxyIPs = [];

    public bool $CSPEnabled = false;

    public function __construct()
    {
        parent::__construct();

        $baseUrlEnv    = getenv('APP_BASEURL') ?: ($_ENV['APP_BASEURL'] ?? null);
        $renderUrl     = getenv('RENDER_EXTERNAL_URL') ?: ($_ENV['RENDER_EXTERNAL_URL'] ?? null);
        $baseUrl       = $baseUrlEnv ?: ($renderUrl ?: 'http://localhost:8888/tienda_barrio/');
        $this->baseURL = rtrim($baseUrl, '/') . '/';

        // Render termina TLS en su proxy y reenvía la petición al contenedor
        // por loopback. Confiamos solo en ese salto local para que CI pueda
        // reconocer X-Forwarded-Proto: https y enviar cookies seguras.
        $render = filter_var(getenv('RENDER') ?: ($_ENV['RENDER'] ?? 'false'), FILTER_VALIDATE_BOOLEAN);
        if ($render) {
            $this->proxyIPs = [
                '::1'                  => 'HTTP_X_FORWARDED_FOR',
                '127.0.0.1'            => 'HTTP_X_FORWARDED_FOR',
                '::ffff:127.0.0.1'    => 'HTTP_X_FORWARDED_FOR',
            ];
        }
    }
}
