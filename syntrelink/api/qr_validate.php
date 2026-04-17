            [$jti, $userId, $eventId]
        );
    }

    db_execute(
        $conn,
        "INSERT INTO admissions (ticket_id, user_id, event_id, scanned_by, scanned_at, gate_location, status)
         VALUES (?, ?, ?, ?, NOW(), ?, 'admitted')",
        'iiiis',
        [(int) $ticket['id'], $userId, $eventId, $officerId, 'Main Gate']
    );

    $conn->commit();

    echo json_encode([
        'status' => 'admitted',
        'message' => 'Admission recorded.',
        'student' => [
            'name' => $ticket['first_name'] . ' ' . $ticket['last_name'],
            'student_id' => $ticket['student_id'],
            'course' => $ticket['course'],
            'year' => $ticket['year_level'],
        ],
    ]);
} catch (Throwable $e) {
    $conn->rollback();

    if ($e->getMessage() === 'DOWNLOAD_QR_ALREADY_USED' || str_contains($e->getMessage(), 'Duplicate entry')) {
        echo json_encode(['status' => 'duplicate', 'message' => 'This QR has already been used.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Admission failed: ' . $e->getMessage()]);
    }
}
?>
