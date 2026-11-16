<?php
// Seamless redirect to products.php?edit=
$id = isset($_GET['id']) ? intval($_GET['id']) : (isset($_GET['edit']) ? intval($_GET['edit']) : 0);
if ($id > 0) {
    header("Location: products.php?edit=" . $id);
    exit();
} else {
    header("Location: manage_products.php");
    exit();
}
