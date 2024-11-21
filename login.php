<?php
session_start();
include 'db.php';

// Kiểm tra kết nối cơ sở dữ liệu
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Xử lý đăng nhập
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {
        
        $hashed_password = hash('sha256', $password);

        // Truy vấn lấy thông tin người dùng 
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Kiểm tra mật khẩu 
        if ($user && $hashed_password === $user['password']) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role_id'] = $user['role_id']; 

            // Chuyển hướng dựa trên vai trò người dùng
            switch ($user['role_id']) {
                case 1: // Admin
                    header("Location: admin_dashboard.php");
                    break;
                case 2: // Staff
                    header("Location: staff_dashboard.php");
                    break;
                case 3: // Doctor
                    header("Location: doctor_dashboard.php");
                    break;
                case 4: // Patient
                    header("Location: patient_dashboard.php");
                    break;
                default:
                    $error = "Vai trò người dùng không hợp lệ.";
            }
            exit;
        } else {
            $error = "Tên đăng nhập hoặc mật khẩu không chính xác.";
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
    <title>Đăng nhập</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .login-container {
            width: 100%;
            max-width: 400px;
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
        <div class="login-container">
            <h3 class="text-center">Đăng nhập</h3>
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['success_message'], ENT_QUOTES, 'UTF-8'); ?></div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
            <form method="POST" action="login.php">
                <div class="form-group">
                    <label for="username">Tên đăng nhập</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password">Mật khẩu</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Đăng nhập</button>
                <a href="register.php" class="btn btn-link btn-block">Tạo tài khoản</a>
            </form>
        </div>
    </div>
</body>
</html>