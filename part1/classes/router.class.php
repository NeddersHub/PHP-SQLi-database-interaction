<?php
/**
* This router will return a main, documentation or about page
* 
* @author Nathan Edmond 
*
*/
class Router {
 private $page;
 private $type = "HTML";

 /**
 * @param array $pageType - can be "main", "documentation" or "about"
 */
 public function __construct($recordset) {

   $url = $_SERVER["REQUEST_URI"];
   $path = parse_url($url)['path'];

   

   $path = str_replace(BASEPATH,"",$path);
   $pathArr = explode('/',$path);
   // $pathArr[0] is main/documentation/about/etc based on the page we are on
   $path = (empty($pathArr[0])) ? "main" : $pathArr[0]; // if we are on week5/b/ then set to main else set to what was specified in path.
   //and at this point $path is also main/doc/etc
   
  ($path == "api") //if we are on /api/ 
     ? $this->api_route($pathArr, $recordset) // call api_route() which will retuns JSON
     : $this->html_route($path); //else call html_route which will return an HTML page
  }

  public function api_route($pathArr, $recordset) {
    $this->type = "JSON";
    $this->page = new JSONpage($pathArr, $recordset);
  }

  public function html_route($path) {
    $ini['routes'] = parse_ini_file("config/routes.ini",true);
    $ini['documentation'] = parse_ini_file("config/documentation.ini",true);
    
    $pageInfo = isset($path, $ini['routes'][$path]) 
    ? $ini['routes'][$path] 
    : $ini['routes']['error'];

    $this->page = new WebPageWithNav($pageInfo['title'], $pageInfo['heading1'], $pageInfo['footer']);

    //If we are on documentation, for each item in the ini, display the endpoint name then loop through the parameters and display them.
    if ($path === "documentation"){
      $this->page->addToBody("<br> update-session and login both utilise POST requests whilst the other endpoints expect GET requests. The bracket notation is [Data Type, expected input] <br />");
      foreach ($ini['documentation'] as $endpoint => $parameters) {
        $this->page->addToBody("<br> <b>".$endpoint."</b> <br>");
        if ($parameters) {
          $this->page->addToBody("Parameters: <br>");
          foreach ($parameters as $parameter => $description) {
              $this->page->addToBody("<b> ".$parameter."=</b>");
              $this->page->addToBody("<b>".$description."</b> <br>");
          }
        } else {
          $this->page->addToBody("No parameters <br>");
        }
          
      }
          
    }
    $this->page->addToBody($pageInfo['text']);

  }

  public function get_type() {
    return $this->type ; 
  }

  public function get_page() {
    return $this->page->get_page(); 
  }
}
?>