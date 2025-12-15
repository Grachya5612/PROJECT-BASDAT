<?php
echo "<h1>🔍 TEST KONEKSI DATABASE</h1>";
echo "<hr>";

// Test 1: Cek file config.php
echo "<h3>1. Cek File config.php</h3>";
if (file_exists('config.php')) {
    echo "✅ File config.php <strong>DITEMUKAN</strong><br><br>";
    require_once 'config.php';
} else {
    echo "❌ File config.php <strong>TIDAK DITEMUKAN</strong><br>";
    echo "Pastikan file config.php ada di folder yang sama!<br><br>";
    die();
}

// Test 2: Cek koneksi database
echo "<h3>2. Cek Koneksi Database</h3>";
if ($conn) {
    echo "✅ Koneksi database <strong>BERHASIL</strong><br>";
    echo "Database: <strong>$db</strong><br><br>";
} else {
    echo "❌ Koneksi database <strong>GAGAL</strong><br>";
    echo "Error: " . mysqli_connect_error() . "<br><br>";
    die();
}

// Test 3: Cek tabel users
echo "<h3>3. Cek Tabel Users</h3>";
$check_table = mysqli_query($conn, "SHOW TABLES LIKE 'users'");
if (mysqli_num_rows($check_table) > 0) {
    echo "✅ Tabel 'users' <strong>DITEMUKAN</strong><br><br>";
} else {
    echo "❌ Tabel 'users' <strong>TIDAK DITEMUKAN</strong><br>";
    echo "Pastikan database sudah di-import!<br><br>";
}

// Test 4: Cek data users
echo "<h3>4. Cek Data Users</h3>";
$result = mysqli_query($conn, "SELECT * FROM users");
if ($result) {
    $jumlah = mysqli_num_rows($result);
    echo "✅ Query berhasil! Ditemukan <strong>$jumlah user</strong><br><br>";
    
    if ($jumlah > 0) {
        echo "<table border='1' cellpadding='10' cellspacing='0'>";
        echo "<tr><th>ID</th><th>Nama</th><th>Email</th><th>Role</th><th>Status</th></tr>";
        while ($user = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            echo "<td>" . $user['id_user'] . "</td>";
            echo "<td>" . $user['nama_user'] . "</td>";
            echo "<td>" . $user['email'] . "</td>";
            echo "<td>" . $user['role'] . "</td>";
            echo "<td>" . $user['status'] . "</td>";
            echo "</tr>";
        }
        echo "</table><br>";
    } else {
        echo "⚠️ <strong>Tidak ada data user!</strong><br>";
        echo "Silakan insert data user terlebih dahulu.<br><br>";
    }
} else {
    echo "❌ Query gagal: " . mysqli_error($conn) . "<br><br>";
}

// Test 5: Test password MD5
echo "<h3>5. Test Password MD5</h3>";
$password_test = "admin123";
$md5_hash = md5($password_test);
echo "Password: <strong>$password_test</strong><br>";
echo "MD5 Hash: <strong>$md5_hash</strong><br>";
echo "<small>(Pastikan password di database sama dengan hash ini)</small><br><br>";

// Test 6: Cek PHP Session
echo "<h3>6. Cek PHP Session</h3>";
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "✅ Session sudah aktif<br>";
} else {
    session_start();
    echo "✅ Session berhasil di-start<br>";
}
echo "Session ID: " . session_id() . "<br>";
echo "Session Save Path: " . session_save_path() . "<br><br>";

// Test 7: Test login simulation
echo "<h3>7. Test Login (Simulasi)</h3>";
$test_email = "admin@inventory.com";
$test_pass = md5("admin123");

$query = "SELECT * FROM users WHERE email = '$test_email' AND password = '$test_pass' AND status = 'aktif'";
$result = mysqli_query($conn, $query);

if ($result && mysqli_num_rows($result) == 1) {
    $user = mysqli_fetch_assoc($result);
    echo "✅ Login test <strong>BERHASIL</strong>!<br>";
    echo "User: <strong>" . $user['nama_user'] . "</strong><br>";
    echo "Email: <strong>" . $user['email'] . "</strong><br>";
    echo "Role: <strong>" . $user['role'] . "</strong><br><br>";
    
    echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px;'>";
    echo "<h4 style='color: #155724; margin: 0;'>✅ SEMUA TEST BERHASIL!</h4>";
    echo "<p style='margin: 10px 0 0 0;'>Sistem siap digunakan. Silakan login di: <a href='index.php'>index.php</a></p>";
    echo "</div>";
} else {
    echo "❌ Login test <strong>GAGAL</strong>!<br>";
    echo "Periksa kembali data di tabel users.<br><br>";
}

echo "<hr>";
echo "<p><a href='index.php'>← Kembali ke Login</a></p>";

mysqli_close($conn);
?>