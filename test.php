<?php

function tozh($t){
    var result, buffer, tmp, i$, ref$, len$, digit;
    result = 0;
    buffer = 0;
    tmp = 0;
    for (i$ = 0, len$ = (ref$ = number.split('')).length; i$ < len$; ++i$) {
      digit = ref$[i$];
      if (zhwordmap[digit] != null) {
        digit = zhwordmap[digit];
      }
      if (digit in zhmap) {
        tmp = zhmap[digit];
      } else if (in$(digit, commitword)) {
        result += (buffer + tmp) * zhmap10[digit];
        buffer = 0;
        tmp = 0;
      } else {
        if (digit === '十' && tmp === 0) {
          tmp = 1;
        }
        buffer += tmp * zhmap10[digit];
        tmp = 0;
      }
    }
    return result + buffer + tmp;
}

$test = array(
 '六百七十',
 '八十一',
 '九十六',
);

foreach($test as $t){

}
