<?php
include 'db.php';

$users = [
    ['username' => 'admin', 'password' => 'admin_password', 'role_id' => 1, 'specialty' => NULL],
    ['username' => 'staff1', 'password' => 'staff_password', 'role_id' => 2, 'specialty' => NULL],
    ['username' => 'doctor1', 'password' => 'doctor_password', 'role_id' => 3, 'specialty' => 'Cardiology'],
    ['username' => 'patient1', 'password' => 'patient_password', 'role_id' => 4, 'specialty' => NULL],
];

foreach ($users as $user) {
    $hashed_password = hash('sha256', $user['password']);
    $stmt = $conn->prepare("INSERT INTO users (username, password, role_id, specialty) VALUES (:username, :password, :role_id, :specialty)");
    $stmt->execute([
        ':username' => $user['username'],
        ':password' => $hashed_password,
        ':role_id' => $user['role_id'],
        ':specialty' => $user['specialty']
    ]);
}
?>