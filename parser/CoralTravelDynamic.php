<?php
namespace parser;
// define('ENVIRONMENT', 'development');
// define('_R', __DIR__ . DIRECTORY_SEPARATOR);
// require_once "lib/em.php";

class CoralTravelDynamic {

	const MAX_CHILD_PROCESSES = 30;
	const ID_CORAL_TRAVEL = 1;

	private $soapClientPackage; 	// soap client tours get
	private $operator; 				// CoralTravel
	
	private $fromAreaID;				// fromAreaId == departureCityId
	private $departureCity;			// city of tour begin

	private $ctCurrencyId;			// 
	private $currency;				// 


	public function __construct() {
		$this->soapClientPackage = new \SoapClient('http://service.coraltravel.ua/package.asmx?WSDL');
	}

	function parseXml() {

		foreach ( scandir("coraltravel_xml") as $fileXml ) {
			$mem_usage = memory_get_usage(true);
			if ( $fileXml!="." && $fileXml!=".." && preg_match("/.xml$/", $fileXml) ) {
				$xml = new \XMLReader;
				$res = $xml->open(_R . "coraltravel_xml". DIRECTORY_SEPARATOR . $fileXml);
				if ($res === TRUE) {

					while ($xml->read()) {
						if ($xml->nodeType == $xml::ELEMENT) {
							if ($xml->name == 'PackagePrices') {
								$this->fromAreaID = $xml->getAttribute('fromAreaID');
								$this->ctCurrencyId = $xml->getAttribute('currency');
							}
							
							if ($xml->name == 'hn') {
								$hotelID = $xml->getAttribute('h');
								$hotelArr = array();
								$itemID = 0;
							}

							if ($xml->name == 'rn')
								$roomID = $xml->getAttribute('r');

							if ($xml->name == 'mn')
								$mealID = $xml->getAttribute('m');

							if ($xml->name == 'a') {
								$adl = $xml->getAttribute('ad');
								$chd = $xml->getAttribute('cd');
								$fcMax = $xml->getAttribute('fmx');
								$scMax = $xml->getAttribute('smx');
								$tcMax = $xml->getAttribute('tmx');
								$requestString = '';
							}

							if ($xml->name == 'p') {
								$itemID++;
								$tourBeginDate = $xml->getAttribute('tb');
								$night = $xml->getAttribute('n');
								$depFlightID = $xml->getAttribute('d');
								$returnFlightID = $xml->getAttribute('r');
								
								$agString = implode('-', array($adl, $chd, $fcMax, $scMax, $tcMax));
								$flightStr = implode('-', array($depFlightID, $returnFlightID, $night, $itemID));
								$hotelArr[$hotelID][$roomID][$mealID][$agString][$flightStr] = $tourBeginDate;
							}

						} elseif ($xml->nodeType == $xml::END_ELEMENT && $xml->name == 'hn') {

							$created = FALSE;

							while (!$created) {

								// проверяем, умер ли дочерний процесс
								while ($signaled_pid = pcntl_waitpid(-1, $status, WNOHANG)) {
									if ($signaled_pid == -1) {
										//детей не осталось
										$childProcesses = array();
										break;
									} else {
										unset($childProcesses[$signaled_pid]);
									}
								}
									
								if (count($childProcesses) < self::MAX_CHILD_PROCESSES) {
									
									$this->checkpoint();

									$pid = pcntl_fork();
									if ($pid == -1) {
									  //ошибка
										echo "error when fork" . PHP_EOL;
									} elseif ($pid) {
										// родительский процесс
										// контроль количества дочерних процессов
										$childProcesses[$pid] = TRUE;
										$created = TRUE;
										echo "c";
									} else {
									  // дочерний процесс
									  // выполнить нагрузку и закрыться нахуй
										$hotelArr = $this->processHotelData($hotelArr);
										$this->dataProvider = new CoralTravelDataProvider;
										$bundles = $this->packBundles($hotelArr);
										$inserts = $this->dataProvider->doInserts($bundles);
										$this->myLog(__LINE__, "inserts: $inserts");
										exit;
									}
									// сюда попадут оба процесса
								} else {
									sleep(1);
									echo "-";
								}
							}
							
						}
					}
					$xml->close();
				}
			}
		}
	}

