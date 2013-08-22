<?php
namespace parser;
use models;

if (!defined("LOCK_START")) { echo "not allowed direct calling";  exit; }

Class CoralTravel extends Parser {

    private $soap;                          // SoapClient()
    public $em;                             // entity manager
    private $ctAgeGroupCache;               // cahe of age groups - we don't need to disturb our database for age group searching...
    private $ctFlightCache;                 // cache of flights models\CtFlight
    private $tourCache;                     // cache of tours - the are only few elements...
    private $ctTourScheduleIndexedCache;    // cache of ctTourSchedule - for fast search
    private $ctFlightIndexedCache;          // cache of ctFlight - for fast search
    private $ctTourIndexedCache;            // cache of ctFlight - for fast search
    private $ctHotelIndexedCache;           // cache of ctHotel - for fast search
    private $departureCityFlag;

    public function __construct() {
        parent::__construct();
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

                                    $operator = $this->em->getReference('models\Operator', 1);

                                    /**
                                     *  fill CtAgeGroup, CtAgeGroupBundle
                                     */
                                    $xml = new \XMLReader;
                                    $res = $xml->open(_R . "coraltravel_xml". DIRECTORY_SEPARATOR . $fileXml);
                                    if ($res === TRUE) {
                                        while ($xml->read()) {
                                            if ($xml->nodeType == $xml::ELEMENT) {
                                                
                                                if ($xml->name == 'PackagePrices') {
                                                    $vars = $this->firstStepTree($xml);            
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

                                                    $prices[$price]['ctFlight'][spl_object_hash($ctFlight)] = $ctFlight;
                                                    $accomodation
                                                    $prices[$price]['accomodation'] = $accomodation;
                                                    $prices[$price]['ctAgeGroups'][] = $ctAgeGroup;
                                                }
                                            }

                                            /**
                                             *  END AS
                                             */
                                            } elseif ($xml->nodeType == $xml::END_ELEMENT) {
                                                if ($xml->name == 'as') {
                                                    foreach ($prices as $price => $params) {
                                                        $ctAgeGroupBundle = $this->getCtAgeGroupBundleFromCache($params['ctAgeGroups']);
                                                        if (!$ctAgeGroupBundle) {
                                                            $ctAgeGroupBundle = new models\$ctAgeGroupBundle;
                                                            foreach ($params['ctAgeGroups'] as $ctAgeGroup) {
                                                                $ctAgeGroupBundle->setCtAgeGroup($ctAgeGroup);
                                                            }
                                                        }
                                                    }
                                                    $prices = array();
                                                }
                                            }
                                        }
                                    }
                                    $this->em->flush();
                                    $xml->close();






                                    // fill CtFlight
                                    $res = $xml->open(_R . "coraltravel_xml". DIRECTORY_SEPARATOR . $fileXml);
                                    if ($res === TRUE) {
                                        while ($xml->read()) {
                                            if ($xml->nodeType == $xml::ELEMENT) {
                                                if ($xml->name == 'p') {
                                                    $this->checkpoint();
                                                    $ctFlight = $this->provideCtFlight($xml);
                                                }
                                            }
                                        }
                                    }
                                    $this->em->flush();
                                    $xml->close();


                                    // fill tour
                                    $res = $xml->open(_R . "coraltravel_xml". DIRECTORY_SEPARATOR . $fileXml);
                                    if ($res === TRUE) {
                                        while ($xml->read()) {
                                            if ($xml->nodeType == $xml::ELEMENT) {
                                                
                                                if ($xml->name == 'PackagePrices') {
                                                    $vars = $this->firstStepTree($xml);            
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

                                                    // tour record
                                                    $createParams = array('hotel' => $hotel,
                                                                          'departureCity' => $departureCity,
                                                                          'room' => $room,
                                                                          'meal' => $meal,
                                                                          'operator' => $operator,
                                                                          'currency' => $currency );

                                                    $tour = $this->provideAccomodation($createParams);
                                                }
                                            }
                                        }
                                    }
                                    $this->em->flush();
                                    $xml->close();

                                    /**
                                     *  Insert relations
                                     */
                                    $count = 0;
                                    $res = $xml->open(_R . "coraltravel_xml". DIRECTORY_SEPARATOR . $fileXml);
                                    if ($res === TRUE) {

                                        // init ctTourScheduleIndexedCache
                                        $ctTourSchedules = $this->em->getRepository('models\CtTourSchedule')->findAll();
                                        if (isset($ctTourSchedules[0])) {
                                            foreach ($ctTourSchedules as $ctTourScheduleDB) {
                                                $this->ctTourScheduleIndexedCache[$ctTourScheduleDB->getPrice()][$ctTourScheduleDB->getCtAgeGroupsJson()] = $ctTourScheduleDB;
                                            }
                                        }

                                        $parsed = true;
                                        while ($xml->read()) {
                                            if ($xml->nodeType == $xml::ELEMENT) {
                                                if ($xml->name == 'PackagePrices') {
                                                    // $vars = $this->firstStepTree($xml);            
                                                    // $currency = $vars['currency'];
                                                    // $departureCity = $vars['departureCity'];
                                                }

                                                // hotel id
                                                if ($xml->name == 'hn') {
                                                    echo "hn $count\r\n"; $count++;
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

                                                // meal id
                                                if ($xml->name == 'mn') {
                                                    $meal = $this->provideMeal($xml);
                                                    if (!$meal)
                                                        continue;

                                                    // tour record
                                                    $createParams = array('hotel' => $hotel,
                                                                          'departureCity' => $departureCity,
                                                                          'room' => $room,
                                                                          'meal' => $meal,
                                                                          'operator' => $operator,
                                                                          'currency' => $currency );

                                                    $tour = $this->provideAccomodation($createParams);
                                                }

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
                                                    $ctFlight = $this->provideCtFlight($xml);
                                                    
                                                    // provide ctTourSchedule
                                                    // price per person
                                                    $price = floor($xml->getAttribute('pr') / ($ctAgeGroup->getAdultCount() + $ctAgeGroup->getChildCount()));

                                                    $paramsCtTourSchedule[$price]['ctFlight'][spl_object_hash($ctFlight)] = $ctFlight;
                                                    $paramsCtTourSchedule[$price]['tour'] = $tour;
                                                    $paramsCtTourSchedule[$price]['ctAgeGroups'][] = $ctAgeGroup->getId();
                                                }

                                            /**
                                             *  END AS
                                             */
                                            } elseif ($xml->nodeType == $xml::END_ELEMENT) {
                                                if ($xml->name == 'as') {
                                                    foreach ($paramsCtTourSchedule as $price => $params) {
                                                        $ctAgeGroupsJson = json_encode($params['ctAgeGroups']);
                                                        if (isset($this->ctTourScheduleIndexedCache[$price][$ctAgeGroupsJson])) {
                                                            $ctTourSchedule = $this->ctTourScheduleIndexedCache[$price][$ctAgeGroupsJson];

                                                            $ctFlights = $ctTourSchedule->getCtFlights();
                                                            foreach ($params['ctFlight'] as $ctFlight) {
                                                                if (!$ctFlights->contains($ctFlight)) {
                                                                    $ctTourSchedule->setCtFlight($ctFlight);
                                                                }
                                                            }

                                                            $tours = $ctTourSchedule->getTours();
                                                            if (!$tours->contains($params['tour'])) {
                                                                $ctTourSchedule->setTour($params['tour']);
                                                            }
                                                        } else {
                                                            $ctTourSchedule = new models\CtTourSchedule;
                                                            $ctTourSchedule->setCtAgeGroupJson($ctAgeGroupsJson);
                                                            $ctTourSchedule->setPrice($price);
                                                            $ctTourSchedule->setTour($params['tour']);
                                                            foreach ($params['ctFlight'] as $ctFlight) {
                                                                if (!$ctTourSchedule->getCtFlights()->contains($ctFlight)) {
                                                                    $ctTourSchedule->setCtFlight($ctFlight);
                                                                }
                                                            }
                                                            $this->em->persist($ctTourSchedule);
                                                            $this->ctTourScheduleIndexedCache[$price][$ctAgeGroupsJson] = $ctTourSchedule;
                                                        }
                                                    }
                                                    $paramsCtTourSchedule = array();
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

    public function provideAccomodation($params) {
        $operator = $params['operator']->getId();
        $currency = $params['currency']->getId();
        $departureCity = $params['departureCity']->getId();
        $hotel = $params['hotel']->getId();
        $room = $params['room']->getId();
        $meal = $params['meal']->getId();

        // cache init
        if (!isset($this->ctTourIndexedCache[0]) || $this->departureCityFlag != $departureCity) {
            $this->departureCityFlag = $departureCity;
            $this->ctTourIndexedCache[0] = 1;
            $tours = $this->em->getRepository('models\Tour')->findBy(array('operator' => $params['operator'], 'departureCity' => $params['departureCity']));
            if(isset($tours[0])) {
                $this->ctTourIndexedCache = array();
                foreach ($tours as $tour) {
                    $operator = $tour->getOperator()->getId();
                    $currency = $tour->getCurrency()->getId();
                    $departureCity = $tour->getDepartureCity()->getId();
                    $hotel = $tour->getHotel()->getId();
                    $room = $tour->getRoom()->getId();
                    $meal = $tour->getMeal()->getId();
                    $this->ctTourIndexedCache[$operator][$currency][$departureCity][$hotel][$room][$meal] = $tour;
                }
            }
        }

        if (isset($this->ctTourIndexedCache[$operator][$currency][$departureCity][$hotel][$room][$meal])) {
            $tour = $this->ctTourIndexedCache[$operator][$currency][$departureCity][$hotel][$room][$meal];
        } else {
            $tour = new models\Tour;
            $tour = models\dto\Tour::toEntity($tour, $params);
            $this->em->persist($tour);
            $this->ctTourIndexedCache[$operator][$currency][$departureCity][$hotel][$room][$meal] = $tour;
        }
        return $tour;
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
            $parsed = false;
            break; // break current xml file parsing
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
}