<?php
header("Content-Type: application/json; charset=utf-8");

$raw = file_get_contents("php://input");
if (!$raw) { http_response_code(400); echo json_encode(["ok"=>false,"err"=>"no input"]); exit; }

$GScriptId = "AKfycbxN2FMOe-o7NLZM8iuW85jxpUQMCXpamzAt-5G_gGA69ZsDA-aaeq1wztptER0TcPjTmg";
$url = "https://script.google.com/macros/s/$GScriptId/exec";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $raw);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);  // important pt 302
curl_setopt($ch, CURLOPT_TIMEOUT, 25);

$resp = curl_exec($ch);
$http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err  = curl_error($ch);
curl_close($ch);

echo json_encode([
  "ok" => ($http >= 200 && $http < 300),
  "google_http" => $http,
  "google_err" => $err,
  "google_body" => $resp
]);