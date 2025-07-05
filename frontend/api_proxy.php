<?php
// リクエストの安全性を確保するため、許可するエンドポイントをリスト化
$allowed_endpoints = ['get_quiz', 'submit_answer', 'get_stats', 'translate'];
$endpoint = $_GET['endpoint'] ?? '';

// 許可されたエンドポイントでなければエラー
if (!in_array($endpoint, $allowed_endpoints)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid endpoint']);
    exit;
}

// バックエンドAPIのURL (サーバー内部から見たアドレス)
$backend_url = 'http://localhost:5001/' . $endpoint;

// GETパラメータをバックエンドに引き継ぐ
$query_params = $_GET;
unset($query_params['endpoint']); // プロキシ用のパラメータは除外
if (!empty($query_params)) {
    $backend_url .= '?' . http_build_query($query_params);
}

// リクエストメソッドに応じて処理を分岐
$method = $_SERVER['REQUEST_METHOD'];

$options = [
    'http' => [
        'method' => $method,
        'header' => "Content-Type: application/json\r\n",
        'ignore_errors' => true // エラー時もレスポンスを取得
    ]
];

// POSTの場合はリクエストボディを転送
if ($method === 'POST') {
    $options['http']['content'] = file_get_contents('php://input');
}

$context = stream_context_create($options);
$response_body = file_get_contents($backend_url, false, $context);

// バックエンドからのレスポンスヘッダーを取得し、クライアントに転送
$response_headers = $http_response_header ?? [];
foreach ($response_headers as $header) {
    // Content-TypeヘッダーとHTTPステータスコードを転送
    if (preg_match('/^HTTP\/\d\.\d\s\d{3}/', $header) || stripos($header, 'content-type:') === 0) {
        header($header, true);
    }
}

// レスポンスボディをクライアントに出力
echo $response_body;

