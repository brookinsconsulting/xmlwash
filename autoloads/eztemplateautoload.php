<?php

// Operator autoloading

$eZTemplateOperatorArray = array();

$eZTemplateOperatorArray[] =
  array( 'script' =>
'extension/xmlwash/classes/xmlwash.php',
         'class' => 'XMLWashOperator',
         'operator_names' => array( 'xmlwash','strip_tags', 'teaser') );

?>
