<?php
	require_once("bin/load_config.php");
    require_once("bin/idgen.php");
	require_once("bin/DBConnection.php");
	
	class QueryManager
	{
		private $PID_MAP;
		private $TABLES_MAP;
		private $Status_MAP;
		
		public function __construct($blade)
		{
			global $Blade_Map;
			$this->PID_MAP = array();
			$this->TABLES_MAP = array();
			$this->Status_MAP = array(0=>"error",1=>"running",2=>"db-ready",3 =>"some-xml-ready",4 =>"all-xml-ready",5 =>"kml-ready");
			
			$blade = $Blade_Map[$blade];
			$host = (string)$blade["host"];
			$port = (int)$blade["port"];
			$user = (string)$blade["user"];
			$pass = is_array($blade["pass"])?"":(string)$blade["pass"];
			$database = (string)$blade["db"];
			$write_db = $blade["write-db"];
			
			$mysqli = new DBConnection($host,$user,$pass,$database,$port,5);
			
			$result = $mysqli->query('SHOW FULL PROCESSLIST;');
			$num = $result->num_rows;
			for($x = 0 ; $x < $num ; $x++){
			    $row = $result->fetch_assoc();
			    if($row['State']!=NULL && stristr($row['Info'],'create table')!=FALSE){
			    	$tbl = strstr(strstr( $row['Info'] ,'DPV_'),'`',true);
					if($tbl){
						$queryID = substr($tbl, -32);
						$type = (strstr($tbl, "_POP_",true)=="DPV")? "POP" : "EDGE";
						$this->PID_MAP[$queryID][$type] = $row['Id'];
					}
				}
			}
			
			$result = $mysqli->query("show tables from ".$write_db." like 'DPV_%'");
			$num = $result->num_rows;
			for($x = 0 ; $x < $num ; $x++){
			    $row = $result->fetch_row();
				$tbl = $row[0];
				$queryID = substr($tbl, -32);
				$type = (strstr($tbl, "_POP_",true)=="DPV")? "POP" : "EDGE";
				$this->TABLES_MAP[$queryID][$type] = true;
			}
			
			$result = $mysqli->query("show open tables from ".$write_db." like 'DPV_%'");
			$num = $result->num_rows;
			for($x = 0 ; $x < $num ; $x++){
			    $row = $result->fetch_assoc();
				$tbl = $row["Table"];
				$locks = intval($row["In_use"]);
				$queryID = substr($tbl, -32);
				$type = (strstr($tbl, "_POP_",true)=="DPV")? "POP" : "EDGE";
				if($locks > 0) $this->TABLES_MAP[$queryID][$type] = false;
			}
			
			$mysqli->close();
				
		}
	
		// 0 - error , 1 - running , 2 - db-ready, 3 - some-xml-ready,  4 - all-xml-ready, 5 - kml-ready
		public function getQueryStatus($QID)
		{
			$idg = new idGen($QID);
			$kml_dst_dir = 'queries/'.$idg->getDirName();
			$kml_filename = $kml_dst_dir.'/result.kmz';
			$edges_filename = $kml_dst_dir.'/edges.xml';
			$pop_filename = $kml_dst_dir.'/pop.xml';
		
			if(file_exists($kml_filename)){
				return 5;
			}
			
			if(file_exists($edges_filename) && file_exists($pop_filename)){
				return 4;
			}
			
			if(file_exists($edges_filename) || file_exists($pop_filename)){
				return 3;
			}
	
			if(isset($this->PID_MAP[$QID]["POP"]) || isset($this->PID_MAP[$QID]["EDGE"]))
				return 1;
			
			if(isset($this->TABLES_MAP[$QID]["POP"]) && 
			$this->TABLES_MAP[$QID]["POP"] == true &&
			isset($this->TABLES_MAP[$QID]["EDGE"]) && 
			$this->TABLES_MAP[$QID]["EDGE"] == true)
				return 2;
				
			return 0;
		}
		
		public function getStatusMsg($status_code){
			if (!isset($this->Status_MAP[$status_code]))
				return "unknown status";
			return $this->Status_MAP[$status_code];
		}
		
		public function getPIDS($QID)
		{
			$tmp = array();
			if(isset($this->PID_MAP[$QID]["POP"]))
				$tmp[] = $this->PID_MAP[$QID]["POP"];
			if(isset($this->PID_MAP[$QID]["EDGE"]))
				$tmp[] = $this->PID_MAP[$QID]["EDGE"];
			return $tmp;
		}
		
	}
	
?>