<?php

/*

Class to create teaser out of html text.
- autocloses any leftover opened tags
- does not count tags in teaser length
- strips all tags except the ones that are specified to keep
- strips all attributes except the ones that are specified to keep
- tried to end the teaser at a space character (which means the 
teaser length might not have exactly the set length, range can be specified)

If errors are encountered (e.g. closing tag without corresponding opening tag)
a substring with all html tags stripped is returned


Usage example:
   $fullText = '<div title="example">This is <b>an example</b>, which contains an <img title="foo" src="bar" alt="quux"> tag</div>';
   $foo = new HtmlTeaser();
   $foo->setTeaserLength(20);
   $foo->setKeepTagsArr(array('div'));                           
   $result = $foo->getTeaser($fullText);
   
   $result: <div title="example">This is  an example ...</div>

Check the public class members for all options.

*/

define('HTML_TAG_TYPE_EMPTY', 0);
define('HTML_TAG_TYPE_CLOSING', 1);
define('HTML_TAG_TYPE_OPENING', 2);

class HtmlTeaser
{
   //public
   var $teaserLength  = 100;
   var $fullText      = '';
   
   /*
     the text that should be added at the end of the text
     this can also be html, e.g. a link for read more
   */
   var $addText       = '...'; 
   
   /*
     if the text ends in one of these tags, add $addtext before the closing tag 
   */  
   var $addTextBeforeTagArr = array('p', 'div');
    
   /* 
     range to look for the last space character
     if the teaser doe snot end with a tag, the script will check
     +/- $fuzzynss characters around the teaserlength for a space
     and skip everything after the space to avoid broken words 
   */
   var $fuzzyness = 5; 
   
   /* tags and attributes to keep, all other are filtered
      - set to false to keep all tags/attribs,
      - set to empty array to keep no tags/attribs
      - use '*' as key for attributes that all tags should keep
   */
   var $keepTagsArr    = array('a', 'br', 'p', 'img');
   var $keepAttribsArr = array('*'   => array('class', 'style', 'title'),
                               'a'   => array('href', 'target'),
                               'img' => array('src', 'alt'));
                               
   //these empty or closing tags will be replaced with a space if they are not kept
   var $tags2SpaceArr = array('br', 'p', 'div', 'img');
   
   //private
   var $resultText    = '';
   var $openingTagArr = array();
   var $error         = false;
   var $lastTagType   = '';
   var $lastTagName   = '';
   var $standAloneAttribs = array('compact', 'checked', 'declare', 'readonly', 'disabled', 'selected', 'defer',
                                  'ismap', 'nohref', 'noshade', 'nowrap', 'multiple', 'noresize');

   var $emptyTags = array('area', 'base', 'basefont', 'bgsound', 'br', 'col', 'embed', 'frame',
                          'hr', 'img', 'input', 'isindex', 'link', 'meta', 'param', 'spacer', 'wbr');


   function HtmlTeaser($fullText = false, $length = false)
   {
     if($fullText)
     {
       $this->fullText = $fullText;
     }

     if($length)
     {
       $this->teaserLength = $length;
     }
   }

   function getTeaser($fullText = false)
   {
     $this->resultText    = '';
     $this->openingTagArr = array();
     $this->lastTagName   = '';
     $this->lastTagType   = '';
     $this->error = false;
     if($fullText)
     {
       $this->fullText = $fullText;
     }
    
     $this->parseText();
   
     $this->addTextToResult();
     
     return $this->resultText;
   }
   
