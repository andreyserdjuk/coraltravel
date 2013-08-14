<?php

namespace models\dto;
use models;

class CtAgeGroup {
	public static function toEntity(\models\CtAgeGroup $ctAgeGroup, $params) {

		if(isset($params['ad']))
        	$ctAgeGroup->setAdultCount($params['ad']);
		if(isset($params['cd']))
        	$ctAgeGroup->setChildCount($params['cd']);
		if(isset($params['fmn']))
        	$ctAgeGroup->setFirstChildMinAge($params['fmn']);
		if(isset($params['fmx']))
        	$ctAgeGroup->setFirstChildMaxAge($params['fmx']);
		if(isset($params['smn']))
        	$ctAgeGroup->setSecondChildMinAge($params['smn']);
		if(isset($params['smx']))
        	$ctAgeGroup->setSecondChildMaxAge($params['smx']);
		if(isset($params['tmn']))
        	$ctAgeGroup->setThirdChildMinAge($params['tmn']);
		if(isset($params['tmx']))
        	$ctAgeGroup->setThirdChildMaxAge($params['tmx']);
 
        return $ctAgeGroup;
	}
}