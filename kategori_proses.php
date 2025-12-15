<?php
include 'config.php';

$action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : '');

if($action == 'add') {
    // Tambah Kategori Baru
    $nama_kategori = sanitize($_POST['nama_kategori']);
    $deskripsi = sanitize($_POST['deskripsi']);
    $icon = sanitize($_POST['icon']);
    
    $query = "INSERT INTO kategori_produk (nama_kategori, deskripsi, icon, status) 
              VALUES ('$nama_kategori', '$deskripsi', '$icon', 'aktif')";
    
    if($conn->query($query)) {
        setAlert('Kategori berhasil ditambahkan!', 'success');
    } else {
        setAlert('Error: ' . $conn->error, 'error');
    }
    
    header('Location: kategori.php');
    exit;
}

elseif($action == 'edit') {
    // Edit Kategori
    $id_kategori = sanitize($_POST['id_kategori']);
    $nama_kategori = sanitize($_POST['nama_kategori']);
    $deskripsi = sanitize($_POST['deskripsi']);
    $icon = sanitize($_POST['icon']);
    
    $query = "UPDATE kategori_produk SET 
              nama_kategori = '$nama_kategori',
              deskripsi = '$deskripsi',
              icon = '$icon'
              WHERE id_kategori = $id_kategori";
    
    if($conn->query($query)) {
        setAlert('Kategori berhasil diupdate!', 'success');
    } else {
        setAlert('Error: ' . $conn->error, 'error');
    }
    
    header('Location: kategori.php');
    exit;
}

elseif($action == 'delete') {
    // Hapus Kategori (soft delete)
    $id_kategori = sanitize($_GET['id']);
    
    $query = "UPDATE kategori_produk SET status = 'tidak_aktif' WHERE id_kategori = $id_kategori";
    
    if($conn->query($query)) {
        setAlert('Kategori berhasil dihapus!', 'success');
    } else {
        setAlert('Error: ' . $conn->error, 'error');
    }
    
    header('Location: kategori.php');
    exit;
}

else {
    header('Location: kategori.php');
    exit;
}
?>