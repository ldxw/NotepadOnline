<?php ($visit) ? ("") : (header('location: /'));?>
<?php
/*这是NPO的库文件
**------------------
**如果您不了解PHP或程序的功能实现方式及内容架构，请勿修改内容*/

class ascii {
  function decode($str, $prefix="&#") {
    $str = str_replace($prefix, "", $str);
    $a = explode(";", $str);
    array_pop($a);//去掉最后一个空字符串
    $utf = '';
    foreach ($a as $dec) {
      /*非纯数字段（历史脏数据、被截断的实体）直接跳过，
      **否则 PHP 8 下与整数比较会抛 TypeError*/
      if (!is_string($dec) || !ctype_digit($dec)) continue;
      $cp = (int)$dec;
      /*非法码点：超出 Unicode 上限、落在代理区（U+D800–U+DFFF）或为负值*/
      if ($cp < 0 || $cp > 0x10FFFF || ($cp >= 0xD800 && $cp <= 0xDFFF)) $cp = 0xFFFD;
      if ($cp < 0x80) {
        $utf .= chr($cp);
      } else if ($cp < 0x800) {
        $utf .= chr(0xC0 + (($cp >> 6) & 0x1F));
        $utf .= chr(0x80 + ($cp & 0x3F));
      } else if ($cp < 0x10000) {
        $utf .= chr(0xE0 + (($cp >> 12) & 0x0F));
        $utf .= chr(0x80 + (($cp >> 6) & 0x3F));
        $utf .= chr(0x80 + ($cp & 0x3F));
      } else if ($cp < 0x200000) {
        $utf .= chr(0xF0 + (($cp >> 18) & 0x07));
        $utf .= chr(0x80 + (($cp >> 12) & 0x3F));
        $utf .= chr(0x80 + (($cp >> 6) & 0x3F));
        $utf .= chr(0x80 + ($cp & 0x3F));
      } else if ($cp < 0x4000000) {
        $utf .= chr(0xF8 + (($cp >> 24) & 0x03));
        $utf .= chr(0x80 + (($cp >> 18) & 0x3F));
        $utf .= chr(0x80 + (($cp >> 12) & 0x3F));
        $utf .= chr(0x80 + (($cp >> 6) & 0x3F));
        $utf .= chr(0x80 + ($cp & 0x3F));
      } else {
        $utf .= chr(0xFC + (($cp >> 30) & 0x01));
        $utf .= chr(0x80 + (($cp >> 24) & 0x3F));
        $utf .= chr(0x80 + (($cp >> 18) & 0x3F));
        $utf .= chr(0x80 + (($cp >> 12) & 0x3F));
        $utf .= chr(0x80 + (($cp >> 6) & 0x3F));
        $utf .= chr(0x80 + ($cp & 0x3F));
      }
    }
    return $utf;
  }
  function encode($c, $prefix="&#") {
    $len = strlen($c);
    $a = 0;
    $scill = '';
    while ($a < $len) {
      $ud = 0;
      /*每个多字节分支都要求剩余长度足够，截断序列走最后的兜底分支；
      **兜底覆盖 0x80-0xBF（孤立续字节）、0xFE-0xFF 与截断序列，
      **统一输出替换符并前进 1 字节，避免不前进导致的死循环与内存膨胀*/
      if (ord($c[$a]) >= 0 && ord($c[$a]) <= 127) {
        $ud = ord($c[$a]);
        $a += 1;
      } else if (ord($c[$a]) >= 192 && ord($c[$a]) <= 223 && $a + 1 < $len) {
        $ud = (ord($c[$a]) - 192) * 64 + (ord($c[$a + 1]) - 128);
        $a += 2;
      } else if (ord($c[$a]) >= 224 && ord($c[$a]) <= 239 && $a + 2 < $len) {
        $ud = (ord($c[$a]) - 224) * 4096 + (ord($c[$a + 1]) - 128) * 64 + (ord($c[$a + 2]) - 128);
        $a += 3;
      } else if (ord($c[$a]) >= 240 && ord($c[$a]) <= 247 && $a + 3 < $len) {
        $ud = (ord($c[$a]) - 240) * 262144 + (ord($c[$a + 1]) - 128) * 4096 + (ord($c[$a + 2]) - 128) * 64 + (ord($c[$a + 3]) - 128);
        $a += 4;
      } else if (ord($c[$a]) >= 248 && ord($c[$a]) <= 251 && $a + 4 < $len) {
        $ud = (ord($c[$a]) - 248) * 16777216 + (ord($c[$a + 1]) - 128) * 262144 + (ord($c[$a + 2]) - 128) * 4096 + (ord($c[$a + 3]) - 128) * 64 + (ord($c[$a + 4]) - 128);
        $a += 5;
      } else if (ord($c[$a]) >= 252 && ord($c[$a]) <= 253 && $a + 5 < $len) {
        $ud = (ord($c[$a]) - 252) * 1073741824 + (ord($c[$a + 1]) - 128) * 16777216 + (ord($c[$a + 2]) - 128) * 262144 + (ord($c[$a + 3]) - 128) * 4096 + (ord($c[$a + 4]) - 128) * 64 + (ord($c[$a + 5]) - 128);
        $a += 6;
      } else { //error：非法或截断的 UTF-8，输出替换符并前进
        $ud = 65533;
        $a += 1;
      }
      $scill .= $prefix.$ud.";";
    }
    return $scill;
  }
}
?>