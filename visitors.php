```php
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
   ADD VISITOR
========================= */

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["add_visitor"])) {

    $student_id = (int) $_POST["student_id"];
    $visitor_name = trim($_POST["visitor_name"]);
    $visitor_phone = trim($_POST["visitor_phone"]);
    $purpose = trim($_POST["purpose"]);
    $visit_date = $_POST["visit_date"];
    $checkin_time = $_POST["checkin_time"];

    if (
        $student_id <= 0 ||
        empty($visitor_name) ||
        empty($visitor_phone) ||
        empty($purpose) ||
        empty($visit_date) ||
        empty($checkin_time)
    ) {

        $message = "Please fill in all fields correctly.";
        $message_type = "error";

    } else {

        $stmt = $conn->prepare(
            "INSERT INTO visitors
            (student_id, visitor_name, visitor_phone, purpose, visit_date, checkin_time)
            VALUES (?, ?, ?, ?, ?, ?)"
        );

        $stmt->bind_param(
            "isssss",
            $student_id,
            $visitor_name,
            $visitor_phone,
            $purpose,
            $visit_date,
            $checkin_time
        );

        if ($stmt->execute()) {

            $message = "Visitor added successfully!";
            $message_type = "success";

        } else {

            $message = "Error adding visitor.";
            $message_type = "error";
        }

        $stmt->close();
    }
}


/* =========================
   CHECKOUT VISITOR
========================= */

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["checkout"])) {

    $visitor_id = (int) $_POST["visitor_id"];

    if ($visitor_id > 0) {

        $stmt = $conn->prepare(
            "UPDATE visitors
             SET checkout_time = CURTIME()
             WHERE visitor_id = ?
             AND checkout_time IS NULL"
        );

        $stmt->bind_param("i", $visitor_id);

        if ($stmt->execute()) {

            if ($stmt->affected_rows > 0) {

                $message = "Visitor checked out successfully!";
                $message_type = "success";

            } else {

                $message = "Visitor has already checked out.";
                $message_type = "error";
            }

        } else {

            $message = "Error checking out visitor.";
            $message_type = "error";
        }

        $stmt->close();
    }
}


/* =========================
   GET STUDENTS
========================= */

$students = $conn->query(
    "SELECT
        student_id,
        student_name
     FROM students
     ORDER BY student_name"
);


/* =========================
   GET VISITORS
========================= */

$visitors = $conn->query(
    "SELECT
        v.visitor_id,
        s.student_name,
        v.visitor_name,
        v.visitor_phone,
        v.purpose,
        v.visit_date,
        v.checkin_time,
        v.checkout_time
     FROM visitors v
     INNER JOIN students s
     ON v.student_id = s.student_id
     ORDER BY v.visitor_id DESC"
);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Visitors - Hostel Management System</title>

    <link rel="stylesheet" href="../css/style.css">

    <style>

        /* =========================
           VISITOR FORM
        ========================= */

        .visitor-form {

            display: grid;

            grid-template-columns: repeat(2, 1fr);

            gap: 15px;

        }


        .visitor-form input,
        .visitor-form select {

            width: 100%;

            padding: 12px;

            border: 1px solid #ddd;

            border-radius: 8px;

            box-sizing: border-box;

        }


        .visitor-form button {

            width: fit-content;

            padding: 12px 25px;

            border: none;

            border-radius: 8px;

            cursor: pointer;

        }


        /* =========================
           MESSAGE
        ========================= */

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


        /* =========================
           TABLE
        ========================= */

        .table-container {

            overflow-x: auto;

        }


        .visitor-table {

            width: 100%;

            border-collapse: collapse;

        }


        .visitor-table th,
        .visitor-table td {

            padding: 12px;

            border-bottom: 1px solid #ddd;

            text-align: left;

            vertical-align: top;

        }


        .visitor-table th {

            background: #f3f4f6;

        }


        /* =========================
           STATUS
        ========================= */

        .inside {

            color: #d97706;

            font-weight: bold;

        }


        .checked-out {

            color: green;

            font-weight: bold;

        }


        /* =========================
           CHECKOUT BUTTON
        ========================= */

        .checkout-button {

            padding: 8px 14px;

            border: none;

            border-radius: 7px;

            background: #dc2626;

            color: white;

            font-weight: bold;

            cursor: pointer;

        }


        .checkout-button:hover {

            background: #b91c1c;

        }


        /* =========================
           MOBILE
        ========================= */

        @media (max-width: 700px) {

            .visitor-form {

                grid-template-columns: 1fr;

            }

            .visitor-table {

                min-width: 950px;

            }

        }

    </style>

</head>


<body>


<div class="dashboard-container">


    <!-- =========================
         SIDEBAR
    ========================= -->

    <aside class="sidebar">

        <h2>
            Hostel Management
        </h2>


        <p>
            Welcome,
            <?php
            echo htmlspecialchars($_SESSION["username"]);
            ?>
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


            <a href="fees.php">
                💰 Fees
            </a>


            <a href="complaints.php">
                📝 Complaints
            </a>


            <a href="visitors.php" class="active">
                👥 Visitors
            </a>


            <a href="reports.php">
                📊 Reports
            </a>


            <a href="../logout.php" class="logout">
                🚪 Logout
            </a>

        </nav>

    </aside>



    <!-- =========================
         MAIN CONTENT
    ========================= -->

    <main class="main-content">


        <h1>
            Visitor Management
        </h1>


        <p>
            Manage hostel visitors and visitor records.
        </p>



        <!-- MESSAGE -->

        <?php if ($message != ""): ?>

            <div class="message <?php echo $message_type; ?>">

                <?php
                echo htmlspecialchars($message);
                ?>

            </div>

        <?php endif; ?>



        <!-- =========================
             ADD VISITOR
        ========================= -->

        <section class="dashboard-section">


            <h2>
                Add Visitor
            </h2>


            <form
                method="POST"
                class="visitor-form"
            >


                <!-- STUDENT -->

                <select
                    name="student_id"
                    required
                >

                    <option value="">
                        Select Student
                    </option>


                    <?php if ($students && $students->num_rows > 0): ?>


                        <?php while ($student = $students->fetch_assoc()): ?>


                            <option
                                value="<?php echo $student["student_id"]; ?>"
                            >

                                <?php
                                echo htmlspecialchars(
                                    $student["student_name"]
                                );
                                ?>

                            </option>


                        <?php endwhile; ?>


                    <?php endif; ?>


                </select>



                <!-- VISITOR NAME -->

                <input
                    type="text"
                    name="visitor_name"
                    placeholder="Visitor Name"
                    required
                >



                <!-- PHONE -->

                <input
                    type="text"
                    name="visitor_phone"
                    placeholder="Visitor Phone Number"
                    required
                >



                <!-- PURPOSE -->

                <input
                    type="text"
                    name="purpose"
                    placeholder="Purpose of Visit"
                    required
                >



                <!-- DATE -->

                <input
                    type="date"
                    name="visit_date"
                    value="<?php echo date('Y-m-d'); ?>"
                    required
                >



                <!-- CHECK-IN -->

                <input
                    type="time"
                    name="checkin_time"
                    value="<?php echo date('H:i'); ?>"
                    required
                >



                <!-- BUTTON -->

                <button
                    type="submit"
                    name="add_visitor"
                    class="btn"
                >

                    Add Visitor

                </button>


            </form>


        </section>



        <!-- =========================
             VISITOR RECORDS
        ========================= -->

        <section class="dashboard-section">


            <h2>
                Visitor Records
            </h2>


            <div class="table-container">


                <?php if ($visitors && $visitors->num_rows > 0): ?>


                    <table class="visitor-table">


                        <thead>

                            <tr>

                                <th>
                                    ID
                                </th>

                                <th>
                                    Student
                                </th>

                                <th>
                                    Visitor
                                </th>

                                <th>
                                    Phone
                                </th>

                                <th>
                                    Purpose
                                </th>

                                <th>
                                    Date
                                </th>

                                <th>
                                    Check-in
                                </th>

                                <th>
                                    Check-out
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                            <?php while ($visitor = $visitors->fetch_assoc()): ?>


                                <tr>


                                    <!-- ID -->

                                    <td>

                                        <?php
                                        echo $visitor["visitor_id"];
                                        ?>

                                    </td>



                                    <!-- STUDENT -->

                                    <td>

                                        <?php
                                        echo htmlspecialchars(
                                            $visitor["student_name"]
                                        );
                                        ?>

                                    </td>



                                    <!-- VISITOR -->

                                    <td>

                                        <?php
                                        echo htmlspecialchars(
                                            $visitor["visitor_name"]
                                        );
                                        ?>

                                    </td>



                                    <!-- PHONE -->

                                    <td>

                                        <?php
                                        echo htmlspecialchars(
                                            $visitor["visitor_phone"]
                                        );
                                        ?>

                                    </td>



                                    <!-- PURPOSE -->

                                    <td>

                                        <?php
                                        echo htmlspecialchars(
                                            $visitor["purpose"]
                                        );
                                        ?>

                                    </td>



                                    <!-- DATE -->

                                    <td>

                                        <?php
                                        echo htmlspecialchars(
                                            $visitor["visit_date"]
                                        );
                                        ?>

                                    </td>



                                    <!-- CHECK-IN -->

                                    <td>

                                        <?php
                                        echo htmlspecialchars(
                                            $visitor["checkin_time"]
                                        );
                                        ?>

                                    </td>



                                    <!-- CHECK-OUT -->

                                    <td>

                                        <?php

                                        if ($visitor["checkout_time"]) {

                                            echo htmlspecialchars(
                                                $visitor["checkout_time"]
                                            );

                                        } else {

                                            echo "Still inside";

                                        }

                                        ?>

                                    </td>



                                    <!-- STATUS -->

                                    <td>


                                        <?php if ($visitor["checkout_time"]): ?>


                                            <span class="checked-out">

                                                Checked Out ✓

                                            </span>


                                        <?php else: ?>


                                            <span class="inside">

                                                Inside

                                            </span>


                                        <?php endif; ?>


                                    </td>



                                    <!-- ACTION -->

                                    <td>


                                        <?php if (!$visitor["checkout_time"]): ?>


                                            <form method="POST">


                                                <input
                                                    type="hidden"
                                                    name="visitor_id"
                                                    value="<?php
                                                    echo $visitor["visitor_id"];
                                                    ?>"
                                                >


                                                <button
                                                    type="submit"
                                                    name="checkout"
                                                    class="checkout-button"
                                                >

                                                    Check Out

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


                    <p>
                        No visitor records yet.
                    </p>


                <?php endif; ?>


            </div>


        </section>


    </main>


</div>


</body>

</html>
```