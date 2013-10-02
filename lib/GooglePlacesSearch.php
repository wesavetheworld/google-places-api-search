<?php
class GooglePlacesSearch {
	
	protected $locationsFile = 'input/set_of_search_areas.csv'; //file containing set of searched locations
	protected $areas = array(); //an array holding the content of the locations .csv file
	protected $file = 'output/this.txt';
	protected $apiKey;
    protected $query;
    private $linesCount = 0;

		
	//constructor
	public function __construct($apiKey="your_default_key", $query="pascal") {
		$this->apiKey = $apiKey;
        $this->query = $query;		
		$this->initSetup();
	}

	//setting up the memory and encoding
	public function initSetup() {
		ini_set('memory_limit', '512M');
		$this->ary[] = "UTF-8";
		$this->ary[] = "ASCII";
		$this->ary[] = "EUC-JP";
		mb_detect_order($this->ary);
	}

	//get coordinates from the locations file (input folder), save into an array
    public function getAreas() {
       $csvData = file_get_contents($this->locationsFile);  
       $csvNumColumns = 5;
       $csvDelim = ",";
       $areas = array_chunk(str_getcsv($csvData, $csvDelim), $csvNumColumns);       
       return $areas; 
    }


    //curl request to Google API
	public function curl_request($url){
		    $ch = curl_init($url);
            curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, TRUE);
            curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            //curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
            //curl_setopt($ch, CURLOPT_PROXY, 'tcp://10.152.4.180');
            //curl_setopt($ch, CURLOPT_PROXYPORT, 80);
            $curl_result = curl_exec($ch);
            if ($curl_result === false) echo curl_error($ch);
            curl_close($ch);
            return $curl_result;
	}

    //Checking, whether there are any results, 
    //eg., Are there any places named by Mandela(within this pair of coordinates+this radius?)
    public function handleResults($output, $currentArea) {
            if($output['status'] === "OK") {                 
                $this->fetchData($output, $currentArea);
                echo "I have results!" . "\n";
            }
            else if ($output['status'] === "ZERO_RESULTS") {
                echo "Nae results!" . "\n";
            } else {
                echo $output['status'];
            }
    }

    //add the xls file header
    public function addOutputHeader() {
        file_put_contents($this->file, "area".","."name".","."lat".","."long".","."id".","."vicinity"."," ."type". "\r\n", FILE_APPEND | LOCK_EX);
    }
    //fetch the data from cURL
    public function fetchData($output, $currentArea) {
            $types = array();
            
            for ($i = 0; $i < sizeof($output['results']); $i++) {
                $this->linesCount++;

                //creating a line
                $line = " " . $this->linesCount. ","; //add the count column
                $line = " " . $currentArea. ","; //add the area column
                
                //adding data from the cURL request to the line
                $line .= str_replace(",", "", $output['results'][$i]['name']) . ","; //eliminate commas in a name
                $line .= $output['results'][$i]['geometry']['location']['lat'] . ",";
                $line .= $output['results'][$i]['geometry']['location']['lng'] . ",";               
                $line .= $output['results'][$i]['id'] . ",";
                $line .= str_replace(",", "", $output['results'][$i]['vicinity']) . ",";
                
                if(isset($output['results'][$i]['types'])) { //https://developers.google.com/places/documentation/supported_types
                    $types = $output['results'][$i]['types'];

                    for($j = 0; $j< sizeof($types); $j++) {
                        if ($j == sizeof($types)-1) {
                            $line .= $types[$j];
                        } else {
                            $line .= $types[$j] . " | ";
                        }
                    }
                } else {
                    $line .= " " . ",";
                }
                //echo $this->encode($line) . "\r\n";
                file_put_contents($this->file, $this->encode($line) . "\r\n", FILE_APPEND | LOCK_EX);
            }
    } //end of fetch data

    //encode the line
    public function encode($line) {
        $encoded_line = mb_convert_encoding($line, "UTF-8", "auto");
        return $encoded_line;
    } 

    //main function
    public function Search() {
        $count = 0;
    	$areas = $this->getAreas();

        //header
        echo "Lat:" . " - " . "Long:" . "\n"; //add header to the console output
    	$this->addOutputHeader();             //add header to the xls output

    	foreach ($areas as $area) {
            $currentArea = trim($area[0]);
            $lat = $area[1];	
        	$lngStart = $area[2];
        	$lng = $lngStart; //-12; //$lat and $lng are the starting values of long (Coordinates for top Left corner) 
        	$latEnd = $area[3]; //40; 
        	$lngEnd = $area[4]; //25;	

            while ($lat > $latEnd ) { 
        	   	$lng = $lngStart; //reset longitude

               while($lng < $lngEnd ) { 

                    $url = "https://maps.googleapis.com/maps/api/place/nearbysearch/json?location={$lat},{$lng}&radius=50000&name={$this->query}&language=en-GB&sensor=true&key={$this->apiKey}";

                    $output = json_decode($this->curl_request($url), true);
                    echo ' area: ' . $currentArea . ' | ' . 'lat: ' . $lat . ' | long: ' . $lng . ' | ';

                    $this->handleResults($output, $currentArea);

                    $lng = $lng + 0.5; //lng step
                }
                $lat = $lat - 0.6; //lat step!       
            }
    } //end of foreach
    } //end of Search()
}