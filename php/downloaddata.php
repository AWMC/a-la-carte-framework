<?php




header('Content-disposition: attachment; filename=carte_export.json');
header('Content-type: application/json; charset=utf-8');


//$jsondata = to_utf8($_POST['jsondata']);
//$test = urldecode($jsondata);

$jsondata = $_POST['jsondata'];


$test = rawurldecode($jsondata);
//echo($test);
//echo("<p></p>");
//echo("NEXT:");

//echo("<p></p>");

//$test2 = json_encode($test);

//echo($test2);
//echo("<p></p>");
//echo("NEXT:");

//echo("<p></p>");

//$jsonIterator = new RecursiveIteratorIterator(new RecursiveArrayIterator(json_decode($test, TRUE)), RecursiveIteratorIterator::SELF_FIRST);
    
    echo ($test);
    
//foreach ($jsonIterator as $key => $val) {
//    if(is_array($val)) {
//        echo "$key:\n";
//    } else {
//        echo "$key => $val\n";
//    }
//}


?>