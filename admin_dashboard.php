<?php
session_start();
include 'db.php';

if ($_SESSION['role_id'] != 1) { 
    header("Location: login.php");
    exit;
}

// Thêm dịch vụ mới 
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_service'])) {
        $service_name = $_POST['service_name'];
        $price = $_POST['price'];
        $stmt = $conn->prepare("INSERT INTO services (name, price) VALUES (?, ?)");
        $stmt->execute([$service_name, $price]);
        $message = "Dịch vụ mới đã được thêm thành công!";
    } elseif (isset($_POST['edit_service'])) {
        $service_id = $_POST['service_id'];
        $service_name = $_POST['service_name'];
        $price = $_POST['price'];
        $stmt = $conn->prepare("UPDATE services SET name = ?, price = ? WHERE id = ?");
        $stmt->execute([$service_name, $price, $service_id]);
        $message = "Dịch vụ đã được cập nhật thành công!";
    } elseif (isset($_POST['delete_service'])) {
        $service_id = $_POST['service_id'];
        $stmt = $conn->prepare("DELETE FROM services WHERE id = ?");
        $stmt->execute([$service_id]);
        $message = "Dịch vụ đã được xóa thành công!";
    } elseif (isset($_POST['add_user'])) {
        $username = $_POST['username'];
        $password = hash('sha256', $_POST['password']);
        $role_id = $_POST['role_id'];
        $specialty = $_POST['specialty'] ?? NULL;
        $stmt = $conn->prepare("INSERT INTO users (username, password, role_id, specialty) VALUES (?, ?, ?, ?)");
        $stmt->execute([$username, $password, $role_id, $specialty]);
        $message = "Người dùng mới đã được thêm thành công!";
    } elseif (isset($_POST['edit_user'])) {
        $user_id = $_POST['user_id'];
        $username = $_POST['username'];
        $password = hash('sha256', $_POST['password']);
        $role_id = $_POST['role_id'];
        $specialty = $_POST['specialty'] ?? NULL;
        $stmt = $conn->prepare("UPDATE users SET username = ?, password = ?, role_id = ?, specialty = ? WHERE id = ?");
        $stmt->execute([$username, $password, $role_id, $specialty, $user_id]);
        $message = "Người dùng đã được cập nhật thành công!";
    } elseif (isset($_POST['delete_user'])) {
        $user_id = $_POST['user_id'];
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $message = "Người dùng đã được xóa thành công!";
    } elseif (isset($_POST['get_statistics'])) {
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        $doctor_id = $_POST['doctor_id'];

        // Lấy tổng số bệnh nhân và doanh thu trong khoảng thời gian và bác sĩ được chọn
        $stmt = $conn->prepare("
            SELECT COUNT(*) as patient_count, SUM(s.price) as revenue 
            FROM appointments a
            JOIN services s ON a.service_id = s.id
            WHERE a.appointment_time BETWEEN ? AND ? 
            AND (a.doctor_id = ? OR ? = 'all')
        ");
        $stmt->execute([$start_date, $end_date, $doctor_id, $doctor_id]);
        $statistics = $stmt->fetch();
    }
}

// Lấy danh sách dịch vụ
$services = $conn->query("SELECT * FROM services")->fetchAll();

// Lấy danh sách bác sĩ
$doctors = $conn->query("SELECT * FROM users WHERE role_id = 3")->fetchAll();

