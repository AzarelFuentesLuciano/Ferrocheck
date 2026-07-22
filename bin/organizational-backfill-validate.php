<?php
declare(strict_types=1);
require dirname(__DIR__).'/config/config.php';require dirname(__DIR__).'/vendor/autoload.php';
$report=(new App\Services\OrganizationalBackfillValidator(App\Core\Database::getConnection()))->report();
echo json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
