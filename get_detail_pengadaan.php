<?php
require_once 'config.php';

$id_pengadaan = isset($_GET['id']) ? $_GET['id'] : 0;

$query = "SELECT dp.*, p.kode_produk, p.nama_produk 
          FROM detail_pengadaan dp
          JOIN produk p ON dp.id_produk = p.id_produk
          WHERE dp.id_pengadaan = $id_pengadaan";

$result = mysqli_query($conn, $query);

$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $data[] = [
        'id_produk' => $row['id_produk'],
        'kode_produk' => $row['kode_produk'],
        'nama_produk' => $row['nama_produk'],
        'qty' => $row['qty'],
        'harga_satuan' => $row['harga_satuan'],
        'subtotal' => $row['subtotal']
    ];
}

header('Content-Type: application/json');
echo json_encode($data);
?>