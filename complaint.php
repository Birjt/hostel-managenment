<?php
session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "student") {
    header("Location: ../login.php");
    exit();
}

include "../config/database.php";

$user_id = $_SESSION["user_id"];

$message = "";
$message_type = "";

/* =========================
   GET STUDENT ID
========================= */

$stmt = $conn->prepare(
    "SELECT student_id
     FROM students
     WHERE user_id = ?"
);

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Student information not found.");
}

$student = $result->fetch_assoc();

$student_id = $student["student_id"];


/* =========================
   SUBMIT COMPLAINT
========================= */

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $complaint_text = trim($_POST["complaint_text"]);

    if ($complaint_text == "") {

        $message = "Please enter your complaint.";
        $message_type = "error";

    } else {

        $stmt = $conn->prepare(
            "INSERT INTO complaints
            (student_id, complaint_text, complaint_date, status)
            VALUES (?, ?, CURDATE(), 'Pending')"
        );

        $stmt->bind_param(
            "is",
            $student_id,
            $complaint_text
        );

        if ($stmt->execute()) {

            $message = "Complaint submitted successfully!";
            $message_type = "success";

        } else {

            $message = "Error submitting complaint.";
            $message_type = "error";
        }
    }
}


/* =========================
   GET MY COMPLAINTS
========================= */

$complaints_stmt = $conn->prepare(
    "SELECT
        complaint_id,
        complaint_text,
        complaint_date,
        status
     FROM complaints
     WHERE student_id = ?
     ORDER BY complaint_id DESC"
);

$complaints_stmt->bind_param("i", $student_id);
$complaints_stmt->execute();

$complaints = $complaints_stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Complaints - Hostel Management System</title>

    <link rel="stylesheet" href="../css/style.css">

    <style>

        .complaint-section {
            background: white;
            padding: 25px;
            margin: 25px 0;
            border-radius: 12px;
        }

        textarea {
            width: 100%;
            min-height: 120px;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-sizing: border-box;
            resize: vertical;
        }

        .submit-button {
            margin-top: 15px;
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

        .complaint-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .complaint-table th,
        .complaint-table td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }

        .complaint-table th {
            background: #f3f4f6;
        }

        .pending {
            color: #d97706;
            font-weight: bold;
        }

        .resolved {
            color: green;
            font-weight: bold;
        }

    </style>

</head>

<body>

<header>

    <div class="logo">
        Hostel Management System
    </div>

    <nav>

        <a href="dashboard.php">
            Dashboard
        </a>

        <a href="../index.php">
            Home
        </a>

        <a href="../logout.php">
            Logout
        </a>

    </nav>

</header>


<section class="hero">

    <div class="login-box">

        <h1>📝 My Complaints</h1>

        <?php if ($message != ""): ?>

            <div class="message <?php echo $message_type; ?>">

                <?php echo htmlspecialchars($message); ?>

            </div>

        <?php endif; ?>


        <!-- SUBMIT COMPLAINT -->

        <div class="complaint-section">

            <h2>Submit a Complaint</h2>

            <form method="POST">

                <textarea
                    name="complaint_text"
                    placeholder="Write your complaint here..."
                    required
                ></textarea>

                <button
                    type="submit"
                    class="submit-button"
                >
                    Submit Complaint
                </button>

            </form>

        </div>


        <!-- MY COMPLAINTS -->

        <div class="complaint-section">

            <h2>My Complaint History</h2>

            <?php if ($complaints->num_rows > 0): ?>

                <table class="complaint-table">

                    <thead>

                        <tr>

                            <th>ID</th>
                            <th>Complaint</th>
                            <th>Date</th>
                            <th>Status</th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php while ($complaint = $complaints->fetch_assoc()): ?>

                            <tr>

                                <td>
                                    <?php echo $complaint["complaint_id"]; ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $complaint["complaint_text"]
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo $complaint["complaint_date"];
                                    ?>
                                </td>

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

                            </tr>

                        <?php endwhile; ?>

                    </tbody>

                </table>

            <?php else: ?>

                <p>
                    You have not submitted any complaints yet.
                </p>

            <?php endif; ?>

        </div>

    </div>

</section>

</body>

</html>