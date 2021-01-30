<?php
/**
* This class creates a JSON page based on the path used by the client
* 
* @author Nathan Edmond / w14025063
* 
*/
class JSONpage {
  private $page;
  private $recordset;

 /**
 * @param array $pathArr an array containing the route information
 * @param recordset  
 */
  public function __construct($pathArr, $recordset) {
    $this->recordset = $recordset;  
    $path = (empty($pathArr[1])) ? "api" : $pathArr[1]; 


    switch ($path) {
      case 'api':
        $this->page = $this->json_welcome();
        break;
      case 'schedule':
        $this->page = $this->json_schedule();
        break;
      case 'authors':
        $this->page = $this->json_authors();
        break;
      case 'sessions':
        $this->page = $this->json_sessions();
        break;
      case 'login':
        $this->page = $this->json_login();
        break;
      case 'update-session':
        $this->page = $this->json_update();
        break;
      case 'chairs':
        $this->page = $this->json_chairs();
        break;
      case 'awards':
        $this->page = $this->json_awards();
        break;
      case 'hash-pls':
        $this->page = $this->json_hashgen();
        break;
      default:
        $this->page = $this->json_error();
        break;
    }
   }


  /**
   * /api/awards will select information about any content that has been given an award
   * /api/awards?awardTypes will select the possible award types.
   * 
   * @param id any contentID. Returns all authors for a piece of content
   */
  private function json_awards() {

    $query = "select distinct content.title as 'contentTitle', content.contentId, content.award as 'awardGiven' FROM content
    where content.award not like '' ";
    $params = [];

    if (isset($_REQUEST['id'])) {
      $searchQuery = filter_var($_REQUEST['id'], FILTER_VALIDATE_INT);

      $query = "select authors.name from authors
      left join content_authors on (content_authors.authorId = authors.authorId)
      inner join content on (content.contentId = content_authors.contentId)
      WHERE content.contentId LIKE :searchTerm";
      $params = ["searchTerm" => "%".$searchQuery."%"];
    }

    //awardType will be either BEST_PAPER or HONORABLE_MENTION
    if (isset($_REQUEST['awardTypes'])) {
      $query = "SELECT DISTINCT content.award FROM content
                WHERE award NOT LIKE ''";
      //$params = null;
    }
    return ($this->recordset->getJSONRecordSet($query, $params));
   }

  /**
   * Gets author and session names
   */
  private function json_chairs() {
    $query = "select authors.name, sessions.name as 'sessionName', sessions.sessionId FROM sessions inner join authors on (authors.authorId = sessions.chairId)";

    return ($this->recordset->getJSONRecordSet($query));
   }

  private function json_schedule() {
    
    
    if (isset($_REQUEST['days'])) {
      $query = "select distinct dayString from slots
      order by dayInt";
      $params = [];
    }

    if (isset($_REQUEST['slotsByDay'])) {
      $selectedDay = filter_var($_REQUEST['slotsByDay'], FILTER_SANITIZE_STRING);
      $query = "SELECT slots.startHour, slots.startMinute, slots.endHour, slots.endMinute, slots.slotId
      FROM slots
      WHERE slots.dayString LIKE :selectedDay";
      $params = ['selectedDay' => "%".$selectedDay."%"];
    }

    if (isset($_REQUEST['sessionsBySlotId'])) { //takes a slot id and returns all sessions associated with it
      $slotId = filter_var($_REQUEST['sessionsBySlotId'], FILTER_VALIDATE_INT);
      $query = "SELECT sessions.sessionId, sessions.name as 'sessionName', session_types.name as 'sessionType', rooms.name as 'roomNumber'
                FROM sessions
                INNER JOIN slots on (slots.slotId = sessions.slotId)
                INNER JOIN session_types on (session_types.typeId = sessions.typeId)
                INNER JOIN rooms on (rooms.roomId = sessions.roomId)
                WHERE slots.slotId ==  :passedSlotId";
      $params = ['passedSlotId' => $slotId];
    }

    if (isset($_REQUEST['infoBySessionId'])) {

      $sessionId = filter_var($_REQUEST['infoBySessionId'], FILTER_VALIDATE_INT);
      
      $query = "SELECT content.title as 'contentTitle', content.abstract as 'contentAbstract', authors.name as 'authorName', rooms.name as 'roomNumber', session_types.name as 'sessionType', content.award as 'awardGiven'
                FROM sessions 
                LEFT JOIN sessions_content on (sessions_content.sessionId = sessions.sessionId)
                LEFT JOIN content on (sessions_content.contentId = content.contentId)
                LEFT JOIN authors on (authors.authorId = sessions.chairId)
                JOIN slots on (slots.slotId = sessions.slotId)
                JOIN rooms on (rooms.roomId = sessions.roomId)
                JOIN session_types on (session_types.typeId = sessions.typeId)
                WHERE sessions.sessionId == :passedSessionId";

      $params = ["passedSessionId" => $sessionId];
    }

    return ($this->recordset->getJSONRecordSet($query, $params));
  }
  
