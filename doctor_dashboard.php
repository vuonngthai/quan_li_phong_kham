<?php
session_start();
include 'db.php';

if ($_SESSION['role_id'] != 3) { // Check for doctor role ID
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['complete_appointment'])) {
        $appointment_id = $_POST['appointment_id'];
        $diagnosis = $_POST['diagnosis'];
        $prescription = $_POST['prescription'];
        $status = 'completed';

        // Cập nhật trạng thái phiếu khám thành "hoàn thành"
        $stmt = $conn->prepare("UPDATE appointments SET status = ? WHERE id = ?");
        $stmt->execute([$status, $appointment_id]);

        // Tạo phiếu khám
        $stmt = $conn->prepare("INSERT INTO medical_records (appointment_id, doctor_id, diagnosis, prescription, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$appointment_id, $_SESSION['user_id'], $diagnosis, $prescription, $status]);

        $message = "Phiếu khám đã được hoàn thành.";
    } elseif (isset($_POST['edit_medical_record'])) {
        $record_id = $_POST['record_id'];
        $diagnosis = $_POST['diagnosis'];
        $prescription = $_POST['prescription'];

        // Cập nhật phiếu khám
        $stmt = $conn->prepare("UPDATE medical_records SET diagnosis = ?, prescription = ? WHERE id = ?");
        $stmt->execute([$diagnosis, $prescription, $record_id]);

        $message = "Phiếu khám đã được cập nhật.";
    } elseif (isset($_POST['delete_medical_record'])) {
        $record_id = $_POST['record_id'];

        // Xóa phiếu khám
        $stmt = $conn->prepare("DELETE FROM medical_records WHERE id = ?");
        $stmt->execute([$record_id]);

        $message = "Phiếu khám đã được xóa.";
    }
}

// Lấy danh sách bệnh nhân đang chờ khám
$stmt = $conn->prepare("SELECT a.*, u.username as patient_name FROM appointments a JOIN users u ON a.patient_id = u.id WHERE a.status = 'chờ khám'");
$stmt->execute();
$appointments = $stmt->fetchAll();

// Lấy danh sách phiếu khám
$medical_records = $conn->prepare("SELECT mr.*, u.username as patient_name FROM medical_records mr JOIN appointments a ON mr.appointment_id = a.id JOIN users u ON a.patient_id = u.id WHERE a.doctor_id = ?");
$medical_records->execute([$_SESSION['user_id']]);
$records = $medical_records->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Bảng điều khiển Bác sĩ</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</head>
<body>
    <div class="container mt-5">
        <h2>Chào mừng Bác sĩ!</h2>
        
        <!-- Navigation Bar -->
        <nav class="navbar navbar-expand-lg navbar-light bg-light">
            <a class="navbar-brand" href="#">Clinic Management</a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="doctor_dashboard.php">Dashboard</a>
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

        <h3>Danh sách Bệnh nhân Chờ Khám</h3>
        <?php if (isset($message)) echo "<p class='alert alert-success'>$message</p>"; ?>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Tên Bệnh nhân</th>
                    <th>Trạng thái</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($appointments as $appointment): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($appointment['patient_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($appointment['status'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <form method="POST" action="">
                                <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                <div class="form-group">
                                    <label for="diagnosis">Chẩn đoán</label>
                                    <textarea class="form-control" id="diagnosis" name="diagnosis" required></textarea>
                                </div>
                                <div class="form-group">
                                    <label for="prescription">Đơn thuốc</label>
                                    <textarea class="form-control" id="prescription" name="prescription" required></textarea>
                                </div>
                                <button type="submit" name="complete_appointment" class="btn btn-primary">Hoàn tất Khám</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h3>Danh sách Phiếu Khám</h3>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Tên Bệnh nhân</th>
                    <th>Chẩn đoán</th>
                    <th>Đơn thuốc</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($records as $record): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($record['patient_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($record['diagnosis'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($record['prescription'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <button type="button" class="btn btn-warning btn-sm" data-toggle="modal" data-target="#editRecordModal<?php echo $record['id']; ?>">Sửa</button>

                            <!-- Sửa phiếu khám -->
                            <div class="modal fade" id="editRecordModal<?php echo $record['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="editRecordModalLabel<?php echo $record['id']; ?>" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="editRecordModalLabel<?php echo $record['id']; ?>">Sửa Phiếu Khám</h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body">
                                            <form method="POST" action="">
                                                <input type="hidden" name="record_id" value="<?php echo $record['id']; ?>">
                                                <div class="form-group">
                                                    <label for="diagnosis">Chẩn đoán</label>
                                                    <textarea class="form-control" id="diagnosis" name="diagnosis" required><?php echo htmlspecialchars($record['diagnosis'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                                                </div>
                                                <div class="form-group">
                                                    <label for="prescription">Đơn thuốc</label>
                                                    <textarea class="form-control" id="prescription" name="prescription" required><?php echo htmlspecialchars($record['prescription'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                                                </div>
                                                <button type="submit" name="edit_medical_record" class="btn btn-primary">Lưu thay đổi</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <form method="POST" action="" style="display:inline-block;">
                                <input type="hidden" name="record_id" value="<?php echo $record['id']; ?>">
                                <button type="submit" name="delete_medical_record" class="btn btn-danger btn-sm">Xóa</button>
                            </form>
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
        font-family: 'Arial', sans-serif;
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
    .navbar {
        margin-bottom: 20px;
    }
    .table {
        margin-top: 20px;
    }
    .form-group label {
        font-weight: bold;
    }
    .btn-primary {
        background-color: #007bff;
        border-color: #007bff;
    }
    .btn-primary:hover {
        background-color: #0056b3;
        border-color: #004085;
    }
    .btn-warning {
        background-color: #ffc107;
        border-color: #ffc107;
    }
    .btn-warning:hover {
        background-color: #e0a800;
        border-color: #d39e00;
    }
    .btn-danger {
        background-color: #dc3545;
        border-color: #dc3545;
    }
    .btn-danger:hover {
        background-color: #c82333;
        border-color: #bd2130;
    }
</style>