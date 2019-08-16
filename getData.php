<?php
error_reporting(E_ERROR | E_PARSE);
include 'APIData.php';

function uploadToFile($tosave, $savevariable) {

		$var_str = var_export($tosave, true);
		$var = "<?php\n\n\$" .$savevariable ." = $var_str;\n\n?>";
		file_put_contents('savedUntappdData.php', $var, FILE_APPEND);

}
	
$untappd = $DATAUSERNAMES;
$usernames=array("");
$beers=array("");
$badges=array("");
$lastbeer=array("");	
$picture=array("");	
	
	
for ($i = 1; $i <= count($untappd); $i++) {
    $currentusername = array_values($untappd)[$i-1];
    $json = file_get_contents('https://api.untappd.com/v4/user/info/' .$currentusername .'?client_id=' .$CLIENTID .'&client_secret=' .$CLIENTSECRET);
	$obj = json_decode($json);
	$beersJSON = ($obj->response->user->stats->total_photos);
	$lastbeerJSON = ($obj->response->user->recent_brews->items[0]->beer->beer_name);
	$pictureJSON =($obj->response->user->recent_brews->items[0]->beer->beer_label);
	$badgesJSON = ($obj->response->user->stats->total_badges);	
	
	array_push($usernames,$currentusername);
	array_push($beers,$beersJSON);
	array_push($badges,$badgesJSON);
	array_push($lastbeer,$lastbeerJSON);
	array_push($picture,$pictureJSON);
	
	

}
if ($beers[1] > 0) {
file_put_contents("savedUntappdData.php", "");
uploadToFile($usernames,"usernames");
uploadToFile($beers,"beers");
uploadToFile($badges,"badges");
uploadToFile($lastbeer,"lastbeer");
uploadToFile($picture,"picture");
echo "yes";
} else {
	include 'savedUntappdData.php';
}
?>
