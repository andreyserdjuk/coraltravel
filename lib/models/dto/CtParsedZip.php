<?php

namespace models\dto;

class CtParsedZip {
	public static function toFileList($ctParsedZip) {
		$ctParsedZips = gettype($ctParsedZip) == 'array'? $ctParsedZip : array($ctParsedZip);
		$res = array();
		foreach ($ctParsedZips as $e) {
			$res[] = $e->getFileZip();
		}
		unset($ctParsedZips);
		return $res;
	}
}