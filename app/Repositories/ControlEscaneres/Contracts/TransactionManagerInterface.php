<?php
declare(strict_types=1);namespace App\Repositories\ControlEscaneres\Contracts;interface TransactionManagerInterface{public function begin():void;public function commit():void;public function rollback():void;public function transactional(callable$operation):mixed;}
