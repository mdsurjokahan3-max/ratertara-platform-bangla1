<?php

header("Content-Type: application/json; charset=UTF-8");

/*
=========================================================
RATER TARA - CLOUDINARY IMAGE UPLOAD
=========================================================
Cloud Name : rawaad4u
API Key    : 713912314143168

IMPORTANT:
শুধু নিচের API Secret লাইনে আপনার আসল Secret বসাবেন।
=========================================================
*/


$CLOUDINARY_CLOUD_NAME = "rawaad4u";

$CLOUDINARY_API_KEY = "713912314143168";

/*
 * এখানে আপনার Cloudinary API Secret বসান।
 *
 * উদাহরণ:
 * $CLOUDINARY_API_SECRET = "xxxxxxxxxxxxxxxxxxxxxxxx";
 */
$CLOUDINARY_API_SECRET = "PASTE_YOUR_CLOUDINARY_API_SECRET_HERE";


/*
=========================================================
CHECK API SECRET
=========================================================
*/

if (
    $CLOUDINARY_API_SECRET ===
    "PASTE_YOUR_CLOUDINARY_API_SECRET_HERE"
) {

    echo json_encode([
        "success" => false,
        "message" =>
            "Cloudinary API Secret এখনো upload.php-তে বসানো হয়নি।"
    ]);

    exit;
}


/*
=========================================================
ONLY POST REQUEST
=========================================================
*/

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    echo json_encode([
        "success" => false,
        "message" => "Invalid request method."
    ]);

    exit;
}


/*
=========================================================
CHECK FILE
=========================================================
*/

if (!isset($_FILES["file"])) {

    echo json_encode([
        "success" => false,
        "message" => "কোনো ছবি পাওয়া যায়নি।"
    ]);

    exit;
}


$file = $_FILES["file"];


/*
=========================================================
PHP UPLOAD ERROR
=========================================================
*/

if ($file["error"] !== UPLOAD_ERR_OK) {

    echo json_encode([
        "success" => false,
        "message" =>
            "ছবি upload করা যায়নি। Error code: " .
            $file["error"]
    ]);

    exit;
}


/*
=========================================================
MAXIMUM FILE SIZE = 5 MB
=========================================================
*/

$maxSize = 5 * 1024 * 1024;


if ($file["size"] > $maxSize) {

    echo json_encode([
        "success" => false,
        "message" =>
            "ছবির সর্বোচ্চ সাইজ 5 MB হতে পারবে।"
    ]);

    exit;
}


/*
=========================================================
CHECK MIME TYPE
=========================================================
*/

$finfo = finfo_open(FILEINFO_MIME_TYPE);

if (!$finfo) {

    echo json_encode([
        "success" => false,
        "message" =>
            "Server file validation চালু করতে পারেনি।"
    ]);

    exit;
}


$mime = finfo_file(
    $finfo,
    $file["tmp_name"]
);

finfo_close($finfo);


$allowedTypes = [
    "image/jpeg",
    "image/png",
    "image/webp"
];


if (!in_array($mime, $allowedTypes, true)) {

    echo json_encode([
        "success" => false,
        "message" =>
            "শুধু JPG, PNG এবং WEBP ছবি upload করা যাবে।"
    ]);

    exit;
}


/*
=========================================================
GENERATE TIMESTAMP
=========================================================
*/

$timestamp = time();


/*
=========================================================
CLOUDINARY SIGNATURE
=========================================================
*/

$paramsToSign = [
    "timestamp" => $timestamp
];


ksort($paramsToSign);


$signatureString = "";


foreach ($paramsToSign as $key => $value) {

    $signatureString .=
        $key . "=" . $value . "&";
}


$signatureString =
    rtrim($signatureString, "&");


$signature =
    sha1(
        $signatureString .
        $CLOUDINARY_API_SECRET
    );


/*
=========================================================
CLOUDINARY UPLOAD ENDPOINT
=========================================================
*/

$uploadUrl =
    "https://api.cloudinary.com/v1_1/" .
    rawurlencode($CLOUDINARY_CLOUD_NAME) .
    "/image/upload";


/*
=========================================================
CREATE CURL REQUEST
=========================================================
*/

$postFields = [

    "file" => new CURLFile(
        $file["tmp_name"],
        $mime,
        $file["name"]
    ),

    "api_key" =>
        $CLOUDINARY_API_KEY,

    "timestamp" =>
        $timestamp,

    "signature" =>
        $signature
];


$ch = curl_init($uploadUrl);


curl_setopt_array($ch, [

    CURLOPT_POST => true,

    CURLOPT_POSTFIELDS =>
        $postFields,

    CURLOPT_RETURNTRANSFER =>
        true,

    CURLOPT_FOLLOWLOCATION =>
        true,

    CURLOPT_CONNECTTIMEOUT =>
        30,

    CURLOPT_TIMEOUT =>
        120

]);


$response = curl_exec($ch);


$curlError =
    curl_error($ch);


$httpCode =
    curl_getinfo(
        $ch,
        CURLINFO_HTTP_CODE
    );


curl_close($ch);


/*
=========================================================
CURL CONNECTION ERROR
=========================================================
*/

if ($response === false) {

    echo json_encode([
        "success" => false,
        "message" =>
            "Cloudinary server-এর সাথে সংযোগ করা যায়নি।",
        "error" =>
            $curlError
    ]);

    exit;
}


/*
=========================================================
DECODE CLOUDINARY RESPONSE
=========================================================
*/

$data =
    json_decode(
        $response,
        true
    );


/*
=========================================================
SUCCESS
=========================================================
*/

if (
    $httpCode >= 200 &&
    $httpCode < 300 &&
    isset($data["secure_url"])
) {

    echo json_encode([

        "success" => true,

        "message" =>
            "ছবি সফলভাবে Cloudinary-তে upload হয়েছে।",

        "secure_url" =>
            $data["secure_url"],

        "url" =>
            $data["secure_url"],

        "public_id" =>
            $data["public_id"] ?? "",

        "width" =>
            $data["width"] ?? 0,

        "height" =>
            $data["height"] ?? 0,

        "format" =>
            $data["format"] ?? ""

    ]);

    exit;
}


/*
=========================================================
CLOUDINARY ERROR
=========================================================
*/

$errorMessage =
    "Cloudinary upload failed.";


if (
    isset($data["error"]["message"])
) {

    $errorMessage =
        $data["error"]["message"];
}


echo json_encode([

    "success" => false,

    "message" =>
        $errorMessage,

    "cloudinary_response" =>
        $data

]);


exit;

?>
