<?php
/*on inclut les fichiers nécessaires (autoload.php)
on inclut les bons paramètres pour se connecter (config APi / authentification)
client_id, client_secret, username, password, scope
on récupère un token (trouver la fonction fournie) pour pouvoir utiliser l’API
on récupère les coordonnées GPS (4 points) depuis l’API de google
on envoie une requête à l’api netatmo pour récupérer les données publiques avec le token et les points GPS (4 points)
on peut passer en plus le paramètre pour filtrer les stations (à TRUE)
on récupère ce que l’API de netatmo nous renvoie et on traite les données pour afficher la moyenne et les informations….*/

include 'src/Netatmo/autoload.php';

$config = array();
$config['client_id'] = '5a2108c02b2b4655f18b53f9';
$config['client_secret'] = 'kyfS8fzUFtqqwuwbl0NIXzdOMPFk4wsSZCvWORF3zmxxu';
$config['scope'] = 'read_station read_thermostat write_thermostat';
$client = new Netatmo\Clients\NAApiClient($config);
$div= "";
$username = 'steeven.demay@orange.fr';
$pwd = '@76Eb1D62d88@';
$client->setVariable('username', $username);
$client->setVariable('password', $pwd);
try
{
    $tokens = $client->getAccessToken(); //On récupère le token
    $refresh_token = $tokens['refresh_token'];
    $access_token = $tokens['access_token'];
}
catch(Netatmo\Exceptions\NAClientException $ex)
{
    echo "An error occcured while trying to retrive your tokens \n";
}


//Fonction qui retourne un tableau correspondant aux données de l'adresse saisie
function lookup($string){

   $string = str_replace (" ", "+", urlencode($string));
   $details_url = "https://maps.googleapis.com/maps/api/geocode/json?address=".$string."&key=AIzaSyBr71wCMwJ886LywpJ4IIkMl5rGpzmZ3Ds";

   $ch = curl_init();
   curl_setopt($ch, CURLOPT_URL, $details_url);
   curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
   $response = json_decode(curl_exec($ch), true);

   // If Status Code is ZERO_RESULTS, OVER_QUERY_LIMIT, REQUEST_DENIED or INVALID_REQUEST
   if ($response['status'] != 'OK') {
    return null;
   }

   //print_r($response);
   $geometry = $response['results'][0]['geometry'];


    $array = array(
        'latNE' => $geometry['bounds']['northeast']['lat'],
        'lonNE' => $geometry['bounds']['northeast']['lng'],
        'latSW' => $geometry['bounds']['southwest']['lat'],
        'lonSW' => $geometry['bounds']['southwest']['lng'],
        'location_type' => $geometry['location_type'],
    );

    return $array;

}



if(isset($_POST['adresse'])){
  $city = $_POST['adresse'];
  $array = lookup($city);
  //print_r($array);

  $div .= "Affichage du résultat : <br>
        Coordonnées GPS de la zone donnée : <br>
        Lat NE : ".$array['latNE']." <br>
        Lon NE : ".$array['lonNE']." <br>
        Lat SW : ".$array['latSW']." <br>
        Lon SW : ".$array['lonSW']." <br>
        ";

    $details_url = "https://api.netatmo.com/api/getpublicdata?access_token=".$access_token."&lat_ne=".$array['latNE']."&lon_ne=".$array['lonNE']."&lat_sw=".$array['latSW']."&lon_sw=".$array['lonSW'];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $details_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $response = json_decode(curl_exec($ch), true);
    echo "<pre>";
    //var_dump($response);
    $i = 0;

    foreach ($response['body'] as $station) {
      $i++;
      $alt += $station['place']['altitude'];
      $somme += array_shift(array_shift(array_shift($station['measures'])))[0];
    }
    $temp_moy = $somme/$i;
    $alt_moy = $alt/$i;

    $div .= "Température moyenne : ".$temp_moy." degrès <br>";
    $div .= "Altitude moyenne des stations : ".$alt_moy."m <br>";
    $div  .= "Nombre de stations dans la zone : ".$i;

    if ($temp_moy < 3){
      $style =  'style="color:blue;"';
    }elseif($temp_moy >=3 && $temp_moy <14){
      $style =  'style="color:orange;"';
    }else{
      $style =  'style="color:red;"';
    }

  //var_dump($array);
}


?>

<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8" />
        <title>Exercices Javascript</title>
    </head>

    <body>

      <form action="index.php" method="post">
        <fieldset>
          <legend>Entrez une adresse ou un lieu pour afficher la moyenne des températures actuelles de la zone</legend>
          <p><input type="text" placeholder="Adresse" name="adresse" /> <input type="submit" value="Let's Go !"></p>
        </fieldset>
      </form>
      <div <?=$style?>><?=$div?></div>
    </body>
</html>
