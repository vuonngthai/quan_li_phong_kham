<?php
session_start();
include 'db.php';

// Kiểm tra vai trò
if ($_SESSION['role_id'] != 2 && $_SESSION['role_id'] != 3) { // Check for staff or doctor role ID
    header("Location: login.php");
    exit;
}

// Thêm lịch hẹn mới (Chỉ dành cho nhân viên)
if ($_SESSION['role_id'] == 2 && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_appointment'])) {
    $patient_id = $_POST['patient_id'];
    $service_id = $_POST['service_id'];
    $appointment_time = $_POST['appointment_time'];
    
    $stmt = $conn->prepare("INSERT INTO appointments (patient_id, service_id, appointment_time, status) VALUES (:patient_id, :service_id, :appointment_time, 'Chờ khám')");
    $stmt->execute([
        ':patient_id' => $patient_id,
        ':service_id' => $service_id,
        ':appointment_time' => $appointment_time
    ]);
}

// Lấy danh sách lịch hẹn
$stmt = $conn->prepare("SELECT a.*, u.username as patient_name, s.name as service_name FROM appointments a JOIN users u ON a.patient_id = u.id JOIN services s ON a.service_id = s.id");
$stmt->execute();
$appointments = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý Lịch hẹn</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2>Quản lý Lịch hẹn</h2>
        
        <?php if ($_SESSION['role_id'] == 2): // Only staff can add appointments ?>
            <h4>Thêm Lịch hẹn mới</h4>
            <form method="POST" action="manage_appointments.php">
                <div class="form-group">
                    <label for="patient_id">Bệnh nhân</label>
                    <select class="form-control" id="patient_id" name="patient_id" required>
                        <?php
                        $patients = $conn->query("SELECT id, username FROM users WHERE role_id = 4")->fetchAll();
                        foreach ($patients as $patient) {
                            echo "<option value='{$patient['id']}'>{$patient['username']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="service_id">Dịch vụ</label>
                    <select class="form-control" id="service_id" name="service_id" required>
                        <?php
                        $services = $conn->query("SELECT id, name FROM services")->fetchAll();
                        foreach ($services as $service) {
                            echo "<option value='{$service['id']}'>{$service['name']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="appointment_time">Thời gian khám</label>
                    <input type="datetime-local" class="form-control" id="appointment_time" name="appointment_time" required>
                </div>
                <button type="submit" name="add_appointment" class="btn btn-primary">Thêm Lịch Hẹn</button>
            </form>
        <?php endif; ?>

        <table class="table mt-4">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Bệnh nhân</th>
                    <th>Dịch vụ</th>
                    <th>Thời gian</th>
                    <th>Trạng thái</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($appointments as $appointment): ?>
                    <tr>
                        <td><?php echo $appointment['id']; ?></td>
                        <td><?php echo htmlspecialchars($appointment['patient_name']); ?></td>
                        <td><?php echo htmlspecialchars($appointment['service_name']); ?></td>
                        <td><?php echo htmlspecialchars($appointment['appointment_time']); ?></td>
                        <td><?php echo htmlspecialchars($appointment['status']); ?></td>
                        <td>
                            <!-- Add any actions you want to perform on the appointments here -->
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>

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
    h2 {
        color: #343a40;
    }
    .form-group label {
        font-weight: bold;
    }
    .btn-primary {
        background-color: #007bff;
        border-color: #007bff;
    }
    .table {
        margin-top: 20px;
    }
    .table th, .table td {
        vertical-align: middle;
    }
</style>