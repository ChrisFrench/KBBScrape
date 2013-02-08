<?php 



class KBB {
	public $makes		= "http://www.kbb.com/jsdata/2.1.37.1_40678/_makes?vehicleclass=UsedCar";
	public $models		= "http://www.kbb.com/jsdata/2.1.85.2_42107/_modelsyears?vehicleclass=NewCar&makeid=4&filterbycpo=false&filter=&priceMin=&priceMax=&categoryId=0&includeDefaultVehicleId=false&includeTrims=false&&hasNCBBPrice=false_=1360291490598";
	public $makesmodels = "http://www.kbb.com/jsdata/2.1.85.2_42107/_makesmodels?vehicleclass=UsedCar&yearid=2012&filterbycpo=false&filter=&priceMin=&priceMax=&categoryId=0&hasNCBBPrice=false&_=1360292707681";


	public $db = 'cars';
    public $username = 'root';
    public $password = 'chris01';
    public $host = 'localhost';
	public $DBH = null;
	public $ch;
    public $root = 'http://www.kbb.com/jsdata/';
    public $debug = false;
    public $make = null;
    public $model = null;
    public $year = null;

	function __construct() {
  	 
  	  $this->DBH = new PDO("mysql:host=$this->host;dbname=$this->db", $this->username, $this->password);  
      $this->DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING ); 	


  	 $this->ch = curl_init();
       // curl_setopt($this->ch, CURLOPT_USERAGENT, 'Mandrill-PHP/1.0.13');
        curl_setopt($this->ch, CURLOPT_POST, true);
        curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($this->ch, CURLOPT_HEADER, false);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($this->ch, CURLOPT_TIMEOUT, 600);



   	}



   	function getModelsByMake($makeid = null, $vehicleclass= 'UsedCar') {
   		$url = '2.1.37.1_40678/_modelsyears';

   		$params = array();
   		$params['vehicleclass'] = $vehicleclass;
   		$params['makeid'] = $makeid;


   		$results = $this->call($url, $params);
   		return $results;

   	}


   	function getMakes($vehicleclass = 'UsedCar') {
   		$url = '2.1.37.1_40678/_makes';

   		$params = array();
   		$params['vehicleclass'] = $vehicleclass;

   		$results = $this->call($url, $params);
   		return $results;
   	}

   	function getMakesModelByYear($year = '2012', $vehicleclass = 'UsedCar') {
   		$url = '2.1.85.2_42107/_makesmodels';

   		$params = array();
   		$params['vehicleclass'] = $vehicleclass;
   		$params['year'] = $year;

   		$results = $this->call($url, $params);
   		return $results;
   	}


   	public function call($url, $params) {
 
        $params = json_encode($params);
        $ch = $this->ch;

        curl_setopt($ch, CURLOPT_URL, $this->root . $url );
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_VERBOSE, $this->debug);

        $start = microtime(true);
        
        if($this->debug) {
            $curl_buffer = fopen('php://memory', 'w+');
            curl_setopt($ch, CURLOPT_STDERR, $curl_buffer);
        }

        $response_body = curl_exec($ch);
        $info = curl_getinfo($ch);
        $time = microtime(true) - $start;
        if($this->debug) {
            rewind($curl_buffer);
            $this->log(stream_get_contents($curl_buffer));
            fclose($curl_buffer);
        }
        
        $result = json_decode($response_body, true);

        

        return $result;
    }


    function storeMake($makeObject, $storeKBB = true){
    	$makeObject = (object) $makeObject;
    	# STH means "Statement Handle"  
  		$STH = $this->DBH->prepare("select id from makes where id = ?");   
		$STH->bindParam(1, $makeObject->Id, PDO::PARAM_INT);
		$STH->execute();
		$id = $STH->fetchColumn();
    	
		if($id) {
			//found maybe update?

			return $id;
		} else {
			
			if($storeKBB) {
				$sql = "INSERT INTO makes (id,make) VALUES (:id,:make)";

				$STH = $this->DBH->prepare($sql);
				$STH->execute(array(':id'=>$makeObject->Id,
		                  ':make'=>$makeObject->Name));	
			} else {
				$sql = "INSERT INTO makes (make) VALUES (:make)";
				$STH = $this->DBH->prepare($sql);
				$STH->execute(array(':make'=>$makeObject->Name));
			}
		
		}
		return $this->DBH->lastInsertId();			

    }

    function storeModel($model, $storeKBB = true){
    
    	# STH means "Statement Handle"  
  		$STH = $this->DBH->prepare("select count(*) from models where id = ?");   
		$STH->bindParam(1, $model->Id, PDO::PARAM_INT);
		$STH->execute();

		

			if($storeKBB) {
				$date = new DateTime();
				$created = $date->format('Y-m-d g:i:s');
				$sql = "INSERT INTO models (id,make_id,model,created,year) VALUES (:id,:make_id,:model,:created,:year)";

				$STH = $this->DBH->prepare($sql);
				$STH->execute(array(':id'=>$model->Id,':make_id'=>$this->make,
		                  ':model'=>$model->Name,':created'=>$created, ':year'=>$this->year ));	

			} else {
				$date = new DateTime();
				$created = $date->format('Y-m-d g:i:s');
				$sql = "INSERT INTO models (make_id,model,created,year) VALUES (:make_id,:model,:created,:year)";

				$STH = $this->DBH->prepare($sql);
				$STH->execute(array(':make_id'=>$this->make,
		                  ':model'=>$model->Name,':created'=>$created, ':year'=>$this->year ));	
			}
		
		//}
		return $dbh->lastInsertId;			

    }

    function doSync() {

	    for ($year = 1993; $year <= 2012; $year++) {
	    $this->year = $year;
	   
		$makesAndModels = $this->getMakesModelByYear( $this->year);
		

		   	foreach ($makesAndModels as $make) {

			$this->make = $this->storeMake($make);
			
				foreach($make['Model'] as $model) {
				
					$model = (object) $model;
					$this->storeModel($model);

				}
			
			}
	    }
    }


}

?>

<?php 

$kbb = new kbb();
$kbb->doSync();






?>