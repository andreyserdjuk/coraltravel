<?php

namespace models\dto;
use models;

class CtFlight {
	
	public static function toArray($ctFlight) {
		
		return array(
			'id'				=> $ctFlight->getId(),
			'tourBegin'			=> $ctFlight->getTourBegin(),
			'nights'			=> $ctFlight->getNights(),
			'departureFlightId'	=> $ctFlight->getDepartureFlightId(),
			'returnFlightId'	=> $ctFlight->getReturnFlightId(),
			'active'			=> $ctFlight->getActive() //,
			// 'ctTourSchedule'	=> $ctFlight->getCtTourSchedule()
		);
	}

	public static function toSimpleArray($ctFlight) {
		
		return array(
			'id'				=> $ctFlight->getId(),
			'tourBegin'			=> $ctFlight->getTourBegin(),
			'nights'			=> $ctFlight->getNights(),
			'departureFlightId'	=> $ctFlight->getDepartureFlightId(),
			'returnFlightId'	=> $ctFlight->getReturnFlightId()
		);
	}

	public static function toEntity($ctFlight, $params) {
		
		if (isset($params['id'])) {
			$ctFlight->setId($params['id']);
		}

		if (isset($params['tourBegin'])) {
			$ctFlight->setTourBegin($params['tourBegin']);
		}

		if (isset($params['nights'])) {
			$ctFlight->setNights($params['nights']);
		}

		if (isset($params['departureFlightId'])) {
			$ctFlight->setDepartureFlightId($params['departureFlightId']);
		}

		if (isset($params['returnFlightId'])) {
			$ctFlight->setReturnFlightId($params['returnFlightId']);
		}

		if (isset($params['active'])) {
			$ctFlight->setActive($params['active']);
		}

		if (isset($params['ctTourSchedule'])) {
			$ctFlight->setCtTourSchedule($params['ctTourSchedule']);
		}

		return $ctFlight;
	}
}