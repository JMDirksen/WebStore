<?php

// GET file
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['path'])) {
    $key = $_GET['path'];
    if (file_exists("/store/$key.txt")) {
        header('Content-Type: text/plain');
        readfile("/store/$key.txt");
    } else {
        http_response_code(404);
        echo "File not found.";
    }
}

// PUT new file
elseif ($_SERVER['REQUEST_METHOD'] === 'PUT' && isset($_GET['path'])) {
    $path = $_GET['path'];
    $key = hash('sha256', $path . $_SERVER['HTTP_HOST']);
    $data = file_get_contents('php://input');
    file_put_contents("/store/$key.txt", $data . PHP_EOL);
    echo $_SERVER["REQUEST_SCHEME"] . "://" . $_SERVER["HTTP_HOST"] . "/$key";
}

// PATCH append to existing file
elseif ($_SERVER['REQUEST_METHOD'] === 'PATCH' && isset($_GET['path'])) {
    $path = $_GET['path'];
    $key = hash('sha256', $path . $_SERVER['HTTP_HOST']);
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

    echo $_SERVER["REQUEST_SCHEME"] . "://" . $_SERVER["HTTP_HOST"] . "/$key";
}

else {
    http_response_code(400);
    echo "Invalid request method or missing parameters.";
}
