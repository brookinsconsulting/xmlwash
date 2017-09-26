<?php
/*!
  This operator allows to wash a xml field (XSS...). As an added value, it deals well with shortened xml fields
*/

define ("XML_HTMLSAX3","extension/xmlwash/safehtml/");
require_once( "extension/xmlwash/safehtml/safehtml.php" );
require_once( "extension/xmlwash/classes/teaser.php" );

class XMLWashOperator
{
    /*!
      Constructor, does nothing by default.
    */
    function XMLWashOperator()
    {
    }

    /*!
     \return an array with the template operator name.
    */
    function operatorList()
    {
        return array( 'xmlwash' , 'strip_tags', 'teaser' );
    }
    /*!
     \return true to tell the template engine that the parameter list exists per operator type,
             this is needed for operator classes that have multiple operators.
    */
    function namedParameterPerOperator()
    {
        return true;
    }    
 
    /*!
     See eZTemplateOperator::namedParameterList
    */
    function namedParameterList()
    {
        return array( 'xmlwash' => array( 'xmlattribute' => array( 'type' => 'attribute',
                                                                   'required' => false,
									 							   'default' => "" ) ),
					  'strip_tags' => array ('tagskept'=> array ('type' => 'array',
					                                       	     'required' => false,
									       					     'default' => array('<p>') ) ),
					  'teaser' => array( 'length' => array( 'type' => 'integer',
                                                            'required' => false,
									 						'default' => 100 ),
									 	 'fuzzyness' => array( 'type' => 'integer',
									 	 					   'required' => false,
									 	 					   'default' => 5 ) ) );
    }
    /*!
     Executes the PHP function for the operator cleanup and modifies \a $operatorValue.
    */
    function modify( &$tpl, &$operatorName, &$operatorParameters, &$rootNamespace, &$currentNamespace, &$operatorValue, &$namedParameters )
    {
      $temp=$operatorValue;
      switch ($operatorName) {
        case 'xmlwash':
          $parser = new SafeHTML();
          //      eZDebug::writeNotice($temp);
          $operatorValue  = $parser->parse($temp);
	  	//      $operatorValue = $temp;
	  	break;
		case 'strip_tags':
		  $temp=str_replace("</p>","",$temp);
		  $temp=preg_replace("/<p.*?>/","<br>",$temp);
		  $tags=implode('',$namedParameters['tagskept']);
		  $operatorValue = strip_tags($temp,$tags);
	  	break;
	  	case 'teaser':
		  $teaser = new HtmlTeaser($temp);
		  $iniSQ = eZINI::instance("ezxml.ini");
		  
		  if ( $iniSQ->hasVariable( 'Teaser', 'Fuzzyness' ) ) {
			$teaser->setFuzzyness( $iniSQ->variable( 'Teaser', 'Fuzzyness' ) );
		  }
		  if ( $iniSQ->hasVariable( 'Teaser', 'Length' ) ) {
			$teaser->setTeaserLength( $iniSQ->variable( 'Teaser', 'Length' ) );
		  }
		  if ( $iniSQ->hasVariable( 'Teaser', 'AddTextBeforeTagArray' ) ) {
			$teaser->setAddTextBeforeTagArr( $iniSQ->variable( 'Teaser', 'AddTextBeforeTagArray' ) );
		  }
		  if ( $iniSQ->hasVariable( 'Teaser', 'KeepTagsArray' ) ) {
			$teaser->setKeepTagsArr( $iniSQ->variable( 'Teaser', 'KeepTagsArray' ) );
		  }
		  if ( $iniSQ->hasVariable( 'Teaser', 'AddText' ) ) {
			$teaser->setAddText( $iniSQ->variable( 'Teaser', 'AddText' ) );
		  }
		  if ( $iniSQ->hasVariable( 'Teaser', 'KeepAttributesArray' ) ) {
			$teaser->setKeepAttribsArr( $iniSQ->variableArray( 'Teaser', 'KeepAttributesArray' ) );
		  }
		  if ( $iniSQ->hasVariable( 'Teaser', 'Tags2SpaceArray' ) ) {
			$teaser->setTags2SpaceArr( $iniSQ->variable( 'Teaser', 'Tags2SpaceArray' ) );
		  }
		  		  		  		 
		  $teaser->setTeaserLength($namedParameters['length']);
		  $teaser->setFuzzyness($namedParameters['fuzzyness']);
		  $operatorValue = $teaser->getTeaser(); 
	  	break;
     }
   }
}
?>
