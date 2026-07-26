<?php

declare(strict_types=1);

return [
    'dashboard' => [
        'key' => 'dashboard',
        'label' => 'Dashboard',
        'description' => 'Resumen general del módulo Rail.',
        'icon' => '▦',
        'default_subsection' => 'resumen',
        'subsections' => [
            'resumen' => ['key' => 'resumen', 'label' => 'Resumen'],
        ],
    ],
    'ferro' => [
        'key' => 'ferro',
        'label' => 'Ferro',
        'description' => 'Consulta y preparación de procesos ferroviarios.',
        'icon' => '▰',
        'default_subsection' => 'buscar',
        'subsections' => [
            'buscar' => ['key' => 'buscar', 'label' => 'Buscar'],
            'importar' => ['key' => 'importar', 'label' => 'Importar'],
            'configuracion' => ['key' => 'configuracion', 'label' => 'Configuración'],
            'incidencias' => ['key' => 'incidencias', 'label' => 'Incidencias'],
            'reportes' => ['key' => 'reportes', 'label' => 'Reportes'],
        ],
    ],
    'consist-rail' => [
        'key' => 'consist-rail',
        'label' => 'Consist Rail',
        'description' => 'Administración futura de composiciones ferroviarias.',
        'icon' => '≋',
        'default_subsection' => 'dashboard',
        'subsections' => [
            'dashboard' => ['key' => 'dashboard', 'label' => 'Dashboard'],
            'consultar' => ['key' => 'consultar', 'label' => 'Consultar'],
            'registrar' => ['key' => 'registrar', 'label' => 'Registrar'],
            'historial' => ['key' => 'historial', 'label' => 'Historial'],
            'reportes' => ['key' => 'reportes', 'label' => 'Reportes'],
        ],
    ],
    'facturacion' => [
        'key' => 'facturacion',
        'label' => 'Facturación',
        'description' => 'Preparación de procesos de facturación ferroviaria.',
        'icon' => '$',
        'default_subsection' => 'dashboard',
        'subsections' => [
            'dashboard' => ['key' => 'dashboard', 'label' => 'Dashboard'],
            'facturar' => ['key' => 'facturar', 'label' => 'Facturar'],
            'pendientes' => ['key' => 'pendientes', 'label' => 'Pendientes'],
            'historial' => ['key' => 'historial', 'label' => 'Historial'],
            'reportes' => ['key' => 'reportes', 'label' => 'Reportes'],
        ],
    ],
    'inventario' => [
        'key' => 'inventario',
        'label' => 'Inventario',
        'description' => 'Estructura inicial del inventario ferroviario.',
        'icon' => '▤',
        'default_subsection' => 'dashboard',
        'subsections' => [
            'dashboard' => ['key' => 'dashboard', 'label' => 'Dashboard'],
            'vias' => ['key' => 'vias', 'label' => 'Vías'],
            'plataformas' => ['key' => 'plataformas', 'label' => 'Plataformas'],
            'ubicaciones' => ['key' => 'ubicaciones', 'label' => 'Ubicaciones'],
            'reportes' => ['key' => 'reportes', 'label' => 'Reportes'],
        ],
    ],
    'evidencias' => [
        'key' => 'evidencias',
        'label' => 'Evidencias',
        'description' => 'Consulta y resguardo futuro de evidencias.',
        'icon' => '▣',
        'default_subsection' => 'consultar',
        'subsections' => [
            'consultar' => ['key' => 'consultar', 'label' => 'Consultar'],
            'capturar' => ['key' => 'capturar', 'label' => 'Capturar'],
            'pendientes' => ['key' => 'pendientes', 'label' => 'Pendientes'],
            'historial' => ['key' => 'historial', 'label' => 'Historial'],
        ],
    ],
    'configuracion' => [
        'key' => 'configuracion',
        'label' => 'Configuración',
        'description' => 'Parámetros internos futuros del módulo Rail.',
        'icon' => '⚙',
        'default_subsection' => 'general',
        'subsections' => [
            'general' => ['key' => 'general', 'label' => 'General'],
            'catalogos' => ['key' => 'catalogos', 'label' => 'Catálogos'],
            'parametros' => ['key' => 'parametros', 'label' => 'Parámetros'],
        ],
    ],
];
