<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}
?>
<?php
include '../includes/db.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);
}
header("Location: manage_products.php");
exit;
?>