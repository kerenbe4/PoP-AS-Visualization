<?php
	/*
	 * this interface is used for reading xml files
	 * that were written using the xmlWriter interface
	 * interface supprot reading of split xml files
	 */
    class XMLChunkReader {
    	private $dir;
		private $basePath;
		
    	private $default_path;
		private $path;
		private $currentChunk;
		private $isDivided;
		
		private $firstTime;
		
		/* initialize reader */
		private function init(){
			$this->currentChunk = 0;
			$this->isDivided = false;
    		$this->default_path = $this->basePath.'/'.$this->dir.'.xml';
			$this->path = $this->basePath."/$this->dir/".$this->dir.$this->currentChunk.'.xml';
			
			if(file_exists($this->default_path)){
				$this->path = $this->default_path;
			} elseif(file_exists($this->path)) {
				$this->isDivided = true;
			}
			
			$this->firstTime = true;
		}
		
    	public function __construct($basePath,$dir){
    		$this->dir = $dir;
			$this->basePath = $basePath;
			
    		$this->init();
    	}
		 /* loads the next XML chunck */
		public function loadNext(){
			$sxe = false;
			if($this->isDivided && !$this->firstTime){
				$this->path = $this->basePath."/$this->dir/".$this->dir.(++$this->currentChunk).'.xml';
			}
			if(file_exists($this->path) && ($this->isDivided || $this->firstTime)){
				$sxe =  simplexml_load_file($this->path);
			}
			$this->firstTime = false;
			return $sxe;
		}
		
		/* indicates if the result file is divided into chunks */
		public function isDivided2Chunks(){
			return $this->isDivided;
		}
		
		/* rsets the reader to ints initial state */
		public function resetReader(){
			$this->init();
		}
		
    }
?>