<?php
require_once 'config.php';

$tgl_dari = isset($_GET['tgl_dari']) ? $_GET['tgl_dari'] : '';
$tgl_sampai = isset($_GET['tgl_sampai']) ? $_GET['tgl_sampai'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';

$query = "SELECT p.*, s.nama_supplier 
          FROM pengadaan p
          LEFT JOIN supplier s ON p.id_supplier = s.id_supplier
          WHERE 1=1";

if ($tgl_dari) {
    $query .= " AND p.tanggal_pengadaan >= '$tgl_dari'";
}
if ($tgl_sampai) {
    $query .= " AND p.tanggal_pengadaan <= '$tgl_sampai'";
}
if ($status) {
    $query .= " AND p.status_pengadaan = '$status'";
}

$query .= " ORDER BY p.tanggal_pengadaan DESC";

$result = mysqli_query($conn, $query);

$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $data[] = [
        'id_pengadaan' => $row['id_pengadaan'],
        'no_pengadaan' => $row['no_pengadaan'],
        'tanggal_pengadaan' => date('d/m/Y', strtotime($row['tanggal_pengadaan'])),
        'id_supplier' => $row['id_supplier'],
        'nama_supplier' => $row['nama_supplier'],
        'total_item' => $row['total_item'],
        'status_pengadaan' => $row['status_pengadaan']
    ];
}

header('Content-Type: application/json');
echo json_encode(['success' => true, 'data' => $data]);
?>