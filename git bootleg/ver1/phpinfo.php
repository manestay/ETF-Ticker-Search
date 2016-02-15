<?php
include('simple_html_dom.php');
$text = '<table class="mainTable" cellpadding="0" cellspacing="0" align="center">
    <tr>
        <td class="day">test 1</td>
        <td data-info="" class=" c"></td>
        <td data-info="91" class="widgets c91">data 1</td>
        <td data-info="" class=" c"></td>
        <td data-info="109" class="widgets c109">data 2</td>
        <td data-info="" class=" c"></td>
        <td data-info="126" class="widgets c126">data 3</td>
    </tr>
    <tr>
        <td class="day">test 2</td>
        <td data-info="83" class="widgets c83">data 4<div class="triangle"></div></td>
        <td data-info="" class=" c"></td>
        <td data-info="100" class="widgets c100">data 5<div class="triangle"></div></td>
        <td data-info="" class=" c"></td>
        <td data-info="118" class="widgets c118">data 6<div class="triangle"></div></td>
        <td data-info="" class=" c"></td>
    </tr>
    <tr>
        <td class="day">test 3</td>
        <td data-info="84" class="widgets c84">data 7</td>
        <td data-info="92" class="widgets c92">data 8</td>
        <td data-info="101" class="widgets c101">data 9</td>
        <td data-info="110" class="widgets c110">data 10</td>
        <td data-info="119" class="widgets c119">data 11</td>
        <td data-info="127" class="widgets c127">data 12</td>
    </tr>
</table>';

echo  "<div>Original Text: <xmp>$text</xmp></div>";

$html = str_get_html($text);

$divArray = $html->find('div');

// if find exists
if ($divArray) {

  echo '<br>Find function found '. count($divArray) . ' results: ';

  foreach($divArray as $key=>$div){
    echo '<br>'.$key . ': ' . $div->tag . ' with class = ' . $div->class;
  }
}
else
  echo "Find() fails !";
?>