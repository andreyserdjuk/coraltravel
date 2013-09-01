<?php
// define('ENVIRONMENT', 'development');
define('_R', __DIR__ . DIRECTORY_SEPARATOR);
// require_once "lib/em.php";

$parser = new CoralParser;
$parser->parseXml();

class CoralParser {

	function parseXml() {

		foreach ( scandir("coraltravel_xml") as $fileXml ) {
		   $mem_usage = memory_get_usage(true);
		   if ( $fileXml!="." && $fileXml!=".." && preg_match("/.xml$/", $fileXml) ) {
				$xml = new \XMLReader;
				$res = $xml->open(_R . "coraltravel_xml". DIRECTORY_SEPARATOR . $fileXml);
				if ($res === TRUE) {
					$cur = 2;
					// $itemID = 0;
					$requestString = '';
					// $hotelCount = 0;
					while ($xml->read()) {
					   if ($xml->nodeType == $xml::ELEMENT) {
					   	if ($xml->name == 'PackagePrices') {
					   		$fromAreaID = $xml->getAttribute('fromAreaID');
					   		$ctCurrencyId = $xml->getAttribute('currency');
					   	}
							
							if ($xml->name == 'hn') {
								$hotelID = $xml->getAttribute('h');
								// if ($hotelCount > 1) {
									// saveXml($requestString);
									// exit;
								// }
								// $hotelCount++;
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
					   		// $itemID++;
								$tourBeginDate = $xml->getAttribute('tb');
								$night = $xml->getAttribute('n');
								$depFlightID = $xml->getAttribute('d');
								$returnFlightID = $xml->getAttribute('r');
								$requestString .= "<Item itemID=\"$itemID\" cur=\"$cur\" tourBeginDate=\"$tourBeginDate\" night=\"$night\" depFlightID=\"$depFlightID\" returnFlightID=\"$returnFlightID\" hotelID=\"$hotelID\" roomID=\"$roomID\" mealID=\"$mealID\" adl=\"$adl\" chd=\"$chd\" fcMax=\"$fcMax\" scMax=\"$scMax\" tcMax=\"$tcMax\" />";
								// $agString = implode('-', array($adl, $chd, $fcMax, $scMax, $tcMax));
								// $flightStr = implode('-', array($depFlightID, $returnFlightID, $night));

								$this->items[$hotelID][$roomID][$mealID][$agString][$flightStr] = $tourBeginDate;
								// $this->flights[$depFlightID] = $returnFlightID;
							}

				   	} elseif ($xml->nodeType == $xml::END_ELEMENT && $xml->name == 'a') {
				   		// check tours availability
				   		$packages = $this->getAvailablePackages($requestString);
				   		if (!$packages) {
				   			$xml->next('hn') or break;
				   		}

				   		// save available packages (tours) to database
				   		// init actual variables...
				   		
				   	}
				   }
				   $xml->close();

				  	// $this->unsetNotActiveFlights();
				  	// $this->checkAvailableTours();
				}
			}
		}
	}

	function checkAvailableTours() {
		$itemID = 0;
		$cur = 2;
		$itemHotel = array();
		$requestStrings = array();
		$requestString = '';

		foreach ($this->items as $hotelID => $roomArr)
		{ // hotel iter
			foreach ($roomArr as $roomID => $mealArr)
			{ // room iter
				foreach ($mealArr as $mealID => $agArr)
				{ // meal iter
					foreach ($agArr as $agString => $flightArr)
					{ // age group iter
						list($adl, $chd, $fcMax, $scMax, $tcMax) = explode('-', $agString);
						foreach ($flightArr as $flightStr => $tourBeginDate) {
							list($depFlightID, $returnFlightID, $night) = explode('-', $flightStr);
							if (isset($this->flights[$depFlightID]) && $this->flights[$depFlightID] == $returnFlightID) {
								$requestString .= "<Item itemID=\"$itemID\" cur=\"$cur\" tourBeginDate=\"$tourBeginDate\" night=\"$night\" depFlightID=\"$depFlightID\" returnFlightID=\"$returnFlightID\" hotelID=\"$hotelID\" roomID=\"$roomID\" mealID=\"$mealID\" adl=\"$adl\" chd=\"$chd\" fcMax=\"$fcMax\" scMax=\"$scMax\" tcMax=\"$tcMax\" />";
								$itemHotel[$itemID] = $hotelID;
								$itemID++;
							}
						} 
						break;
					} // age group iter
					break;
				} // meal iter
				break;
			} // room iter
			$requestStrings[] = $requestString;
			$requestString = '';			
		} // hotel iter

	 	$this->tourCheckXml($requestStrings);
	}

	function unsetNotActiveFlights() {
		$soap = new \SoapClient('http://service.coraltravel.ua/Transport.asmx?WSDL');
		
		$scope = $this;
		$startElement = function($parser, $currentNodeName, $currentAttrs) use ($scope) {
			if ($currentNodeName == 'RESULT') {
				if (isset($currentAttrs['DEPARTUREALLOTMENTSTATUSID'])) {
					if ($currentAttrs['DEPARTUREALLOTMENTSTATUSID'] == 0) {
						unset($scope->flights[$currentAttrs['DEPARTUREFLIGHTID']]);
					}
	         }
			}
		};

		foreach ($this->flights as $depFlightID => $returnFlightID) {
			$xml_parser = xml_parser_create();
		   xml_set_element_handler($xml_parser, $startElement, null);
			$xmlString = @$soap->FlightStatusCheck( array('arrivalFlightID' => $returnFlightID, 'departureFlightID' => $depFlightID ) )->FlightStatusCheckResult->any;
		   xml_parse($xml_parser, $xmlString, false);
	   	xml_parser_free($xml_parser);
		}
	}

	function saveXml($xml) {
		$file = fopen('logs'.DIRECTORY_SEPARATOR.'xmlResult.xml', 'a+');
		fwrite($file, $xml);
		fclose($file);
	}

	function tourCheckXml($xmlString) {
		$soap = new \SoapClient('http://service.coraltravel.ua/package.asmx?WSDL');
		$xml = '<Query author="Coral Travel Ukraine" version="1.0.0.0">' . $xml . '</Query>';
		$xmlString = @$soap->PackagePriceCheckingUkraine( array('xml' => $xml) )->PackagePriceCheckingUkraineResult->any;
		// $this->saveXml($xmlString);
		// $result = $this->_http('http://service.coraltravel.ua/package.asmx/PackagePriceCheckingUkraine', 'xml='.$xml, 'POST', array('POST /package.asmx/PackagePriceCheckingUkraine HTTP/1.1','Host: service.coraltravel.ua','Content-Type: application/x-www-form-urlencoded'));

		$scope = $this;
		$startElement = function($parser, $currentNodeName, $currentAttrs) use ($scope) {
			if ($currentNodeName == 'ITEM') {
				if (isset($currentAttrs['TOTALPRICE'])) {
					if ($currentAttrs['TOTALPRICE'] != 0) {
						print_r($currentAttrs);
					}
	         }
			}
		};

		$xml_parser = xml_parser_create();
		xml_set_element_handler($xml_parser, $startElement, null);
		xml_parse($xml_parser, $xmlString, false);
	   xml_parser_free($xml_parser);		
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
}