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
   MARK COMPLAINT AS RESOLVED
========================= */

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["resolve_complaint"])) {

    $complaint_id = (int) $_POST["complaint_id"];

    if ($complaint_id > 0) {

        $stmt = $conn->prepare(
            "UPDATE complaints
             SET status = 'Resolved'
             WHERE complaint_id = ?"
        );

        $stmt->bind_param("i", $complaint_id);

        if ($stmt->execute()) {

            $message = "Complaint marked as resolved!";
            $message_type = "success";

        } else {

            $message = "Error updating complaint.";
            $message_type = "error";
        }

        $stmt->close();

    } else {

        $message = "Invalid complaint.";
        $message_type = "error";
    }
}


/* =========================
   GET ALL COMPLAINTS
========================= */

$complaints = $conn->query(
    "SELECT
        c.complaint_id,
        s.student_name,
        s.email,
        s.phone,
        c.complaint_text,
        c.complaint_date,
        c.status
     FROM complaints c
     INNER JOIN students s
     ON c.student_id = s.student_id
     ORDER BY c.complaint_id DESC"
);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Complaints - Hostel Management System</title>

    <link rel="stylesheet" href="../css/style.css">

    <style>

        /* =========================
           PAGE
        ========================= */

        .page-header {
            margin-bottom: 25px;
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
            background: white;
            border-radius: 12px;
        }

        .complaint-table {
            width: 100%;
            border-collapse: collapse;
        }

        .complaint-table th,
        .complaint-table td {
            padding: 14px;
            border-bottom: 1px solid #ddd;
            text-align: left;
            vertical-align: top;
        }

        .complaint-table th {
            background: #f3f4f6;
        }


        /* =========================
           COMPLAINT TEXT
        ========================= */

        .complaint-text {
            min-width: 250px;
            max-width: 400px;
            white-space: normal;
            line-height: 1.5;
        }


        /* =========================
           STUDENT INFO
        ========================= */

        .student-info {
            min-width: 180px;
        }

        .student-info strong {
            display: block;
            margin-bottom: 5px;
        }

        .student-info small {
            color: #666;
        }


        /* =========================
           STATUS
        ========================= */

        .pending {
            color: #d97706;
            font-weight: bold;
        }

        .resolved {
            color: green;
            font-weight: bold;
        }


        /* =========================
           RESOLVE BUTTON
        ========================= */

        .resolve-button {
            padding: 8px 14px;
            border: none;
            border-radius: 7px;
            background: #16a34a;
            color: white;
            font-weight: bold;
            cursor: pointer;
        }

        .resolve-button:hover {
            background: #15803d;
        }


        /* =========================
           MOBILE
        ========================= */

        @media (max-width: 700px) {

            .complaint-table {
                min-width: 900px;
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


            <a href="complaints.php" class="active">
                📝 Complaints
            </a>


            <a href="visitors.php">
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


        <div class="page-header">

            <h1>
                Complaint Management
            </h1>

            <p>
                View and manage complaints submitted by hostel students.
            </p>

        </div>



        <!-- =========================
             MESSAGE
        ========================= -->

        <?php if ($message != ""): ?>

            <div class="message <?php echo $message_type; ?>">

                <?php
                echo htmlspecialchars($message);
                ?>

            </div>

        <?php endif; ?>



        <!-- =========================
             COMPLAINT LIST
        ========================= -->

        <section class="dashboard-section">

            <h2>
                All Complaints
            </h2>


            <div class="table-container">


                <?php if ($complaints && $complaints->num_rows > 0): ?>


                    <table class="complaint-table">


                        <thead>

                            <tr>

                                <th>
                                    ID
                                </th>

                                <th>
                                    Student
                                </th>

                                <th>
                                    Complaint
                                </th>

                                <th>
                                    Date
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


                            <?php while ($complaint = $complaints->fetch_assoc()): ?>


                                <tr>


                                    <!-- ID -->

                                    <td>

                                        <?php
                                        echo $complaint["complaint_id"];
                                        ?>

                                    </td>



                                    <!-- STUDENT -->

                                    <td>

                                        <div class="student-info">

                                            <strong>

                                                <?php
                                                echo htmlspecialchars(
                                                    $complaint["student_name"]
                                                );
                                                ?>

                                            </strong>


                                            <small>

                                                <?php
                                                echo htmlspecialchars(
                                                    $complaint["email"]
                                                );
                                                ?>

                                            </small>


                                            <br>


                                            <small>

                                                <?php
                                                echo htmlspecialchars(
                                                    $complaint["phone"]
                                                );
                                                ?>

                                            </small>

                                        </div>

                                    </td>



                                    <!-- COMPLAINT -->

                                    <td>

                                        <div class="complaint-text">

                                            <?php

                                            echo nl2br(
                                                htmlspecialchars(
                                                    $complaint["complaint_text"]
                                                )
                                            );

                                            ?>

                                        </div>

                                    </td>



                                    <!-- DATE -->

                                    <td>

                                        <?php
                                        echo htmlspecialchars(
                                            $complaint["complaint_date"]
                                        );
                                        ?>

                                    </td>



                                    <!-- STATUS -->

                                    <td>


                                        <?php if ($complaint["status"] == "Resolved"): ?>


                                            <span class="resolved">

                                                Resolved ✓

                                            </span>


                                        <?php else: ?>


                                            <span class="pending">

                                                Pending

                                            </span>


                                        <?php endif; ?>


                                    </td>



                                    <!-- ACTION -->

                                    <td>


                                        <?php if ($complaint["status"] == "Pending"): ?>


                                            <form method="POST">

                                                <input
                                                    type="hidden"
                                                    name="complaint_id"
                                                    value="<?php
                                                    echo $complaint["complaint_id"];
                                                    ?>"
                                                >


                                                <button
                                                    type="submit"
                                                    name="resolve_complaint"
                                                    class="resolve-button"
                                                >

                                                    Mark Resolved

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
                        No complaints submitted yet.
                    </p>


                <?php endif; ?>


            </div>

        </section>


    </main>


</div>


</body>

</html>
```