	private function packBundles($hotelArr) {

		$this->currency = $this->dataProvider->provideCurrency($this->ctCurrencyId);
		$this->departureCity = $this->dataProvider->provideDepartureCity($this->fromAreaID);
		$operator = $this->dataProvider->em->getReference('models\Operator', self::ID_CORAL_TRAVEL);
		$hotelID = key($hotelArr);
		$hotel = $this->dataProvider->provideHotel($hotelID);
		$bundles = array();

		foreach ($hotelArr as $ctHotelId => $roomArr)
		{ // hotel iter
			// $hotel = $this->dataProvider->provideHotel($ctHotelId);
			
			foreach ($roomArr as $ctRoomId => $mealArr)
			{ // room iter
				$room = $this->dataProvider->provideRoom($ctRoomId);

				foreach ($mealArr as $ctMealId => $agArr)
				{ // meal iter
					$meal = $this->dataProvider->provideMeal($ctMealId);

					foreach ($agArr as $agString => $items)
					{ // age group iter
						list($adl, $chd, $fcMax, $scMax, $tcMax) = explode('-', $agString);
						$ctAgeGroup = $this->dataProvider->getCtAgeGroupFromCache(array('ad' => $adl,'cd' => $chd,'fmn' => FALSE,'fmx' => $fcMax,'smn' => FALSE,'smx' => $scMax,'tmn' => FALSE,'tmx' => $tcMax));
						
						foreach ($items as $itemID => $flightArr)
						{ // items
								
							if (!empty($flightArr))
							{
								foreach ($flightArr as $flightStr => $tourBeginDate)
								{
									list($depFlightID, $returnFlightID, $night) = explode('-', $flightStr);
									$accomodation = $this->dataProvider->provideAccomodation(array('operator' => $this->operator, 'currency' => $this->currency, 'departureCity' => $this->departureCity, 'hotel' => $hotel, 'room' => $room, 'meal' => $meal));
									$tourBeginDate = new \DateTime("$tourBeginDate");
									$ctFlight = $this->dataProvider->provideCtFlight($tourBeginDate, $night, $depFlightID, $returnFlightID);
									$bundles[$room->getId() . '-' . $meal->getId()][$price]['ctFlight'][spl_object_hash($ctFlight)] = $ctFlight;
									$bundles[$room->getId() . '-' . $meal->getId()][$price]['accomodation'] = $accomodation;
									$bundles[$room->getId() . '-' . $meal->getId()][$price]['ctAgeGroups'][spl_object_hash($ctAgeGroup)] = $ctAgeGroup;
								}
							}

						} // items
					} // age group iter
				} // meal iter
			} // room iter
		} // hotel iter

		return $bundles;
	}

	public function processHotelData($hotelArr) {

		$firstLoop = TRUE;
		$cur = $this->ctCurrencyId;

		foreach ($hotelArr as $hotelID => $roomArr)
		{ // hotel iter
			foreach ($roomArr as $roomID => $mealArr)
			{ // room iter
				foreach ($mealArr as $mealID => $agArr)
				{ // meal iter
						
					foreach ($agArr as $agString => $flightArr)
					{ // age group iter
						list($adl, $chd, $fcMax, $scMax, $tcMax) = explode('-', $agString);
						$requestString = '';

						// prepare string for SOAP request
						foreach ($flightArr as $flightStr => $tourBeginDate)
						{ // items
							list($depFlightID, $returnFlightID, $night, $itemID) = explode('-', $flightStr);
							$requestString .= "<Item itemID=\"$itemID\" cur=\"$cur\" tourBeginDate=\"$tourBeginDate\" night=\"$night\" depFlightID=\"$depFlightID\" returnFlightID=\"$returnFlightID\" hotelID=\"$hotelID\" roomID=\"$roomID\" mealID=\"$mealID\" adl=\"$adl\" chd=\"$chd\" fcMax=\"$fcMax\" scMax=\"$scMax\" tcMax=\"$tcMax\" />";
						} // items

						// get active packages from CoralTravel api
						$packagesIds = $this->getPackagesFromSOAP($requestString);
						$this->checkpoint();

						if ($packagesIds) {

							// remove inactive offers
							foreach ($flightArr as $flightStr => $tourBeginDate)
							{ // items
								list($depFlightID, $returnFlightID, $night, $itemID) = explode('-', $flightStr);
								if (!array_search($itemID, $packagesIds)) {
									unset($hotelArr[$hotelID][$roomID][$mealID][$agString][$flightStr]);
								}
							} // items

							$firstLoop = FALSE;
						} elseif($firstLoop) { // if hotel has no tours in the first loop, there are no tours in the next loop too
							exit;
						}

					} // age group iter
				} // meal iter
			} // room iter
		} // hotel iter
		return $hotelArr;
	}
	
