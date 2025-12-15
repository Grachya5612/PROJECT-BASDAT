<?php
require_once 'config.php';

$search = isset($_GET['q']) ? $_GET['q'] : '';

$query = "SELECT p.id_produk as id, p.kode_produk, p.nama_produk, 
          p.harga_beli, p.harga_jual, s.singkatan as satuan
          FROM produk p
          LEFT JOIN satuan s ON p.id_satuan = s.id_satuan
          WHERE p.status = 'aktif' 
          AND (p.kode_produk LIKE '%$search%' OR p.nama_produk LIKE '%$search%')
          LIMIT 20";

$result = mysqli_query($conn, $query);

$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $data[] = [
        'id' => $row['id'],
        'text' => $row['kode_produk'] . ' - ' . $row['nama_produk'] . ' (' . $row['satuan'] . ')',
        'kode_produk' => $row['kode_produk'],
        'nama_produk' => $row['nama_produk'],
        'harga_beli' => $row['harga_beli'],
        'harga_jual' => $row['harga_jual']
    ];
}

header('Content-Type: application/json');
echo json_encode($data);
?>