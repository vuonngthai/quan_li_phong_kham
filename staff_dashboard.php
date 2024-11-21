<?php
session_start();
include 'db.php';

if ($_SESSION['role_id'] != 2) { // Check for staff role ID
    header("Location: login.php");
    exit;
}

// Lấy danh sách các cuộc hẹn đang chờ khám
$appointments = $conn->query("
    SELECT a.id, u.username AS patient_name, s.name AS service_name, s.price, a.appointment_time, a.status 
    FROM appointments a 
    JOIN users u ON a.patient_id = u.id 
    JOIN services s ON a.service_id = s.id 
    WHERE a.status = 'pending'
")->fetchAll();

// Tạo phiếu khám và hóa đơn cho bệnh nhân đã hoặc chưa đặt lịch
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_invoice'])) {
        $appointment_id = $_POST['appointment_id'];
        $amount = $_POST['amount'];

        // Tạo hóa đơn
        $stmt = $conn->prepare("INSERT INTO bills (appointment_id, amount, payment_status) VALUES (?, ?, 'unpaid')");
        $stmt->execute([$appointment_id, $amount]);

        // Cập nhật trạng thái phiếu khám thành "chờ thanh toán"
        $update_stmt = $conn->prepare("UPDATE appointments SET status = 'pending payment' WHERE id = ?");
        $update_stmt->execute([$appointment_id]);
    } elseif (isset($_POST['create_appointment'])) {
        $patient_id = $_POST['patient_id'];
        $service_id = $_POST['service_id'];
        $doctor_id = $_POST['doctor_id'];
        $appointment_time = $_POST['appointment_time'];

        // Tạo phiếu khám mới
        $stmt = $conn->prepare("INSERT INTO appointments (patient_id, service_id, doctor_id, appointment_time, status) VALUES (?, ?, ?, ?, 'pending payment')");
        $stmt->execute([$patient_id, $service_id, $doctor_id, $appointment_time]);

        $message = "Lịch hẹn đã được tạo thành công và đang chờ thanh toán.";
    } elseif (isset($_POST['pay_invoice'])) {
        $bill_id = $_POST['bill_id'];

        // Cập nhật trạng thái hóa đơn thành "paid"
        $stmt = $conn->prepare("UPDATE bills SET payment_status = 'paid' WHERE id = ?");
        $stmt->execute([$bill_id]);

        // Cập nhật trạng thái phiếu khám thành "chờ khám"
        $appointment_id = $_POST['appointment_id'];
        $update_stmt = $conn->prepare("UPDATE appointments SET status = 'chờ khám' WHERE id = ?");
        $update_stmt->execute([$appointment_id]);
    }
}