   function parseText()
   {
     $plainLength   = 0;
     $resultText    = '';
     $plainText     = '';
     $fullTextLength = strlen($this->fullText);

     $i = 0;
     while(true)
     {
       //start position of next tag
       $tagStartPos = strpos($this->fullText, '<', $i);

       if($plainLength + ($tagStartPos - $i) > $this->teaserLength)
       {
         $tagStartPos = false;
       }

       //if there is a tag
       if($tagStartPos !== false)
       {
         //if we have an opening angled bracket, there must be a closing one, otherwise error
         $tagEndPos = strpos($this->fullText, '>', $tagStartPos);
         if(!$tagEndPos)
         {
           $this->error = true;
           break;
         }
         //plain text is everything until start of tag
         $plainLength += $tagStartPos - $i;
         $plainText    = substr($this->fullText, $i, $tagStartPos - $i);

         $tagContent = $this->processTag(substr($this->fullText, $tagStartPos, $tagEndPos - $tagStartPos), $tagStartPos);

         //add plain text to result plus current tag
         $this->resultText .= $plainText . $tagContent;
       }
       else //take required number of characters to get teaserlength until last space character
       {
         //we extract a few more characters to get a range for fetching the last space
         $plainText = substr($this->fullText, $i, $this->teaserLength + $this->fuzzyness - $plainLength);
         $lastSpacePos = strrpos($plainText, ' ');
         
         //double check start position of next tag
       	 $tagStartPos = strpos($this->fullText, '<', $i);
       	 if($tagStartPos !== false)
       	 {
       	 	$tagStartPos = $tagStartPos - $i;
       	 	if ($tagStartPos < $lastSpacePos)
       	 	{
       	 		$lastSpacePos = strrpos(substr($plainText, 0, $tagStartPos), ' ');
       	 	}
       	 }
         
         if($lastSpacePos && $lastSpacePos >= (strlen($plainText) - (2 * $this->fuzzyness)))
         {
           $this->resultText .= substr($plainText, 0, $lastSpacePos);
         }
         else
         {
           $this->resultText .= substr($this->fullText, $i, $this->teaserLength - $plainLength);
         }
         break;
       }

       if($tagEndPos !== false)
       {
         $i = $tagEndPos + 1;
       }

       //break if we have reached the length of the teaser
       if($plainLength >= $this->teaserLength || $i >= $fullTextLength || $this->error)
       {
         break;
       }
     }
     
     //default text if we have an error
     if($this->error)
     {
       $this->resultText = substr(strip_tags($this->fullText), 0, $this->teaserLength);
     }
     else
     {
       //close all remaining opening tags
       $tmpTagArr = array_reverse($this->openingTagArr);
       foreach($tmpTagArr as $tagName)
       {
         $this->lastTagType = HTML_TAG_TYPE_CLOSING;
         $this->lastTagName = $tagName;
         $this->resultText .= $this->formatTag($tagName, $this->lastTagType);
       }
     }
   }
   
   //returns the appropiate tag string depending on what tags and attributes to keep
   function processTag($tagStr)
   {
     $this->lastTagType = '-1';
     $tagStr = trim($tagStr, "<> \0\r\n\t");
     //cleanup common inconsistency with spaces
     $tagContent = str_replace(array('= "', ' ="'), '="', trim($tagStr, '/ '));

     //the tagname either ends with a space or is the whole tagcontent (as rest was stripped above)
     $this->lastTagName = strtolower(strtok($tagContent, " \0\r\n\t"));


     //determine whether this is an opening, empty or closing tag
     if(in_array($this->lastTagName, $this->emptyTags))
     {
       $this->lastTagType = HTML_TAG_TYPE_EMPTY;       
     }
     elseif(strpos($tagStr, '/') === 0)
     {
       $this->lastTagType = HTML_TAG_TYPE_CLOSING;
     }
     else
     {
       $this->lastTagType = HTML_TAG_TYPE_OPENING;
     }


     if(is_array($this->keepTagsArr) && !in_array($this->lastTagName, $this->keepTagsArr))
     {
       if($this->lastTagType != HTML_TAG_TYPE_OPENING
          && is_array($this->tags2SpaceArr)
          && (in_array('*', $this->tags2SpaceArr)
              || in_array($this->lastTagName, $this->tags2SpaceArr)))
       {
         return ' ';
       }
       else
       {
         return '';
       }
     }
     
     //match opening and closing tags
     if($this->lastTagType == HTML_TAG_TYPE_CLOSING)
     {
       if(array_pop($this->openingTagArr) != $this->lastTagName)
       {
         $this->error = true;
         return '';
       }
       reset($this->openingTagArr);
     }
     elseif($this->lastTagType == HTML_TAG_TYPE_OPENING)
     {
       $this->openingTagArr[] = $this->lastTagName;
     }
     

     if(is_array($this->keepAttribsArr)
        && !isset($this->keepAttribsArr[$this->lastTagName])
        && !isset($this->keepAttribsArr['*']))
     {
       return $this->formatTag($this->lastTagName, $this->lastTagType);
     }

     //filter attributes
     if(is_array($this->keepAttribsArr))
     {
       $attribStr    = '';
       $tmpAttribStr = '';
       while(true)
       {
         $attrib = strtok(" \0\r\n\t");
         if($attrib === false)
         {
           break;
         }
         if($tmpAttribStr) $tmpAttribStr .= ' ';
         $tmpAttribStr .= $attrib;

         //if attrib is standalone
         if(in_array($tmpAttribStr, $this->standAloneAttribs))
         {
           $tmpAttribStr = strtolower($tmpAttribStr);
           if((isset($this->keepAttribsArr[$this->lastTagName]) 
               && in_array($tmpAttribStr, $this->keepAttribsArr[$this->lastTagName]))
              || in_array($tmpAttribStr, $this->keepAttribsArr['*']))
           {
             if($attribStr) $attribStr .= ' ';
             $attribStr .= $tmpAttribStr . '="'. $tmpAttribStr . '"';
           }
           $tmpAttribStr = '';
         }
         /*
           a full key=value pair ends with a unescaped quotation mark, contains =" and
           the ending quotation mark must not be the one after the equal sign
         */
         else
         {
           $strLen   = strlen($tmpAttribStr);
           $equalPos = strpos($tmpAttribStr, '="');
           if(strrpos($tmpAttribStr, '"') === ($strLen - 1)
              && strpos($tmpAttribStr, '\"') !== ($strLen - 2)
              && $equalPos
              && $equalPos + 1 !== ($strLen - 1))
           {
             $attribKey = strtolower(substr($tmpAttribStr, 0, $equalPos));
             if((isset($this->keepAttribsArr[$this->lastTagName])
                 && in_array($attribKey, $this->keepAttribsArr[$this->lastTagName]))
                || in_array($attribKey, $this->keepAttribsArr['*']))
             {
               if($attribStr) $attribStr .= ' ';
               $attribStr .= $tmpAttribStr;
             }
             $tmpAttribStr = '';
           }
         }
       }
     }
     else
     {
       $attribStr    = strtok('>');
     }
     if($attribStr) $attribStr = ' ' . $attribStr;
     return $this->formatTag($this->lastTagName . $attribStr, $this->lastTagType);
   }

