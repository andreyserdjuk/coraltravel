<?php
namespace parser;
use models;

if (!defined("LOCK_START")) { echo "not allowed direct calling";  exit; }

Class CoralTravelXML extends Parser {

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
                            $as = 1;
                            $insertCounter = 1;
                            if ($res === TRUE) {
                                while ($xml->read()) {
                                    if ($xml->nodeType == $xml::ELEMENT) {
                                        
                                        if ($xml->name == 'PackagePrices') {
                                            $vars = $this->firstStepTree($xml);
                                            if (!$vars) {
                                                $parsed = false;
                                                break;
                                            } else $parsed = true;
                                            $currency = $vars['currency'];
                                            $departureCity = $vars['departureCity'];
                                        }

                                        // hotel id
                                        if ($xml->name == 'hn') {
                                            $hotel = $this->provideHotel($xml);
                                            if (!$hotel)
                                                continue;
                                        }

                                        // room id
                                        if ($xml->name == 'rn') {
                                            $room = $this->provideRoom($xml);
                                            if (!$room)
                                                continue;
                                        }

                                        if ($xml->name == 'mn') {
                                            $meal = $this->provideMeal($xml);
                                            if (!$meal)
                                                continue;

                                            $accomodationCreateParams = array('hotel' => $hotel,
                                                                  'departureCity' => $departureCity,
                                                                  'room' => $room,
                                                                  'meal' => $meal,
                                                                  'operator' => $operator,
                                                                  'currency' => $currency );
                                            // + build accomodation cache
                                            $accomodation = $this->provideAccomodation($accomodationCreateParams);
                                            // $flightsAppendCache = array();
                                        }

                                        if ($xml->name == 'as') {
                                            $as++;

                                            if ($insertCounter > 100) {
                                                $this->doInserts($ass);
                                                echo "-i-";
                                                $insertCounter = 0;
                                                $ass = array();
                                            }
                                            $insertCounter++;
                                        }

                                        if ($xml->name == 'a') {
                                            $this->checkpoint();
                                            $createParams = array('ad' => $xml->getAttribute('ad'),
                                                            'cd' => $xml->getAttribute('cd'),
                                                            'fmn' => $xml->getAttribute('fmn'),
                                                            'fmx' => $xml->getAttribute('fmx'),
                                                            'smn' => $xml->getAttribute('smn'),
                                                            'smx' => $xml->getAttribute('smx'),
                                                            'tmn' => $xml->getAttribute('tmn'),
                                                            'tmx' => $xml->getAttribute('tmx'));
                                            $ctAgeGroup = $this->getCtAgeGroupFromCache($createParams);
                                            if (!$ctAgeGroup) {
                                                // if AgeGroup is not found in cache (cache contains all Entities), we should to create new AgeGroup
                                                $ctAgeGroup = new models\CtAgeGroup;
                                                $ctAgeGroup = models\dto\CtAgeGroup::toEntity($ctAgeGroup, $createParams);
                                                $this->ctAgeGroupCache[] = $ctAgeGroup;
                                                $this->em->persist($ctAgeGroup);
                                            }
                                        }

                                        // CtFlight & CtTourSchedule
                                        if ($xml->name == 'p') {

                                            // provide ctFlight
                                            $ctFlight = $this->provideCtFlight($xml);

                                            // price per person
                                            $price = floor($xml->getAttribute('pr') / ($ctAgeGroup->getAdultCount() + $ctAgeGroup->getChildCount()));

                                            $ass[$as][$price]['ctFlight'][spl_object_hash($ctFlight)] = $ctFlight;
                                            $ass[$as][$price]['accomodation'] = $accomodation;
                                            $ass[$as][$price]['ctAgeGroups'][spl_object_hash($ctAgeGroup)] = $ctAgeGroup;
                                        }
                                    }
                                }
                                    
                                $xml->close();

                                if($parsed) {
                                    $this->doInserts($ass);
                                    echo "last insert";
                                    $ctParsedZip = new models\CtParsedZip;
                                    $ctParsedZip->setFileZip($fileZip);
                                    $ctParsedZip->setUpdateTime(new \DateTime("now"));
                                    $ctParsedZip->save();
                                }

                                chmod(_R . "coraltravel_xml". DIRECTORY_SEPARATOR . $fileXml, 0777);
                                unlink(_R . "coraltravel_xml". DIRECTORY_SEPARATOR . $fileXml);
                            }

                            $this->myLog(__LINE__, "parsing of file: $fileXml ended");
                            // exit;
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

    public function provideCtFlight($xml) {

        $params = array('tourBegin'         => new \DateTime($xml->getAttribute('tb')),
                        'nights'            => $xml->getAttribute('n'),
                        'departureFlightId' => $xml->getAttribute('d'),
                        'returnFlightId'    => $xml->getAttribute('r'));
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
        if (!isset($this->accomodationsIndexedCache) || $this->departureCityFlag != $departureCity) {
            $this->departureCityFlag = $departureCity;
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

    public function provideHotel($xml) {
        // cache init
        if (!isset($this->ctHotelIndexedCache[0])) {
            $this->ctHotelIndexedCache[0] = 1;

            $q = $this->em->createQuery('SELECT ct_h, h from models\CtHotel ct_h join ct_h.hotel h');
            $ctHotels = $q->getResult($q::HYDRATE_ARRAY);
            if(isset($ctHotels[0])) {
                foreach ($ctHotels as $row) {
                    $this->ctHotelIndexedCache[$row['ctHotelId']] = $row['hotel']['id'];
                }
            }
        }

        $ctHotelId = $xml->getAttribute('h');
        
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

    public function provideMeal($xml) {
        // cache init
        if (!isset($this->ctMealIndexedCache[0])) {
            $this->ctMealIndexedCache[0] = 1;
            $q = $this->em->createQuery('SELECT ct_m, m from models\CtMeal ct_m join ct_m.meal m');
            $ctMeals = $q->getResult($q::HYDRATE_ARRAY);
            if(isset($ctMeals[0])) {
                foreach ($ctMeals as $row) {
                    $this->ctMealIndexedCache[$row['ctMealId']] = $row['meal']['id'];
                }
            }
        }
        
        $ctMealId = $xml->getAttribute('m');

        if (isset($this->ctMealIndexedCache[$ctMealId])) {
            $mealId = $this->ctMealIndexedCache[$ctMealId];
            return $this->em->getReference('models\Meal', $mealId);
        } else {
            $this->myLog(__LINE__, "cann't find ctMeal id: $ctMealId");
            $this->enableTasks(array(CtParsingTask::ID_UPDATE_MEAL_CATEGORY,
                                     CtParsingTask::ID_UPDATE_MEAL));
        }
    }

    public function provideRoom($xml) {
        // cache init
        if (!isset($this->ctRoomIndexedCache[0])) {
            $this->ctRoomIndexedCache[0] = 1;
            $q = $this->em->createQuery('SELECT ct_r, r from models\CtRoom ct_r join ct_r.room r');
            $ctRooms = $q->getResult($q::HYDRATE_ARRAY);
            if(isset($ctRooms[0])) {
                foreach ($ctRooms as $row) {
                    $this->ctRoomIndexedCache[$row['ctRoomId']] = $row['room']['id'];
                }
            }
        }

        $ctRoomId = $xml->getAttribute('r');

        if (isset($this->ctRoomIndexedCache[$ctRoomId])) {
            $roomId = $this->ctRoomIndexedCache[$ctRoomId];
            return $this->em->getReference('models\Room', $roomId);
        } else {
            $this->myLog(__LINE__, "cann't find ctRoomId: $ctRoomId");
            $this->enableTasks(array(CtParsingTask::ID_UPDATE_ROOM_CATEGORY,
                                     CtParsingTask::ID_UPDATE_ROOM));
        }
    }

    public function firstStepTree($xml) {
        // destination Country
        // $destinationID = $xml->getAttribute('destinationID');
        // $q = $this->em
        //     ->createQuery(
        //         'SELECT ctc, c
        //             from models\CtCountry ctc
        //             left join ctc.country c
        //             where ctc.ctCountryId = :destinationID')
        //     ->setParameter('destinationID', $destinationID);

        // $ctCountry = $q->getOneOrNullResult($q::HYDRATE_ARRAY);
        // if (!$ctCountry) {
        //     $this->myLog(__LINE__, 'not found country with id:' . $ctCountry['country']['id'], 1);
        // }
        // $desinationCountryId = $ctCountry['country']['id'];

        // departure Place (city)
        $fromAreaID = $xml->getAttribute('fromAreaID');
        $ctArea = $this->em->getRepository('models\CtArea')->findOneBy(array('ctAreaId' => $fromAreaID));
        if (!$ctArea) {
            $this->myLog(__LINE__, "cann't find CtArea (fromAreaID) with ctAreaId: $fromAreaID", 1);
            $this->enableTasks(array(CtParsingTask::ID_UPDATE_COUNTRY,
                                     CtParsingTask::ID_UPDATE_REGION,
                                     CtParsingTask::ID_UPDATE_AREA,
                                     CtParsingTask::ID_UPDATE_PLACE));
            return false; // break current xml file parsing
        }
        $area = $ctArea->getArea();
        $departureCity = $this->em->getRepository('models\Place')->findOneBy(array('area' => $area->getId()));

        // currency
        $ctCurrencyId = $xml->getAttribute('currency');
        $ctCurrency = $this->em->getRepository('models\CtCurrency')->findOneBy(array('ctCurrencyId' => $ctCurrencyId));
        $currency = $ctCurrency->getCurrency();

        return array('currency'          => $currency,
                     'departureCity'     => $departureCity);
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
        
        foreach ($ass as $priceArr) {
            foreach ($priceArr as $price => $param) {  

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
    }
}