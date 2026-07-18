<?php
use App\ViewModels\ControlEscaneres\ScannerCatalogViewModel;
$vistaActual='catalogo';
$catalogViewModel=$catalogViewModel??new ScannerCatalogViewModel([],0,1,25,1,[]);
$h=static fn(mixed$v):string=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');
$url=static fn(string$section,int$id):string=>BASE_URL.'/index.php?modulo=control-escaneres&amp;seccion='.$section.'&amp;scanner_id='.$id;
ob_start();
?>
<?php foreach($catalogViewModel->flashMessages as$message): ?><div class="ce-card" role="status"><?= $h($message['message']??'') ?></div><?php endforeach; ?>
<div class="ce-page-head"><div><span class="ce-eyebrow">Catálogo maestro</span><h2>Escáneres registrados</h2><p>Datos operativos reales del catálogo canónico.</p></div><span class="ce-badge ce-badge--blue"><?= $catalogViewModel->total ?> equipos</span></div>
<article class="ce-card">
<form class="ce-toolbar" method="get"><input type="hidden" name="modulo" value="control-escaneres"><input type="hidden" name="seccion" value="catalogo"><input class="ce-input" name="q" maxlength="100" value="<?= $h($catalogViewModel->filters['search']??'') ?>" placeholder="Código, serie, marca o modelo"><select class="ce-select" name="estado"><option value="">Todos los estados</option><?php foreach(\App\Domain\ControlEscaneres\ScannerStatus::VALUES as$status): ?><option value="<?= $h($status) ?>" <?=($catalogViewModel->filters['status']??null)===$status?'selected':''?>><?= $h(ucwords(str_replace('_',' ',$status))) ?></option><?php endforeach; ?></select><button class="ce-btn ce-btn--primary">Filtrar</button></form>
<div style="overflow-x:auto"><table class="ce-table"><thead><tr><th>ID / código</th><th>Equipo</th><th>Serie</th><th>IMEI / teléfono</th><th>Estado</th><th>Última entrega</th><th>Incidencia</th><th>Acciones</th></tr></thead><tbody>
<?php if(!$catalogViewModel->items): ?><tr><td colspan="8">No se encontraron escáneres con los filtros seleccionados.</td></tr><?php endif; ?>
<?php foreach($catalogViewModel->items as$item): ?><tr><td><strong>#<?= $item->id ?> · <?= $h($item->code) ?></strong><br><small><?= $item->active?'Activo':'Inactivo' ?></small></td><td><?= $h($item->brand) ?><br><small><?= $h($item->model) ?></small></td><td><?= $h($item->serial) ?></td><td><?= $h($item->maskedImei) ?><br><small><?= $h($item->maskedPhone) ?></small></td><td><span class="ce-badge <?= $item->status==='entregado'?'ce-badge--blue':($item->status==='disponible'?'':'ce-badge--amber') ?>"><?= $h(ucwords(str_replace('_',' ',$item->status))) ?></span></td><td><?= $h($item->lastDelivery??'—') ?></td><td><?= $item->hasOpenIncident?'<span class="ce-badge ce-badge--red">Abierta</span>':'—' ?></td><td><?php foreach($item->actions as$action): ?><a class="ce-btn" href="<?= $url($action,$item->id) ?>"><?= $h(ucfirst($action)) ?></a><?php endforeach; ?></td></tr><?php endforeach; ?>
</tbody></table></div>
<div class="ce-actions" style="margin-top:14px"><span>Página <?= $catalogViewModel->page ?> de <?= $catalogViewModel->totalPages ?></span><?php if($catalogViewModel->page>1): ?><a class="ce-btn" href="<?= BASE_URL ?>/index.php?modulo=control-escaneres&amp;seccion=catalogo&amp;pagina=<?= $catalogViewModel->page-1 ?>">Anterior</a><?php endif; ?><?php if($catalogViewModel->page<$catalogViewModel->totalPages): ?><a class="ce-btn" href="<?= BASE_URL ?>/index.php?modulo=control-escaneres&amp;seccion=catalogo&amp;pagina=<?= $catalogViewModel->page+1 ?>">Siguiente</a><?php endif; ?></div>
</article>
<?php $contenidoModulo=ob_get_clean();require __DIR__.'/plantilla.php'; ?>
