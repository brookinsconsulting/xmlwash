<?
/*!
  This operator allows to wash a xml field (XSS...). As an added value, it deals well with shortened xml fields
*/

define ("XML_HTMLSAX3","extension/xmlwash/safehtml/");
require_once( "extension/xmlwash/safehtml/safehtml.php" );
//include_once( 'lib/ezxml/classes/ezxml.php' );

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
        return array( 'xmlwash' , 'strip_tags' );
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
					       'default' => array('<p>')
					     )
			      )   
										            );

    }
    /*!
     Executes the PHP function for the operator cleanup and modifies \a $operatorValue.
    */
    function modify( &$tpl, &$operatorName, &$operatorParameters, &$rootNamespace, &$currentNamespace, &$operatorValue, &$namedParameters )
    {
      $temp=$operatorValue;
      switch ($operatorName) {
        case 'xmlwash':
          $parser =& new SafeHTML();
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
     }

   }
}
?>
