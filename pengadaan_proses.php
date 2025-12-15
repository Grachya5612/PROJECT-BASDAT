<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    // Start transaction
    mysqli_begin_transaction($conn);
    
    // Ambil data dari POST
    $id_pengadaan = $_POST['id_pengadaan'] ?? 0;
    $no_pengadaan = $_POST['no_pengadaan'];
    $tanggal_pengadaan = $_POST['tanggal_pengadaan'];
    $id_supplier = $_POST['id_supplier'];
    $id_user = $_POST['id_user'];
    $catatan = $_POST['catatan'] ?? '';
    $items = json_decode($_POST['items'], true);
    
    if (empty($items)) {
        throw new Exception('Detail item tidak boleh kosong');
    }
    
    // Hitung total
    $total_item = count($items);
    $total_harga = 0;
    foreach ($items as $item) {
        $total_harga += $item['subtotal'];
    }
    $pajak = $total_harga * 0.1; // PPN 10%
    $grand_total = $total_harga + $pajak;
    
    if ($id_pengadaan > 0) {
        // UPDATE MODE
        $query = "UPDATE pengadaan SET 
                  tanggal_pengadaan = '$tanggal_pengadaan',
                  id_supplier = $id_supplier,
                  total_item = $total_item,
                  total_harga = $total_harga,
                  diskon = 0,
                  pajak = $pajak,
                  grand_total = $grand_total,
                  catatan = '$catatan',
                  status_pengadaan = 'selesai'
                  WHERE id_pengadaan = $id_pengadaan";
        
        if (!mysqli_query($conn, $query)) {
            throw new Exception('Gagal update pengadaan: ' . mysqli_error($conn));
        }
        
        // Hapus detail lama
        mysqli_query($conn, "DELETE FROM detail_pengadaan WHERE id_pengadaan = $id_pengadaan");
        
    } else {
        // INSERT MODE
        $query = "INSERT INTO pengadaan 
                  (no_pengadaan, tanggal_pengadaan, id_supplier, id_user, 
                   total_item, total_harga, diskon, pajak, grand_total, 
                   catatan, status_pengadaan, created_at) 
                  VALUES 
                  ('$no_pengadaan', '$tanggal_pengadaan', $id_supplier, $id_user, 
                   $total_item, $total_harga, 0, $pajak, $grand_total, 
                   '$catatan', 'selesai', NOW())";
        
        if (!mysqli_query($conn, $query)) {
            throw new Exception('Gagal insert pengadaan: ' . mysqli_error($conn));
        }
        
        $id_pengadaan = mysqli_insert_id($conn);
    }
    
    // Insert detail pengadaan
    foreach ($items as $item) {
        $id_produk = $item['id_produk'];
        $qty = $item['qty'];
        $harga_satuan = $item['harga_satuan'];
        $subtotal = $item['subtotal'];
        
        $query = "INSERT INTO detail_pengadaan 
                  (id_pengadaan, id_produk, qty, harga_satuan, subtotal, created_at) 
                  VALUES 
                  ($id_pengadaan, $id_produk, $qty, $harga_satuan, $subtotal, NOW())";
        
        if (!mysqli_query($conn, $query)) {
            throw new Exception('Gagal insert detail: ' . mysqli_error($conn));
        }
    }
    
    // Commit transaction
    mysqli_commit($conn);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Data berhasil disimpan',
        'id_pengadaan' => $id_pengadaan
    ]);
    
} catch (Exception $e) {
    // Rollback jika error
    mysqli_rollback($conn);
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}

mysqli_close($conn);
?>