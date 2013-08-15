<?php
namespace parser;
use models;

if (!defined("LOCK_START")) { echo "not allowed direct calling";  exit; }

Class CoralTravel extends Parser {

    private $soap;                  // SoapClient()
    public $em;                     // entity manager
    private $ctAgeGroupCache;       // cahe of age groups - we don't need to disturb our database for age group searching...
    private $ctFlightCache;         // cache of flights models\CtFlight
    private $ctTourScheduleCanEdit; // id's of tour schedule, what we can edin in current loop
    private $tourCache;             // cache of tours - the are only few elements...

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
            $xmlString = $this->soap->AreaList( array('destinationID' => $ctCountryId, 'regionID' => $CtRegion->getCtRegionId()) )->AreaListResult->any;
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
                $xmlString = $this->soap->PlaceList($params)->PlaceListResult->any;
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
        $xmlString = $this->soap->HotelCategoryGroupList()->HotelCategoryGroupListResult->any;
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
        $xmlString = $this->soap->HotelCategoryList()->HotelCategoryListResult->any;
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
        $xmlString = $this->soap->HotelDetailList()->HotelDetailListResult->any;

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

    public function parseXml() {

        /**
         *  get 3 next month numbers (max deep or future tours = 6, but now we need only 3)
         */
        $d = new \DateTime( 'now' );
        $monthNumbers = array();
        $i = 0;
        do {
            $monthNumbers[] = $d->format( 'm' );
            $d->modify( 'first day of next month' );
            $i++;
        } while ($i < 4); 
        
        /**
         * extract file list from found elements
         * @example $fileList[1][0] = "140_12_04_2013";  $fileList[3][0] = "15"; from "140_12_04_2013_15.xml.zip"
         */
        for ($attemts=0; $attemts < 5; $attemts++) { 
            $page = @file_get_contents("http://service.coraltravel.ua/XmlFiles/");
            if (!$page) {
                echo "cann't connect to server\r\n";
                $this->myLog(__LINE__, "cann't connect to server");
            } else {
                break;
            }
        }
        if (!$page) {
            echo "exit: cann't connect to server\r\n";
            $this->myLog(__LINE__, "cann't connect to server", 1);
        }

        $monthNumbers = implode('|', $monthNumbers);
        $departureCityKiev = 140;
        $pregFileLink = "/(?<=\"\/XmlFiles\/)(".$departureCityKiev."_\d+_(" . $monthNumbers . ")_\d{4}_)(\d+).xml.zip(?=\">)/";
        preg_match_all($pregFileLink, $page, $fileList);
        // var_dump($fileList[1]); exit;
        // var_dump($fileList[3]); exit;
        $fileList = array_combine($fileList[1], $fileList[3]);
        $result = array();
        foreach ($fileList as $key => $value) { 
            $result[] = $key . $value;
        }
        $fileList = $result;
        // var_dump($fileList); exit();
        unset($result);
        $ctParsedZips = $this->em->getRepository("models\CtParsedZip")->findAll();
        $ctParsedZips = models\dto\CtParsedZip::toFileList($ctParsedZips);

        // delete all files in xml-tmp folder
        $files = glob(_R . 'coraltravel_xml'. DIRECTORY_SEPARATOR .'*'); // get all file names
        foreach($files as $file){ // iterate files
          if(is_file($file)) {
            chmod($file, 0777);
            unlink($file); // delete file
          }
        }
        try {
            foreach ($fileList as $fileZip) {
                $fileZip = "$fileZip.xml.zip";
                if ( !in_array( $fileZip, $ctParsedZips ) )  {
                    $this->copyfile_chunked("http://service.coraltravel.ua/XmlFiles/$fileZip", _R . "coraltravel_xml" . DIRECTORY_SEPARATOR . $fileZip);
                    $zip = new \ZipArchive;
                    $res = $zip->open( _R . 'coraltravel_xml'. DIRECTORY_SEPARATOR . $fileZip);

                    if ($res === TRUE) {

                        $zip->extractTo("coraltravel_xml");
                        $zip->close();

                        chmod(_R . "coraltravel_xml". DIRECTORY_SEPARATOR . $fileZip, 0777);
                        unlink( _R . "coraltravel_xml". DIRECTORY_SEPARATOR . $fileZip );

                        $this->myLog(__LINE__, "began processing archive: $fileZip");
                        
                            foreach ( scandir("coraltravel_xml") as $fileXml ) {
                                $mem_usage = memory_get_usage(true);
                                if ( $fileXml!="." && $fileXml!=".." && preg_match("/.xml$/", $fileXml) ) {
                                    $xml = new \XMLReader;

                                    $res1 = $xml->open(_R . "coraltravel_xml". DIRECTORY_SEPARATOR . $fileXml);
                                    // fill AgeGroup
                                    if ($res1 === TRUE) {
                                        while ($xml->read()) {
                                            if ($xml->nodeType == $xml::ELEMENT) {
                                                if ($xml->name == 'a') {
                                                    $params = array('ad' => $xml->getAttribute('ad'),
                                                                    'cd' => $xml->getAttribute('cd'),
                                                                    'fmn' => $xml->getAttribute('fmn'),
                                                                    'fmx' => $xml->getAttribute('fmx'),
                                                                    'smn' => $xml->getAttribute('smn'),
                                                                    'smx' => $xml->getAttribute('smx'),
                                                                    'tmn' => $xml->getAttribute('tmn'),
                                                                    'tmx' => $xml->getAttribute('tmx'));

                                                    $cachedAgeGroup = $this->getAgeGroupFromCache($params);
                                                    if (!$cachedAgeGroup) {
                                                        // if AgeGroup is not found in cache (cache contains all Entities), we should to create new AgeGroup
                                                        $ctAgeGroupNew = new models\CtAgeGroup;
                                                        $ctAgeGroupNew = models\dto\CtAgeGroup::toEntity($ctAgeGroupNew, $params);
                                                        $this->ctAgeGroupCache[] = $ctAgeGroupNew;
                                                        $this->em->persist($ctAgeGroupNew);
                                                    }
                                                }
                                            }
                                        }
                                    }
                                    $this->em->flush();
                                    $xml->close();

                                    $res = $xml->open(_R . "coraltravel_xml". DIRECTORY_SEPARATOR . $fileXml);
                                    if ($res === TRUE) {
                                        $parsed = true;
                                        $flushCount = 0;
                                        while ($xml->read()) {
                                            if ($xml->nodeType == $xml::ELEMENT) {
                                                if ($xml->name == 'PackagePrices') {
                                                    
                                                    // destination Country
                                                    $destinationID = $xml->getAttribute('destinationID');
                                                    $q = $this->em
                                                        ->createQuery(
                                                            'SELECT ctc, c
                                                                from models\CtCountry ctc
                                                                left join ctc.country c
                                                                where ctc.ctCountryId = :destinationID')
                                                        ->setParameter('destinationID', $destinationID);

                                                    $ctCountry = $q->getOneOrNullResult($q::HYDRATE_ARRAY);
                                                    if (!$ctCountry) {
                                                        $this->myLog(__LINE__, 'not found country with id:' . $ctCountry['country']['id'], 1);
                                                    }
                                                    $desinationCountryId = $ctCountry['country']['id'];

                                                    // departure Place (city)
                                                    $fromAreaID = $xml->getAttribute('fromAreaID');
                                                    $ctArea = $this->em->getRepository('models\CtArea')->findOneBy(array('ctAreaId' => $fromAreaID));
                                                    if (!$ctArea) {
                                                        $this->myLog(__LINE__, "cann't find CtArea (fromAreaID) with ctAreaId: $fromAreaID", 1);
                                                        $this->enableTasks(array(CtParsingTask::ID_UPDATE_COUNTRY,
                                                                                 CtParsingTask::ID_UPDATE_REGION,
                                                                                 CtParsingTask::ID_UPDATE_AREA,
                                                                                 CtParsingTask::ID_UPDATE_PLACE));
                                                        $parsed = false;
                                                        break; // break current xml file parsing
                                                    }
                                                    $area = $ctArea->getArea();
                                                    $departureCity = $this->em->getRepository('models\Place')->findOneBy(array('area' => $area->getId()));

                                                    // currency
                                                    $ctCurrencyId = $xml->getAttribute('currency');
                                                    $ctCurrency = $this->em->getRepository('models\CtCurrency')->findOneBy(array('ctCurrencyId' => $ctCurrencyId));
                                                    $currency = $ctCurrency->getCurrency();

                                                    // $month = $xml->getAttribute('month');
                                                }

                                                // hotel id
                                                if ($xml->name == 'hn') {
                                                    // $this->memoryUsage("before hn flush");
                                                        echo "$flushCount\r\n";
                                                    // if ($flushCount > 10) {
                                                    //     $this->em->flush();
                                                    //     $this->em->clear();
                                                    //     $flushCount = 0;
                                                    // }
                                                    $flushCount++;
                                                    // $this->memoryUsage("after hn flush");

                                                    $ctHotelId = $xml->getAttribute('h');
                                                    $q = $this->em
                                                        ->createQuery('SELECT ct_h, h
                                                                        from models\CtHotel ct_h
                                                                            join ct_h.hotel h
                                                                            where ct_h.ctHotelId = :ctHotelId');
                                                    $q->setParameter('ctHotelId', $ctHotelId);
                                                    $ctHotel = $q->getOneOrNullResult($q::HYDRATE_ARRAY);
                                                    if (!$ctHotel) {
                                                        $this->myLog(__LINE__, "cann't find hotel id: $ctHotelId");
                                                        $this->enableTasks(array(CtParsingTask::ID_UPDATE_HOTEL_CATEGORY_GROUP,
                                                                                 CtParsingTask::ID_UPDATE_HOTEL_CATEGORY,
                                                                                 CtParsingTask::ID_UPDATE_HOTEL));
                                                        continue;
                                                    }
                                                    $hoteId = $ctHotel['hotel']['id'];
                                                }

                                                // room id
                                                if ($xml->name == 'rn') {
                                                    $ctRoomId = $xml->getAttribute('r');
                                                    $q = $this->em
                                                        ->createQuery('SELECT ct_r, r
                                                                        from models\CtRoom ct_r
                                                                            join ct_r.room r
                                                                            where ct_r.ctRoomId = :ctRoomId');
                                                    $q->setParameter('ctRoomId', $ctRoomId);
                                                    $ctRoom = $q->getOneOrNullResult($q::HYDRATE_ARRAY);
                                                    if (!$ctRoom) {
                                                        $this->myLog(__LINE__, "cann't find ctRoomId: $ctRoomId");
                                                        $this->enableTasks(array(CtParsingTask::ID_UPDATE_ROOM_CATEGORY,
                                                                                 CtParsingTask::ID_UPDATE_ROOM));
                                                        continue;
                                                    }
                                                    $roomId = $ctRoom['room']['id'];
                                                }

                                                // meal id
                                                if ($xml->name == 'mn') {

                                                    $ctMealId = $xml->getAttribute('m');
                                                    $q = $this->em
                                                        ->createQuery('SELECT ct_m, m
                                                                        from models\CtMeal ct_m
                                                                            join ct_m.meal m
                                                                            where ct_m.ctMealId = :ctMealId')
                                                        ->setParameter('ctMealId', $ctMealId);
                                                    $ctMeal = $q->getOneOrNullResult($q::HYDRATE_ARRAY);
                                                    if (!$ctMeal) {
                                                        $this->myLog(__LINE__, "cann't find ctMeal id: $ctMealId");
                                                        $this->enableTasks(array(CtParsingTask::ID_UPDATE_MEAL_CATEGORY,
                                                                                 CtParsingTask::ID_UPDATE_MEAL));
                                                        continue;
                                                    }
                                                    $mealId = $ctMeal['meal']['id'];

                                                    // alternative to merging :)
                                                    $departureCity = $this->em->getReference('models\Place', $departureCity->getId());
                                                    $currency = $this->em->getReference('models\Currency', $currency->getId());
                                                    $operator = $this->em->getReference('models\Operator', 1);
                                                    $meal = $this->em->getReference('models\Meal', $mealId);
                                                    $room = $this->em->getReference('models\Room', $roomId);
                                                    $hotel = $this->em->getReference('models\Hotel', $hoteId);

                                                    // tour record
                                                    $createParams = array('hotel' => $hotel,
                                                                          'departureCity' => $departureCity,
                                                                          'room' => $room,
                                                                          'meal' => $meal,
                                                                          'operator' => $operator,
                                                                          'currency' => $currency );

                                                    // $tour = $this->em->getRepository('models\Tour')->findOneBy($searchParams);
                                                    $tour = $this->provideTour($createParams);
                                                }
                                                // $tour = $this->em->getReference('models\Tour', $tour->getId());

                                                // ct_age_group id
                                                if ($xml->name == 'a') {
                                                    $params = array('ad' => $xml->getAttribute('ad'),
                                                                    'cd' => $xml->getAttribute('cd'),
                                                                    'fmn' => $xml->getAttribute('fmn'),
                                                                    'fmx' => $xml->getAttribute('fmx'),
                                                                    'smn' => $xml->getAttribute('smn'),
                                                                    'smx' => $xml->getAttribute('smx'),
                                                                    'tmn' => $xml->getAttribute('tmn'),
                                                                    'tmx' => $xml->getAttribute('tmx'));

                                                    $ctAgeGroup = $this->getAgeGroupFromCache($params);
                                                }

                                                // CtFlight & CtTourSchedule
                                                if ($xml->name == 'p') {
                                                    $this->checkpoint();

                                                    // provide ctFlight
                                                    $paramsCtFlight = array('tourBegin'         => new \DateTime($xml->getAttribute('tb')),
                                                                            'nights'            => $xml->getAttribute('n'),
                                                                            'departureFlightId' => $xml->getAttribute('d'),
                                                                            'returnFlightId'    => $xml->getAttribute('r'));

                                                    $ctFlight = $this->provideCtFlight($paramsCtFlight);
                                                    
                                                    // provide ctTourSchedule
                                                    // price per person
                                                    $price = floor($xml->getAttribute('pr') / ($params['ad'] + $params['cd']));
                                                    $paramsCtTourScheduleCreate = array('ctFlight'      => $ctFlight,
                                                                                        'price'         => $price,
                                                                                        'tour'          => $tour,
                                                                                        'ctAgeGroups'   => $ctAgeGroup->getId());

                                                    $ctTourSchedule = $this->provideCtTourSchedule($paramsCtTourScheduleCreate);
                                                }

                                            /**
                                             *  END AS
                                             */
                                            } elseif ($xml->nodeType == $xml::END_ELEMENT) {
                                                if ($xml->name == 'as') {
                                                    $this->ctTourScheduleCanEdit = array();
                                                }
                                            }
                                        }
                                        $this->em->flush();
                                        $xml->close();
                                        chmod(_R . "coraltravel_xml". DIRECTORY_SEPARATOR . $fileXml, 0777);
                                        unlink(_R . "coraltravel_xml". DIRECTORY_SEPARATOR . $fileXml);

                                        if($parsed) {
                                            $ctParsedZip = new models\CtParsedZip;
                                            $ctParsedZip->setFileZip($fileZip);
                                            $ctParsedZip->setUpdateTime(new \DateTime("now"));
                                            $ctParsedZip->save();
                                        }

                                        $this->myLog(__LINE__, "parsing of file: $fileXml ended");
                                    } else {
                                        $this->myLog(__LINE__, "error open res: " . __R . 'coraltravel_xml'.DIRECTORY_SEPARATOR.'$fileXml', 1);
                                    }
                                }
                            }
                    } else {
                        $this->myLog(__LINE__, 'error open res: ' . _R .'coraltravel_xml'.DIRECTORY_SEPARATOR.'$fileZip', 1);
                    }
               }
            }   
        } catch (Exception $e) {
            $this->myLog(__LINE__, $e->getMessage(), 1);
        }
    }

    public function getAgeGroupFromCache($params) {

        if (!isset($this->ctAgeGroupCache[0])) {
            $this->ctAgeGroupCache = $this->em->getRepository('models\CtAgeGroup')->findAll();
        }

        foreach ($this->ctAgeGroupCache as $ctAgeGroup) {
            if (
                    $ctAgeGroup->getAdultCount()        == $params['ad']   &&
                    $ctAgeGroup->getChildCount()        == $params['cd']   &&
                    $ctAgeGroup->getFirstChildMinAge()  == $params['fmn']  &&
                    $ctAgeGroup->getFirstChildMaxAge()  == $params['fmx']  &&
                    $ctAgeGroup->getSecondChildMinAge() == $params['smn']  &&
                    $ctAgeGroup->getSecondChildMaxAge() == $params['smx']  &&
                    $ctAgeGroup->getThirdChildMinAge()  == $params['tmn']  &&
                    $ctAgeGroup->getThirdChildMaxAge()  == $params['tmx']
                ) 
            {
                $ctAgeGroupFound = $this->em->getReference('models\CtAgeGroup', $ctAgeGroup->getId());
                return $ctAgeGroupFound;
            }
        }
    }

    /**
     * @param $params params for create CtTourSchedule
     */
    public function provideCtTourSchedule($params) {

        $q = $this->em->createQuery("SELECT ts FROM models\CtTourSchedule ts 
                                               JOIN ts.ctFlights f
                                               JOIN ts.tours t
                                     WHERE ts.price = :price
                                     AND ts.ctAgeGroups LIKE :ctAgeGroup")
                      ->setParameter('price', $params['price'])
                      ->setParameter('ctAgeGroup', '%' . $params['ctAgeGroups'] . '%');
        
        $ctTourSchedule = $this->searchScheduledInsertionCtTourSchedule($params['price']);

        if ($ctTourSchedule && in_array($ctTourSchedule, $this->ctTourScheduleCanEdit)) {
            $ctTourSchedule->setCtAgeGroup($params['ctAgeGroups']);
        } else {
            // $q::HYDRATE_ARRAY
            $ctTourSchedule = $q->getResult();
            if (isset($ctTourSchedule[0])) {
                $ctTourSchedule = $ctTourSchedule[0];
                // $ctTourSchedule = $this->em->find('models\CtTourSchedule', $ctTourScheduleArray['id']);
                $ctFlights = $ctTourSchedule->getCtFlights();
                if (!$ctFlights->contains($params['ctFlight'])) {
                    $ctTourSchedule->setCtFlight($params['ctFlight']);
                }
                $tours = $ctTourSchedule->getTours();
                if (!$tours->contains($params['tour'])) {
                    $ctTourSchedule->setTour($params['tour']);
                }
            } else {
                $ctTourSchedule = new models\CtTourSchedule;
                $ctTourSchedule = models\dto\CtTourSchedule::toEntity($ctTourSchedule, $params);
                $this->em->persist($ctTourSchedule);
                $this->ctTourScheduleCanEdit[] = $ctTourSchedule;
            }
        }
        return $ctTourSchedule;
    }

    public function provideCtFlight($params) {

        // init cache
        if (!isset($this->ctFlightCache[0])) {
            $q = $this->em->createQuery("SELECT ctf FROM models\CtFlight ctf")->setMaxResults(999);
            $this->ctFlightCache = $q->getResult($q::HYDRATE_ARRAY);
        }

        // at first search in scheduled for insert
        $ctFlight = $this->searchScheduledInsertionBy('models\CtFlight', $params);

        if (!$ctFlight) {
            // search in cache
            foreach ($this->ctFlightCache as $ctFlightEl) {
                if ( $ctFlightEl['tourBegin']->format("Y-m-d") == $params['tourBegin']->format("Y-m-d") &&
                     $ctFlightEl['nights']                     == $params['nights']                     &&
                     $ctFlightEl['departureFlightId']          == $params['departureFlightId']          &&
                     $ctFlightEl['returnFlightId']             == $params['returnFlightId']
                    )
                {
                    return $this->em->getReference('models\CtFlight', $ctFlightEl['id']);
                }
            }

            $ctFlight = $this->em->getRepository('models\CtFlight')->findOneBy($params);

            if (!$ctFlight) {
                $ctFlight = new models\CtFlight;
                $ctFlight = models\dto\CtFlight::toEntity($ctFlight, $params);
                $this->em->persist($ctFlight);
            } else {
                if (isset($this->ctFlightCache[1000])) {
                    array_shift($this->ctFlightCache);
                }
                $this->ctFlightCache[] = models\dto\CtFlight::toSimpleArray($ctFlight);
            }
        }
        return $ctFlight;
    }

    function memoryUsage($msg) {
        if(!isset($this->mem_usage))
            $this->mem_usage = memory_get_usage(true);
        if($this->mem_usage < memory_get_usage(true)) {
            $this->mem_usage = memory_get_usage(true);
            echo "$this->mem_usage  --  $msg\r\n";
        }
    }


    /**
     * Enable incoming tasks
     *
     * @param $tasks array()
     */
    public function enableTasks($tasks) {
        foreach ($tasks as $taskId) {
            $setting = $this->em->getRepository('models\CtParsingTask')->find($taskId);
            $this->em->persist($setting);
        }
        $this->em->flush();
    }

    public function searchScheduledInsertionBy($className, $props) {
        $scheduledEntityInsertions = $this->uof->getScheduledEntityInsertions();
        
        if (gettype($scheduledEntityInsertions) == 'array') {

            foreach ($scheduledEntityInsertions as $hash => $object) {

                if (get_class($object) == $className) {

                    foreach ($props as $propName => $value) {
                        $methodName = 'get' . ucfirst($propName);
                        if($object->$methodName() != $value) {
                            break;
                        }
                    }
                    return $object;
                }
            }
        }
    }

    public function searchScheduledInsertionCtTourSchedule($price) {
        $scheduledEntityInsertions = $this->uof->getScheduledEntityInsertions();
        
        if (gettype($scheduledEntityInsertions) == 'array') {

            foreach ($scheduledEntityInsertions as $hash => $object) {

                if (get_class($object) == 'models\CtTourSchedule') {

                    if ($object->getPrice() == $price) {
                        return $object;
                    }
                }
            }
        }
    }

    public function provideTour($params) {

        $searchParams = array('hotel'           => $params['hotel']->getId(),
                              'departureCity'   => $params['departureCity']->getId(),
                              'room'            => $params['room']->getId(),
                              'meal'            => $params['meal']->getId(),
                              'operator'        => $params['operator']->getId(),
                              'currency'        => $params['currency']->getId() );

        if(!isset($this->tourCache['0'])) {
            $this->tourCache = $this->em->getRepository('models\Tour')->findAll();
        }

        foreach ($this->tourCache as $tour) {

            if (
                 $tour->getDepartureCity()->getId()  == $params['hotel']->getId()  &&
                 $tour->getHotel()->getId()          == $params['departureCity']->getId()  &&
                 $tour->getMeal()->getId()           == $params['room']->getId()  &&
                 $tour->getOperator()->getId()       == $params['meal']->getId()  &&
                 $tour->getRoom()->getId()           == $params['operator']->getId()  &&
                 $tour->getCurrency()->getId()       == $params['currency']->getId()
                )
            {
                // $tour = $this->em->getReference('models\Tour', $tour->getId());
                return $tour;
            }
        }


        $tour = new models\Tour;
        $tour = models\dto\Tour::toEntity($tour, $params);
        $this->tourCache[] = $tour;
        // $tour->save();
        return $tour;
    }

    // function updateCtHotelRank() {
    //     $stmt = $this->conn->prepare("TRUNCATE TABLE `tour_parsing`.`ct_hotel_rank`");
    //     $stmt->execute();
    //     $stmt = $this->conn->prepare(
    //         'INSERT INTO ct_hotel_rank(hotel_id)
    //             SELECT hotel.id hotel_id
    //                 FROM hotel
    //                 INNER JOIN tour
    //                     ON tour.`hotel_id` = hotel.id AND tour.`operator_id` = 1
    //                 INNER JOIN tour_schedule
    //                     ON tour.`id` =  tour_schedule.`tour_id`
    //                 INNER JOIN `ct_age_group`
    //                     ON ct_age_group.`id` = tour.`age_group` AND ct_age_group.`ad` = 2 AND ct_age_group.cd = 0
    //             GROUP BY hotel.id
    //             ORDER BY price'
    //     );
    //     $stmt->execute();
    // }
}

// $stmt = $this->conn->prepare(
//     'DELETE FROM tour_schedule 
//         where tour_id IN (
//             select id from tour
//                 where departure_place_id = :departure_place_id
//                 and desination_country_id = :desination_country_id
//                 and operator_id = 1
//         )
//         AND MONTH(tour_begin) = :tour_begin_month
//     ');
// // we can delete only older parsed info!
// $stmt->bindValue('departure_place_id', $departureCity->getId());
// $stmt->bindValue('desination_country_id', $desinationCountryId);
// $stmt->bindValue('tour_begin_month', $month);
// $stmt->execute();
// $stmt = $this->conn->prepare('DELETE FROM tour WHERE id NOT IN (SELECT DISTINCT tour_id FROM tour_schedule) AND operator_id = 1');
// $stmt->execute();