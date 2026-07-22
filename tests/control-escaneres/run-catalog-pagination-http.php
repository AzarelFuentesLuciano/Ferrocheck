<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$base = 'http://localhost/Ferrocheck/public/index.php?modulo=control-escaneres&seccion=catalogo';

function paginationGet(string $url): string
{
    $context = stream_context_create(['http' => ['timeout' => 5, 'ignore_errors' => true]]);
    $html = file_get_contents($url, false, $context);
    if ($html === false) throw new RuntimeException('GET local falló.');
    return $html;
}

function paginationHref(string $html, string $rel): string
{
    $quotedRel = preg_quote($rel, '/');
    $pattern = '/<a[^>]*rel="' . $quotedRel . '"[^>]*href="([^"]+)"|<a[^>]*href="([^"]+)"[^>]*rel="' . $quotedRel . '"/';
    if (!preg_match($pattern, $html, $matches)) throw new RuntimeException('Enlace ' . $rel . ' ausente.');
    return html_entity_decode($matches[1] !== '' ? $matches[1] : $matches[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function paginationAbsoluteUrl(string $href): string
{
    return str_starts_with($href, 'http') ? $href : 'http://localhost' . $href;
}

$page1 = paginationGet($base . '&q=SC-900&por_pagina=1&orden=codigo&direccion=ASC&pagina=1');
$next = paginationHref($page1, 'next');
parse_str((string) parse_url($next, PHP_URL_QUERY), $nextParams);
test('página 1 genera siguiente sin amp literal', fn () => ok(!str_contains($next, 'amp;') && ($nextParams['pagina'] ?? '') === '2'));
test('siguiente conserva módulo, sección, orden y filtros', fn () => ok(
    ($nextParams['modulo'] ?? '') === 'control-escaneres'
    && ($nextParams['seccion'] ?? '') === 'catalogo'
    && ($nextParams['q'] ?? '') === 'SC-900'
    && ($nextParams['orden'] ?? '') === 'codigo'
    && ($nextParams['direccion'] ?? '') === 'ASC'
    && ($nextParams['por_pagina'] ?? '') === '1'
));

$page2 = paginationGet(paginationAbsoluteUrl($next));
$previous = paginationHref($page2, 'prev');
parse_str((string) parse_url($previous, PHP_URL_QUERY), $previousParams);
test('página 2 permanece en Catálogo', fn () => ok(str_contains($page2, 'SC-9002') && str_contains($page2, 'Página 2 de 2') && !str_contains($page2, '&amp;amp;')));
test('anterior regresa a página 1', fn () => ok(
    ($previousParams['seccion'] ?? '') === 'catalogo'
    && ($previousParams['pagina'] ?? '') === '1'
    && str_contains(paginationGet(paginationAbsoluteUrl($previous)), 'SC-9001')
));

$descending = paginationGet($base . '&q=SC-900&por_pagina=1&orden=codigo&direccion=DESC&pagina=1');
test('orden DESC se interpreta y conserva', fn () => ok(
    str_contains($descending, 'SC-9002')
    && str_contains(html_entity_decode($descending, ENT_QUOTES | ENT_HTML5, 'UTF-8'), 'direccion=DESC')
));
test('paginación numérica identifica la página actual', fn () => ok(str_contains($page2, 'aria-current="page"') && str_contains($page2, 'aria-label="Página 2"')));

finish('Catalog Pagination HTTP');
