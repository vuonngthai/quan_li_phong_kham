<?php
session_start();
include 'db.php';

if ($_SESSION['role_id'] != 4) { // Check for patient role ID
    header("Location: login.php");
    exit;
}

$services = $conn->query("SELECT * FROM services")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['appointment'])) {
    $doctor_id = $_POST['doctor_id'];
    $service_id = $_POST['service_id'];
    $appointment_date = $_POST['appointment_date'];

    $stmt = $conn->prepare("INSERT INTO appointments (patient_id, doctor_id, service_id, appointment_time, status) VALUES (?, ?, ?, ?, 'pending')");
    $stmt->execute([$_SESSION['user_id'], $doctor_id, $service_id, $appointment_date]);
    $message = "Lịch hẹn của bạn đã được tạo thành công!";
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Bảng điều khiển Bệnh nhân</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h2>Chào mừng Bệnh nhân!</h2>
    <a href="logout.php">Đăng xuất</a>

    <h3>Thông tin Dịch vụ</h3>
    <table>
        <tr><th>Dịch vụ</th><th>Giá</th></tr>
        <?php foreach ($services as $service): ?>
            <tr>
                <td><?php echo htmlspecialchars($service['name']); ?></td>
                <td><?php echo number_format($service['price']); ?> VND</td>
            </tr>
        <?php endforeach; ?>
    </table>

    <h3>Danh sách Bác sĩ</h3>
    <table>
        <tr><th>Tên Bác sĩ</th><th>Chuyên khoa</th></tr>
        <?php
        $doctors = $conn->query("SELECT * FROM users WHERE role_id = 3")->fetchAll(); // Check for doctor role ID
        foreach ($doctors as $doctor): ?>
            <tr>
                <td><?php echo htmlspecialchars($doctor['username']); ?></td>
                <td><?php echo htmlspecialchars($doctor['specialty']); ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <h3>Đặt Lịch Hẹn</h3>
    <form method="POST" action="">
        <label for="service_id">Dịch vụ:</label>
        <select name="service_id" required>
            <?php foreach ($services as $service): ?>
                <option value="<?php echo $service['id']; ?>"><?php echo htmlspecialchars($service['name']); ?></option>
            <?php endforeach; ?>
        </select>
        <label for="doctor_id">Bác sĩ:</label>
        <select name="doctor_id" required>
            <?php foreach ($doctors as $doctor): ?>
                <option value="<?php echo $doctor['id']; ?>"><?php echo htmlspecialchars($doctor['username']); ?></option>
            <?php endforeach; ?>
        </select>
        <label for="appointment_date">Ngày hẹn:</label>
        <input type="datetime-local" name="appointment_date" required>
        <button type="submit" name="appointment">Đặt lịch</button>
    </form>
    <?php if (isset($message)) echo "<p>$message</p>"; ?>
</body>
</html>

<style>
    body {
        font-family: Arial, sans-serif;
        background-color: #f4f4f4;
        margin: 0;
        padding: 0;
    }
    h2, h3 {
        color: #333;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }
    table, th, td {
        border: 1px solid #ddd;
    }
    th, td {
        padding: 8px;
        text-align: left;
    }
    th {
        background-color: #f2f2f2;
    }
    form {
        background-color: #fff;
        padding: 20px;
        border: 1px solid #ddd;
        border-radius: 5px;
    }
    label {
        display: block;
        margin-bottom: 5px;
    }
    select, input[type="datetime-local"], button {
        width: 100%;
        padding: 10px;
        margin-bottom: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
    }
    button {
        background-color: #4CAF50;
        color: white;
        border: none;
        cursor: pointer;
    }
    button:hover {
        background-color: #45a049;
    }
    p {
        color: green;
    }
</style>

<h3>Hướng dẫn Thanh toán</h3>
<p>Để gặp bác sĩ, vui lòng đến phòng khám để thanh toán cho nhân viên phòng khám và nhận phiếu khám.</p>
<h3>Lịch hẹn của bạn</h3>
<table>

    <?php
    $appointments = $conn->prepare("SELECT a.*, u.username as doctor_name, s.name as service_name FROM appointments a JOIN users u ON a.doctor_id = u.id JOIN services s ON a.service_id = s.id WHERE a.patient_id = ?");
    $appointments->execute([$_SESSION['user_id']]);
    foreach ($appointments as $appointment): ?>
        <tr>
            <td><?php echo htmlspecialchars($appointment['doctor_name']); ?></td>
            <td><?php echo htmlspecialchars($appointment['service_name']); ?></td>
            <td><?php echo htmlspecialchars($appointment['appointment_time']); ?></td>
            <td><?php echo htmlspecialchars($appointment['status']); ?></td>
        </tr>
    <?php endforeach; ?>
</table>




<style>
    footer {
        background-color: #333;
        color: white;
        text-align: center;
        padding: 10px 0;
        position: fixed;
        bottom: 0;
        width: 100%;
    }
</style>