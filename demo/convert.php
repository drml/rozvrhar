<?php

/**
 * Demoukázka použití třídy Schedule.
 *
 * @author Ondřej Doktor <doktor.ml@gmail.com>
 * @link https://github.com/drml/rozvrhar
 * @license MIT
 */


require dirname(__FILE__) . '/../Schedule.php';

// INPUT A SANITACE
$osoba		= strtoupper($_GET['osoba']);
$rok		= (int) $_GET['rok'];
$semestr	= $_GET['semestr'];
$format		= $_GET['format'];

$valid = true;

if (!preg_match('/^\w{3,10}$/', $osoba)) {
	echo "Chyba: Číslo osoby neodpovídá formátu /^\w{3,10}$/";
	$valid = false;
}

if($rok > (int) date("Y") + 1 || $rok < 2000){
	echo "Chyba: Rok je mimo rozsah (2000 - ".((int) date("Y") + 1).")";
	$valid = false;
}

if(!in_array($semestr,array("LS","ZS"))) {
	echo "Chyba: Označení semestru mimo rozsah (LS,ZS)";
	$valid = false;
}

if(!in_array($format,array('A','B','C','D'))) {
	echo "Chyba: Formát mimo rozsah (A,B,C,D)";
	$valid = false;
}
	
if ($valid === false) exit();

// PŘÍPRAVA DAT
$schedule = new Schedule('wstag.jcu.cz');
$schedule->setFormat($format);


// ODESÍLÁNÍ DAT
header('Content-Type: text/calendar');
header("Content-disposition: attachment; filename=\"rozvrh - $osoba - $rok - $semestr - $format.ics\"");
echo $schedule->formatRozvrhByStudentICAL($osoba, $rok, $semestr);


