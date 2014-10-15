<?php
ini_set('memory_limit', '512M');
error_reporting(E_ALL & ~E_NOTICE);
include("lib/LIB_http.php");
include("lib/LIB_parse.php");
$limit = 100000;
$court_name = array(
  'TPD',
  'PCD',
  'SLD',
  'TYD',
  'SCD',
  'MLD',
  'TCD',
  'NTD',
  'CHD',
  'ULD',
  'CYD',
  'TND',
  'KSD',
  'PTD',
  'TTD',
  'HLD',
  'ILD',
  'KLD',
  'PHD',
  'KSY',
  'LCD',
  'KMD',
);

$output="output/all_detail.csv";
if(file_exists($output)){
  $exists = csv2array($output, TRUE);
  $first_run = FALSE;
  $first_row = FALSE;
}
else{
  $exists = array();
  $first_run = TRUE;
  $first_row = TRUE;
}

foreach($court_name as $court){	
  echo "Running {$court} \n";
  
	$filename= "output/".$court.".csv";
  if(file_exists($filename)){
    $rows = csv2array($filename);
  }
  else{
    continue; // to next court
  }

  foreach($rows as $cols){
    $x++;
    if($x > $limit){
      break;
    }
    $url = $cols[2];
    $key = $court.$cols[0];
    if($exists[$key]){
      // skip exists
      // echo "skip {$key}\n";
      continue;
    }
    usleep(500000);
    $response_start = http_get(str_replace("WHD6K05", "WHD6K03", $url), "http://cdcb.judicial.gov.tw/abbs/wkw/");

    // remove unecessery blank td
    $content = str_replace(array("\n", "\r\n", "\r"), '' , $response_start["FILE"]);
    $content = preg_replace('/<tr>\s*<td\scolspan="7">[^<]+<\/td>\s*<\/tr>/i', '', $content);
    $response_parse_start = parse_array($content,"<td","</td>");

    $fields = array();
    $row = '';
    if($first_row && $first_run){
      for($i=1; $i<count($response_parse_start); $i++){
        if($i%2==1){
          $fields[] = iconv("BIG5","UTF-8", trim(preg_replace('/[\n\r\t]/','',strip_tags($response_parse_start[$i]))));
        }	
      }
      $fields[] = '董監事';
      $row = array2csv(array($fields));
      file_put_contents($output, $row, FILE_APPEND);

      $fields = array();
      $row = '';
      $first_row = FALSE;
    }
    $permit = array();
    for($i=1;$i<count($response_parse_start);$i++){
      if($i%2==0){
        $str = iconv("BIG5","UTF-8", trim(preg_replace('/[\n\r\t]/','',strip_tags($response_parse_start[$i]))));
        $str = removespace($str);
        if($i == 2){
          $str = $key;
        }
        if($i != 26 && $i != 32){
          $str = chinese2num($str);
        }
        $fields[] = $str;
        /*
        if($i == 36){
          // 臺北市政府文化局中華民國103年4月25日北市文化文創字第10331182600號函
          $permit = permit_get($str);
        }
        */
      }	
    }
    $fields[] = trustee_get(str_replace('WHD6K05', 'WHD6K04', $url));
    /*
    $fields[] = $permit['gov'];
    $fields[] = $permit['date'];
    $fields[] = $permit['num'];
    */
    if(count($fields) > 10){
      $row = array2csv(array($fields));
      echo "Processed $row\n";
      file_put_contents($output, $row, FILE_APPEND);
    }
  }
}
function csv2array($filename, $nodata = FALSE){
  $data = array();
  if (($handle = fopen($filename, "r")) !== FALSE) {
    while (($col = fgetcsv($handle, 0, ",")) !== FALSE) {
      if($nodata){
        $data[$col[0]] = 1;
      }
      else{
        $data[$col[0]] = $col;
      }
    }
    fclose($handle);
  }
  return $data;
}
function array2csv($array){
  $csv = '';
  foreach($array as $k => $v){
    $csv .= '"'.implode('","', $v).'"'."\n";
  }
  return $csv;
}

function trustee_get($url){
  $res = http_get($url, "http://cdcb.judicial.gov.tw/abbs/wkw/");
  $res_parsed = parse_array($res['FILE'], "<tr", "</tr>");
  $rows = array();
  foreach($res_parsed as $key => $r){
    if($key < 3) {
      continue;
    }
    $fields = array();
    $fields = parse_array($r, "<td", "</td>");
    foreach($fields as $k => $f){
      if($k < 1) {
        unset($fields[$k]);
        continue;
      }
      $f = preg_replace("@</?td[^>]*>@i", '', $f);
      $f = iconv("BIG5", "UTF-8", trim($f));
      $f = removespace($f);
      $fields[$k] = $f;
    }
    $rows[] = implode(':', $fields);
  }
  return implode("|", $rows);
}

function removespace($i){
  return str_replace(array('&nbsp;', '　', ' '), '', $i);
}

function chinese2num($in){
  $map = array(
    '一'=>1,
    '二'=>2,
    '三'=>3,
    '四'=>4,
    '五'=>5,
    '六'=>6,
    '七'=>7,
    '八'=>8,
    '九'=>9,
    '十'=>0,
    '０'=>0,
    '元月'=>'1月',
  ); 
  return str_replace(array_keys($map), $map , $in);
}
function permit_get($str){
  // 臺北市政府文化局中華民國103年4月25日北市文化文創字第10331182600號函
  $result = array(
    'gov' => '',
    'date' => '',
    'num' => '',
  );
  $matches = array();
  preg_match('/(中華)?(民國)?\d{1,3}年\d{1,2}月\d{1,2}日/i', $str, $matches);
  $date = $matches[0];
  $result['date'] = $date;
  $str = str_replace(array($date, '，', '、'), '||', $str);
  $str = explode('||', $str);
  foreach($str as $s){
    if(!empty($s)){
      if(preg_match('/第\d+號/i', $s)){
        $result['num'] = $s;
      }
      else{
        $result['gov'] = $s;
      }
    }
  }
  return $result;
}
