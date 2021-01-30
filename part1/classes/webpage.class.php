<?php
/**
* Creates an HTML webpage using the given params
* 
* @author YOUR NAME
* 
*/
abstract class WebPage {
 private $main; 
 private $pageStart;
 protected $header; 
 private $css; 
 private $footer; 
 private $pageEnd;

 /**
 *
 * @param $pageTitle - A string to appear as web page title
 * @param $css - link for a css file
 * @param $pageHeading1 - a string to appear as an <h1>
 * @param $footerText - footer text should include any html tags
 *
 */
 public function __construct($pageTitle, $pageHeading1, $footerText) {
   $this->main = "";
   $this->set_css();
   $this->set_pageStart($pageTitle,$this->css);
   $this->set_header($pageHeading1);
   $this->set_footer($footerText);
   $this->set_pageEnd();
 }

 private function set_pageStart($pageTitle,$css) {
   $this->pageStart = <<<PAGESTART
<!DOCTYPE html>
<html lang="en">
<head>
 <meta charset="utf-8" />
 <title>$pageTitle</title>
 <link rel="stylesheet" href="$css">
</head>
<body>
PAGESTART;
 }

 private function set_css() {
   $this->css = BASEPATH.CSSPATH; 
 }

 protected function set_header($pageHeading1) {
   $this->header = <<<HEADER
<header>
 <h1>$pageHeading1</h1>
</header>
HEADER;
 }

 private function set_main($main) {
   $this->main = <<<MAIN
<main>
 $main
</main>
MAIN;
 }

 private function set_footer($footerText) {
   $this->footer = <<<FOOTER
<footer>
 $footerText
</footer>
FOOTER;
 }

 private function set_pageEnd() {
   $this->pageEnd = <<<PAGEEND
</body>
</html>
PAGEEND;
 }

 public function addToBody($text) {
   $this->main .= $text;
 }

 public function get_page() {
   $this->set_main($this->main);
   return 
     $this->pageStart.
     $this->header.
     $this->main.
     $this->footer.
     $this->pageEnd; 
 }
}
?>