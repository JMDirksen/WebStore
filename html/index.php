<?php

$method = $_SERVER['REQUEST_METHOD'];
$scheme = $_SERVER["HTTP_X_FORWARDED_SCHEME"] ?? $_SERVER["REQUEST_SCHEME"];
$host = $_SERVER["HTTP_HOST"];
$path = preg_replace('/[^A-Za-z0-9_\-]/', '-', $_GET['path'] ?? '');
$key = hash('sha256', $path . $host);
header("Content-Type: text/plain");

// Usage
if (empty($path)) {
    echo '# WebStore #' . PHP_EOL . PHP_EOL;
    echo 'REST API to store some data, like json, csv or txt.' . PHP_EOL;
    echo 'https://github.com/JMDirksen/WebStore' . PHP_EOL . PHP_EOL . PHP_EOL;
    echo '# Examples #' . PHP_EOL . PHP_EOL;
    echo 'Store data example:' . PHP_EOL . PHP_EOL;
    echo 'curl -X PUT -d "key,value" "https://webstore.example.com/mysecret?maxlines=2&headerlines=1"' . PHP_EOL;
    echo 'curl -X PATCH -d "test,value1" "https://webstore.example.com/mysecret?maxlines=2&headerlines=1"' . PHP_EOL;
    echo 'curl -X PATCH -d "test,value2" "https://webstore.example.com/mysecret?maxlines=2&headerlines=1"' . PHP_EOL;
    echo 'curl -X PATCH -d "test,value3" "https://webstore.example.com/mysecret?maxlines=2&headerlines=1"' . PHP_EOL . PHP_EOL;
    echo 'The commands above output an url with which you can retrieve the stored data.' . PHP_EOL . PHP_EOL . PHP_EOL;
    echo 'Retrieve data example:' . PHP_EOL;
    echo 'curl "https://webstore.example.com/... the return url ..."' . PHP_EOL;
    exit;
}

// GET file
if ($method === 'GET') {
    if (file_exists("/store/$path.txt")) {
        readfile("/store/$path.txt");
    } else {
        http_response_code(404);
        echo "File not found.";
    }
}

// PUT new file
elseif ($method === 'PUT') {
    $data = file_get_contents('php://input');
    file_put_contents("/store/$key.txt", $data . PHP_EOL);
    echo $scheme . "://" . $host . "/$key";
}

// PATCH append to existing file
elseif ($method === 'PATCH') {
    $data = file_get_contents('php://input');
    file_put_contents("/store/$key.txt", $data . PHP_EOL, FILE_APPEND);

    // Max lines
    if (isset($_GET['maxlines'])) {
        $maxlines = (int) $_GET['maxlines'];
        $headerlines = $_GET['headerlines'] ?? 0;
        $lines = file("/store/$key.txt");
        if (count($lines) - $headerlines > $maxlines) {
            file_put_contents("/store/$key.txt", implode("", array_merge(
                array_slice($lines, 0, $headerlines),
                array_slice($lines, -$maxlines)
            )));
        }
    }
    echo $scheme . "://" . $host . "/$key";
}

// Else
else {
    http_response_code(400);
    echo "Invalid request method (only GET, PUT and PATCH are allowed).";
}
