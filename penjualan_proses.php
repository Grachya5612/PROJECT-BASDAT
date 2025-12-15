<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    mysqli_begin_transaction($conn);
    
    $id_penjualan = $_POST['id_penjualan'] ?? 0;
    $no_penjualan = $_POST['no_penjualan'];
    $tanggal_penjualan = $_POST['tanggal_penjualan'];
    $id_customer = $_POST['id_customer'];
    $id_user = $_POST['id_user'];
    $metode_pembayaran = $_POST['metode_pembayaran'];
    $bayar = $_POST['bayar'];
    $kembalian = $_POST['kembalian'];
    $items = json_decode($_POST['items'], true);
    
    if (empty($items)) throw new Exception('Detail item kosong');
    
    // Hitung total
    $total_item = count($items);
    $total_harga = 0;
    foreach ($items as $item) {
        $total_harga += $item['subtotal'];
    }
    $diskon = 0;
    $pajak = $total_harga * 0.1;
    $grand_total = $total_harga + $pajak - $diskon;
    
    if ($id_penjualan > 0) {
        // UPDATE - Rollback stok lama dulu
        $detail_lama = mysqli_query($conn, "SELECT * FROM detail_penjualan WHERE id_penjualan = $id_penjualan");
        while ($old = mysqli_fetch_assoc($detail_lama)) {
            mysqli_query($conn, "UPDATE produk SET stok_tersedia = stok_tersedia + {$old['qty']} WHERE id_produk = {$old['id_produk']}");
        }
        
        $query = "UPDATE penjualan SET 
                  tanggal_penjualan = '$tanggal_penjualan',
                  id_customer = $id_customer,
                  total_item = $total_item,
                  total_harga = $total_harga,
                  diskon = $diskon,
                  pajak = $pajak,
                  grand_total = $grand_total,
                  bayar = $bayar,
                  kembalian = $kembalian,
                  metode_pembayaran = '$metode_pembayaran',
                  status_penjualan = 'selesai'
                  WHERE id_penjualan = $id_penjualan";
        
        if (!mysqli_query($conn, $query)) throw new Exception('Gagal update penjualan');
        
        mysqli_query($conn, "DELETE FROM detail_penjualan WHERE id_penjualan = $id_penjualan");
        
    } else {
        // INSERT
        $query = "INSERT INTO penjualan 
                  (no_penjualan, tanggal_penjualan, id_customer, id_user,
                   total_item, total_harga, diskon, pajak, grand_total,
                   bayar, kembalian, metode_pembayaran, status_penjualan, created_at) 
                  VALUES 
                  ('$no_penjualan', '$tanggal_penjualan', $id_customer, $id_user,
                   $total_item, $total_harga, $diskon, $pajak, $grand_total,
                   $bayar, $kembalian, '$metode_pembayaran', 'selesai', NOW())";
        
        if (!mysqli_query($conn, $query)) throw new Exception('Gagal insert penjualan');
        
        $id_penjualan = mysqli_insert_id($conn);
    }
    
    // Insert detail dan update stok
    foreach ($items as $item) {
        $id_produk = $item['id_produk'];
        $qty = $item['qty'];
        $harga_satuan = $item['harga_satuan'];
        $diskon_item = $item['diskon_item'];
        $subtotal = $item['subtotal'];
        
        // Cek stok
        $cek_stok = mysqli_query($conn, "SELECT stok_tersedia FROM produk WHERE id_produk = $id_produk");
        $stok = mysqli_fetch_assoc($cek_stok)['stok_tersedia'];
        
        if ($stok < $qty) throw new Exception("Stok {$item['nama_produk']} tidak cukup! Tersedia: $stok");
        
        // Insert detail
        $query = "INSERT INTO detail_penjualan 
                  (id_penjualan, id_produk, qty, harga_satuan, diskon_item, subtotal, created_at) 
                  VALUES 
                  ($id_penjualan, $id_produk, $qty, $harga_satuan, $diskon_item, $subtotal, NOW())";
        
        if (!mysqli_query($conn, $query)) throw new Exception('Gagal insert detail');
        
        // Update stok produk
        $query = "UPDATE produk SET stok_tersedia = stok_tersedia - $qty WHERE id_produk = $id_produk";
        if (!mysqli_query($conn, $query)) throw new Exception('Gagal update stok');
        
        // Insert kartu stok
        $query = "INSERT INTO kartu_stok 
                  (id_produk, tanggal, jenis_transaksi, referensi, masuk, keluar, keterangan, created_at)
                  VALUES
                  ($id_produk, '$tanggal_penjualan', 'penjualan', '$no_penjualan', 0, $qty, 'Penjualan kepada customer', NOW())";
        
        if (!mysqli_query($conn, $query)) throw new Exception('Gagal insert kartu stok');
    }
    
    mysqli_commit($conn);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Penjualan berhasil disimpan',
        'id_penjualan' => $id_penjualan
    ]);
    
} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}

mysqli_close($conn);
?>