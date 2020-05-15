<?php
set_time_limit(30000);
    $fileName = $_FILES['filedata']['name'];
    $tmpName  = $_FILES['filedata']['tmp_name'];
    $fileSize = $_FILES['filedata']['size'];
    $fileType = $_FILES['filedata']['type'];
    $fp      = fopen($tmpName, 'r');
    $content = fread($fp, filesize($tmpName));
    fclose($fp);
    
echo "{\"success\": true, \"data\":$content}";

?>