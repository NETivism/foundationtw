#!/usr/bin/php
<?php
require_once "lib/LIB_http.php";
require_once "lib/LIB_parse.php";

ini_set("memory_limit", '256M');
ini_set("max_execute_time", 10000);
global $debug;
$debug = 0;

function catch_total($court){
  dd("Enter calc total for ".$court);
  $action = "http://cdcb.judicial.gov.tw/abbs/wkw/WHD6K02.jsp";
  $method = "POST";
  $ref = " ";
  //data setting
  $data_array["court"] = $court;
  $data_array["classType"] = "RA001";
  $data_array["year"] = "";
  $data_array["word"] = "";
  $data_array["no"] = "";
  $data_array["recno"] = "";
  $data_array["kind"] = "0";
  $data_array["Date1Start"] = "";
  $data_array["Date1End"] = "";
  $data_array["kind1"] = "0";
  $data_array["comname"] = "";

  $response = http($action, $ref, $method, $data_array, EXCL_HEAD);
  $response_parse = parse_array($response["FILE"],"<td","</td>");
  $text = parse_array($response_parse[97],"<td>","</td>");
  $string = (string)$text[0];
  $pattern = "/[\d]/";
  preg_match_all($pattern, $string, $match);
  $total = implode($match[0]);
  $total = (int)$total;
  dd("Leave calc total for ".$court);
  return $total;
}

function crawler_court($court, $max_page = 0){
  dd("Enter crawler for ".$court);
  $total_items = catch_total($court);
  $total_page = ((int)($total_items/10))+1;

  list($filename) = explode('&', $court, 2);
  $filename = 'output/'.$filename.'.csv';
  if(file_exists($filename)){
    $rows = csv2array($filename);
    $first_run = FALSE;
  }
  else{
    $first_run = TRUE;
  }

  $max_page = empty($max_page) ? $total_page : $max_page;
  
  // from last page
  for($page = $max_page; $page >= 1; $page--){
    echo "fetch page $page\n";
    $action = "http://cdcb.judicial.gov.tw/abbs/wkw/WHD6K02.jsp";
    $method = "POST";
    $ref = " ";
    //data setting
    $data_array["court"] = $court;
    $data_array["classType"] = "RA001";
    $data_array["year"] = "";
    $data_array["word"] = "";
    $data_array["no"] = "";
    $data_array["recno"] = "";
    $data_array["kind"] = "0";
    $data_array["Date1Start"] = "";
    $data_array["Date1End"] = "";
    $data_array["kind1"] = "0";
    $data_array["comname"] = "";
    $data_array["pageSize"] = "10";
    $data_array["pageTotal"]= $total_items;
    $data_array["pageNow"] = $page;
    //get response
    $response = http($action, $ref, $method, $data_array, EXCL_HEAD);
    $link = parse_array($response["FILE"], "<a", "/>");//get link
    $after_parse = parse_array($response["FILE"], "<div ", "</div>");//parse
    $key_array = $name_array = $link_array = $num_array = $row = array();

    // every row has 8 column
    // skip first 8 column for header
    $j = count($after_parse);
    $col = 1;
    for($i=8; $i < $j; $i++){
      if($col == 2){
        $key = return_between($after_parse[$i], "<div align='center'>", "</div>" ,EXCL);
        $key_array[] = str_replace(array('&nbsp;', '　', ' '), '', $key);
      }
      /*
      if($col == 3){
        $num = return_between($after_parse[$i], "<div align='center'>", "</div>" ,EXCL);
        $num_array[] = str_replace("&nbsp;","",$num);
      }
      */
      elseif($col == 5){	
        $name = return_between($after_parse[$i], "<div align='center'>", "</div>" ,EXCL);//remove div
        $name_array[] = str_replace("&nbsp;", "", $name);//remove space
      }
      elseif($col == 8){
        $col = 0;
      }
      $col++;
    }

    //Creating link array
    for($i=0; $i < count($link); $i++){
      $link_href = get_attribute($link[$i], "href");
      $link_array[$i] = "http://cdcb.judicial.gov.tw/abbs/wkw/".$link_href;
    }

    // check if all exists
    for($i=0; $i < count($name_array); $i++){
      $line = array(
        iconv("BIG5", "UTF-8", $key_array[$i]),
        iconv("BIG5", "UTF-8", $name_array[$i]),
        $link_array[$i],
      );
      // check exist?
      if($rows[$line[0]]){
        $exists_row++;
        echo $line[1]." skipped \n";
        continue;
      }
      else{
        $row[] = $line;
        $rows[$line[0]] = $line;
        echo $line[1]." add \n";
      }
    }
    echo "saving...\n";
    file_put_contents($filename, array2csv($row), FILE_APPEND);
    if($exists_row == count($name_array) && !$first_run){
      echo "All the item in this page added, stop crawler.\n";
      break;
    }
    usleep(500000);
  }
  
  dd("Leave crawler for ".$court);
}

function csv2array($filename){
  $data = array();
  copy($filename, $filename.'.bak');
  if (($handle = fopen($filename, "r")) !== FALSE) {
    while (($col = fgetcsv($handle, 0, ",")) !== FALSE) {
      $data[$col[0]] = $col;
    }
    fclose($handle);
  }
  krsort($data);
  return $data;
}

function array2csv($array){
  $csv = '';
  foreach($array as $k => $v){
    $csv .= '"'.implode('","', $v).'"'."\n";
  }
  return $csv;
}

function dd($in){
  global $debug;
  if($debug){
    if(is_string($in)){
      print "debug: ".$in."\n";
    }
    else{
      print "debug:\n";
      print_r($in);
    }
  }
}

$all_court = array(
  'TPD&臺灣台北地方法院',
  'PCD&臺灣新北地方法院',
  'SLD&臺灣士林地方法院',
  'TYD&臺灣桃園地方法院',
  'SCD&臺灣新竹地方法院',
  'MLD&臺灣苗栗地方法院',
  'TCD&臺灣臺中地方法院',
  'NTD&臺灣南投地方法院',
  'CHD&臺灣彰化地方法院',
  'ULD&臺灣雲林地方法院',
  'CYD&臺灣嘉義地方法院',
  'TND&臺灣臺南地方法院',
  'KSD&臺灣高雄地方法院',
  'PTD&臺灣屏東地方法院',
  'TTD&臺灣臺東地方法院',
  'HLD&臺灣花蓮地方法院',
  'ILD&臺灣宜蘭地方法院',
  'KLD&臺灣基隆地方法院',
  'PHD&臺灣澎湖地方法院',
  'KSY&臺灣高雄少年法院',
  'LCD&褔建連江地方法院',
  'KMD&福建金門地方法院',
);

foreach($all_court as $c){
  //crawler_court($c, 20);
  crawler_court($c);
}
