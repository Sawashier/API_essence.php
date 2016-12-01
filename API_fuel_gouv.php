<?php

/* url: /?cp=X&type=Y&sort=Z
  ->X: code postal (Obligatoire)
  ->Y: type de carburant, peut être un des suivant: gazole, sp95, sp98, gpl, e10, e85 (Obligatoire)
  ->Z: ordre de tri, soit asc, soit desc (comme en SQL pour nous simplifier la vie)
 *  (facultatif, défaut asc si non précisé, du moins cher au plus cher).
 */
$today = time();
$todayMoins7 = date("Ymd",$today - (3600 * 24 * 8));

$fileZip = 'cache_zip.zip/PrixCarburants_quotidien_' . $todayMoins7 . '.zip';
$fileXml = 'cache_xml.xml/PrixCarburants_quotidien_' . $todayMoins7 . '.xml';

API_fuel_file($today, $todayMoins7, $fileXml, $fileZip);


if (!isset($_REQUEST['cp']) 
        || empty($_REQUEST['cp'])
        || intval($_REQUEST['cp'])<=0) {
    
    echo json_encode(array("results" => array(), "status" => "ERROR_PARAM", "error_message" => "Code postal invalide"), JSON_PRETTY_PRINT);
    exit(0);
}

if (!isset($_REQUEST['type'])
        || empty($_REQUEST['type']) ){
    
    echo json_encode(array("results" => array(), "status" => "ERROR_PARAM", "error_message" => "Champ Carburant invalide"), JSON_PRETTY_PRINT);
    exit(0);
}

if ($_REQUEST['type'] == "Gazole" 
        || $_REQUEST['type'] == "SP95" 
        || $_REQUEST['type'] == "SP98" 
        || $_REQUEST['type'] == "GPLc" 
        || $_REQUEST['type'] == "E10" 
        || $_REQUEST['type'] == "E85") {

    if (file_exists($fileXml)) {
        $xml = simplexml_load_file($fileXml);

        foreach ($xml as $pdv) {
            if (intval($_REQUEST['cp']) == intval($pdv->attributes()->cp)) {
                echo $pdv->ville . "<br>" . $pdv->adresse . "<br>";
                foreach ($pdv->prix as $info) {
                    if ($_REQUEST['type'] == $info->attributes()->nom) {
                        echo $info->attributes()->nom . " " . $info->attributes()->valeur / 1000 . " " . '€' . "<br>" . "<br>";
                    }
                }
            }
        }
    } 
} elseif ($_REQUEST['type'] !== "Gazole" 
        || $_REQUEST['type'] !== "SP95" 
        || $_REQUEST['type'] !== "SP98" 
        || $_REQUEST['type'] !== "GPLc" 
        || $_REQUEST['type'] !== "E10" 
        || $_REQUEST['type'] !== "E85") {
    
    echo json_encode(array("results" => array(), "status" => "ERROR_PARAM", "error_message" => "Carburant invalide"), JSON_PRETTY_PRINT);
    exit(0);

}
    
function API_fuel_file($today, $todayMoins7, $fileXml, $fileZip) {

    
    if (file_exists($fileXml)) {
        if (time() - filemtime($fileXml) > (3600 * 24)) {

            $url = "http://donnees.roulez-eco.fr/opendata/jour/" . $todayMoins7;

            $data = file_get_contents($url);
            $fileZipName = 'cache_zip.zip/PrixCarburants_quotidien_' . $todayMoins7 . '.zip';
            file_put_contents($fileZipName, $data);

            $zip = new ZipArchive();

            if ($zip->open($fileZipName) === TRUE) {
                $zip->extractTo(dirname($fileXml));
                $zip->close();
            }
        }
    }
}