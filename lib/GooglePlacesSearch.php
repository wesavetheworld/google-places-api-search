<?php
class GooglePlacesSearch {
	
	protected $searched_areas_source_file = 'input/set_of_searched_areas.csv';
	protected $set_of_searched_areas = array();
	protected $output_file = 'output/this.txt';
	protected $api_key;
    protected $query;
    private $lines_count = 0;
    private $lng_step = 0.5;
    private $lat_step = 0.6;

	/*
     * Constructor
     */
	public function __construct($api_key="your_default_key", $query="gogh") {
		ini_set('memory_limit', '512M');
        $this->ary[] = "UTF-8";
        $this->ary[] = "ASCII";
        $this->ary[] = "EUC-JP";
        mb_detect_order($this->ary);
        $this->api_key = $api_key;
        $this->query = $query;		
	}

	/* 
     * Get coordinates from the locations file
     * input/set_of_searched_areas.csv, saves it into an array
     */
    protected function getAreas() {
       $csvData = file_get_contents($this->searched_areas_source_file);  
       $csvNumColumns = 5;
       $csvDelim = ",";
       $set_of_searched_areas  = array_chunk(str_getcsv($csvData, $csvDelim), $csvNumColumns);
       return $set_of_searched_areas; 
    }

    protected function curl_request_to_google_api($url){
		    $ch = curl_init($url);
            curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, TRUE);
            curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            $curl_result = curl_exec($ch);
            if ($curl_result === false) echo curl_error($ch);
            curl_close($ch);
            return $curl_result;
	}

    /* 
     * Checking whether there are any results, 
     * eg., are there any places named by Van Gogh
     * (within this pair of coordinates+this radius?)
     */
    protected function handleResults($output, $currentArea) {
            if($output['status'] === "OK") {                 
                $this->fetch_data_from_curl($output, $currentArea);
                echo "I have results!" . "\n";
            }
            else if ($output['status'] === "ZERO_RESULTS") {
                echo "No results!" . "\n";
            } else {
                echo $output['status'];
            }
    }

    protected function add_output_header() {
        file_put_contents($this->output_file, "area".","."name".","."lat".","."long".","."id".","."vicinity"."," ."type". "\r\n", FILE_APPEND | LOCK_EX);
    }
    
    protected function fetch_data_from_curl($output, $currentArea) {
            $types = array();
            
            for ($i = 0; $i < count($output['results']); $i++) {
                $this->lines_count++;

                //creating a line
                $line = " " . $this->lines_count. ","; //add the count column
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
                file_put_contents($this->output_file, $this->encode_line($line) . "\r\n", FILE_APPEND | LOCK_EX);
            }
    }

    protected function encode_line($line) {
        $encoded_line = mb_convert_encoding($line, "UTF-8", "auto");
        return $encoded_line;
    } 

  
    public function Search() {
        $count = 0;
    	$set_of_searched_areas = $this->getAreas();

        //header
        echo "Lat:" . " - " . "Long:" . "\n"; //add header to the console output
    	$this->add_output_header();           //add header to the xls output

    	foreach ($set_of_searched_areas as $area) {
            $currentArea = trim($area[0]);
            $lat = $area[1];	
        	$lngStart = $area[2];
        	$lng = $lngStart; //$lat and $lng are the starting values of long (Coordinates for top Left corner) 
        	$latEnd = $area[3]; 
        	$lngEnd = $area[4];	

            while ($lat > $latEnd ) { 
        	   	$lng = $lngStart; //reset longitude

               while($lng < $lngEnd ) { 

                    $url = "https://maps.googleapis.com/maps/api/place/nearbysearch/json?location={$lat},{$lng}&radius=50000&name={$this->query}&language=en-GB&sensor=true&key={$this->api_key}";

                    $output = json_decode($this->curl_request_to_google_api($url), true);
                    echo ' area: ' . $currentArea . ' | ' . 'lat: ' . $lat . ' | long: ' . $lng . ' | ';

                    $this->handleResults($output, $currentArea);

                    $lng = $lng + $this->lng_step;
                }
                $lat = $lat - $this->lat_step;       
            }
        } //end of foreach
    } //end of Search()
}