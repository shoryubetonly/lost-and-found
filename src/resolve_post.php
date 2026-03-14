<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) { header("Location: index.php"); exit; }

$item_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

try {
    $pdo = new PDO("mysql:host=db;dbname=lost_and_found_db;charset=utf8mb4", "admin", "password123");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. ตรวจสอบสิทธิ์แอดมิน
    $stmt_admin = $pdo->prepare("SELECT email FROM users WHERE id = ?");
    $stmt_admin->execute([$user_id]);
    $current_email = $stmt_admin->fetchColumn();
    $is_admin = ($current_email === '66209010013@bncc.ac.th');

    // 2. ดึงข้อมูลโพสต์นี้มาก่อน เพื่อเช็กว่าเป็นประเภท FOUND หรือไม่ และใครเป็นเจ้าของ
    $stmt_item = $pdo->prepare("SELECT user_id, post_type, status FROM items WHERE id = ?");
    $stmt_item->execute([$item_id]);
    $item = $stmt_item->fetch(PDO::FETCH_ASSOC);

    if (!$item) { header("Location: index.php"); exit; }

    // 3. ตรวจสอบว่าเคสนี้ยังไม่ถูกปิด (ป้องกันการปั๊มแต้มจากการ Refresh หน้าจอ)
    if ($item['status'] !== 'RESOLVED') {
        
        $can_resolve = false;
        if ($is_admin) {
            $can_resolve = true;
        } elseif ($item['user_id'] == $user_id) {
            $can_resolve = true;
        }

        if ($can_resolve) {
            // เริ่มต้นการทำงานแบบ Transaction (เพื่อให้ชัวร์ว่าอัปเดตทั้งคู่หรือไม่อัปเดตเลย)
            $pdo->beginTransaction();

            // ก. อัปเดตสถานะโพสต์เป็น RESOLVED
            $update_status = $pdo->prepare("UPDATE items SET status = 'RESOLVED' WHERE id = ?");
            $update_status->execute([$item_id]);

            // ข. ถ้าเป็นประเภท FOUND (เก็บได้) ให้เพิ่มแต้มความดี +10 ให้ "เจ้าของโพสต์"
            if ($item['post_type'] === 'FOUND') {
                $update_points = $pdo->prepare("UPDATE users SET points = points + 10 WHERE id = ?");
                $update_points->execute([$item['user_id']]);
            }

            $pdo->commit();
        }
    }

} catch (Exception $e) {
    if (isset($pdo)) { $pdo->rollBack(); }
    die("เกิดข้อผิดพลาด: " . $e->getMessage());
}

header("Location: index.php");
exit;