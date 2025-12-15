<?php
include 'config.php';

$action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : '');

if($action == 'add') {
    // Tambah Produk Baru
    $kode_produk = sanitize($_POST['kode_produk']);
    $nama_produk = sanitize($_POST['nama_produk']);
    $id_kategori = sanitize($_POST['id_kategori']);
    $id_satuan = sanitize($_POST['id_satuan']);
    $harga_beli = sanitize($_POST['harga_beli']);
    $harga_jual = sanitize($_POST['harga_jual']);
    $stok_minimal = sanitize($_POST['stok_minimal']);
    $stok_tersedia = sanitize($_POST['stok_tersedia']);
    
    $query = "INSERT INTO produk (kode_produk, nama_produk, id_kategori, id_satuan, harga_beli, harga_jual, stok_minimal, stok_tersedia, status) 
              VALUES ('$kode_produk', '$nama_produk', $id_kategori, $id_satuan, $harga_beli, $harga_jual, $stok_minimal, $stok_tersedia, 'aktif')";
    
    if($conn->query($query)) {
        setAlert('Produk berhasil ditambahkan!', 'success');
    } else {
        setAlert('Error: ' . $conn->error, 'error');
    }
    
    header('Location: produk.php');
    exit;
}

elseif($action == 'edit') {
    // Edit Produk
    $id_produk = sanitize($_POST['id_produk']);
    $kode_produk = sanitize($_POST['kode_produk']);
    $nama_produk = sanitize($_POST['nama_produk']);
    $id_kategori = sanitize($_POST['id_kategori']);
    $id_satuan = sanitize($_POST['id_satuan']);
    $harga_beli = sanitize($_POST['harga_beli']);
    $harga_jual = sanitize($_POST['harga_jual']);
    $stok_minimal = sanitize($_POST['stok_minimal']);
    $stok_tersedia = sanitize($_POST['stok_tersedia']);
    
    $query = "UPDATE produk SET 
              kode_produk = '$kode_produk',
              nama_produk = '$nama_produk',
              id_kategori = $id_kategori,
              id_satuan = $id_satuan,
              harga_beli = $harga_beli,
              harga_jual = $harga_jual,
              stok_minimal = $stok_minimal,
              stok_tersedia = $stok_tersedia
              WHERE id_produk = $id_produk";
    
    if($conn->query($query)) {
        setAlert('Produk berhasil diupdate!', 'success');
    } else {
        setAlert('Error: ' . $conn->error, 'error');
    }
    
    header('Location: produk.php');
    exit;
}

elseif($action == 'delete') {
    // Hapus Produk (soft delete)
    $id_produk = sanitize($_GET['id']);
    
    $query = "UPDATE produk SET status = 'tidak_aktif' WHERE id_produk = $id_produk";
    
    if($conn->query($query)) {
        setAlert('Produk berhasil dihapus!', 'success');
    } else {
        setAlert('Error: ' . $conn->error, 'error');
    }
    
    header('Location: produk.php');
    exit;
}

else {
    header('Location: produk.php');
    exit;
}
?>