<?php

session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "admin") {
    header("Location: ../login.php");
    exit();
}

include "../config/database.php";

$message = "";
$message_type = "";

/* ADD ROOM */

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $room_number = trim($_POST["room_number"]);
    $room_type = trim($_POST["room_type"]);
    $capacity = (int) $_POST["capacity"];
    $status = $_POST["status"];

    if (
        empty($room_number) ||
        empty($room_type) ||
        $capacity <= 0 ||
        empty($status)
    ) {

        $message = "Please fill in all fields correctly.";
        $message_type = "error";

    } else {

        /* Check if room number already exists */

        $check = $conn->prepare(
            "SELECT room_id FROM rooms WHERE room_number = ?"
        );

        $check->bind_param("s", $room_number);
        $check->execute();

        $check_result = $check->get_result();

        if ($check_result->num_rows > 0) {

            $message = "Room number already exists.";
            $message_type = "error";

        } else {

            $stmt = $conn->prepare(
                "INSERT INTO rooms
                (room_number, room_type, capacity, status)
                VALUES (?, ?, ?, ?)"
            );

            $stmt->bind_param(
                "ssis",
                $room_number,
                $room_type,
                $capacity,
                $status
            );

            if ($stmt->execute()) {

                $message = "Room added successfully!";
                $message_type = "success";

            } else {

                $message = "Error adding room.";
                $message_type = "error";
            }
        }
    }
}


/* GET ROOMS */

$result = $conn->query(
    "SELECT * FROM rooms ORDER BY room_id DESC"
);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Rooms - Hostel Management System</title>

    <link rel="stylesheet" href="../css/style.css">

    <style>

        .room-form {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .room-form input,
        .room-form select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-sizing: border-box;
        }

        .room-form button {
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

        .room-table {
            width: 100%;
            border-collapse: collapse;
        }

        .room-table th,
        .room-table td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }

        .room-table th {
            background: #f3f4f6;
        }

        @media (max-width: 700px) {

            .room-form {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>

<body>

<div class="dashboard-container">

    <!-- SIDEBAR -->

    <aside class="sidebar">

        <h2>Hostel Management</h2>

        <p>
            Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?>
        </p>

        <nav>

            <a href="dashboard.php">
                🏠 Dashboard
            </a>

            <a href="students.php">
                👨‍🎓 Students
            </a>

            <a href="rooms.php" class="active">
                🛏️ Rooms
            </a>

            <a href="allocation.php">
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


    <!-- MAIN CONTENT -->

    <main class="main-content">

        <h1>Room Management</h1>

        <p>Manage hostel rooms and their availability.</p>


        <?php if ($message != ""): ?>

            <div class="message <?php echo $message_type; ?>">

                <?php echo htmlspecialchars($message); ?>

            </div>

        <?php endif; ?>


        <!-- ADD ROOM -->

        <section class="dashboard-section">

            <h2>Add New Room</h2>

            <form method="POST" class="room-form">

                <input
                    type="text"
                    name="room_number"
                    placeholder="Room Number"
                    required
                >

                <input
                    type="text"
                    name="room_type"
                    placeholder="Room Type (Single/Double/Triple)"
                    required
                >

                <input
                    type="number"
                    name="capacity"
                    placeholder="Capacity"
                    min="1"
                    required
                >

                <select name="status" required>

                    <option value="">Select Status</option>

                    <option value="Available">
                        Available
                    </option>

                    <option value="Full">
                        Full
                    </option>

                    <option value="Maintenance">
                        Maintenance
                    </option>

                </select>

                <button type="submit" class="btn">
                    Add Room
                </button>

            </form>

        </section>


        <!-- ROOM LIST -->

        <section class="dashboard-section">

            <h2>All Rooms</h2>

            <div class="table-container">

                <?php if ($result && $result->num_rows > 0): ?>

                    <table class="room-table">

                        <thead>

                            <tr>

                                <th>ID</th>
                                <th>Room Number</th>
                                <th>Room Type</th>
                                <th>Capacity</th>
                                <th>Status</th>

                            </tr>

                        </thead>

                        <tbody>

                            <?php while ($room = $result->fetch_assoc()): ?>

                                <tr>

                                    <td>
                                        <?php echo $room["room_id"]; ?>
                                    </td>

                                    <td>
                                        <?php echo htmlspecialchars($room["room_number"]); ?>
                                    </td>

                                    <td>
                                        <?php echo htmlspecialchars($room["room_type"]); ?>
                                    </td>

                                    <td>
                                        <?php echo $room["capacity"]; ?>
                                    </td>

                                    <td>
                                        <?php echo htmlspecialchars($room["status"]); ?>
                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        </tbody>

                    </table>

                <?php else: ?>

                    <p>No rooms added yet.</p>

                <?php endif; ?>

            </div>

        </section>

    </main>

</div>

</body>

</html>