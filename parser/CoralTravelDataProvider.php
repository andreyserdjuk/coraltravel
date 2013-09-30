<?php
namespace parser;
use models;

if (!defined("LOCK_START")) { echo "not allowed direct calling";  exit; }

Class CoralTravelDataProvider extends Parser {

    public $em;                             // entity manager
    private $ctAgeGroupCache;               // cahe of age groups - we don't need to disturb our database for age group searching...
    private $ctFlightCache;                 // cache of flights models\CtFlight
    private $ctFlightIndexedCache;          // cache of ctFlight - for fast search
    private $ctHotelIndexedCache;           // cache of ctHotel - for fast search
    private $departureCityFlag;
    private $conn;                          // connection

    public function __construct() {
        parent::__construct();
        $this->conn = $this->em->getConnection();
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
        } while ($i < 3); 
        
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

                            $operator = $this->em->getReference('models\Operator', 1);

                            /**
                             *  fill CtAgeGroup, CtAgeGroupBundle
                             */
                            $xml = new \XMLReader;
                            $res = $xml->open(_R . "coraltravel_xml". DIRECTORY_SEPARATOR . $fileXml);
                        }
                    }
                } else {
                    $this->myLog(__LINE__, 'error open res: ' . _R .'coraltravel_xml'.DIRECTORY_SEPARATOR.$fileZip, 1);
                }
            }
        }   

    }

    public function getCtAgeGroupFromCache($params) {
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
                return $ctAgeGroup;
            }
        }
    }

    public function provideCtFlight($tourBeginDate, $nights, $departureFlightId, $returnFlightId) {

        $params = array('tourBegin'         => $tourBeginDate,
                        'nights'            => $nights,
                        'departureFlightId' => $departureFlightId,
                        'returnFlightId'    => $returnFlightId);
        // init cache
        if (!isset($this->ctFlightIndexedCache[0])) {
            $this->ctFlightIndexedCache[0] = 1;
            $ctFlights = $this->em->getRepository('models\CtFlight')->findAll();
            foreach ($ctFlights as $ctf) {
                $nights = $ctf->getNights();
                $departureFlightId = $ctf->getDepartureFlightId();
                $returnFlightId = $ctf->getReturnFlightId();
                $this->ctFlightIndexedCache[$nights][$departureFlightId][$returnFlightId] = $ctf;
            }
        }

        if (isset($this->ctFlightIndexedCache[$params['nights']][$params['departureFlightId']][$params['returnFlightId']])) {
            $ctFlight = $this->ctFlightIndexedCache[$params['nights']][$params['departureFlightId']][$params['returnFlightId']];
        } else {
            $ctFlight = new models\CtFlight;
            $ctFlight = models\dto\CtFlight::toEntity($ctFlight, $params);
            $this->ctFlightIndexedCache[$params['nights']][$params['departureFlightId']][$params['returnFlightId']] = $ctFlight;
            $this->em->persist($ctFlight);
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

    public function provideAccomodation($params) {
        $operator = $params['operator']->getId();
        $currency = $params['currency']->getId();
        $departureCity = $params['departureCity']->getId();
        $hotel = $params['hotel']->getId();
        $room = $params['room']->getId();
        $meal = $params['meal']->getId();

        // cache init
        if (!isset($this->accomodationsIndexedCache)) {
            $accomodations = $this->em->getRepository('models\Accomodation')->findBy(array('operator' => $params['operator'], 'departureCity' => $params['departureCity']));
            if(isset($accomodations[0])) {
                $this->accomodationsIndexedCache = array();
                foreach ($accomodations as $accomodation) {
                    $operator = $accomodation->getOperator()->getId();
                    $currency = $accomodation->getCurrency()->getId();
                    $departureCity = $accomodation->getDepartureCity()->getId();
                    $hotel = $accomodation->getHotel()->getId();
                    $room = $accomodation->getRoom()->getId();
                    $meal = $accomodation->getMeal()->getId();
                    $this->accomodationsIndexedCache[$currency][$departureCity][$hotel][$room][$meal] = $accomodation;
                }
            }
        }

        if (isset($this->accomodationsIndexedCache[$currency][$departureCity][$hotel][$room][$meal])) {
            $accomodation = $this->accomodationsIndexedCache[$currency][$departureCity][$hotel][$room][$meal];
        } else {
            $accomodation = new models\Accomodation;
            $accomodation = models\dto\Accomodation::toEntity($accomodation, $params);
            $this->em->persist($accomodation);
            $this->accomodationsIndexedCache[$currency][$departureCity][$hotel][$room][$meal] = $accomodation;
        }
        return $accomodation;
    }

    public function provideHotelFromXml($xml) {
        $ctHotelId = $xml->getAttribute('h');
        return $this->provideHotel($ctHotelId);
    }

    public function activateHotelCache() {
        // cache init
        $q = $this->em->createQuery('SELECT ct_h, h from models\CtHotel ct_h join ct_h.hotel h');
        $ctHotels = $q->getResult($q::HYDRATE_ARRAY);
        if(isset($ctHotels[0])) {
            foreach ($ctHotels as $row) {
                $this->ctHotelIndexedCache[$row['ctHotelId']] = $row['hotel']['id'];
            }
        }
    }

    public function getHotelFromCache($ctHotelId) {
        
        if (isset($this->ctHotelIndexedCache[$ctHotelId])) {
            $hoteId = $this->ctHotelIndexedCache[$ctHotelId];
            return $this->em->getReference('models\Hotel', $hoteId);
        } else {
            $this->myLog(__LINE__, "cann't find hotel id: $ctHotelId");
            $this->enableTasks(array(CtParsingTask::ID_UPDATE_HOTEL_CATEGORY_GROUP,
                                     CtParsingTask::ID_UPDATE_HOTEL_CATEGORY,
                                     CtParsingTask::ID_UPDATE_HOTEL));
        }
    }

    public function provideHotel($ctHotelId) { 
       $q = $this->em->createQuery('SELECT ct_h, h from models\CtHotel ct_h join ct_h.hotel h WHERE ct_h = :ct_hotel_id');
        $q->setParameter('ct_hotel_id', $ctHotelId);
        $ctHotel = $q->getOneOrNullResult($q::HYDRATE_ARRAY);
        if (isset($ctHotel['hotel']['id'])) {
            $hotelId = $ctHotel['hotel']['id'];
            return $this->em->getReference('models\Hotel', $hotelId);
        } else {
            $this->myLog(__LINE__, "cann't find hotel id: $ctHotelId");
            $this->enableTasks(array(CtParsingTask::ID_UPDATE_HOTEL_CATEGORY_GROUP,
                                     CtParsingTask::ID_UPDATE_HOTEL_CATEGORY,
                                     CtParsingTask::ID_UPDATE_HOTEL));
        }
    }

    public function provideMealFromXml($xml) {
        
        $ctMealId = $xml->getAttribute('m');
        return $this->provideMeal($ctMealId);
    }

    public function activateMealCache() {
        // cache init
        $q = $this->em->createQuery('SELECT ct_m, m from models\CtMeal ct_m join ct_m.meal m');
        $ctMeals = $q->getResult($q::HYDRATE_ARRAY);
        if(isset($ctMeals[0])) {
            foreach ($ctMeals as $row) {
                $this->ctMealIndexedCache[$row['ctMealId']] = $row['meal']['id'];
            }
        }
    }

    public function getMealFromCache($ctMealId) {
        if (isset($this->ctMealIndexedCache[$ctMealId])) {
            $mealId = $this->ctMealIndexedCache[$ctMealId];
            return $this->em->getReference('models\Meal', $mealId);
        } else {
            $this->myLog(__LINE__, "cann't find ctMeal id: $ctMealId");
            $this->enableTasks(array(CtParsingTask::ID_UPDATE_MEAL_CATEGORY,
                                     CtParsingTask::ID_UPDATE_MEAL));
        }
    }

    public function provideMeal($ctMealId) {
        $q = $this->em->createQuery('SELECT ct_m, m from models\CtMeal ct_m join ct_m.meal m WHERE ct_m = :ctMealId');
        $q->setParameter('ctMealId', $ctMealId);
        $ctMeal = $q->getOneOrNullResult($q::HYDRATE_ARRAY);
        if (isset($ctMeal['meal']['id'])) {
            $mealId = $ctMeal['meal']['id'];
            return $this->em->getReference('models\Meal', $mealId);
        } else {
            $this->myLog(__LINE__, "cann't find ctMeal id: $ctMealId");
            $this->enableTasks(array(CtParsingTask::ID_UPDATE_MEAL_CATEGORY,
                                     CtParsingTask::ID_UPDATE_MEAL));
        }
    }

    public function provideRoomFromXml($xml) {

        $ctRoomId = $xml->getAttribute('r');
        return $this->provideRoom($ctRoomId);
    }

    public function activateRoomCache() {
        // cache init
        $q = $this->em->createQuery('SELECT ct_r, r from models\CtRoom ct_r join ct_r.room r');
        $ctRooms = $q->getResult($q::HYDRATE_ARRAY);
        if(isset($ctRooms[0])) {
            foreach ($ctRooms as $row) {
                $this->ctRoomIndexedCache[$row['ctRoomId']] = $row['room']['id'];
            }
        }
    }

    public function getRoomFromCache($ctRoomId) {
        if (isset($this->ctRoomIndexedCache[$ctRoomId])) {
            $roomId = $this->ctRoomIndexedCache[$ctRoomId];
            return $this->em->getReference('models\Room', $roomId);
        } else {
            $this->myLog(__LINE__, "cann't find ctRoomId: $ctRoomId");
            $this->enableTasks(array(CtParsingTask::ID_UPDATE_ROOM_CATEGORY,
                                     CtParsingTask::ID_UPDATE_ROOM));
        }
    }

    public function provideRoom($ctRoomId) {
        $q = $this->em->createQuery('SELECT ct_r, r from models\CtRoom ct_r join ct_r.room r WHERE ct_r = :ctRoomId');
        $q->setParameter('ctRoomId', $ctRoomId);
        $ctRoom = $q->getOneOrNullResult($q::HYDRATE_ARRAY);
        if (isset($ctRoom['room']['id'])) {
            $roomId = $ctRoom['room']['id'];
            return $this->em->getReference('models\Room', $roomId);
        } else {
            $this->myLog(__LINE__, "cann't find ctRoomId: $ctRoomId");
            $this->enableTasks(array(CtParsingTask::ID_UPDATE_ROOM_CATEGORY,
                                     CtParsingTask::ID_UPDATE_ROOM));
        }
    }

    public function provideDepartureCity($fromAreaID) {
        // departure Place (city)
         // = $xml->getAttribute('fromAreaID');
        $ctArea = $this->em->getRepository('models\CtArea')->findOneBy(array('ctAreaId' => $fromAreaID));
        if (!$ctArea) {
            $this->myLog(__LINE__, "cann't find CtArea (fromAreaID) with ctAreaId: $fromAreaID", 1);
            $this->enableTasks(array(CtParsingTask::ID_UPDATE_COUNTRY,
                                     CtParsingTask::ID_UPDATE_REGION,
                                     CtParsingTask::ID_UPDATE_AREA,
                                     CtParsingTask::ID_UPDATE_PLACE));
        } else {
            $area = $ctArea->getArea();
            $departureCity = $this->em->getRepository('models\Place')->findOneBy(array('area' => $area->getId()));

            return $departureCity;
        }
    }

    public function provideCurrency($ctCurrencyId) {
        // currency
        $ctCurrency = $this->em->getRepository('models\CtCurrency')->findOneBy(array('ctCurrencyId' => $ctCurrencyId));
        $currency = $ctCurrency->getCurrency();
        return $currency;
    }

    public function getCtAgeGroupBundleFromCache($ctAgeGroups) {
        if (!isset($this->ctAgeGroupBundleCache)) {
            $q = $this->em->createQuery("select agb, ag from models\CtAgeGroupBundle agb join agb.ctAgeGroups ag");
            $this->ctAgeGroupBundleCache = $q->getResult();
        }
        if (is_array($this->ctAgeGroupBundleCache)) {
            foreach ($this->ctAgeGroupBundleCache as $ctAgeGroupBundle) {
                $found = TRUE;
                foreach ($ctAgeGroupBundle->getCtAgeGroups() as $ctAgeGroup) {
                    if (!in_array($ctAgeGroup, $ctAgeGroups)) {
                        $found = FALSE;
                    }
                }
                if ($found) {
                    return $ctAgeGroupBundle;
                }
            }
        }
    }

    public function getCtFlightBundleFromCache($ctFlights) {
        if (!isset($this->ctFlightBundleCache)) {
            $q = $this->em->createQuery("select ctf, f from models\CtFlightBundle ctf join ctf.ctFlights f");
            $this->ctFlightBundleCache = $q->getResult();
        }
        if (is_array($this->ctFlightBundleCache)) {
            foreach ($this->ctFlightBundleCache as $ctFlightBundle) {
                $found = TRUE;
                foreach ($ctFlightBundle->getCtFlights() as $ctFlight) {
                    if (!in_array($ctFlight, $ctFlights)) {
                        $found = FALSE;
                    }
                }
                if ($found) {
                    return $ctFlightBundle;
                }
            }
        }
    }

    public function doInserts($ass) {
        foreach ($ass as $as => $priceArr) {
            foreach ($priceArr as $price => $param) {
                $ctAgeGroupBundle = $this->getCtAgeGroupBundleFromCache($param['ctAgeGroups']);
                if (!$ctAgeGroupBundle) {
                    $ctAgeGroupBundle = new models\CtAgeGroupBundle;
                    foreach ($param['ctAgeGroups'] as $ctAgeGroup) {
                        $ctAgeGroupBundle->setCtAgeGroup($ctAgeGroup);
                    }
                    $this->em->persist($ctAgeGroupBundle);
                    $this->ctAgeGroupBundleCache[] = $ctAgeGroupBundle;
                }
                unset($ass[$as][$price]['ctAgeGroups']);
                $ass[$as][$price]['ctAgeGroupBundle'] = $ctAgeGroupBundle;

                $ctFlightBundle = $this->getCtFlightBundleFromCache($param['ctFlight']);
                if (!$ctFlightBundle) {
                    $ctFlightBundle = new models\CtFlightBundle;
                    foreach ($param['ctFlight'] as $ctFlight) {
                        $ctFlightBundle->setCtFlight($ctFlight);
                    }
                    $this->em->persist($ctFlightBundle);
                    $this->ctFlightBundleCache[] = $ctFlightBundle;
                }
                unset($ass[$as][$price]['ctFlight']);
                $ass[$as][$price]['ctFlightBundle'] = $ctFlightBundle;
            }

        }

        $this->em->flush();

        // $priceArr = current($ass);
        // $param = current($priceArr);
        // $ctPrices = $this->em->getRepository('models\CtPrice')->findBy(array('accomodation' => $param['accomodation']));
        // foreach ($ctPrices as $ctPrice) {
        //     $ctPricesCache[$ctPrice->getAccomodation()->getId()][$ctPrice->getCtFlightBundle()->getId()][$ctPrice->getCtAgeGroupBundle()->getId()] = $ctPrice->getPrice();
        // }
        $pCountInsert = 0;
        foreach ($ass as $priceArr) {
            foreach ($priceArr as $price => $param) {  
                $pCountInsert++;
                // $doInsert = true;
                // foreach ($ctPrices as $ctPrice) {
                //     if ( isset($ctPricesCache[$param['accomodation']->getId()][$param['ctFlightBundle']->getId()][$param['ctAgeGroupBundle']->getId()])     &&
                //          $ctPricesCache[$param['accomodation']->getId()][$param['ctFlightBundle']->getId()][$param['ctAgeGroupBundle']->getId()] == $price
                //         )
                //     {
                //         $doInsert = false;
                //         break;
                //     }
                // }

                // if ($doInsert) {
                    if (!isset($query)) {
                        $query = 'insert into ct_price(price, accomodation, ct_age_group_bundle, ct_flight_bundle_id) 
                                    values('.$price.','.$param['accomodation']->getId().','.$param['ctAgeGroupBundle']->getId().','.$param['ctFlightBundle']->getId().')';
                    } else {
                        $query .= ',('.$price.','.$param['accomodation']->getId().','.$param['ctAgeGroupBundle']->getId().','.$param['ctFlightBundle']->getId().')';
                    }
                // }  
            }
        }
        if ($query) {
            $this->conn->executeQuery($query); 
        }
        return $pCountInsert;
    }
}