	private function getPackagesFromSOAP($xml) {

		$xml = '<Query author="Coral Travel Ukraine" version="1.0.0.0">' . $xml . '</Query>';

		$xmlString = '';
		do {
			try {
				$xmlString = $this->soapClientPackage->PackagePriceCheckingUkraine( array('xml' => $xml) );
			} catch (\SoapFault $e) {
				echo "\r\nsoap error: $e->faultcode\r\n";
				$this->myLog(__LINE__, "soap error: $e->faultcode");
			}
		} while ($xmlString == '');

		$xmlString = $xmlString->PackagePriceCheckingUkraineResult->any;

		$scope = $this;
		$startElement = function($parser, $currentNodeName, $currentAttrs) use ($scope, $xmlString, $xml) {
			if ($currentNodeName == 'ITEM') {
				if (isset($currentAttrs['SALESTATUS']) && isset($currentAttrs['TOTALPRICE'])) {
					if ($currentAttrs['SALESTATUS'] == 1) {
						$scope->itemIDs[$currentAttrs['ITEMID']] = $currentAttrs['TOTALPRICE'];
					}
				}
			}
		};

		$xml_parser = xml_parser_create();
		xml_set_element_handler($xml_parser, $startElement, null);
		xml_parse($xml_parser, $xmlString, FALSE);
		xml_parser_free($xml_parser);	

		// return FALSE, if $itemIDs is empty
		if (isset($this->itemIDs)) {
			$itemIDs = $this->itemIDs;
			unset($this->itemIDs);
			return $itemIDs;
		}
	}

	function saveXml($xml, $hotelID) {
		$file = fopen('logs'.DIRECTORY_SEPARATOR.'xmlResult' . $hotelID . '.txt', 'a+');
		fwrite($file, $xml);
		fclose($file);
	}

	function _http( $url, $data = FALSE, $type = 'POST', $header = FALSE ) {
		 $ch = curl_init();

		 if( isset( $data ) && $type == 'GET' ) {
			  $pairs = array();
			  foreach ($data as $key => $value) {
					if ( $value )
						 $pairs[] = $key . "=" . $value;
			  }
			  $data = "?" . implode('&', $pairs);
			  $url .= $data; 
		 }
		 curl_setopt( $ch, CURLOPT_URL, $url );
		 curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 10 );
		 curl_setopt( $ch, CURLOPT_HEADER, 0);
		 curl_setopt( $ch, CURLOPT_TIMEOUT, 10 );
		 curl_setopt( $ch, CURLOPT_RETURNTRANSFER, TRUE );

		 if ( $header )
			  curl_setopt( $ch, CURLOPT_HTTPHEADER, $header);

		 if( $data && $type == 'POST' ) {
			  curl_setopt( $ch, CURLOPT_POST, TRUE );
			  curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );
		 }
		 $response = curl_exec( $ch );
		 curl_close( $ch );
		 return $response;
	}

	// function unsetNotActiveFlights() {
	// 	$soap = new \SoapClient('http://service.coraltravel.ua/Transport.asmx?WSDL');
		
	// 	$scope = $this;
	// 	$startElement = function($parser, $currentNodeName, $currentAttrs) use ($scope) {
	// 		if ($currentNodeName == 'RESULT') {
	// 			if (isset($currentAttrs['DEPARTUREALLOTMENTSTATUSID'])) {
	// 				if ($currentAttrs['DEPARTUREALLOTMENTSTATUSID'] == 0) {
	// 					unset($scope->flights[$currentAttrs['DEPARTUREFLIGHTID']]);
	// 				}
	// 			}
	// 		}
	// 	};

	// 	foreach ($this->flights as $depFlightID => $returnFlightID) {
	// 		$xml_parser = xml_parser_create();
	// 		xml_set_element_handler($xml_parser, $startElement, null);
	// 		$xmlString = @$soap->FlightStatusCheck( array('arrivalFlightID' => $returnFlightID, 'departureFlightID' => $depFlightID ) )->FlightStatusCheckResult->any;
	// 		xml_parse($xml_parser, $xmlString, FALSE);
	// 		xml_parser_free($xml_parser);
	// 	}
	// }
	function checkpoint($line = 0, $message = 'checkpoint stop') {
		$f = parse_ini_file ('options'.DIRECTORY_SEPARATOR.'checkpoint.ini');
		if ( $f['die'] == 1 ) {
			$this->myLog($line, $message, true);
		}
	}

	function myLog($line = 0, $message = 'defalt message', $terminate=FALSE) {
	    $file = fopen('logs'.DIRECTORY_SEPARATOR.'log.txt', 'a+');
	    $dtime = new \DateTime();
	    $dtime = $dtime->format("Y-m-d H:i:s");
	    $line = sprintf("%1$05d", $line);
	    fwrite($file, "$dtime | line: $line | message: $message\r\n");
	    fclose($file);
	    if ($terminate) {
	        exit("exit on line $line");
	    }
	}
}