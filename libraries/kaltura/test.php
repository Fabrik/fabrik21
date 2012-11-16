<?php
require_once("kaltura_client.php");

$conf = new KalturaConfiguration(250, 25000);
$user = new KalturaSessionUser(2);
$cl = new KalturaClient($conf);
$secert = 'dae1be648b8a86d25adafdac2d32e8c3';
$cl->start($user, $secert);

$kshow = new KalturaKShow();
$kshow->name = "test php";
$kshow->description = "desc php";

$result = $cl->addKShow($user, $kshow, 1);
print_r($result);
?>