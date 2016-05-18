<?php 

require_once("ViewApi.php");

class ViewJson extends ViewApi{
	public function __construct($state = 200){
		$this->state = $state;
	}

	public function prints($body){
		if($this->state){
			http_response_code($this->state);
		}
		header("Access-Control-Allow-Origin: *");
		header("Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE");
		header("Content-Type: application/json; charset=utf8");
		//echo json_encode($body, JSON_PRETTY_PRINT);
		echo json_encode($body,JSON_UNESCAPED_UNICODE);
		
	}
}

?>