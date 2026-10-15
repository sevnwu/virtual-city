<?php
/* 目录入口：直接访问 https://www.faceabc.com/city/sampledata/ 时输出样例数据集页面。
   本站 nginx 的 index 列表以 index.php 优先，只放 index.html 会导致目录地址 403
   （实测 /city/sampledata/ 在只放 index.html 时返回 403，而 /city/ 与 /city/sjz/ 因为
     各有 index.php 而正常）⇒ 沿用同一模式。
   同时设 no-cache，保证页面 HTML 立即生效、不被缓存住。 */
$f = __DIR__ . '/index.html';
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
if (!is_file($f)) { http_response_code(500); echo 'index.html 缺失'; exit; }
readfile($f);
