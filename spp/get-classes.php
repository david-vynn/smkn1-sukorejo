<?php

require_once __DIR__ . '/../database/config.php';

header('Content-Type: application/json');

$majorId = $_GET['major_id'] ?? '';

if (!is_numeric($majorId)) {
    echo json_encode([]);
    exit;
}

$stmt = mysqli_prepare($conn, "
        SELECT id, class_name
        FROM classes
        WHERE major_id = ?
        ORDER BY
        FIELD(grade, 'X', 'XI', 'XII'),
        class_number ASC"
    );

mysqli_stmt_bind_param( $stmt,'i', $majorId);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$classes = [];

while ($row = mysqli_fetch_assoc($result)) {
    $classes[] = $row;
}

echo json_encode($classes);

?>