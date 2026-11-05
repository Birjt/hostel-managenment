<?php

session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "admin") {
    header("Location: ../login.php");
    exit();
}

include "../config/database.php";

$message = "";
$message_type = "";


/* =========================
   ALLOCATE ROOM
========================= */

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $student_id = (int) $_POST["student_id"];
    $room_id = (int) $_POST["room_id"];
    $allocation_date = $_POST["allocation_date"];

    /* Check room */

    $room_stmt = $conn->prepare(
        "SELECT capacity, status
         FROM rooms
         WHERE room_id = ?"
    );

    $room_stmt->bind_param("i", $room_id);
    $room_stmt->execute();

    $room_result = $room_stmt->get_result();

    if ($room_result->num_rows != 1) {

        $message = "Room not found.";
        $message_type = "error";

    } else {

        $room = $room_result->fetch_assoc();

        /* Count current occupants */

        $count_stmt = $conn->prepare(
            "SELECT COUNT(*) AS occupied
             FROM room_allocations
             WHERE room_id = ?
             AND checkout_date IS NULL"
        );

        $count_stmt->bind_param("i", $room_id);
        $count_stmt->execute();

        $count_result = $count_stmt->get_result();
        $count_data = $count_result->fetch_assoc();

        $occupied = (int) $count_data["occupied"];


        if ($room["status"] == "Maintenance") {

            $message = "This room is under maintenance.";
            $message_type = "error";

        } elseif ($occupied >= $room["capacity"]) {

            $message = "This room is already full.";
            $message_type = "error";

        } else {

            /* Check whether student already has a room */

            $student_check = $conn->prepare(
                "SELECT allocation_id
                 FROM room_allocations
                 WHERE student_id = ?
                 AND checkout_date IS NULL"
            );

            $student_check->bind_param("i", $student_id);
            $student_check->execute();

            $student_result = $student_check->get_result();

            if ($student_result->num_rows > 0) {

                $message = "This student already has a room.";
                $message_type = "error";

            } else {

                /* Insert allocation */

                $stmt = $conn->prepare(
                    "INSERT INTO room_allocations
                    (student_id, room_id, allocation_date)
                    VALUES (?, ?, ?)"
                );

                $stmt->bind_param(
                    "iis",
                    $student_id,
                    $room_id,
                    $allocation_date
                );

                if ($stmt->execute()) {

                    /* Calculate new occupancy */

                    $new_occupied = $occupied + 1;

                    if ($new_occupied >= $room["capacity"]) {

                        $new_status = "Full";

                    } else {

                        $new_status = "Available";
                    }

                    $update_room = $conn->prepare(
                        "UPDATE rooms
                         SET status = ?
                         WHERE room_id = ?"
                    );

                    $update_room->bind_param(
                        "si",
                        $new_status,
                        $room_id
                    );

                    $update_room->execute();

                    $message = "Room allocated successfully!";
                    $message_type = "success";

                } else {

                    $message = "Error allocating room.";
                    $message_type = "error";
                }
            }
        }
    }
}


/* =========================
   GET STUDENTS WITHOUT ROOM
========================= */

$students = $conn->query(
    "SELECT s.student_id, s.student_name
     FROM students s
     LEFT JOIN room_allocations ra
     ON s.student_id = ra.student_id
     AND ra.checkout_date IS NULL
     WHERE ra.allocation_id IS NULL
     ORDER BY s.student_name"
);


/* =========================
   GET AVAILABLE ROOMS
========================= */

$rooms = $conn->query(
    "SELECT
        r.room_id,
        r.room_number,
        r.room_type,
        r.capacity,
        r.status,
        COUNT(ra.allocation_id) AS occupied
     FROM rooms r
     LEFT JOIN room_allocations ra
     ON r.room_id = ra.room_id
     AND ra.checkout_date IS NULL
     WHERE r.status != 'Maintenance'
     GROUP BY
        r.room_id,
        r.room_number,
        r.room_type,
        r.capacity,
        r.status
     HAVING occupied < r.capacity
     ORDER BY r.room_number"
);


/* =========================
   GET CURRENT ALLOCATIONS
========================= */

