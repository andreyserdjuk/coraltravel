<?php
// define('ENVIRONMENT', 'development');
// define('_R', __DIR__ . DIRECTORY_SEPARATOR);
// require_once "lib/em.php";

$parser = new CoralTravelDynamic;
$parser->parseXml();

class CoralTravelDynamic {

	public function __construct() {
		$this->dataProvider = new parser\CoralTravelDataProvider;
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
								$fromAreaID = $xml->getAttribute('fromAreaID');
								$ctCurrencyId = $xml->getAttribute('currency');
							}
							
							if ($xml->name == 'hn') {
								$hotelID = $xml->getAttribute('h');
								$hotelArr = array();
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
								$itemID = 0;
							}

							if ($xml->name == 'p') {
								$itemID++;
								$tourBeginDate = $xml->getAttribute('tb');
								$night = $xml->getAttribute('n');
								$depFlightID = $xml->getAttribute('d');
								$returnFlightID = $xml->getAttribute('r');
								
								$agString = implode('-', array($adl, $chd, $fcMax, $scMax, $tcMax));
								$flightStr = implode('-', array($depFlightID, $returnFlightID, $night));
								$hotelArr[$hotelID][$roomID][$mealID][$agString][$itemID][$flightStr] = $tourBeginDate;
							}

						} elseif ($xml->nodeType == $xml::END_ELEMENT && $xml->name == 'hn') {
							$this->processHotelData($hotelArr);
						}
					}
					$xml->close();
				}
			}
		}
	}

	private function savePackages($hotelArr, $packagesIds) {
		// $this->saveXml("$hotelID, $roomID, $mealID, $adl, $chd, $fcMax, $scMax, $tcMax, $depFlightID, $returnFlightID, $night, $price \r\n");

		foreach ($hotelArr as $ctHotelId => $roomArr)
		{ // hotel iter
			$hotel = $this->dataProvider->provideHotel($ctHotelId);

			foreach ($roomArr as $ctRoomId => $mealArr)
			{ // room iter
				$room = $this->dataProvider->provideRoom($ctRoomId);

				foreach ($mealArr as $ctMealId => $agArr)
				{ // meal iter
					$meal = $this->dataProvider->provideMeal($ctMealId);

					foreach ($agArr as $agString => $items)
					{ // age group iter
						list($adl, $chd, $fcMax, $scMax, $tcMax) = explode('-', $agString);
						
						foreach ($items as $itemID => $flightArr)
						{ // items
							
							if(in_array($itemID, $packagesIds)) {

								foreach ($flightArr as $flightStr => $tourBeginDate)
								{
									list($depFlightID, $returnFlightID, $night) = explode('-', $flightStr);

								}
							}

						} // items
					} // age group iter
				} // meal iter
			} // room iter
		} // hotel iter		
	}

	public function processHotelData($hotelArr) {

		$firstLoop = TRUE;
		$totalIds = array();
		$cur = 2;
		foreach ($hotelArr as $hotelID => $roomArr)
		{ // hotel iter
			foreach ($roomArr as $roomID => $mealArr)
			{ // room iter
				foreach ($mealArr as $mealID => $agArr)
				{ // meal iter
						echo 1;
					foreach ($agArr as $agString => $items)
					{ // age group iter
						list($adl, $chd, $fcMax, $scMax, $tcMax) = explode('-', $agString);
						foreach ($items as $itemID => $flightArr)
						{ // items
							
							$requestString = '';
							foreach ($flightArr as $flightStr => $tourBeginDate) {
								list($depFlightID, $returnFlightID, $night) = explode('-', $flightStr);
								$requestString .= "<Item itemID=\"$itemID\" cur=\"$cur\" tourBeginDate=\"$tourBeginDate\" night=\"$night\" depFlightID=\"$depFlightID\" returnFlightID=\"$returnFlightID\" hotelID=\"$hotelID\" roomID=\"$roomID\" mealID=\"$mealID\" adl=\"$adl\" chd=\"$chd\" fcMax=\"$fcMax\" scMax=\"$scMax\" tcMax=\"$tcMax\" />";
							}

							$packagesIds = $this->getPackagesFromXml($requestString);

							if ($packagesIds) {
								$totalIds = array_merge($totalIds, $packagesIds);
								$firstLoop = FALSE;
							} elseif($firstLoop) {
								return false;
							}
						} // items
					} // age group iter
				} // meal iter
			} // room iter
		} // hotel iter
		// $this->savePackages($hotelArr, $packagesIds);
		echo "\r\n" . count($packagesIds) . "\r\n"; exit;
		// $this->savePackage($hotelID, $roomID, $mealID, $adl, $chd, $fcMax, $scMax, $tcMax, $depFlightID, $returnFlightID, $night, $price);
	}
	
	public function getPackagesFromXml($xml) {

		$soap = new \SoapClient('http://service.coraltravel.ua/package.asmx?WSDL');
		$xml = '<Query author="Coral Travel Ukraine" version="1.0.0.0">' . $xml . '</Query>';
		$xmlString = @$soap->PackagePriceCheckingUkraine( array('xml' => $xml) )->PackagePriceCheckingUkraineResult->any;

		$scope = $this;
		$startElement = function($parser, $currentNodeName, $currentAttrs) use ($scope) {
			if ($currentNodeName == 'ITEM') {
				// echo '-';
				if (isset($currentAttrs['SALESTATUS']) && isset($currentAttrs['TOTALPRICE'])) {
					if ($currentAttrs['SALESTATUS'] == 1) {
						$scope->itemIDs[$currentAttrs['ITEMID']] = $currentAttrs['TOTALPRICE'];
						// echo 'SALE';
					}
				}
			}
		};

		$xml_parser = xml_parser_create();
		xml_set_element_handler($xml_parser, $startElement, null);
		xml_parse($xml_parser, $xmlString, false);
		xml_parser_free($xml_parser);	

		// return false, if $itemIDs is empty
		if (isset($this->itemIDs)) {
			$itemIDs = $this->itemIDs;
			unset($this->itemIDs);
			return $itemIDs;
		} else {
			return false;
		}
	}

	function saveXml($xml) {
		$file = fopen('logs'.DIRECTORY_SEPARATOR.'xmlResult.xml', 'a+');
		fwrite($file, $xml);
		fclose($file);
	}

	function _http( $url, $data = false, $type = 'POST', $header = false ) {
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
	// 		xml_parse($xml_parser, $xmlString, false);
	// 		xml_parser_free($xml_parser);
	// 	}
	// }
}