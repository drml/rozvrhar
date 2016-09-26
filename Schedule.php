<?php

/**
 * Třída určená pro stahování a přeformátování iCAL rozvrhů ze systému IS STAG.
 *
 * @author Ondřej Doktor <doktor.ml@gmail.com>
 * @link https://github.com/drml/rozvrhar
 * @license MIT
 */
class Schedule {
	

	private $stagHostname;
	private $predmetInfoBuffer = array();
	private $format;
	
	/**
	 * V konstruktoru předejte hostname serveru, na které je dostupný IS STAG.
	 * 
	 * @param string $stagHostname 
	 */
	public function __construct($stagHostname)
	{
		$this->stagHostname = $stagHostname;
	}
	
	/**
	 * 
	 * Nastaví výstupní formát popisu rozvrhových událostí.
	 * Jako argument bere řetězec označující formát:
	 * 
	 * 	A:	ABC/123 - Předmět - Přednáška
	 *	B:	ABC/123 - Předmět
	 *	C:	Předmět - Přednáška
	 *	D:	Předmět
	 * 
	 * Pokud je zadán jiný řetězec, příp. je nastaveno NULL, ponechá se pole SUMMARY tak jak je.
	 * 
	 * @param string|NULL $format
	 */
	public function setFormat($format)
	{
		$this->format = $format;
	}

	/**
	 * Interní metoda pro získávání rozvrhu osoby ve formáti iCAL ze serveru.
	 * Název metody i interface odpovídá API IS STAG.
	 * 
	 * @param string $osoba Osobní číslo
	 * @param string $rok
	 * @param string $semestr Řetězec 'LS' nebo 'ZS'
	 * @return string|boolean Rozvrh ve formátu iCAL nebo FALSE
	 */
	private function getRozvrhByStudentICAL($osoba, $rok, $semestr){
		return file_get_contents("https://$this->stagHostname/ws/services/rest/rozvrhy/getRozvrhByStudentICAL?semestr=$semestr&osCislo=$osoba&rok=$rok");
	}
	
	/**
	 * Interní metoda pro získávání informací o předmětu ze serveru.
	 * Název metody i interface odpovídá API IS STAG.
	 * Pro okapované dotazy využívá metoda vlastní cache.
	 * 
	 * @param string $katedra
	 * @param string $zkratka
	 * @return stdObj|boolean Objekt s informacemi o předmětu nebo FALSE
	 */
	private function getPredmetInfo($katedra, $zkratka){
		
		if (!isset($this->predmetInfoBuffer[$katedra.$zkratka])){
			
			$json = file_get_contents("https://$this->stagHostname/ws/services/rest/predmety/getPredmetInfo?zkratka=$zkratka&outputFormat=json&katedra=$katedra");

			if($json){
				$this->predmetInfoBuffer[$katedra.$zkratka] = json_decode(str_replace(array('[', ']'), '', $json));
			} else {
				$this->predmetInfoBuffer[$katedra.$zkratka] = false;
			}
		}
		
		return $this->predmetInfoBuffer[$katedra.$zkratka];
	}
	
	/**
	 * Pomocná metoda pro formátování iCAL pole SUMMARY.
	 * Metoda má rozhraní připravené pro preg_replace_callback.
	 * 
	 * @param array $matches
	 * @return string
	 */
	private function formatSummary($matches)
	{
		
		$predmet = $this->getPredmetInfo($matches[1], $matches[2]);
		if ($predmet === false || $this->format == null){
			return $matches[0];
		}
		
		switch ($this->format){
			
			case 'A':
				return 'SUMMARY:'.$matches[1] . "/" . $matches[2] . " - " . $predmet->nazev . $matches[3];
				break;
			
			case 'B':
				return 'SUMMARY:'.$matches[1] . "/" . $matches[2] . " - " . $predmet->nazev;
				break;
			
			case 'C':
				return 'SUMMARY:'.$predmet->nazev . $matches[3];
				break;
			
			case 'D':
				return 'SUMMARY:'.$predmet->nazev;
				break;
				
			default:
				return $matches[0];
			
		}
		
	}
	
	/**
	 * Metoda vrací rozvrh osoby ve fromátu iCAL, přeformátovaný podle nastaveného formátu.
	 * 
	 * @param string $osoba Osobní číslo
	 * @param string $rok
	 * @param string $semestr Řetězec 'LS' nebo 'ZS'
	 * @return string Rozvrh ve formátu iCAL
	 */
	public function formatRozvrhByStudentICAL($osoba, $rok, $semestr){
		$ical = $this->getRozvrhByStudentICAL($osoba, $rok, $semestr);
		return preg_replace_callback('/^SUMMARY:(\w+)\/(\w+)(\s.*)$/m', array($this, 'formatSummary'), $ical);
	
	}
	
}