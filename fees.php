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
   ADD FEE
========================= */

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["add_fee"])) {

    $student_id = (int) $_POST["student_id"];
    $amount = (float) $_POST["amount"];
    $due_date = $_POST["due_date"];

    if ($student_id <= 0 || $amount <= 0 || empty($due_date)) {

        $message = "Please fill all fields correctly.";
        $message_type = "error";

    } else {

        $stmt = $conn->prepare(
            "INSERT INTO fees
            (student_id, amount, due_date, status)
            VALUES (?, ?, ?, 'Pending')"
        );

        $stmt->bind_param(
            "ids",
            $student_id,
            $amount,
            $due_date
        );

        if ($stmt->execute()) {

            $message = "Fee added successfully!";
            $message_type = "success";

        } else {

            $message = "Error adding fee.";
            $message_type = "error";
        }
    }
}


/* =========================
   MARK FEE AS PAID
========================= */

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["mark_paid"])) {

    $fee_id = (int) $_POST["fee_id"];

    $stmt = $conn->prepare(
        "UPDATE fees
         SET status = 'Paid',
             payment_date = CURDATE()
         WHERE fee_id = ?"
    );

    $stmt->bind_param("i", $fee_id);

    if ($stmt->execute()) {

        $message = "Fee marked as paid!";
        $message_type = "success";

    } else {

        $message = "Error updating fee.";
        $message_type = "error";
    }
}


/* =========================
   GET STUDENTS
========================= */

$students = $conn->query(
    "SELECT student_id, student_name
     FROM students
     ORDER BY student_name"
);


/* =========================
   GET FEES
========================= */

$fees = $conn->query(
    "SELECT
        f.fee_id,
        s.student_name,
        f.amount,
        f.due_date,
        f.payment_date,
        f.status
     FROM fees f
     INNER JOIN students s
     ON f.student_id = s.student_id
     ORDER BY f.fee_id DESC"
);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Fee Management - Hostel Management System</title>

    <link rel="stylesheet" href="../css/style.css">

    <style>

        .fee-form {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }

        .fee-form select,
        .fee-form input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-sizing: border-box;
        }

        .fee-form button {
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

        .fee-table {
            width: 100%;
            border-collapse: collapse;
        }

        .fee-table th,
        .fee-table td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }

        .fee-table th {
            background: #f3f4f6;
        }

        .paid {
            color: green;
            font-weight: bold;
        }

        .pending {
            color: #d97706;
            font-weight: bold;
        }

        .paid-button {
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            background: #16a34a;
            color: white;
        }

        @media (max-width: 700px) {

            .fee-form {
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

            <a href="allocation.php">
                📋 Room Allocation
            </a>

            <a href="fees.php" class="active">
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

        <h1>Fee Management</h1>

        <p>Manage student hostel fees and payments.</p>


        <?php if ($message != ""): ?>

            <div class="message <?php echo $message_type; ?>">

                <?php echo htmlspecialchars($message); ?>

            </div>

        <?php endif; ?>


        <!-- ADD FEE -->

        <section class="dashboard-section">

            <h2>Add Hostel Fee</h2>

            <form method="POST" class="fee-form">

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


                <input
                    type="number"
                    name="amount"
                    placeholder="Fee Amount"
                    min="1"
                    step="0.01"
                    required
                >


                <input
                    type="date"
                    name="due_date"
                    required
                >


                <button
                    type="submit"
                    name="add_fee"
                    class="btn"
                >
                    Add Fee
                </button>

            </form>

        </section>


        <!-- FEES TABLE -->

        <section class="dashboard-section">

            <h2>Fee Records</h2>

            <div class="table-container">

                <?php if ($fees && $fees->num_rows > 0): ?>

                    <table class="fee-table">

                        <thead>

                            <tr>

                                <th>ID</th>
                                <th>Student</th>
                                <th>Amount</th>
                                <th>Due Date</th>
                                <th>Payment Date</th>
                                <th>Status</th>
                                <th>Action</th>

                            </tr>

                        </thead>

                        <tbody>

                            <?php while ($fee = $fees->fetch_assoc()): ?>

                                <tr>

                                    <td>
                                        <?php echo $fee["fee_id"]; ?>
                                    </td>

                                    <td>
                                        <?php
                                        echo htmlspecialchars(
                                            $fee["student_name"]
                                        );
                                        ?>
                                    </td>

                                    <td>
                                        ₹<?php
                                        echo number_format(
                                            $fee["amount"],
                                            2
                                        );
                                        ?>
                                    </td>

                                    <td>
                                        <?php echo $fee["due_date"]; ?>
                                    </td>

                                    <td>

                                        <?php

                                        if ($fee["payment_date"]) {

                                            echo $fee["payment_date"];

                                        } else {

                                            echo "Not paid";

                                        }

                                        ?>

                                    </td>

                                    <td>

                                        <?php if ($fee["status"] == "Paid"): ?>

                                            <span class="paid">
                                                Paid
                                            </span>

                                        <?php else: ?>

                                            <span class="pending">
                                                Pending
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                    <td>

                                        <?php if ($fee["status"] == "Pending"): ?>

                                            <form method="POST">

                                                <input
                                                    type="hidden"
                                                    name="fee_id"
                                                    value="<?php echo $fee["fee_id"]; ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    name="mark_paid"
                                                    class="paid-button"
                                                >
                                                    Mark Paid
                                                </button>

                                            </form>

                                        <?php else: ?>

                                            ✓ Completed

                                        <?php endif; ?>

                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        </tbody>

                    </table>

                <?php else: ?>

                    <p>No fee records yet.</p>

                <?php endif; ?>

            </div>

        </section>

    </main>

</div>

</body>

</html>