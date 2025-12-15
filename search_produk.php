<?php
include 'config.php';

$keyword = isset($_GET['keyword']) ? sanitize($_GET['keyword']) : '';

if($keyword != '') {
    $query = "SELECT p.*, k.nama_kategori, s.singkatan as satuan
              FROM produk p
              LEFT JOIN kategori_produk k ON p.id_kategori = k.id_kategori
              LEFT JOIN satuan s ON p.id_satuan = s.id_satuan
              WHERE p.status = 'aktif'
              AND (p.nama_produk LIKE '%$keyword%' OR p.kode_produk LIKE '%$keyword%')
              LIMIT 10";
    
    $result = $conn->query($query);
    
    if($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            echo '<div class="product-item" onclick="selectProduk(' . $row['id_produk'] . ', \'' . addslashes($row['nama_produk']) . '\', ' . $row['harga_beli'] . ')">';
            echo '<div style="display: flex; justify-content: space-between; align-items: center;">';
            echo '<div>';
            echo '<strong>' . $row['kode_produk'] . '</strong> - ' . $row['nama_produk'];
            echo '<br><small style="color: #666;">' . $row['nama_kategori'] . ' | Stok: ' . $row['stok_tersedia'] . ' ' . $row['satuan'] . '</small>';
            echo '</div>';
            echo '<div style="text-align: right;">';
            echo '<strong style="color: #3c8dbc;">' . formatRupiah($row['harga_beli']) . '</strong>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
        }
    } else {
        echo '<div style="padding: 15px; text-align: center; color: #999;">Produk tidak ditemukan</div>';
    }
} else {
    echo '<div style="padding: 15px; text-align: center; color: #999;">Ketik minimal 2 karakter</div>';
}
?>