$allocations = $conn->query(
    "SELECT
        ra.allocation_id,
        s.student_name,
        r.room_number,
        r.room_type,
        ra.allocation_date,
        ra.checkout_date
     FROM room_allocations ra
     INNER JOIN students s
     ON ra.student_id = s.student_id
     INNER JOIN rooms r
     ON ra.room_id = r.room_id
     ORDER BY ra.allocation_id DESC"
);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Room Allocation - Hostel Management System</title>

    <link rel="stylesheet" href="../css/style.css">

    <style>

        .allocation-form {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .allocation-form select,
        .allocation-form input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-sizing: border-box;
        }

        .allocation-form button {
            width: fit-content;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }

        .message {
            padding: 12px 15px;
            margin: 20px 0;
            border-radius: 8px;
        }

        .success {
            background: #d1fae5;
            color: #065f46;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
        }

        .table-container {
            overflow-x: auto;
        }

        .allocation-table {
            width: 100%;
            border-collapse: collapse;
        }

        .allocation-table th,
        .allocation-table td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }

        .allocation-table th {
            background: #f3f4f6;
        }

        @media (max-width: 700px) {

            .allocation-form {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>

<body>

<div class="dashboard-container">

    <aside class="sidebar">

        <h2>Hostel Management</h2>

        <p>
            Welcome,
            <?php echo htmlspecialchars($_SESSION["username"]); ?>
        </p>

        <nav>

            <a href="dashboard.php">
                🏠 Dashboard
            </a>

            <a href="students.php">
                👨‍🎓 Students
            </a>

            <a href="rooms.php">
                🛏️ Rooms
            </a>

            <a href="allocation.php" class="active">
                📋 Room Allocation
            </a>

            <a href="fees.php">
                💰 Fees
            </a>

            <a href="complaints.php">
                📝 Complaints
            </a>

            <a href="visitors.php">
                👥 Visitors
            </a>

            <a href="reports.php">
                📊 Reports
            </a>

            <a href="../logout.php">
                🚪 Logout
            </a>

        </nav>

    </aside>


    <main class="main-content">

        <h1>Room Allocation</h1>

        <p>Allocate hostel rooms to students.</p>


        <?php if ($message != ""): ?>

            <div class="message <?php echo $message_type; ?>">

                <?php echo htmlspecialchars($message); ?>

            </div>

        <?php endif; ?>


        <!-- ALLOCATE ROOM -->

        <section class="dashboard-section">

            <h2>Allocate Room</h2>

            <form method="POST" class="allocation-form">

                <select name="student_id" required>

                    <option value="">
                        Select Student
                    </option>

                    <?php if ($students && $students->num_rows > 0): ?>

                        <?php while ($student = $students->fetch_assoc()): ?>

                            <option value="<?php echo $student["student_id"]; ?>">

                                <?php
                                echo htmlspecialchars(
                                    $student["student_name"]
                                );
                                ?>

                            </option>

                        <?php endwhile; ?>

                    <?php endif; ?>

                </select>


                <select name="room_id" required>

                    <option value="">
                        Select Room
                    </option>

                    <?php if ($rooms && $rooms->num_rows > 0): ?>

                        <?php while ($room = $rooms->fetch_assoc()): ?>

                            <option value="<?php echo $room["room_id"]; ?>">

                                <?php
                                echo htmlspecialchars(
                                    $room["room_number"]
                                );
                                ?>

                                -
                                <?php
                                echo htmlspecialchars(
                                    $room["room_type"]
                                );
                                ?>

                                -
                                <?php
                                echo $room["occupied"];
                                ?>
                                /
                                <?php
                                echo $room["capacity"];
                                ?>

                            </option>

                        <?php endwhile; ?>

                    <?php endif; ?>

                </select>


                <input
                    type="date"
                    name="allocation_date"
                    value="<?php echo date('Y-m-d'); ?>"
                    required
                >


                <button type="submit" class="btn">
                    Allocate Room
                </button>

            </form>

        </section>


        <!-- CURRENT ALLOCATIONS -->

        <section class="dashboard-section">

            <h2>Current Room Allocations</h2>

            <div class="table-container">

                <?php if ($allocations && $allocations->num_rows > 0): ?>

                    <table class="allocation-table">

                        <thead>

                            <tr>

                                <th>ID</th>
                                <th>Student</th>
                                <th>Room</th>
                                <th>Type</th>
                                <th>Allocation Date</th>
                                <th>Checkout Date</th>

                            </tr>

                        </thead>

                        <tbody>

                            <?php while ($allocation = $allocations->fetch_assoc()): ?>

                                <tr>

                                    <td>
                                        <?php
                                        echo $allocation["allocation_id"];
                                        ?>
                                    </td>

                                    <td>
                                        <?php
                                        echo htmlspecialchars(
                                            $allocation["student_name"]
                                        );
                                        ?>
                                    </td>

                                    <td>
                                        <?php
                                        echo htmlspecialchars(
                                            $allocation["room_number"]
                                        );
                                        ?>
                                    </td>

                                    <td>
                                        <?php
                                        echo htmlspecialchars(
                                            $allocation["room_type"]
                                        );
                                        ?>
                                    </td>

                                    <td>
                                        <?php
                                        echo $allocation["allocation_date"];
                                        ?>
                                    </td>

                                    <td>

                                        <?php

                                        if (
                                            $allocation["checkout_date"]
                                        ) {

                                            echo $allocation["checkout_date"];

                                        } else {

                                            echo "Not checked out";

                                        }

                                        ?>

                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        </tbody>

                    </table>

                <?php else: ?>

                    <p>No room allocations yet.</p>

                <?php endif; ?>

            </div>

        </section>

    </main>

</div>

</body>

</html>