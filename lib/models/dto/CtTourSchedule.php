<?php

namespace models\dto;
use models;

class CtTourSchedule {
	
	public static function toEntity($tourSchedule, $params) {
		
		if(isset($params['ctFlight'])) {
			$tourSchedule->setCtFlight($params['ctFlight']);
		}

		if(isset($params['price'])) {
			$tourSchedule->setPrice($params['price']);
		}

		if(isset($params['tour'])) {
			$tourSchedule->setTour($params['tour']);
		}

		if(isset($params['ctAgeGroups'])) {
			$tourSchedule->setCtAgeGroup($params['ctAgeGroups']);
		}

		return $tourSchedule;
	}
}