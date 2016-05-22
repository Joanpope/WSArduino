<?php 

require_once("utilities/ExceptionApi.php");

class Robot  extends AbstractDAO {
	//Camps de la taula "robots"
	const TABLE_NAME = "robots";
	const ID = "id";
	const CODE = "code";
	const NAME = "name";
	const IP_ADDRESS = "ip_address";
	const LATITUDE = "latitude";
	const LONGITUDE = "longitude";
	const ID_CURRENT_STATUS = "id_current_status";
	const IP_CAM = "ip_cam";

//HTTP REQUEST GET
	public static function get($request){
		if($request[0] == 'getAll'){
			return parent::getAll();
		}else if($request[0] == 'getById'){
			return parent::getById($request[1]);
		}else if ($request[0] == 'getAllRobotsAdmin') {
			return self::getAllRobotsAdmin();
		}else{
			throw new ExceptionApi(parent::STATE_URL_INCORRECT, "Url mal formada", 400);
		}
	}

	//HTTP REQUEST DELETE
	public static function delete($request){
		if($request[0] == 'deleteById'){
			return parent::deleteById($request[1]);
		}else{
			throw new ExceptionApi(parent::STATE_URL_INCORRECT, "Url mal formada", 400);
		}
	}

	//HTTP REQUEST GET
	public static function post($request){
		if($request[0] == 'create'){
			return parent::create();
		}else if($request[0] == 'updateIp'){
			$idRobot = $request[1];
			$body = file_get_contents('php://input');
			$robot = json_decode($body);
			if(self::updateIP($idRobot, $robot) > 0){
				http_response_code(200);
				return [
				"state" => parent::STATE_SUCCESS,
				"message" => "Actualització IP del robot existosa"
				];
			} else {
				throw new ExceptionApi(parent::STATE_URL_INCORRECT, "El robot que intentes accedir no existeix",404);
			}
		} else {
			throw new ExceptionApi(parent::STATE_URL_INCORRECT, "Url mal formada", 400);
		}
	}

	//HTTP REQUEST PUT
	public static function put($request){
		if(!empty($request[0]) && !empty($request[1])){
			$route = $request[0];
			$idRobot = $request[1];
			$body = file_get_contents('php://input');
			$robot = json_decode($body);
			if($route == "updateAll"){
				if(self::update($idRobot, $robot) > 0){
					http_response_code(200);
					return [
					"state" => parent::STATE_SUCCESS,
					"message" => "Actualització robot existosa"
					];
				}else{
					throw new ExceptionApi(parent::STATE_URL_INCORRECT, "El robot que intentes accedir no existeix",404);
				}
			}else if($route == "updateIp"){
				if(self::updateIP($idRobot, $robot) > 0){
					http_response_code(200);
					return [
					"state" => parent::STATE_SUCCESS,
					"message" => "Actualització IP del robot existosa"
					];
				}else{
					throw new ExceptionApi(parent::STATE_URL_INCORRECT, "El robot que intentes accedir no existeix",404);
				}
			}else if($route == "updateStatus"){
				$idStatus = $request[2];
				if(self::updateIP($idRobot, $idStatus) > 0){
					http_response_code(200);
					return [
					"state" => parent::STATE_SUCCESS,
					"message" => "Actualització estat del robot existosa"
					];
				}else{
					throw new ExceptionApi(parent::STATE_URL_INCORRECT, "El robot que intentes accedir no existeix",404);
				}
			}else{
				throw new ExceptionApi(parent::STATE_ERROR_PARAMETERS, "La ruta especificada no existeix",422);
			}
		}else{
			throw new ExceptionApi(parent::STATE_ERROR_PARAMETERS, "Falta la ruta del robot", 422);
		}
	}

	public static function getAllRobotsAdmin(){
		try{ 
			$db = new Database();
			$sql = "select " . self::TABLE_NAME . ".". self::ID .",
			" . self::TABLE_NAME . ".". self::CODE .",
			" . self::TABLE_NAME . "." . self::NAME .", 
			concat(" . self::TABLE_NAME . "." . self::LATITUDE .",'/'," . self::TABLE_NAME . "." . self::LONGITUDE .") as ubication, 
			status_robot.description as robot_status
			from " . self::TABLE_NAME . "
			inner join status_robot on status_robot.id = ". self::TABLE_NAME ."." . self::ID_CURRENT_STATUS. ";";
			$stmt = $db->prepare($sql);
			$result = $stmt->execute();

			if($result){
				http_response_code(200);
				return [
				"state" => self::STATE_SUCCESS,
				"data"	=> $stmt->fetchAll(PDO::FETCH_ASSOC)
				];
			}else{
				throw new ExceptionApi(self::STATE_ERROR, "S'ha produït un error");
			}
		}catch(PDOException $e){
			throw new ExceptionApi(self::STATE_ERROR_DB, $e->getMessage());
		}
	}

