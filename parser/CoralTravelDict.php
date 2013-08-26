<?php
namespace parser;
use models;

if (!defined("LOCK_START")) { echo "not allowed direct calling";  exit; }

Class CoralTravelDict extends Parser {

    private $soap;                          // SoapClient()
    public $em;                             // entity manager
    private $ctAgeGroupCache;               // cahe of age groups - we don't need to disturb our database for age group searching...
    private $ctFlightCache;                 // cache of flights models\CtFlight
    private $ctTourScheduleCanEdit;         // id's of tour schedule, what we can edin in current loop
    private $tourCache;                     // cache of tours - the are only few elements...
    private $ctTourScheduleIndexedCache;    // cache of ctTourSchedule - for fast search
    private $ctFlightIndexedCache;          // cache of ctFlight - for fast search
    private $ctTourIndexedCache;            // cache of ctFlight - for fast search
    private $ctHotelIndexedCache;           // cache of ctHotel - for fast search
    private $departureCityFlag;

    public function __construct() {
        parent::__construct();
        $this->ctTourScheduleCanEdit = array();
        try {
            $this->soap = new \SoapClient('http://service.coraltravel.ua/KeyDefinition.asmx?WSDL');
        } catch (Exception $e) {
            $this->myLog(__LINE__, $e->getMessage(), 1);
        }
    }

    public function updateDictionaries() {
        // get all parsing settings
        $settings = $this->em->getRepository('models\CtParsingTask')->findAll();
        foreach ($settings as $setting) {
            if ($setting->getEnabled() == TRUE) {
                $funcName = $setting->getName();
                $this->$funcName();
            }
        }
    }

    public function updateCountry() {
        $xmlString = $this->soap->CountryList()->CountryListResult->any;
        $scope = $this;
        $startElement = function($parser, $currentNodeName, $currentAttrs) use ($scope) {
            $this->checkpoint();
            if ($currentNodeName=='COUNTRY') {
                
                $nameEn = $currentAttrs['NAME'];
                if (isset($currentAttrs['LNAME'])) {
                    $nameRu = trim($currentAttrs['LNAME'], " ");
                }
                $id = $currentAttrs['ID'];

                // does the entity reflection already exist in database?
                $CtCountry = $scope->em->getRepository("models\CtCountry")->findOneBy(array('ctCountryId' => $id));

                if(!$CtCountry) {
                    $country = new models\Country;
                    $country->setNameEn($nameEn);
                    if (isset($nameRu)) {
                        $country->setNameRu($nameRu);
                    }
                    $country->save();
                    // set coral travel id
                    $CtCountry = new models\CtCountry;
                    $CtCountry->setCtCountryId($id);
                    $CtCountry->setCountry($country);
                    $CtCountry->save();
                }
            }
        };

        $xml_parser = xml_parser_create();
        xml_set_element_handler($xml_parser, $startElement, null);
        xml_parse($xml_parser, $xmlString, false);
        xml_parser_free($xml_parser);
        $countrySetting = $this->em->getRepository('models\CtParsingTask')->find(1);
        $countrySetting->setDisabled();
        $countrySetting->save();
    }

    public function updateRegion() {

        $q = $this->em->createQuery('SELECT ctc, co from models\CtCountry ctc join ctc.country co');
        $CtCountries = $q->getResult();
        foreach ($CtCountries as $CtCountry) {
            $xmlString = $this->soap->RegionList( array('destinationID' => $CtCountry->getCtCountryId()) )->RegionListResult->any;
            $scope = $this;
            $startElement = function($parser, $currentNodeName, $currentAttrs) use ($scope, $CtCountry) {
                $scope->checkpoint();
                if ($currentNodeName=='REGION') {
                    
                    $nameEn = $currentAttrs['NAME'];
                    if (isset($currentAttrs['LNAME'])) {
                        $nameRu = trim($currentAttrs['LNAME'], " ");
                    }
                    $id = $currentAttrs['ID'];

                    // does the entity reflection already exist in database?
                    $CtRegion = $scope->em->getRepository("models\CtRegion")->findOneBy(array('ctRegionId' => $id));

                    if(!$CtRegion) {
                        $region = new models\Region;
                        $region->setNameEn($nameEn);
                        if (isset($nameRu)) {
                            $region->setNameRu($nameRu);
                        }
                        $country = $CtCountry->getCountry();
                        $region->setCountry($country);
                        $region->save();
                        // set coral travel id
                        $CtRegion = new models\CtRegion;
                        $CtRegion->setCtRegionId($id);
                        $CtRegion->setRegion($region);
                        $CtRegion->setCtCountry($CtCountry);
                        $CtRegion->save();
                    }
                }
            };

            $xml_parser = xml_parser_create();
            xml_set_element_handler($xml_parser, $startElement, null);
            xml_parse($xml_parser, $xmlString, false);
            xml_parser_free($xml_parser);
        }
        $regionSetting = $this->em->getRepository('models\CtParsingTask')->find(2);
        $regionSetting->setDisabled();
        $regionSetting->save();
    }

    public function updateArea() {

        $q = $this->em->createQuery('SELECT ctr, r, c from models\CtRegion ctr join ctr.region r join r.country c');
        $CtRegions = $q->getResult();

        // preparing cache        
        $q = $this->em->createQuery('SELECT cta.ctAreaId from models\CtArea cta');
        $ctAreasCache = array();
        foreach ($q->getResult($q::HYDRATE_ARRAY) as $cta) {
            $ctAreasCache[] = $cta['ctAreaId'];
        }

        foreach ($CtRegions as $CtRegion) {

            $country = $CtRegion->getRegion()->getCountry();
            $ctCountryId = $this->em->getRepository("models\CtCountry")->findOneBy(array('country' => $country))->getCtCountryId();
            $xmlString = false;
            do {
                $xmlString = @$this->soap->AreaList( array('destinationID' => $ctCountryId, 'regionID' => $CtRegion->getCtRegionId()) )->AreaListResult->any;
            } while (!$xmlString);
            $scope = $this;
            $startElement = function($parser, $currentNodeName, $currentAttrs) use ($scope, $CtRegion, $ctAreasCache) {
                $scope->checkpoint();
                if ($currentNodeName=='AREA') {
                    
                    $id = $currentAttrs['ID'];
                    $nameEn = $currentAttrs['NAME'];
                    if (isset($currentAttrs['LNAME'])) {
                        $nameRu = trim($currentAttrs['LNAME'], " ");
                    }
                    $ctRegionId = $currentAttrs['REGIONID'];
                    $ctCountryId = $currentAttrs['COUNTRYID'];

                    // does the entity reflection already exist in database?
                    if(!in_array($id, $ctAreasCache)) {
                        $area = new models\Area;
                        $area->setNameEn($nameEn);
                        if (isset($nameRu)) {
                            $area->setNameRu($nameRu);
                        }
                        $country = $CtRegion->getRegion()->getCountry();
                        $area->setCountry($country);
                        $area->setRegion($CtRegion->getRegion());
                        $area->save();
                        // set coral travel id
                        $CtArea = new models\CtArea;
                        $CtArea->setCtAreaId($id);
                        $CtArea->setArea($area);
                        $CtArea->setCtRegion($CtRegion);
                        $CtArea->save();
                    }
                }
            };

            $xml_parser = xml_parser_create();
            xml_set_element_handler($xml_parser, $startElement, null);
            xml_parse($xml_parser, $xmlString, false);
            xml_parser_free($xml_parser);
        }
        $areaSetting = $this->em->getRepository('models\CtParsingTask')->find(3);
        $areaSetting->setDisabled();
        $areaSetting->save();
    }

    public function updatePlace() {

        $q = $this->em->createQuery('SELECT cta, a, ctr, r, ctc, c
                                       from models\CtArea cta
                                           join cta.area a
                                           join cta.ctRegion ctr
                                           join ctr.region r
                                           join ctr.ctCountry ctc
                                           join ctc.country c');
        $ctAreas = $q->getResult();

        // preparing ctPlaces cache
        $q = $this->em->createQuery('SELECT ctp.ctPlaceId FROM models\CtPlace ctp');
        $ctPlacesCache = array();
        foreach ($q->getResult($q::HYDRATE_ARRAY) as $ctPlace) {
            $ctPlacesCache[] = $ctPlace['ctPlaceId'];
        }

        foreach ($ctAreas as $ctArea) {

            $ctRegion = $ctArea->getCtRegion();
            $ctCountry = $ctRegion->getCtCountry();
            $params = array( 'destinationID' => $ctCountry->getCtCountryId(),
                             'regionID'      => $ctRegion->getCtRegionId(),
                             'areaID'        => $ctArea->getCtAreaId() ) ;

            try {
                do {
                    $xmlString = @$this->soap->PlaceList($params)->PlaceListResult->any;
                } while (!$xmlString);
            } catch (SoapFault $e) {
                $this->myLog(__LINE__, $e->getMessage(), 1);
            }

            $scope = $this;
            $x = 0;
            $startElement = function($parser, $currentNodeName, $currentAttrs) use ($scope, $ctArea, $ctRegion, $ctCountry, $ctPlacesCache, $x) {
                $scope->checkpoint();
                if ($currentNodeName=='PLACE') {
                    
                    $ctPlaceId = $currentAttrs['ID'];
                    $nameEn = $currentAttrs['NAME'];
                    if (isset($currentAttrs['LNAME'])) {
                        $nameRu = trim($currentAttrs['LNAME'], " ");
                    }
                    $ctCountryId = $currentAttrs['COUNTRYID'];
                    $ctRegionId = $currentAttrs['REGIONID'];
                    $ctAreaId = $currentAttrs['AREAID'];

                    // does the entity reflection already exist in database?
                    if(!in_array($ctPlaceId, $ctPlacesCache)) {
                        $place = new models\Place;
                        $place->setNameEn($nameEn);
                        if (isset($nameRu)) {
                            $place->setNameRu($nameRu);
                        }
                        $country = $ctRegion->getRegion()->getCountry();
                        $place->setCountry($country);
                        $place->setRegion($ctRegion->getRegion());
                        $place->setArea($ctArea->getArea());
                        // $place->save();
                        $this->em->persist($place);
                        // set coral travel id
                        $ctPlace = new models\CtPlace;
                        $ctPlace->setCtPlaceId($ctPlaceId);
                        $ctPlace->setPlace($place);
                        // $ctPlace->save();
                        $this->em->persist($ctPlace);
                        // echo $place->getNameEn(); echo "\r\n";
                        $x++;
                        if ($x > 100) {
                            $this->em->flush();
                            $x = 0;
                        }
                    }
                }
            };
            $this->em->flush();

            $xml_parser = xml_parser_create();
            xml_set_element_handler($xml_parser, $startElement, null);
            xml_parse($xml_parser, $xmlString, false);
            xml_parser_free($xml_parser);
        }
        $placeSetting = $this->em->getRepository('models\CtParsingTask')->find(4);
        $placeSetting->setDisabled();
        $placeSetting->save();
    }

    public function updateHotelCategoryGroup() {
        do {
            $xmlString = @$this->soap->HotelCategoryGroupList()->HotelCategoryGroupListResult->any;
        } while (!$xmlString);
        $scope = $this;
        $startElement = function($parser, $currentNodeName, $currentAttrs) use ($scope) {
            $scope->checkpoint();
            if ($currentNodeName=='HOTELCATEGORYGROUP') {
                
                $nameEn = $currentAttrs['NAME'];
                    $id = $currentAttrs['ID'];

                // does the entity reflection already exist in database?
                $ctHotelCategoryGroup = $scope->em->getRepository("models\CtHotelCategoryGroup")->findOneBy(array('ctHotelCategoryGroupId' => $id));

                if(!$ctHotelCategoryGroup) {
                    $hotelCategoryGroup = new models\HotelCategoryGroup;
                    $hotelCategoryGroup->setNameEn($nameEn);
                    $hotelCategoryGroup->save();
                    // set coral travel id
                    $ctHotelCategoryGroup = new models\CtHotelCategoryGroup;
                    $ctHotelCategoryGroup->setHotelCategoryGroup($hotelCategoryGroup);
                    $ctHotelCategoryGroup->setCtHotelCategoryGroupId($id);
                    $ctHotelCategoryGroup->save();
                }
            }
        };

        $xml_parser = xml_parser_create();
        xml_set_element_handler($xml_parser, $startElement, null);
        xml_parse($xml_parser, $xmlString, false);
        xml_parser_free($xml_parser);

        $placeSetting = $this->em->getRepository('models\CtParsingTask')->find(5);
        $placeSetting->setDisabled();
        $placeSetting->save();
    }

    public function updateHotelCategory() { 
        do {
            $xmlString = $this->soap->HotelCategoryList()->HotelCategoryListResult->any;
        } while (!$xmlString);
        $scope = $this;
        $startElement = function($parser, $currentNodeName, $currentAttrs) use ($scope) {
            $scope->checkpoint();
            if ($currentNodeName=='ITEM') {
                
                $nameEn = $currentAttrs['NAME'];
                    $id = $currentAttrs['ID'];
                $hotelCategoryGroupId = $currentAttrs['HOTELCATEGORYGROUP'];

                // does the entity reflection already exist in database?
                $ctHotelCategory = $scope->em->getRepository("models\CtHotelCategory")->findOneBy(array('ctHotelCategoryId' => $id));
                $ctHotelCategoryGroup = $scope->em->getRepository("models\CtHotelCategoryGroup")->findOneBy(array('ctHotelCategoryGroupId' => $hotelCategoryGroupId));

                if(!$ctHotelCategory && $ctHotelCategoryGroup) {
                    $hotelCategory = new models\HotelCategory;
                    $hotelCategory->setNameEn($nameEn);
                    $hotelCategory->setHotelCategoryGroup($ctHotelCategoryGroup->getHotelCategoryGroup());
                    $hotelCategory->save();
                    // set coral travel id
                    $ctHotelCategory = new models\CtHotelCategory;
                    $ctHotelCategory->setHotelCategory($hotelCategory);
                    $ctHotelCategory->setCtHotelCategoryId($id);
                    $ctHotelCategory->save();
                }
            }
        };

        $xml_parser = xml_parser_create();
        xml_set_element_handler($xml_parser, $startElement, null);
        xml_parse($xml_parser, $xmlString, false);
        xml_parser_free($xml_parser);

        $placeSetting = $this->em->getRepository('models\CtParsingTask')->find(6);
        $placeSetting->setDisabled();
        $placeSetting->save();
    }

    public function updateHotel() {
        do {
            $xmlString = @$this->soap->HotelDetailList()->HotelDetailListResult->any;
        } while (!$xmlString);

        // preparing ctHotels cache
        $q = $this->em->createQuery('SELECT cth.ctHotelId FROM models\CtHotel cth');
        $ctHotelsCache = array();
        foreach ($q->getResult($q::HYDRATE_ARRAY) as $ctHotel) {
            $ctHotelsCache[] = $ctHotel['ctHotelId'];
        }

        // hotel categories cache
        $ctHotelCategories = $this->em->getRepository("models\CtHotelCategory")->findAll();
        // places cache
        $ctPlaces = $this->em->getRepository('models\CtPlace')->findAll();

        $scope = $this;
        $startElement = function($parser, $currentNodeName, $currentAttrs) use ($scope, $ctHotelsCache, $ctHotelCategories, $ctPlaces) {
            $scope->checkpoint();
            if ($currentNodeName=='ROW') {
                
                $nameEn = $currentAttrs['NAME'];
                if (isset($currentAttrs['LNAME'])) {
                    $nameRu = trim($currentAttrs['LNAME'], " ");
                }
                $ctHotelId = $currentAttrs['ID'];
                $url = isset($currentAttrs['WEB'])? $currentAttrs['WEB'] : '';
                $hotelCategoryId = $currentAttrs['HOTELCATEGORY'];
                $ctPlaceId = $currentAttrs['PLACE'];
                $latitude = isset($currentAttrs['LATITUDE'])? $currentAttrs['LATITUDE'] : '';
                $longitude = isset($currentAttrs['LONGITUDE'])? $currentAttrs['LONGITUDE'] : '';
                
                $category = false;
                foreach ($ctHotelCategories as $ctHotelCategory) {
                    if ($ctHotelCategory->getCtHotelCategoryId() == $hotelCategoryId) {
                        $category = $ctHotelCategory->getHotelCategory();
                        break;
                    }
                }

                $ctPlace = false;
                foreach ($ctPlaces as $_ctPlace) {
                    if ($_ctPlace->getCtPlaceId() == $ctPlaceId) {
                        $ctPlace = $_ctPlace;
                    }
                }
                
                // $ctPlace = $scope->em->getRepository("models\CtPlace")->findOneBy(array('ctPlaceId' => $ctPlaceId));
                
                if ($ctPlace) {
                    $place = $ctPlace->getPlace();
                    $country = $place->getCountry();
                    $region = $place->getRegion();
                    $area = $place->getArea();

                    // does the entity reflection already exist in database AND category defined?
                    if(!in_array($ctHotelId, $ctHotelsCache) && $category) {
                        $hotel = new models\Hotel;
                        $hotel->setNameEn($nameEn);
                        if (isset($nameRu)) {
                            $hotel->setNameRu($nameRu);
                        }
                        $hotel->setPlace($place);
                        $hotel->setUrl($url);
                        $hotel->setLatitude($latitude);
                        $hotel->setLongitude($longitude);
                        $hotel->setCategory($category);
                        $hotel->setCountry($country);
                        $hotel->setRegion($region);
                        $hotel->setArea($area);
                        // $hotel->save();
                        $this->em->persist($hotel);

                        // set coral travel id
                        $ctHotel = new models\CtHotel;
                        $ctHotel->setCtHotelId($ctHotelId);
                        $ctHotel->setHotel($hotel);
                        // $ctHotel->save();
                        $this->em->persist($ctHotel);
                    }
                } else {
                    $scope->myLog(__LINE__, "cann't find ctPlace with ctPlaceId: $ctPlaceId");
                }
            }
        };
        $this->em->flush();
        $xml_parser = xml_parser_create();
        xml_set_element_handler($xml_parser, $startElement, null);
        xml_parse($xml_parser, $xmlString, false);
        xml_parser_free($xml_parser);

        $placeSetting = $this->em->getRepository('models\CtParsingTask')->find(7);
        $placeSetting->setDisabled();
        $placeSetting->save();
    }

    public function updateRoomCategory() {
        $xmlString = $this->soap->RoomCategoryList()->RoomCategoryListResult->any;
        $scope = $this;
        $startElement = function($parser, $currentNodeName, $currentAttrs) use ($scope) {
            $scope->checkpoint();
            if ($currentNodeName=='RC' && isset($currentAttrs['ID'])) {
                    $id = $currentAttrs['ID'];
                $nameEn = $currentAttrs['NAME'];
                if (isset($currentAttrs['LNAME'])) {
                    $nameRu = $currentAttrs['LNAME'];
                }

                // does the entity reflection already exist in database?
                $ctRoomCategory = $scope->em->getRepository("models\CtRoomCategory")->findOneBy(array('ctRoomCategoryId' => $id));

                if(!$ctRoomCategory) {
                    $roomCategory = new models\RoomCategory;
                    $roomCategory->setNameEn($nameEn);
                    if (isset($nameRu)) {
                        $roomCategory->setNameRu($nameRu);
                    }
                    $roomCategory->save();
                    // set coral travel id
                    $ctRoomCategory = new models\CtRoomCategory;
                    $ctRoomCategory->setRoomCategory($roomCategory);
                    $ctRoomCategory->setCtRoomCategoryId($id);
                    $ctRoomCategory->save();
                }
            }
        };

        $xml_parser = xml_parser_create();
        xml_set_element_handler($xml_parser, $startElement, null);
        xml_parse($xml_parser, $xmlString, false);
        xml_parser_free($xml_parser);  

        $placeSetting = $this->em->getRepository('models\CtParsingTask')->find(8);
        $placeSetting->setDisabled();
        $placeSetting->save();
    }

    public function updateRoom() {
        $xmlString = $this->soap->RoomList()->RoomListResult->any;

        // preparing ctRooms cache
        $q = $this->em->createQuery('SELECT ctr.ctRoomId FROM models\CtRoom ctr');
        $ctRoomsCache = array();
        foreach ($q->getResult($q::HYDRATE_ARRAY) as $ctRoom) {
            $ctRoomsCache[] = $ctRoom['ctRoomId'];
        }

        $scope = $this;
        $startElement = function($parser, $currentNodeName, $currentAttrs) use ($scope, $ctRoomsCache) {
            $this->checkpoint();
            if ($currentNodeName=='ROOM') {
                
                $ctRoomId = $currentAttrs['ID'];
                $nameEn = $currentAttrs['NAME'];
                if (isset($currentAttrs['LNAME'])) {
                    $nameRu = $currentAttrs['LNAME'];
                }
                $ctRoomCategoryId = $currentAttrs['ROOMCATEGORY'];

                // does the entity reflection already exist in database?
                $ctRoomCategory = $scope->em->getRepository("models\CtRoomCategory")->findOneBy(array('ctRoomCategoryId' => $ctRoomCategoryId));
                $roomCategory = $ctRoomCategory->getRoomCategory(); 

                if(!in_array($ctRoomId, $ctRoomsCache)) {
                    $room = new models\Room;
                    $room->setNameEn($nameEn);
                    if (isset($nameRu)) {
                        $room->setNameRu($nameRu);
                    }
                    $room->setCategory($roomCategory);
                    // $room->save();
                    $this->em->persist($room);
                    // set coral travel id
                    $ctRoom = new models\CtRoom;
                    $ctRoom->setRoom($room);
                    $ctRoom->setCtRoomId($ctRoomId);
                    // $ctRoom->save();
                    $this->em->persist($ctRoom);
                }
            }
        };
        $this->em->flush();

        $xml_parser = xml_parser_create();
        xml_set_element_handler($xml_parser, $startElement, null);
        xml_parse($xml_parser, $xmlString, false);
        xml_parser_free($xml_parser);     

        $placeSetting = $this->em->getRepository('models\CtParsingTask')->find(9);
        $placeSetting->setDisabled();
        $placeSetting->save();
    }

    public function updateMealCategory() {
        $xmlString = $this->soap->MealCategoryList()->MealCategoryListResult->any;
        $scope = $this;
        $startElement = function($parser, $currentNodeName, $currentAttrs) use ($scope) {
            $this->checkpoint();
            if ($currentNodeName=='MEALCATEGORY') {
                
                    $id = $currentAttrs['ID'];
                $nameEn = $currentAttrs['NAME'];
                if (isset($currentAttrs['LNAME'])) {
                    $nameRu = $currentAttrs['LNAME'];
                }

                // does the entity reflection already exist in database?
                $ctMealCategory = $scope->em->getRepository("models\CtMealCategory")->findOneBy(array('ctMealCategoryId' => $id));

                if(!$ctMealCategory) {
                    $mealCategory = new models\MealCategory;
                    $mealCategory->setNameEn($nameEn);
                    if (isset($nameRu)) {
                        $mealCategory->setNameRu($nameRu);
                    }
                    $mealCategory->save();
                    // set coral travel id
                    $ctMealCategory = new models\CtMealCategory;
                    $ctMealCategory->setMealCategory($mealCategory);
                    $ctMealCategory->setCtMealCategoryId($id);
                    $ctMealCategory->save();
                }
            }
        };

        $xml_parser = xml_parser_create();
        xml_set_element_handler($xml_parser, $startElement, null);
        xml_parse($xml_parser, $xmlString, false);
        xml_parser_free($xml_parser);   

        $placeSetting = $this->em->getRepository('models\CtParsingTask')->find(10);
        $placeSetting->setDisabled();
        $placeSetting->save();
    }

    public function updateMeal() {
        $xmlString = $this->soap->MealList()->MealListResult->any;
        $scope = $this;
        $startElement = function($parser, $currentNodeName, $currentAttrs) use ($scope) {
            $this->checkpoint();
            if ($currentNodeName=='MEAL') {
                
                    $id = $currentAttrs['ID'];
                $nameEn = $currentAttrs['NAME'];
                if (isset($currentAttrs['LNAME'])) {
                    $nameRu = $currentAttrs['LNAME'];
                }
                $ctMealCategoryId = $currentAttrs['MEALCATEGORYID'];

                // does the entity reflection already exist in database?
                $ctMealCategory = $scope->em->getRepository("models\CtMealCategory")->findOneBy(array('ctMealCategoryId' => $ctMealCategoryId));
                $mealCategory = $ctMealCategory->getMealCategory();

                $ctMeal = $scope->em->getRepository("models\CtMeal")->findOneBy(array('ctMealId' => $id));

                if(!$ctMeal) {
                    $meal = new models\Meal;
                    $meal->setNameEn($nameEn);
                    if (isset($nameRu)) {
                        $meal->setNameRu($nameRu);
                    }
                    $meal->setCategory($mealCategory);
                    $meal->save();
                    // set coral travel id
                    $ctMeal = new models\CtMeal;
                    $ctMeal->setMeal($meal);
                    $ctMeal->setCtMealId($id);
                    $ctMeal->save();
                }
            }
        };

        $xml_parser = xml_parser_create();
        xml_set_element_handler($xml_parser, $startElement, null);
        xml_parse($xml_parser, $xmlString, false);
        xml_parser_free($xml_parser); 

        $placeSetting = $this->em->getRepository('models\CtParsingTask')->find(11);
        $placeSetting->setDisabled();
        $placeSetting->save();
    }

    public function updateCurrency() {
        $xmlString = file_get_contents('http://service.coraltravel.ua/Accounting.asmx/CurrencyList');
        $scope = $this;
        $startElement = function($parser, $currentNodeName, $currentAttrs) use ($scope) {
            $this->checkpoint();
            if ($currentNodeName=='CURRENCY') {
                
                    $id = $currentAttrs['ID'];
                $code = $currentAttrs['NAME'];
                if (isset($currentAttrs['LNAME'])) {
                    $codeRu = $currentAttrs['LNAME'];
                }

                // does the entity reflection already exist in database?
                $ctCurrency = $scope->em->getRepository("models\CtCurrency")->findOneBy(array('ctCurrencyId' => $id));

                if(!$ctCurrency) {
                    $currency = new models\Currency;
                    $currency->setCode($code);
                    if (isset($codeRu)) {
                        $currency->setCodeRu($codeRu);
                    }
                    $currency->save();
                    // set coral travel id
                    $ctCurrency = new models\CtCurrency;
                    $ctCurrency->setCurrency($currency);
                    $ctCurrency->setCtCurrencyId($id);
                    $ctCurrency->save();
                }
            }
        };

        $xml_parser = xml_parser_create();
        xml_set_element_handler($xml_parser, $startElement, null);
        xml_parse($xml_parser, $xmlString, false);
        xml_parser_free($xml_parser); 

        $placeSetting = $this->em->getRepository('models\CtParsingTask')->find(12);
        $placeSetting->setDisabled();
        $placeSetting->save();
    }
}