<?php

namespace models\dto;
use models;

class Tour {
	public static function toEntity($tour, $params) {
			
			if(isset($params['hotel']))
				$tour->setHotel($params['hotel']);
			if(isset($params['departureCity']))
				$tour->setDepartureCity($params['departureCity']);
			if(isset($params['room']))
				$tour->setRoom($params['room']);
			if(isset($params['meal']))
				$tour->setMeal($params['meal']);
			if(isset($params['operator']))
				$tour->setOperator($params['operator']);
			if(isset($params['currency']))
				$tour->setCurrency($params['currency']);

		return $tour;
	}
}