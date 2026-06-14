<?php
/**
 * Router cho PHP Built-in Server
 * Thay thế .htaccess rewrite rules
 * 
 * Chức năng: tự động thêm .php nếu URL không có extension
 * Ví dụ: /forum -> /forum.php, /gioi-thieu -> /gioi-thieu.php
 */

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Nếu file tồn tại (css, js, images, ...) → serve trực tiếp
if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
    return false; // let PHP built-in server handle static files
}

// Nếu là thư mục có index.php → serve index.php
if (is_dir(__DIR__ . $uri)) {
    $index = rtrim($uri, '/') . '/index.php';
    if (file_exists(__DIR__ . $index)) {
        require __DIR__ . $index;
        return true;
    }
}

// Thử thêm .php vào URL (giống RewriteRule trong .htaccess)
$phpFile = __DIR__ . $uri . '.php';
if (!pathinfo($uri, PATHINFO_EXTENSION) && file_exists($phpFile)) {
    require $phpFile;
    return true;
}

// Mặc định → serve index.php
if ($uri === '/') {
    require __DIR__ . '/index.php';
    return true;
}

// 404
http_response_code(404);
echo "404 - Không tìm thấy trang: " . htmlspecialchars($uri);
return true;
