<?php

namespace models\dto;
use models;

class Accomodation {
	public static function toEntity($accomodation, $params) {
			
			if(isset($params['hotel']))
				$accomodation->setHotel($params['hotel']);
			if(isset($params['departureCity']))
				$accomodation->setDepartureCity($params['departureCity']);
			if(isset($params['room']))
				$accomodation->setRoom($params['room']);
			if(isset($params['meal']))
				$accomodation->setMeal($params['meal']);
			if(isset($params['operator']))
				$accomodation->setOperator($params['operator']);
			if(isset($params['currency']))
				$accomodation->setCurrency($params['currency']);

		return $accomodation;
	}
}