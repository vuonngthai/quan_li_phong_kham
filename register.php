<?php
include 'db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    if (!empty($username) && !empty($password) && !empty($confirm_password)) {
        if ($password === $confirm_password) {
            // Mã hóa mật khẩu người dùng nhập vào bằng SHA-256
            $hashed_password = hash('sha256', $password);

            // Kiểm tra xem tên đăng nhập đã tồn tại chưa
            $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = :username");
            $stmt->execute([':username' => $username]);
            $count = $stmt->fetchColumn();

            if ($count == 0) {
                // Chuẩn bị truy vấn SQL
                $stmt = $conn->prepare("INSERT INTO users (username, password, role_id) VALUES (:username, :password, :role_id)");
                $stmt->bindValue(':username', $username);
                $stmt->bindValue(':password', $hashed_password);
                $stmt->bindValue(':role_id', 4); // 4 is the role_id for 'patient'

                // Thực thi truy vấn
                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "Tài khoản của bạn đã được tạo thành công. Vui lòng đăng nhập.";
                    header("Location: login.php");
                    exit;
                } else {
                    $error = "Đã xảy ra lỗi khi tạo tài khoản.";
                }
            } else {
                $error = "Tên đăng nhập đã tồn tại. Vui lòng chọn tên đăng nhập khác.";
            }
        } else {
            $error = "Mật khẩu xác nhận không khớp.";
        }
    } else {
        $error = "Vui lòng điền đầy đủ thông tin.";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Tạo tài khoản</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .register-container {
            width: 100%;
            max-width: 450px;
            margin: 50px auto;
            padding: 2em;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="register-container">
            <h3 class="text-center">Tạo tài khoản</h3>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
            <form method="POST" action="register.php">
                <div class="form-group">
                    <label for="username">Tên đăng nhập</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password">Mật khẩu</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Xác nhận mật khẩu</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Đăng ký</button>
            </form>
            <a href="login.php" class="btn btn-link btn-block">Đã có tài khoản? Đăng nhập</a>
        </div>
    </div>
</body>
</html>