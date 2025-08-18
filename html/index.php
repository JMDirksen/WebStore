<?php

$method = $_SERVER['REQUEST_METHOD'];
$scheme = $_SERVER["HTTP_X_FORWARDED_SCHEME"] ?? $_SERVER["REQUEST_SCHEME"];
$host = $_SERVER["HTTP_HOST"];
$path = preg_replace('/[^A-Za-z0-9_\-]/', '-', $_GET['path'] ?? '');
$key = hash('sha256', $path . $host);
$maxfilesize = 102400; // 100 KB
header("Content-Type: text/plain");

// Usage
if (empty($path)) {
    $secret = "<mysecret>";
    $key = hash('sha256', preg_replace('/[^A-Za-z0-9_\-]/', '-', $secret) . $host);
    echo "# WebStore #" . PHP_EOL . PHP_EOL;
    echo "REST API to store some data, like json, csv or txt." . PHP_EOL;
    echo "https://github.com/JMDirksen/WebStore" . PHP_EOL . PHP_EOL . PHP_EOL;
    echo "# Examples #" . PHP_EOL . PHP_EOL;
    echo "Store data example (you should choose your own secret, replace \"$secret\"):" . PHP_EOL;
    echo "These commands will put some headers in the file and add 3 lines, but limit the file size to 2 lines (excluding headers):" . PHP_EOL . PHP_EOL;
    echo "curl -X PUT -d \"key,value\" \"$scheme://$host/$secret?maxlines=2&headerlines=1\"" . PHP_EOL;
    echo "curl -X PATCH -d \"test,value1\" \"$scheme://$host/$secret?maxlines=2&headerlines=1\"" . PHP_EOL;
    echo "curl -X PATCH -d \"test,value2\" \"$scheme://$host/$secret?maxlines=2&headerlines=1\"" . PHP_EOL;
    echo "curl -X PATCH -d \"test,value3\" \"$scheme://$host/$secret?maxlines=2&headerlines=1\"" . PHP_EOL . PHP_EOL;
    echo "The commands above output an url with which you can retrieve the stored data." . PHP_EOL . PHP_EOL . PHP_EOL;
    echo "Retrieve data example:" . PHP_EOL;
    echo "curl \"$scheme://$host/$key\"" . PHP_EOL . PHP_EOL;
    echo "The output would look like this:" . PHP_EOL;
    echo "key,value" . PHP_EOL;
    echo "test,value2" . PHP_EOL;
    echo "test,value3" . PHP_EOL;
}

// GET file
elseif ($method === 'GET') {
    $file = "/store/$path.txt";
    if (file_exists($file)) {
        touch($file);
        readfile($file);
    } else {
        http_response_code(404);
        echo "File not found." . PHP_EOL;
    }
}

// PUT new file
elseif ($method === 'PUT') {
    $file = "/store/$key.txt";
    $data = file_get_contents('php://input');
    $size = strlen($data);
    if ($size > $maxfilesize) {
        http_response_code(413);
        echo "Content size exceeds limit.". PHP_EOL;
        exit;
    }
    file_put_contents($file, $data . PHP_EOL);
    echo $scheme . "://" . $host . "/$key" . PHP_EOL;
}

// PATCH append to existing file
elseif ($method === 'PATCH') {
    $file = "/store/$key.txt";
    $data = file_get_contents('php://input');
    $size = strlen($data);
    $size += file_exists($file) ? filesize($file) : 0;
    if ($size > $maxfilesize) {
        http_response_code(413);
        echo "Content size exceeds limit.". PHP_EOL;
        exit;
    }
    file_put_contents($file, $data . PHP_EOL, FILE_APPEND);

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
    echo $scheme . "://" . $host . "/$key" . PHP_EOL;
}

// Invalid request method
else {
    http_response_code(400);
    echo "Invalid request method (only GET, PUT and PATCH are allowed)." . PHP_EOL;
}

// File cleanup
$files = glob('/store/*.txt');
foreach ($files as $file) {
    if (filemtime($file) < time() - 2592000) { // 1 month
        unlink($file);
    }
}
