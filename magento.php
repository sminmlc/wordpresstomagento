<?php

$sku = "P077800370";

//Get custom product
$mproduct = getProduct($sku);

var_dump($mproduct);
print " Product id -> ".$mproduct->id;
print "<br /> Product name -> ".$mproduct->name;
print "<br /> Product sku -> ".$mproduct->sku;
print "<br /> Product price -> ".$mproduct->price;

print "<br /> Product url -> ".getBaseUrl().$mproduct->custom_attributes[6]->value.".html";

//Get thumbnail image from product
print "<br /> Product first image -> ".getMediaUrl().$mproduct->custom_attributes[5]->value;

//Get all images from product
//$images = getProductImages($mproduct->media_gallery_entries);

function getBaseUrl(){
    return "http://test.rabat.cat/";
}

function getUrl(){
    return getBaseUrl()."rest/V1/";
}

function getMediaUrl(){
    return getBaseUrl()."pub/media/catalog/product";
}

//Create magento conexion
function connectToMagento(){
    $url = getUrl();
    $userData = array("username" => "marc", "password" => "39397612sS");
    $ch = curl_init($url."integration/admin/token");
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($userData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "Content-Length: " . strlen(json_encode($userData))));

    $token = curl_exec($ch);
    return $token;
}

function getProduct($sku){
    $token = connectToMagento();
    $url = getUrl();

    //If want get custom fields
    //?fields=id,name,price"

    $request = $url."products/".$sku;

    $ch = curl_init($request);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "Authorization: Bearer " . json_decode($token)));

    $result = curl_exec($ch);
    $result = json_decode($result);
    //var_dump($result);
    return $result;
}

//Get all images from product
function getProductImages($images){
    foreach ($images as $image) {
        print "<br /> Product image -> ".getMediaUrl().$image->file;
    }
}
?>