<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Exceptions extends BaseConfig
{
    /**
     * Si se deben loguear las excepciones capturadas.
     */
    public bool $log = true;

    /**
     * Códigos de estatus HTTP que NO se deben loguear.
     *
     * @var list<int>
     */
    public array $ignoreCodes = [];

    /**
     * Carpeta donde CodeIgniter busca las plantillas de vista de error.
     */
    public string $errorViewPath = APPPATH . 'Views/errors';

    /**
     * Claves de datos sensibles a ocultar en el trace de la excepción
     * cuando se muestra en pantalla (modo desarrollo).
     *
     * @var array<string, list<string>>
     */
    public array $sensitiveDataInTrace = [];

    /**
     * Si se deben loguear los avisos de deprecated de PHP.
     */
    public bool $logDeprecations = true;

    /**
     * Nivel de log usado para los avisos de deprecated.
     */
    public string $deprecationLogLevel = 'warning';
}
