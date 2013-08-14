<?php

namespace models\dto;
use models;

class CtParsingTask {
	public static function toEntity($CtParsingTask, $params) {

		if(isset($params['tourBegin']))
			$CtParsingTask->setTourBegin($params['tourBegin']);
		if(isset($params['nights']))
			$CtParsingTask->setNights($params['nights']);
		if(isset($params['departureFlightId']))
			$CtParsingTask->setDepartureFlightId($params['departureFlightId']);
		if(isset($params['returnFlightId']))
			$CtParsingTask->setReturnFlightId($params['returnFlightId']);

		return $CtParsingTask;
	}
}