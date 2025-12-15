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
    
    // Ambil data dari POST
    $id_penerimaan = $_POST['id_penerimaan'] ?? 0;
    $no_penerimaan = $_POST['no_penerimaan'];
    $tanggal_penerimaan = $_POST['tanggal_penerimaan'];
    $id_pengadaan = $_POST['id_pengadaan'];
    $id_supplier = $_POST['id_supplier'];
    $id_user = $_POST['id_user'];
    $catatan = $_POST['catatan'] ?? '';
    $items = json_decode($_POST['items'], true);
    
    if (empty($items)) {
        throw new Exception('Detail item tidak boleh kosong');
    }
    
    // Hitung total
    $total_item = count($items);
    $total_qty = 0;
    foreach ($items as $item) {
        $total_qty += $item['qty_diterima'];
    }
    
    // Tentukan status penerimaan
    $status_penerimaan = 'diterima_lengkap';
    foreach ($items as $item) {
        if ($item['qty_diterima'] < $item['qty_dipesan']) {
            $status_penerimaan = 'diterima_sebagian';
            break;
        }
    }
    
    if ($id_penerimaan > 0) {
        // UPDATE MODE
        $query = "UPDATE penerimaan SET 
                  tanggal_penerimaan = '$tanggal_penerimaan',
                  id_pengadaan = $id_pengadaan,
                  id_supplier = $id_supplier,
                  total_item = $total_item,
                  total_qty = $total_qty,
                  catatan = '$catatan',
                  status_penerimaan = '$status_penerimaan'
                  WHERE id_penerimaan = $id_penerimaan";
        
        if (!mysqli_query($conn, $query)) {
            throw new Exception('Gagal update penerimaan: ' . mysqli_error($conn));
        }
        
        // Hapus detail lama (dan rollback stok)
        $detail_lama = mysqli_query($conn, "SELECT * FROM detail_penerimaan WHERE id_penerimaan = $id_penerimaan");
        while ($old = mysqli_fetch_assoc($detail_lama)) {
            // Kurangi stok produk
            mysqli_query($conn, "UPDATE produk SET stok_tersedia = stok_tersedia - {$old['qty_diterima']} WHERE id_produk = {$old['id_produk']}");
        }
        mysqli_query($conn, "DELETE FROM detail_penerimaan WHERE id_penerimaan = $id_penerimaan");
        
    } else {
        // INSERT MODE
        $query = "INSERT INTO penerimaan 
                  (no_penerimaan, tanggal_penerimaan, id_pengadaan, id_supplier, id_user,
                   total_item, total_qty, catatan, status_penerimaan, created_at) 
                  VALUES 
                  ('$no_penerimaan', '$tanggal_penerimaan', $id_pengadaan, $id_supplier, $id_user,
                   $total_item, $total_qty, '$catatan', '$status_penerimaan', NOW())";
        
        if (!mysqli_query($conn, $query)) {
            throw new Exception('Gagal insert penerimaan: ' . mysqli_error($conn));
        }
        
        $id_penerimaan = mysqli_insert_id($conn);
    }
    
    // Insert detail dan update stok produk & kartu stok
    foreach ($items as $item) {
        $id_produk = $item['id_produk'];
        $qty_dipesan = $item['qty_dipesan'];
        $qty_diterima = $item['qty_diterima'];
        $kondisi = $item['kondisi'];
        $keterangan = $item['keterangan'];
        
        // Insert detail penerimaan
        $query = "INSERT INTO detail_penerimaan 
                  (id_penerimaan, id_produk, qty_dipesan, qty_diterima, kondisi, keterangan, created_at) 
                  VALUES 
                  ($id_penerimaan, $id_produk, $qty_dipesan, $qty_diterima, '$kondisi', '$keterangan', NOW())";
        
        if (!mysqli_query($conn, $query)) {
            throw new Exception('Gagal insert detail: ' . mysqli_error($conn));
        }
        
        // Update stok produk (hanya yang kondisi baik)
        if ($kondisi == 'baik') {
            $query = "UPDATE produk SET stok_tersedia = stok_tersedia + $qty_diterima WHERE id_produk = $id_produk";
            if (!mysqli_query($conn, $query)) {
                throw new Exception('Gagal update stok: ' . mysqli_error($conn));
            }
            
            // Insert ke kartu stok
            $query = "INSERT INTO kartu_stok 
                      (id_produk, tanggal, jenis_transaksi, referensi, masuk, keluar, keterangan, created_at)
                      VALUES
                      ($id_produk, '$tanggal_penerimaan', 'penerimaan', '$no_penerimaan', $qty_diterima, 0, 'Penerimaan barang dari supplier', NOW())";
            
            if (!mysqli_query($conn, $query)) {
                throw new Exception('Gagal insert kartu stok: ' . mysqli_error($conn));
            }
        }
    }
    
    // Commit transaction
    mysqli_commit($conn);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Data berhasil disimpan dan stok berhasil diupdate',
        'id_penerimaan' => $id_penerimaan
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