<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Cookie extends BaseConfig
{
    public string $prefix   = '';
    public string $expires  = '0';
    public string $path     = '/';
    public string $domain   = '';
    public bool $secure     = false;
    public bool $httponly   = true;
    public string $samesite = 'Lax';
    public bool $raw        = false;

    public function __construct()
    {
        parent::__construct();

        $render = filter_var(getenv('RENDER') ?: ($_ENV['RENDER'] ?? 'false'), FILTER_VALIDATE_BOOLEAN);
        $configuredSecure = getenv('COOKIE_SECURE') ?: ($_ENV['COOKIE_SECURE'] ?? ($render ? 'true' : 'false'));
        $secure = filter_var($configuredSecure, FILTER_VALIDATE_BOOLEAN);
        $this->secure = $secure;
    }
}
