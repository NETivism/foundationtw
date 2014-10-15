<?php
/*
########################################################################                                        
Copyright 2007, Michael Schrenk                                                                                 
   This software is designed for use with the book,                                                             
   "Webbots, Spiders, and Screen Scarpers", Michael Schrenk, 2007 No Starch Press, San Francisco CA             
                                                                                                                
W3C® SOFTWARE NOTICE AND LICENSE                                                                                
                                                                                                                
This work (and included software, documentation such as READMEs, or other                                       
related items) is being provided by the copyright holders under the following license.                          
 By obtaining, using and/or copying this work, you (the licensee) agree that you have read,                     
 understood, and will comply with the following terms and conditions.                                           
                                                                                                                
Permission to copy, modify, and distribute this software and its documentation, with or                         
without modification, for any purpose and without fee or royalty is hereby granted, provided                    
that you include the following on ALL copies of the software and documentation or portions thereof,             
including modifications:                                                                                        
   1. The full text of this NOTICE in a location viewable to users of the redistributed                         
      or derivative work.                                                                                       
   2. Any pre-existing intellectual property disclaimers, notices, or terms and conditions.                     
      If none exist, the W3C Software Short Notice should be included (hypertext is preferred,                  
      text is permitted) within the body of any redistributed or derivative code.                               
   3. Notice of any changes or modifications to the files, including the date changes were made.                
      (We recommend you provide URIs to the location from which the code is derived.)                           
                                                                                                                
THIS SOFTWARE AND DOCUMENTATION IS PROVIDED "AS IS," AND COPYRIGHT HOLDERS MAKE NO REPRESENTATIONS OR           
WARRANTIES, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO, WARRANTIES OF MERCHANTABILITY OR FITNESS          
FOR ANY PARTICULAR PURPOSE OR THAT THE USE OF THE SOFTWARE OR DOCUMENTATION WILL NOT INFRINGE ANY THIRD         
PARTY PATENTS, COPYRIGHTS, TRADEMARKS OR OTHER RIGHTS.                                                          
                                                                                                                
COPYRIGHT HOLDERS WILL NOT BE LIABLE FOR ANY DIRECT, INDIRECT, SPECIAL OR CONSEQUENTIAL DAMAGES ARISING OUT     
OF ANY USE OF THE SOFTWARE OR DOCUMENTATION.                                                                    
                                                                                                                
The name and trademarks of copyright holders may NOT be used in advertising or publicity pertaining to the      
software without specific, written prior permission. Title to copyright in this software and any associated     
documentation will at all times remain with copyright holders.                                                  
########################################################################                                        
*/

/***********************************************************************
harvest_links($url)                                                     
-------------------------------------------------------------			
DESCRIPTION:															
		Collects all links from a web page                              
                                                                        
INPUT:																    
		$url                                                            
            Fully resolved web address of target web page               
RETURNS:																
		Returns an array of links                                       
***********************************************************************/
function harvest_links($url)
    {
    # Initialize
    global $DELAY;
    $link_array = array();
    
    # Get page base for $url
    $page_base = get_base_page_address($url);
    
    # Download webpage
    sleep($DELAY);          
    $downloaded_page = http_get($url, "");
    $anchor_tags = parse_array($downloaded_page['FILE'], "<a", "</a>", EXCL);
    # Put http attributes for each tag into an array
    for($xx=0; $xx<count($anchor_tags); $xx++)
        {
        $href = get_attribute($anchor_tags[$xx], "href");
        $resolved_addres = resolve_address($href, $page_base);
        $link_array[] = $resolved_addres;
        echo "Harvested: ".$resolved_addres." \n";
        }
    return $link_array;
    }

/***********************************************************************
archive_links($spider_array, $penetration_level, $temp_link_array)      
-------------------------------------------------------------			
DESCRIPTION:															
		Puts raw links into an archival array                           
                                                                        
INPUT:																    
        $spider_array                                                   
            The name of the archival array                              
                                                                        
        $penetration_level                                              
            Page depth at which the spidering was conducted             
                                                                        
        $temp_link_array                                                
            $temporary array of raw links                               
RETURNS:																
		Returns archival array                                          
***********************************************************************/
function archive_links($spider_array, $penetration_level, $temp_link_array)
    {
    for($xx=0; $xx<count($temp_link_array); $xx++)
        {
        # Don't add exlcuded links to $spider_array
        if(!excluded_link($spider_array, $temp_link_array[$xx]))
            {
            $spider_array[$penetration_level][] = $temp_link_array[$xx];
            }
        }
    return $spider_array;
    }

/***********************************************************************
get_domain($url)                                                        
-------------------------------------------------------------			
DESCRIPTION:															
        Gets the domain for a web address                               
INPUT:																    
        $url                                                            
            The web address                                             
                                                                        
RETURNS:																
		Returns the domain for the inputed url                          
***********************************************************************/
function get_domain($url)
    {
    // Remove protocol from $url
    $url = str_replace("http://", "", $url);
    $url = str_replace("https://", "", $url);
    
    // Remove page and directory references
    if(stristr($url, "/"))
        $url = substr($url, 0, strpos($url, "/"));
    
    return $url;
    }

/***********************************************************************
excluded_link($spider_array, $link)                                     
-------------------------------------------------------------			
DESCRIPTION:															
        Tests a link to see if it should be in the archival array       
INPUT:																    
        $spider_array                                                   
            The spider's archival array                                 
                                                                        
        $link                                                           
            The link under test                                         
RETURNS:																
		Returns TRUE or FALSE depending on if the link should be        
        excluded                                                        
***********************************************************************/
function excluded_link($spider_array, $link)
    {
    # Initialization
    global $SEED_URL, $exclusion_array, $ALLOW_OFFISTE;
    $exclude = false;
    
    // Exclude links that are JavaScript commands
    if(stristr($link, "javascript"))
        {
        echo "Ignored JavaScript fuction: $link\n";
        $exclude=true;
        }
    
    // Exclude redundant links
    for($xx=0; $xx<count($spider_array); $xx++)
        {
        $saved_link="";
        while(isset($saved_link))
            {
            $saved_link=array_pop($spider_array[$xx]);
            if($link == array_pop($spider_array[$xx]))
                {
                echo "Ignored redundant link: $link\n";
                $exclude=true;
                break;
                }
            }
        }
    
    // Exclude links found in $exclusion_array
    for($xx=0; $xx<count($exclusion_array); $xx++)
        {
        if(stristr($link, $exclusion_array[$xx]))
            {
            echo "Ignored excluded link: $link\n";
            $exclude=true;
            }
        }
        
    // Exclude offsite links if requested
    if($ALLOW_OFFISTE==false)
        {
        if(get_domain($link)!=get_domain($SEED_URL))
            {
            echo "Ignored offsite link: $link\n";
            $exclude=true;
            }
        }

    return $exclude;
    }
?>