   //adds the text addText depending on settings
   function addTextToResult()
   {
     //now add the addText
     if($this->addText) 
     {
	     if ( strlen($this->resultText) < strlen($this->fullText) )
	     {
	       //if the text ends with a tag from addTextBeforeTagArr, add addtext before this tag
	       if(is_array($this->addTextBeforeTagArr) && in_array($this->lastTagName, $this->addTextBeforeTagArr)
	          && strrpos($this->resultText, '>') === strlen($this->resultText) - 1)
	       {
	         $lastTagPos = strrpos($this->resultText, '<');
	         if($lastTagPos)
	         {
	           $this->resultText = rtrim(substr($this->resultText, 0, strrpos($this->resultText, '<')), '-,.!?:;') 
	                       . $this->addText
	                       . $this->formatTag($this->lastTagName, $this->lastTagType);
	         }
	         else
	         {
	           $this->resultText = rtrim(substr($this->resultText, 0, strrpos($this->resultText, '<')), '-,.!?:;') . $this->addText; 
	         } 
	       }
	       else
	       {
	         $this->resultText = rtrim($this->resultText, '-,.!?:;') . $this->addText;
	       }
	     }
     }	
   }   
   
   function formatTag($tagStr, $tagType)
   {
     switch($tagType)
     {
       case HTML_TAG_TYPE_EMPTY:
         return '<' . $tagStr . '/>';
       break;
       case HTML_TAG_TYPE_OPENING:
         return '<' . $tagStr . '>';
       break;
       case HTML_TAG_TYPE_CLOSING:
         return '</' . $tagStr . ">";
       break;
       default:
        $this->error = true;
        return '';
     }
   }
   
   function setKeepAttribsArr($keepAttribsArr)
   {
     if(is_array($keepAttribsArr))
     {
       $this->keepAttribsArr = $keepAttribsArr;
     }
     else
     {
       $this->keepAttribsArr = false;
     }
   }
   
   function setKeepTagsArr($keepTagsArr)
   {
     if(is_array($keepTagsArr))
     {
       $this->keepTagsArr = $keepTagsArr;
     }
     else
     {
       $this->keepTagsArr = false;
     }
   }
   
   function setFuzzyness($fuzzyness)
   {
     $this->fuzzyness = (int)$fuzzyness;
   }
   
   function setAddText($text)
   {
     if(is_string($text))
     {
       $this->addText = $text;
     }
     else
     {
       $this->addText = '';
     }
   }

   function setAddTextBeforeTagArr($addTextBeforeTagArr)
   {
     if(is_array($addTextBeforeTagArr))
     {
       $this->addTextBeforeTagArr = $addTextBeforeTagArr;
     }
     else
     {
       $this->addTextBeforeTagArr = false;
     }
   }   

   function setFullText($fullText)
   {
     if(is_string($fullText))
     {
       $this->fullText = $fullText;
     }
     else
     {
       $this->fullText = '';
     }
   } 
   
   function setTeaserLength($teaserLength)
   {
     $this->teaserLength = (int)$teaserLength;
   } 
   
   function setTags2SpaceArr($tags2SpaceArr)
   {
     if(is_array($tags2SpaceArr))
     {
       $this->tags2SpaceArr = $tags2SpaceArr;
     }
     else
     {
       $this->tags2SpaceArr = false;
     }
   }    
}


?>