// Lấy danh sách người dùng
$users = $conn->query("SELECT * FROM users")->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Bảng điều khiển Quản lý</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</head>
<body>
    <div class="container mt-5">
        <h2>Chào mừng Quản lý!</h2>
        <a href="logout.php" class="btn btn-danger">Đăng xuất</a>

        <?php if (isset($message)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <h3>Thêm Dịch vụ Mới</h3>
        <form method="POST" action="">
            <div class="form-group">
                <label for="service_name">Tên Dịch vụ:</label>
                <input type="text" class="form-control" name="service_name" required>
            </div>
            <div class="form-group">
                <label for="price">Giá:</label>
                <input type="number" class="form-control" name="price" required>
            </div>
            <button type="submit" name="add_service" class="btn btn-primary">Thêm dịch vụ</button>
        </form>

        <h3>Danh sách Dịch vụ</h3>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tên Dịch vụ</th>
                    <th>Giá</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($services as $service): ?>
                    <tr>
                        <td><?php echo $service['id']; ?></td>
                        <td><?php echo htmlspecialchars($service['name']); ?></td>
                        <td><?php echo number_format($service['price']); ?> VND</td>
                        <td>
                            <button type="button" class="btn btn-warning btn-sm" data-toggle="modal" data-target="#editServiceModal<?php echo $service['id']; ?>">Sửa</button>

                            <!-- Sửa dịch vụ dùng bootstrap modal  -->
                            <div class="modal fade" id="editServiceModal<?php echo $service['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="editServiceModalLabel<?php echo $service['id']; ?>" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="editServiceModalLabel<?php echo $service['id']; ?>">Sửa Dịch vụ</h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body">
                                            <form method="POST" action="">
                                                <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
                                                <div class="form-group">
                                                    <label for="service_name">Tên Dịch vụ:</label>
                                                    <input type="text" class="form-control" name="service_name" value="<?php echo htmlspecialchars($service['name']); ?>" required>
                                                </div>
                                                <div class="form-group">
                                                    <label for="price">Giá:</label>
                                                    <input type="number" class="form-control" name="price" value="<?php echo $service['price']; ?>" required>
                                                </div>
                                                <button type="submit" name="edit_service" class="btn btn-primary">Lưu thay đổi</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <form method="POST" action="" style="display:inline-block;">
                                <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
                                <button type="submit" name="delete_service" class="btn btn-danger btn-sm">Xóa</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h3>Thêm Người dùng Mới</h3>
        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Tên Người dùng:</label>
                <input type="text" class="form-control" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Mật khẩu:</label>
                <input type="password" class="form-control" name="password" required>
            </div>
            <div class="form-group">
                <label for="role_id">Vai trò:</label>
                <select class="form-control" name="role_id" required>
                    <option value="1">Quản lý</option>
                    <option value="2">Nhân viên</option>
                    <option value="3">Bác sĩ</option>
                    <option value="4">Bệnh nhân</option>
                </select>
            </div>
            <div class="form-group">
                <label for="specialty">Chuyên khoa (nếu có):</label>
                <input type="text" class="form-control" name="specialty">
            </div>
            <button type="submit" name="add_user" class="btn btn-primary">Thêm Người dùng</button>
        </form>

        <h3>Danh sách Người dùng</h3>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tên Người dùng</th>
                    <th>Vai trò</th>
                    <th>Chuyên khoa</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo $user['role_id']; ?></td>
                        <td><?php echo htmlspecialchars($user['specialty']); ?></td>
                        <td>
                            <button type="button" class="btn btn-warning btn-sm" data-toggle="modal" data-target="#editUserModal<?php echo $user['id']; ?>">Sửa</button>

                            <!-- Sửa người dùng dùng bootstrap modal  -->
                            <div class="modal fade" id="editUserModal<?php echo $user['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="editUserModalLabel<?php echo $user['id']; ?>" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="editUserModalLabel<?php echo $user['id']; ?>">Sửa Người dùng</h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body">
                                            <form method="POST" action="">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <div class="form-group">
                                                    <label for="username">Tên Người dùng:</label>
                                                    <input type="text" class="form-control" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                                                </div>
                                                <div class="form-group">
                                                    <label for="password">Mật khẩu:</label>
                                                    <input type="password" class="form-control" name="password" required>
                                                </div>
                                                <div class="form-group">
                                                    <label for="role_id">Vai trò:</label>
                                                    <select class="form-control" name="role_id" required>
                                                        <option value="1" <?php if ($user['role_id'] == 1) echo 'selected'; ?>>Quản lý</option>
                                                        <option value="2" <?php if ($user['role_id'] == 2) echo 'selected'; ?>>Nhân viên</option>
                                                        <option value="3" <?php if ($user['role_id'] == 3) echo 'selected'; ?>>Bác sĩ</option>
                                                        <option value="4" <?php if ($user['role_id'] == 4) echo 'selected'; ?>>Bệnh nhân</option>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label for="specialty">Chuyên khoa (nếu có):</label>
                                                    <input type="text" class="form-control" name="specialty" value="<?php echo htmlspecialchars($user['specialty']); ?>">
                                                </div>
                                                <button type="submit" name="edit_user" class="btn btn-primary">Lưu thay đổi</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <form method="POST" action="" style="display:inline-block;">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <button type="submit" name="delete_user" class="btn btn-danger btn-sm">Xóa</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

 <!--        <h3>Thống kê Số lượng Bệnh nhân và Doanh thu</h3>
        <form method="POST" action="">
            <div class="form-group">
                <label for="start_date">Từ ngày:</label>
                <input type="date" class="form-control" name="start_date" required>
            </div>
            <div class="form-group">
                <label for="end_date">Đến ngày:</label>
                <input type="date" class="form-control" name="end_date" required>
            </div>
            <div class="form-group">
                <label for="doctor_id">Bác sĩ:</label>
                <select class="form-control" name="doctor_id">
                    <option value="all">Tất cả</option>
                    <?php foreach ($doctors as $doctor): ?>
                        <option value="<?php echo $doctor['id']; ?>"><?php echo htmlspecialchars($doctor['username']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" name="get_statistics" class="btn btn-primary">Lấy thống kê</button>
        </form>

        <?php if (!empty($statistics)): ?>
            <h4>Kết quả Thống kê</h4>
            <p>Số lượng bệnh nhân: <?php echo $statistics['patient_count']; ?></p>
            <p>Doanh thu: <?php echo number_format($statistics['revenue']); ?> VND</p>
        <?php endif; ?>
    </div>
</body>
</html>
 -->
<style>
    body {
        background-color: #f8f9fa;
    }
    .container {
        background-color: #ffffff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }
    h2, h3 {
        color: #343a40;
    }
    .btn-primary {
        background-color: #007bff;
        border-color: #007bff;
    }
    .btn-danger {
        background-color: #dc3545;
        border-color: #dc3545;
    }
    .btn-warning {
        background-color: #ffc107;
        border-color: #ffc107;
    }
    .table {
        margin-top: 20px;
    }
    .form-group label {
        font-weight: bold;
    }
</style>