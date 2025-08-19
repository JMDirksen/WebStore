<?php

// Config
$maxfilesize = 102400; // 100 KB
$maxfileage = 2592000; // 30 days

// Init
$method = $_SERVER['REQUEST_METHOD'];
$scheme = $_SERVER["HTTP_X_FORWARDED_SCHEME"] ?? $_SERVER["REQUEST_SCHEME"];
$host = $_SERVER["HTTP_HOST"];
$path = explode('/', $_GET['path'] ?? "");
header("Content-Type: text/plain");

// Usage
if (empty($path[0])) {
    usage($scheme, $host);
    exit;
}

// GET file
if ($method === 'GET') {
    $key = $path[0];
    $file = "/store/$key.txt";
    if (file_exists($file)) {
        touch($file);
        readfile($file);
    } else {
        http_response_code(404);
        echo "File not found." . PHP_EOL;
    }
}

// POST (or PUT, PATCH)
else {
    $key = hash('sha256', preg_replace('/[^A-Za-z0-9_\-]/', '-', $path[0] ?? "") . $host);
    $data = file_get_contents('php://input');

    // PUT new file
    if ($method === 'PUT') storedata($key, $data, false);

    // PATCH append to existing file
    elseif ($method === 'PATCH') storedata($key, $data);

    // POST
    elseif ($method === 'POST') {
        $append = $_REQUEST['append'] ?? '1';
        storedata($key, $data, $append);
    }

    // Invalid request method
    else {
        http_response_code(400);
        echo "Invalid request method (only GET, PUT and PATCH are allowed)." . PHP_EOL;
    }
}

filecleanup();

function storedata($key, $data, $append = true) {
    global $scheme, $host;

    $file = "/store/$key.txt";
    $size = strlen($data);

    // Append data
    if ($append) {
        $size += file_exists($file) ? filesize($file) : 0;
        checksize($size);
        file_put_contents($file, $data . PHP_EOL, FILE_APPEND);

        // Max lines
        if (isset($_REQUEST['maxlines'])) {
            $maxlines = (int) $_REQUEST['maxlines'];
            $headerlines = $_REQUEST['headerlines'] ?? 0;
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

    // New data
    else {
        checksize($size);
        file_put_contents($file, $data . PHP_EOL);
        echo $scheme . "://" . $host . "/$key";
    }
}

function checksize($size) {
    global $maxfilesize;
    if ($size > $maxfilesize) {
        http_response_code(413);
        echo "Content size exceeds limit." . PHP_EOL;
        exit;
    }
}

function filecleanup() {
    global $maxfileage;
    $files = glob('/store/*.txt');
    foreach ($files as $file) {
        if (filemtime($file) < time() - $maxfileage) {
            unlink($file);
        }
    }
}

function usage($scheme, $host) {
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
