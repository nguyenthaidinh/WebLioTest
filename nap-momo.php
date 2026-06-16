<?php
include_once 'set.php';

if ($_login == null) {
    header("Location: dang-nhap");
    exit();
}

header("Location: /app/nap-ngoc.php");
exit();
?>
