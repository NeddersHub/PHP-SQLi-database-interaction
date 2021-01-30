<?php
class View {
 public function __construct($page) {
   $page->get_type() == "JSON" 
     ? $this->JSONheaders()
     : $this->HTMLheaders();

     echo $page->get_page();
 }

 private function JSONheaders() {
   header("Access-Control-Allow-Origin: *"); 
   header("Content-Type: application/json; charset=UTF-8"); 
   header("Access-Control-Allow-Methods: GET, POST");
 }

 private function HTMLheaders() {
  header("Content-Type: text/html; charset=UTF-8");
 }
}
?>