	public static function insert($robot){
		$code = $robot->code;
		$name = $robot->name;
		$ipAddress = $robot->ipAddress;
		$latitude = $robot->latitude;
		$longitude = $robot->longitude;
		$idCurrentStatus = $robot->statusRobot;
		$ipCam = $robot->ipCam;
		try{
			$db = new Database();
			$sql = "INSERT INTO " . self::TABLE_NAME . " ( " .
			self::CODE . "," .
			self::NAME . "," .
			self::IP_ADDRESS . "," .
			self::LATITUDE . "," .
			self::LONGITUDE . "," .
			self::ID_CURRENT_STATUS . "," .
			self::IP_CAM . ")" .
			" VALUES(:code,:name,:ip_address,:latitude,:longitude,:id_current_status,:ip_cam)";

			$stmt = $db->prepare($sql);
			$stmt->bindParam(":code", $code);
			$stmt->bindParam(":name", $name);
			$stmt->bindParam(":ip_address", $ipAddress);
			$stmt->bindParam(":latitude", $latitude);
			$stmt->bindParam(":longitude", $longitude);
			$stmt->bindParam(":id_current_status", $idCurrentStatus);
			$stmt->bindParam(":ip_cam", $ipCam);

			$result = $stmt->execute();

			if($result){
				return parent::STATE_CREATE_SUCCESS;
			}else{
				return parent::STATE_CREATE_FAIL;
			}
		}catch(PDOException $e){
			throw new ExceptionApi(parent::STATE_ERROR_DB, $e->getMessage());
		}
	}

	public static function update($id, $robot){
		try{
			//creant la consulta UPDATE
			$db = new Database();
			$sql = "UPDATE " . self::TABLE_NAME . 
			" SET " . self::CODE . " = :code," .
			self::NAME . " = :name," .
			self::IP_ADDRESS . " = :ip_address," .
			self::LATITUDE . " = :latitude," .
			self::LONGITUDE . " = :longitude," .
			self::ID_CURRENT_STATUS . " = :id_current_status," .
			self::IP_CAM . " = :ip_cam " .
			"WHERE " . self::ID . " = :id";

			//prerarem la sentencia
			$stmt = $db->prepare($sql);
			$stmt->bindParam(":code", $robot->code);
			$stmt->bindParam(":name", $robot->name);
			$stmt->bindParam(":ip_address", $robot->ipAddress);
			$stmt->bindParam(":latitude", $robot->latitude);
			$stmt->bindParam(":longitude", $robot->longitude);
			$stmt->bindParam(":id_current_status", $robot->statusRobot);
			$stmt->bindParam(":ip_cam", $robot->ipCam);
			$stmt->bindParam(":id", $id);

			$stmt->execute();

			return $stmt->rowCount();
		}catch(PDOException $e){
			throw new ExceptionApi(parent::STATE_ERROR_DB, $e->getMessage());
		}
	}

	public static function updateIP($id, $robot){
		try{
			//creant la consulta UPDATE
			$db = new Database();
			$sql = "UPDATE " . self::TABLE_NAME . 
			" SET " . self::IP_ADDRESS . " = :ip_address " .
			"WHERE " . self::ID . " = :id";

			//prerarem la sentencia
			$stmt = $db->prepare($sql);
			$stmt->bindParam(":ip_address", $robot->ipAddress);
			$stmt->bindParam(":id", $id);

			$stmt->execute();

			return $stmt->rowCount();
		}catch(PDOException $e){
			throw new ExceptionApi(parent::STATE_ERROR_DB, $e->getMessage());
		}
	}

	public static function updateStatus($idRobot, $idStatus){
		try{
			//creant la consulta UPDATE
			$db = new Database();
			$sql = "UPDATE " . self::TABLE_NAME . 
			" SET " . self::ID_CURRENT_STATUS . " = :id_current_status " .
			"WHERE " . self::ID . " = :id";

			//prerarem la sentencia
			$stmt = $db->prepare($sql);
			$stmt->bindParam(":id_current_status", $idStatus);
			$stmt->bindParam(":id", $id);

			$stmt->execute();

			return $stmt->rowCount();
		}catch(PDOException $e){
			throw new ExceptionApi(parent::STATE_ERROR_DB, $e->getMessage());
		}
	}

}

?>