  private function json_sessions() {

    $query  = "SELECT DISTINCT sessions.sessionId, sessions.name as 'sessionName', authors.name as 'authorName', rooms.name as 'roomNumber', slots.dayString, slots.startHour, 
    slots.startMinute, slots.endHour, slots.endMinute, session_types.name, slots.dayInt as 'sessionType'
    FROM sessions 
    LEFT JOIN sessions_content on (sessions_content.sessionId = sessions.sessionId)
    LEFT JOIN content on (sessions_content.contentId = content.contentId)
    LEFT JOIN authors on (authors.authorId = sessions.chairId)
    JOIN slots on (slots.slotId = sessions.slotId)
    JOIN rooms on (rooms.roomId = sessions.roomId)
    JOIN session_types on (session_types.typeId = sessions.typeId)
    ORDER BY dayInt, startHour";
    $params = [];

    if (isset($_REQUEST['search'])) {
        $searchQuery = filter_var($_REQUEST['search'], FILTER_SANITIZE_STRING);
        $query .= " WHERE sessions.name LIKE :searchTerm";
        $params = ["searchTerm" => "%".$searchQuery."%"];
    }

    if (isset($_REQUEST['id'])) {
        $limit = filter_var($_REQUEST['id'], FILTER_VALIDATE_INT);
        $query .= " WHERE author.authorId LIKE :searchTerm";
        $params = ["searchTerm" => $limit];
    }


    //OOOOOOOOOOKAY SO THE BELOW BLOCK WANTS TO SHOW CONTENT.TITLE AND CONTENT.ABSTRACT FOR EACH PIECE OF CONTENT ID WHERE 
    //authors.authorId = content_authors.authorId 
    //inner join 

    if (isset($_REQUEST['authorId'])) {
      $authorId = filter_var($_REQUEST['authorId'], FILTER_VALIDATE_INT);
      //we want to get every film that an actor has appeared in
      // so for an actor being clicked we:
      // get actor id, find all film_id's in the table film_actor where film_actor.authorId = actor.authorId
      // get the film.title for every returned film.film_id
        $query = "select content.title
        from content
        inner join content_authors on (content_authors.contentId = content.contentId)
        inner join authors on (authors.authorId = content_authors.authorId)
        where authors.authorId = :searchTerm";
        $params = ["searchTerm" => $authorId];
    }

    if (isset($_REQUEST['limit'])) {
        $limit = filter_var($_REQUEST['limit'], FILTER_VALIDATE_INT);
        $query .= " LIMIT :searchTerm";
        $params = ["searchTerm" => $limit];
    }

    if (isset($_REQUEST['page'])) {
      $pageNum = filter_var($_REQUEST['page'], FILTER_VALIDATE_INT);
      $query .= " LIMIT 10
                  OFFSET :searchTerm;";
      $params = ["searchTerm" => ($pageNum*10)];
  }
    return ($this->recordset->getJSONRecordSet($query, $params));
  }

  private function json_hashgen() {

    if (isset($_REQUEST['input'])) {
      $cleanInput = filter_var($_REQUEST['input'], FILTER_SANITIZE_STRING);
      $hash = password_hash($cleanInput, PASSWORD_BCRYPT);
      return json_encode(array("Your input was: " => $cleanInput, "hash" => $hash));
    } else { 
      return json_encode(array("msg" => "Your input was null!"));
    }
  }

  /**
  * Work on an update endpoint that will update the description of a film if a token (value "1234"), a film_id, 
  * and a description are contained in the body of the request. You could call the endpoint /api/update-film
  * 
  * Note that the implementation of this endpoint is good enough for the coursework but it can be improved by, 
  * for example: checking first if the film_id exists; and using a different database class that uses PDO to check 
  * if any rows were affected by the update.
  *
  * json_update
  * 
  */ 
  private function json_update() {
    $input = json_decode(file_get_contents("php://input"));

    if (is_null($input->token)) {
      return json_encode(array("status" => 401, "message" => "Not authorised"));
    }

    try {
      $tokenDecoded = \Firebase\JWT\JWT::decode($input->token, JWTKEY, array('HS256'));
    }
    catch (UnexpectedValueException $e) {        
      return json_encode(array("status" => 401, "message" => $e->getMessage()));
    }


    if (is_null($input->sessionName) || is_null($input->sessionId)) {  
      return json_encode(array("status" => 400, "message" => "Invalid request"));
    }

    if ($tokenDecoded->admin == 1) {
      $query  = "UPDATE sessions SET name = :sessionName WHERE sessionId = :sessionId";
      $params = ["sessionName" => $input->sessionName, "sessionId" => $input->sessionId];
      $res = $this->recordset->getJSONRecordSet($query, $params);    
      return json_encode(array("status" => 200, "message" => "ok"));
    } else {
      return json_encode(array("status" => 403, "message" => "Unauthorized request, you may be trying to perform an action that require administrator privileges."));
    }
  }
  