// Lấy danh sách hóa đơn chưa thanh toán
$bills = $conn->query("
    SELECT b.id, b.appointment_id, b.amount, u.username AS patient_name, s.name AS service_name, a.appointment_time 
    FROM bills b 
    JOIN appointments a ON b.appointment_id = a.id 
    JOIN users u ON a.patient_id = u.id 
    JOIN services s ON a.service_id = s.id 
    WHERE b.payment_status = 'unpaid'
")->fetchAll();

// Lấy danh sách bác sĩ
$doctors = $conn->query("SELECT id, username FROM users WHERE role_id = 3")->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Nhân viên - Dashboard</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2>Dashboard Nhân viên</h2>
        
        <!-- Navigation Bar -->
        <nav class="navbar navbar-expand-lg navbar-light bg-light">
            <a class="navbar-brand" href="#">Clinic Management</a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="staff_dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_appointments.php">Quản lý Lịch hẹn</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Đăng xuất</a>
                    </li>
                </ul>
            </div>
        </nav>

        <?php if (isset($message)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <h4>Danh sách bệnh nhân đã đặt lịch khám</h4>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tên Bệnh Nhân</th>
                    <th>Dịch Vụ</th>
                    <th>Giá</th>
                    <th>Ngày Hẹn</th>
                    <th>Trạng Thái</th>
                    <th>Hành Động</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($appointments as $appointment): ?>
                    <tr>
                        <td><?php echo $appointment['id']; ?></td>
                        <td><?php echo htmlspecialchars($appointment['patient_name']); ?></td>
                        <td><?php echo htmlspecialchars($appointment['service_name']); ?></td>
                        <td><?php echo number_format($appointment['price'], 2); ?> VND</td>
                        <td><?php echo $appointment['appointment_time']; ?></td>
                        <td><?php echo $appointment['status']; ?></td>
                        <td>
                            <form method="POST" action="staff_dashboard.php">
                                <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                <input type="hidden" name="amount" value="<?php echo $appointment['price']; ?>">
                                <button type="submit" name="create_invoice" class="btn btn-primary btn-sm">Tạo Hóa Đơn</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h4>Danh sách Hóa Đơn Chưa Thanh Toán</h4>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID Hóa Đơn</th>
                    <th>Tên Bệnh Nhân</th>
                    <th>Dịch Vụ</th>
                    <th>Giá</th>
                    <th>Ngày Hẹn</th>
                    <th>Hành Động</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bills as $bill): ?>
                    <tr>
                        <td><?php echo $bill['id']; ?></td>
                        <td><?php echo htmlspecialchars($bill['patient_name']); ?></td>
                        <td><?php echo htmlspecialchars($bill['service_name']); ?></td>
                        <td><?php echo number_format($bill['amount'], 2); ?> VND</td>
                        <td><?php echo $bill['appointment_time']; ?></td>
                        <td>
                            <form method="POST" action="staff_dashboard.php">
                                <input type="hidden" name="bill_id" value="<?php echo $bill['id']; ?>">
                                <input type="hidden" name="appointment_id" value="<?php echo $bill['appointment_id']; ?>">
                                <button type="submit" name="pay_invoice" class="btn btn-success btn-sm">Thanh Toán</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h4>Tạo Phiếu Khám Mới Cho Bệnh Nhân</h4>
        <form method="POST" action="staff_dashboard.php">
            <div class="form-group">
                <label for="patient_id">Chọn Bệnh Nhân</label>
                <select name="patient_id" id="patient_id" class="form-control" required>
                    <?php
                    $patients = $conn->query("SELECT id, username FROM users WHERE role_id = 4")->fetchAll();
                    foreach ($patients as $patient) {
                        echo "<option value='{$patient['id']}'>{$patient['username']}</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label for="service_id">Chọn Dịch Vụ</label>
                <select name="service_id" id="service_id" class="form-control" required>
                    <?php
                    $services = $conn->query("SELECT id, name, price FROM services")->fetchAll();
                    foreach ($services as $service) {
                        echo "<option value='{$service['id']}'>{$service['name']} - " . number_format($service['price'], 2) . " VND</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label for="doctor_id">Chọn Bác Sĩ</label>
                <select name="doctor_id" id="doctor_id" class="form-control" required>
                    <?php
                    $doctors = $conn->query("SELECT id, username FROM users WHERE role_id = 3")->fetchAll();
                    foreach ($doctors as $doctor) {
                        echo "<option value='{$doctor['id']}'>{$doctor['username']}</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label for="appointment_time">Ngày Hẹn</label>
                <input type="datetime-local" class="form-control" id="appointment_time" name="appointment_time" required>
            </div>
            <button type="submit" name="create_appointment" class="btn btn-success">Tạo Phiếu Khám</button>
        </form>
    </div>
</body>
</html>

<style>
    body {
        font-family: 'Arial', sans-serif;
        background-color: #f8f9fa;
    }
    .container {
        background-color: #ffffff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }
    h2, h4 {
        color: #343a40;
    }
    .navbar {
        margin-bottom: 20px;
    }
    .table {
        margin-top: 20px;
    }
    .btn {
        margin-right: 5px;
    }
    .form-group label {
        font-weight: bold;
    }
</style>