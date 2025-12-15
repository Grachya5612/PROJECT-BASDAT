<?php
include 'config.php';
header('Content-Type: application/json');

if(isset($_GET['id'])) {
    $id = sanitize($_GET['id']);
    
    $query = "SELECT * FROM kategori_produk WHERE id_kategori = $id";
    $result = $conn->query($query);
    
    if($result->num_rows > 0) {
        echo json_encode($result->fetch_assoc());
    } else {
        echo json_encode(['error' => 'Kategori tidak ditemukan']);
    }
} else {
    echo json_encode(['error' => 'ID tidak valid']);
}
?>