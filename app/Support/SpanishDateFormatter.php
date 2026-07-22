<?php
declare(strict_types=1);
namespace App\Support;
final class SpanishDateFormatter
{
    private const MONTHS=[1=>'ene',2=>'feb',3=>'mar',4=>'abr',5=>'may',6=>'jun',7=>'jul',8=>'ago',9=>'sep',10=>'oct',11=>'nov',12=>'dic'];
    public static function format(\DateTimeInterface|string|null$value,string$empty='—'):string
    {
        if($value===null||$value==='')return$empty;
        try{$date=$value instanceof \DateTimeInterface?\DateTimeImmutable::createFromInterface($value):new \DateTimeImmutable(trim($value));$date=$date->setTimezone(new \DateTimeZone(date_default_timezone_get()));}catch(\Throwable){return$empty;}
        $period=(int)$date->format('G')<12?'a. m.':'p. m.';
        return$date->format('j').' '.self::MONTHS[(int)$date->format('n')].' '.$date->format('Y').', '.(int)$date->format('g').':'.$date->format('i').' '.$period;
    }
}
