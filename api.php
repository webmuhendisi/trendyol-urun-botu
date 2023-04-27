<?php
// URL sınırlama
$allowed_urls = array('site.net', 'www.site.net'); // izin verilen URL'ler

if (!in_array($_SERVER['HTTP_HOST'], $allowed_urls)) {
    die("Bu web sitesi erişime kapalıdır.");
}
// Aylık istek limiti sınırlama
$monthly_limit = 10000;
$requests_file = 'requests.txt';
$current_month = date('Y-m');
$current_month_requests = 0;
if (file_exists($requests_file)) {
    $requests = unserialize(file_get_contents($requests_file));
    if (isset($requests[$current_month])) {
        $current_month_requests = $requests[$current_month];
    }
}
if ($current_month_requests >= $monthly_limit) {
    die("Aylık istek limitine ulaşıldı.");
}
// İstek sayısını arttırma
$requests[$current_month] = $current_month_requests + 1;
file_put_contents($requests_file, serialize($requests));
$t = $_GET['t'];
$url = "$t";
// create curl handle
$ch = curl_init();
// set options
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_TIMEOUT, 10); // 10 saniye içerisinde yanıt alamazsa iptal et
// perform the curl request
$html = curl_exec($ch);
// close curl handle
curl_close($ch);
// create DOMDocument and load HTML content
$doc = new DOMDocument();
libxml_use_internal_errors(true);
$doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
libxml_clear_errors();
// create DOMXPath object
$xpath = new DOMXPath($doc);
// retrieve product title
$productTitle = "";
$titleNode = $xpath->query('//h1[@class="pr-new-br"]');
if ($titleNode->length > 0) {
    $productTitle = trim($titleNode->item(0)->textContent);
}
// retrieve product price
$productPrice = "";
$priceNode = $xpath->query('//div[contains(@class, "prc-box-sllng")]/span[contains(@class, "value")]');
if ($priceNode->length > 0) {
    $productPrice = trim($priceNode->item(0)->textContent);
} else {
    $discountPriceNode = $xpath->query('//span[contains(@class, "prc-dsc")]');
    if ($discountPriceNode->length > 0) {
        $productPrice = trim($discountPriceNode->item(0)->textContent);
    }
}
// create product info array
$productInfo = [
    'title' => $productTitle,
    'price' => $productPrice
];
// output JSON encoded product info
echo json_encode($productInfo);
?>