  /**
  * json_login
  * 
  * Accepts an email and password through a POST then checks against a database to authorise a user.
  */ 
  private function json_login() {
    $msg = "Invalid request. Username and password required";
    $status = 400;
    $token = null;
    $input = json_decode(file_get_contents("php://input"));


    if (!is_null($input->email) && !is_null($input->password)) {  
      $query  = "SELECT username, password, admin FROM users WHERE email LIKE :email";
      $params = ["email" => $input->email];
      $res = json_decode($this->recordset->getJSONRecordSet($query, $params),true);

      if (password_verify($input->password, $res['data'][0]['password'])) {
        $msg = "User authorised. Welcome ". $res['data'][0]['username'];
        $status = 200;

        $token = array();
        $token['email'] = $input->email;
        $token['username'] = $res['data'][0]['username'];
        $token['iat'] = time();
        $token['exp'] = (time()+3600);
        $token['admin'] = $res['data'][0]['admin'];
        $token = \Firebase\JWT\JWT::encode($token, JWTKEY, 'HS256');

      } else { 
        $msg = 
        $msg = "username or password are invalid";
        $status = 401;
      }
    }
    return json_encode(array("status" => $status, "message" => $msg, "token" => $token));
   }

  private function json_welcome() {
    $msg =  array("message"=>"This API retrieves information about the CHI 2018 conference (unofficially!) from a database and returns it in JSON format. You can POST to /api/login and /api/update-session. and send a GET request to /api/, /api/sessions, /api/schedule, /api/authors, /api/chairs, /api/awards ", "developer"=>"Nathan Edmond");
    $status = 200;
    return json_encode(array("status" => $status, "message" => $msg), JSON_UNESCAPED_SLASHES);
    
   }

  private function json_error() {
    $msg = array("message"=>"Error, no valid /path/ entered");
    $status = 404;
    return json_encode(array("status" => $status, "message" => $msg), JSON_UNESCAPED_SLASHES);
   }

  private function json_authors() {
    $query  = "SELECT name, authorId FROM authors";
    $params = [];

    if (isset($_REQUEST['search'])) {
      $searchQuery = filter_var($_REQUEST['search'], FILTER_SANITIZE_STRING);
      $query .= " WHERE name LIKE :searchTerm";
      $params = ["searchTerm" => "%".$searchQuery."%"];
    }

    if (isset($_REQUEST['id'])) {
      $limit = filter_var($_REQUEST['id'], FILTER_VALIDATE_INT);
      $query .= " WHERE authorId LIKE :searchTerm";
      $params = ["searchTerm" => $limit];
    }


    //OOOOOOOOOOKAY SO THE BELOW BLOCK WANTS TO SHOW CONTENT.TITLE AND CONTENT.ABSTRACT FOR EACH PIECE OF CONTENT ID WHERE 
    //authors.authorId = content_authors.authorId 
    //inner join 

    if (isset($_REQUEST['authorId'])) {
      $authorId = filter_var($_REQUEST['authorId'], FILTER_VALIDATE_INT);
      //we want to get every film that an actor has appeared in
      // so for an actor being clicked we:
      // get actor id, find all film_id's in the table film_actor where film_actor.authorId = actor.authorId
      // get the film.title for every returned film.film_id
      $query = "select content.contentId, content.title
      from content
      inner join content_authors on (content_authors.contentId = content.contentId)
      inner join authors on (authors.authorId = content_authors.authorId)
      where authors.authorId = :searchTerm";
      $params = ["searchTerm" => $authorId];
    }

    if (isset($_REQUEST['limit'])) {
      $limit = filter_var($_REQUEST['limit'], FILTER_VALIDATE_INT);
      $query .= " LIMIT :searchTerm";
      $params = ["searchTerm" => $limit];
    }

    if (isset($_REQUEST['page'])) {
      $pageNum = filter_var($_REQUEST['page'], FILTER_VALIDATE_INT);
      $query .= " LIMIT 10
                  OFFSET :searchTerm";
      $params = ["searchTerm" => ($pageNum*10)];
    }
    return ($this->recordset->getJSONRecordSet($query, $params));
  }

  /**
  * As with the other methods in this class, there is more going on here than ought to be within a single method (and within a single class). 
  * You should be considering how this class can be improved.
  */
  public function get_page() {
    return $this->page;
  }
}
?>