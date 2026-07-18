<?php
declare(strict_types=1); namespace App\Repositories\ControlEscaneres\Pdo;
use App\Exceptions\ControlEscaneres\PersistenceException;
abstract class AbstractPdoRepository {public function __construct(protected \PDO $pdo){$pdo->setAttribute(\PDO::ATTR_ERRMODE,\PDO::ERRMODE_EXCEPTION);}protected function stmt(string $sql,array $p=[]):\PDOStatement{try{$s=$this->pdo->prepare($sql);$s->execute($p);return $s;}catch(\PDOException $e){throw new PersistenceException('No fue posible completar la operación de persistencia.',0,$e);}}protected function forUpdate():string{return $this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME)==='sqlite'?'':' FOR UPDATE';}}
