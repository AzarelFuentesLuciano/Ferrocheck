<?php
declare(strict_types=1); namespace App\Repositories\ControlEscaneres;
final class TransactionManager {public function __construct(private \PDO $pdo){}public function begin():void{if(!$this->pdo->inTransaction())$this->pdo->beginTransaction();}public function commit():void{if($this->pdo->inTransaction())$this->pdo->commit();}public function rollback():void{if($this->pdo->inTransaction())$this->pdo->rollBack();}public function transactional(callable $fn):mixed{$this->begin();try{$v=$fn();$this->commit();return $v;}catch(\Throwable $e){$this->rollback();throw $e;}}}
