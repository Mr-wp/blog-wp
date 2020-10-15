<?php
date_default_timezone_set("Asia/Shanghai");

// 填写自己项目根目录绝对路径
$applicationPath = "/usr/share/nginx/html/application";
// 这里是在 github webhooks页面设置的 Secret
$secret = "webhooksalfred";

// github webhooks 请求头中的签名
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE'];
if (!$signature) {
   return http_response_code(404);
}
// github webhooks 请求体 Payload 内容
$payloadJson = file_get_contents("php://input");
$content = json_decode($payloadJson, true);
list($algo, $hash) = explode("=", $signature, 2);

// 组装 webhooks 请求信息
$pushInfo = "{$content['head_commit']['author']['name']} 在 " . date('Y-m-d H:i:s') . PHP_EOL;
$pushInfo .= "向 {$content['repository']['name']} 项目的 {$content['ref']} 分支 " .PHP_EOL;
$pushInfo .= "push 了 " . count($content['commits']) . " 个commit: " . PHP_EOL;
// 验签
$payloadHash = hash_hmac($algo, $payloadJson, $secret);
if ($hash === $payloadHash) {
    $ret = shell_exec("cd {$applicationPath} && sudo git pull origin master");
    $responseLog = "Success: " . PHP_EOL;
    $responseLog .= $pushInfo . $ret . PHP_EOL . PHP_EOL;
} else {
    $responseLog  = "Error: " . PHP_EOL;
    $responseLog .= "{$pushInfo} 验签失败" . PHP_EOL . PHP_EOL;
}
// 输出响应内容，可在 github webhooks - Recent Deliveries 中的 Response Body 中查看 
echo $responseLog;
// 记录 webhooks 请求日志
file_put_contents("/tmp/webhooks.log", $responseLog);
