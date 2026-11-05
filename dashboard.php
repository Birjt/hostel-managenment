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
   GET STUDENT INFORMATION
========================= */

$stmt = $conn->prepare(
    "SELECT student_id, student_name, email, phone, course, year
     FROM students
     WHERE user_id = ?"
);

$stmt->bind_param("i", $user_id);
$stmt->execute();

$student_result = $stmt->get_result();

if ($student_result->num_rows == 0) {
    die("Student information not found.");
}

$student = $student_result->fetch_assoc();

$student_id = $student["student_id"];


/* =========================
   SUBMIT COMPLAINT
========================= */

if (
    $_SERVER["REQUEST_METHOD"] == "POST"
    && isset($_POST["submit_complaint"])
) {

    $complaint_text = trim($_POST["complaint_text"]);

    if ($complaint_text == "") {

        $message = "Please enter your complaint.";
        $message_type = "error";

    } else {

        $complaint_insert = $conn->prepare(
            "INSERT INTO complaints
            (student_id, complaint_text, complaint_date, status)
            VALUES (?, ?, CURDATE(), 'Pending')"
        );

        $complaint_insert->bind_param(
            "is",
            $student_id,
            $complaint_text
        );

        if ($complaint_insert->execute()) {

            $message = "Complaint submitted successfully!";
            $message_type = "success";

        } else {

            $message = "Error submitting complaint.";
            $message_type = "error";
        }
    }
}


/* =========================
   GET ROOM INFORMATION
========================= */

$room_stmt = $conn->prepare(
    "SELECT
        r.room_number,
        r.room_type,
        r.capacity,
        ra.allocation_date
     FROM room_allocations ra
     INNER JOIN rooms r
     ON ra.room_id = r.room_id
     WHERE ra.student_id = ?
     AND ra.checkout_date IS NULL
     LIMIT 1"
);

$room_stmt->bind_param("i", $student_id);
$room_stmt->execute();

$room_result = $room_stmt->get_result();

$room = null;

if ($room_result->num_rows > 0) {
    $room = $room_result->fetch_assoc();
}


/* =========================
   GET FEE INFORMATION
========================= */

$fee_stmt = $conn->prepare(
    "SELECT
        fee_id,
        amount,
        due_date,
        payment_date,
        status
     FROM fees
     WHERE student_id = ?
     ORDER BY fee_id DESC"
);

$fee_stmt->bind_param("i", $student_id);
$fee_stmt->execute();

$fees = $fee_stmt->get_result();

$fee_data = [];
$total_fee = 0;
$pending_fee = 0;
$paid_fee = 0;

while ($fee = $fees->fetch_assoc()) {

    $fee_data[] = $fee;

    $total_fee += $fee["amount"];

    if ($fee["status"] == "Paid") {
        $paid_fee += $fee["amount"];
    } else {
        $pending_fee += $fee["amount"];
    }
}


/* =========================
   GET COMPLAINT INFORMATION
========================= */

$complaint_stmt = $conn->prepare(
    "SELECT
        complaint_id,
        complaint_text,
        complaint_date,
        status
     FROM complaints
     WHERE student_id = ?
     ORDER BY complaint_id DESC"
);

$complaint_stmt->bind_param("i", $student_id);
$complaint_stmt->execute();

$complaints = $complaint_stmt->get_result();

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Student Dashboard | Hostel Management System</title>

<link rel="stylesheet" href="../css/style.css">


<style>

/* =====================================================
   STUDENT DASHBOARD
===================================================== */

.student-dashboard {
    min-height: 100vh;
    background: #f7f8fc;
}


/* =====================================================
   HEADER
===================================================== */

.student-header {
    width: 100%;
    background: white;

    padding: 18px 6%;

    display: flex;
    justify-content: space-between;
    align-items: center;

    box-shadow: 0 2px 15px rgba(0,0,0,0.06);

    position: sticky;
    top: 0;

    z-index: 1000;
}


.student-logo {
    font-size: 23px;
    font-weight: 800;
    color: #4f46e5;
}


.student-nav {
    display: flex;
    align-items: center;
    gap: 12px;
}


.student-nav a {
    text-decoration: none;

    color: #374151;

    font-weight: 600;

    padding: 9px 15px;

    border-radius: 8px;

    transition: 0.3s;
}


.student-nav a:hover {
    background: #eef2ff;
    color: #4f46e5;
}


.student-logout {
    background: #4f46e5;
    color: white !important;
}


.student-logout:hover {
    background: #4338ca !important;
    color: white !important;
}


/* =====================================================
   MAIN CONTENT
===================================================== */

.student-main {
    width: 100%;
    max-width: 1450px;

    margin: auto;

    padding: 40px 5%;
}


/* =====================================================
   WELCOME
===================================================== */

.student-welcome {
    margin-bottom: 30px;
}


.student-welcome h1 {
    font-size: 34px;

    color: #111827;

    margin-bottom: 6px;
}


.student-welcome p {
    color: #6b7280;

    font-size: 16px;
}


/* =====================================================
   MESSAGE
===================================================== */

.student-message {
    padding: 14px 18px;

    border-radius: 10px;

    margin-bottom: 25px;

    font-weight: 600;
}


.student-success {
    background: #d1fae5;
    color: #065f46;
}


.student-error {
    background: #fee2e2;
    color: #991b1b;
}


/* =====================================================
   TOP CARDS
===================================================== */

.student-top-cards {

    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 22px;

    margin-bottom: 25px;
}


.student-card {

    background: white;

    border-radius: 18px;

    padding: 25px;

    box-shadow:
        0 5px 20px rgba(0,0,0,0.05);

    border: 1px solid #eef0f4;

    transition: 0.3s;
}


.student-card:hover {

    transform: translateY(-3px);

    box-shadow:
        0 12px 30px rgba(0,0,0,0.08);
}


/* =====================================================
   CARD TITLE
===================================================== */

.student-card-title {

    display: flex;

    align-items: center;

    gap: 12px;

    margin-bottom: 20px;
}


.student-card-icon {

    width: 48px;
    height: 48px;

    display: flex;

    align-items: center;
    justify-content: center;

    background: #eef2ff;

    border-radius: 12px;

    font-size: 23px;
}


.student-card-title h2 {

    font-size: 19px;

    color: #111827;

    margin: 0;
}


/* =====================================================
   INFORMATION ROW
===================================================== */

.info-row {

    display: flex;

    justify-content: space-between;

    align-items: center;

    gap: 15px;

    padding: 10px 0;

    border-bottom:
        1px solid #f0f0f0;
}


.info-row:last-child {
    border-bottom: none;
}


.info-label {

    color: #6b7280;

    font-size: 14px;
}


.info-value {

    font-weight: 600;

    color: #1f2937;

    text-align: right;

    word-break: break-word;
}


/* =====================================================
   ROOM CARD
===================================================== */

.room-number {

    font-size: 36px;

    font-weight: 800;

    color: #4f46e5;

    margin-bottom: 3px;
}


.room-type {

    color: #6b7280;

    margin-bottom: 18px;
}


.status-badge {

    display: inline-block;

    padding: 6px 13px;

    border-radius: 20px;

    background: #d1fae5;

    color: #047857;

    font-size: 13px;

    font-weight: 700;
}


/* =====================================================
   FEE SUMMARY
===================================================== */

.fee-summary {

    font-size: 32px;

    font-weight: 800;

    color: #4f46e5;

    margin-bottom: 2px;
}


.fee-text {

    color: #6b7280;

    margin-bottom: 18px;
}


/* =====================================================
   WIDE SECTION
===================================================== */

.student-wide-card {

    background: white;

    border-radius: 18px;

    padding: 25px;

    margin-bottom: 25px;

    box-shadow:
        0 5px 20px rgba(0,0,0,0.05);

    border: 1px solid #eef0f4;
}


.student-wide-card h2 {

    margin-bottom: 20px;

    color: #111827;
}


/* =====================================================
   TABLES
===================================================== */

.fee-table,
.complaint-table {

    width: 100%;

    border-collapse: collapse;
}


.fee-table th,
.fee-table td,
.complaint-table th,
.complaint-table td {

    padding: 14px;

    border-bottom:
        1px solid #e5e7eb;

    text-align: left;
}


.fee-table th,
.complaint-table th {

    background: #f3f4f6;

    color: #374151;

    font-weight: 700;
}


.fee-table tr:hover,
.complaint-table tr:hover {

    background: #f9fafb;
}


.paid,
.resolved {

    color: #059669;

    font-weight: 700;
}


.pending,
.complaint-pending {

    color: #d97706;

    font-weight: 700;
}


/* =====================================================
   COMPLAINT SECTION
===================================================== */

.complaint-grid {

    display: grid;

    grid-template-columns:
        0.8fr 1.5fr;

    gap: 25px;
}


.complaint-description {

    color: #6b7280;

    margin-bottom: 18px;
}


.complaint-form textarea {

    width: 100%;

    min-height: 135px;

    padding: 14px;

    border:
        1px solid #d1d5db;

    border-radius: 10px;

    resize: vertical;

    font-family: inherit;

    font-size: 15px;

    outline: none;
}


.complaint-form textarea:focus {

    border-color: #4f46e5;

    box-shadow:
        0 0 0 3px
        rgba(79,70,229,0.1);
}


.complaint-button {

    margin-top: 14px;

    padding: 12px 22px;

    border: none;

    border-radius: 9px;

    background: #4f46e5;

    color: white;

    font-size: 15px;

    font-weight: 700;

    cursor: pointer;

    transition: 0.3s;
}


.complaint-button:hover {

    background: #4338ca;

    transform: translateY(-1px);
}


/* =====================================================
   MOBILE / TABLET
===================================================== */

@media (max-width: 1100px) {

    .student-top-cards {

        grid-template-columns:
            repeat(2, 1fr);
    }


    .complaint-grid {

        grid-template-columns:
            1fr;
    }

}


@media (max-width: 700px) {

    .student-header {

        padding:
            15px 5%;

    }


    .student-logo {

        font-size: 18px;

    }


    .student-nav {

        gap: 5px;

    }


    .student-nav a {

        padding:
            7px 9px;

        font-size: 13px;

    }


    .student-main {

        padding:
            25px 4%;

    }


    .student-welcome h1 {

        font-size: 27px;

    }


    .student-top-cards {

        grid-template-columns:
            1fr;

    }


    .fee-table,
    .complaint-table {

        display: block;

        overflow-x: auto;

        white-space: nowrap;

    }

}


</style>

</head>


<body>


<div class="student-dashboard">


    <!-- =================================================
         HEADER
    ================================================== -->

    <header class="student-header">


        <div class="student-logo">

            🏠 Hostel Management System

        </div>


        <nav class="student-nav">


            <a href="../index.php">

                Home

            </a>


            <a
                href="../logout.php"
                class="student-logout"
            >

                Logout

            </a>


        </nav>


    </header>



    <!-- =================================================
         MAIN CONTENT
    ================================================== -->

    <main class="student-main">


        <!-- WELCOME -->

        <div class="student-welcome">

            <h1>

                Welcome,
                <?php
                echo htmlspecialchars(
                    $_SESSION["username"]
                );
                ?>
                👋

            </h1>


            <p>

                Here's an overview of your hostel information.

            </p>

        </div>



        <!-- MESSAGE -->

        <?php if ($message != ""): ?>

            <div
                class="student-message
                <?php
                echo
                    $message_type == "success"
                    ? "student-success"
                    : "student-error";
                ?>"
            >

                <?php

                echo htmlspecialchars(
                    $message
                );

                ?>

            </div>

        <?php endif; ?>



        <!-- =================================================
             TOP THREE CARDS
        ================================================== -->

        <div class="student-top-cards">


            <!-- ==============================
                 MY INFORMATION
            =============================== -->

            <div class="student-card">


                <div class="student-card-title">


                    <div class="student-card-icon">

                        👤

                    </div>


                    <h2>

                        My Information

                    </h2>


                </div>



                <div class="info-row">

                    <span class="info-label">
                        Name
                    </span>

                    <span class="info-value">

                        <?php
                        echo htmlspecialchars(
                            $student["student_name"]
                        );
                        ?>

                    </span>

                </div>



                <div class="info-row">

                    <span class="info-label">
                        Email
                    </span>

                    <span class="info-value">

                        <?php
                        echo htmlspecialchars(
                            $student["email"]
                        );
                        ?>

                    </span>

                </div>



                <div class="info-row">

                    <span class="info-label">
                        Phone
                    </span>

                    <span class="info-value">

                        <?php
                        echo htmlspecialchars(
                            $student["phone"]
                        );
                        ?>

                    </span>

                </div>



                <div class="info-row">

                    <span class="info-label">
                        Course
                    </span>

                    <span class="info-value">

                        <?php
                        echo htmlspecialchars(
                            $student["course"]
                        );
                        ?>

                    </span>

                </div>



                <div class="info-row">

                    <span class="info-label">
                        Year
                    </span>

                    <span class="info-value">

                        <?php
                        echo htmlspecialchars(
                            $student["year"]
                        );
                        ?>

                    </span>

                </div>


            </div>



            <!-- ==============================
                 MY ROOM
            =============================== -->

            <div class="student-card">


                <div class="student-card-title">


                    <div class="student-card-icon">

                        🛏️

                    </div>


                    <h2>

                        My Room

                    </h2>


                </div>



                <?php if ($room): ?>


                    <div class="room-number">

                        <?php
                        echo htmlspecialchars(
                            $room["room_number"]
                        );
                        ?>

                    </div>


                    <div class="room-type">

                        <?php
                        echo htmlspecialchars(
                            $room["room_type"]
                        );
                        ?>

                        Room

                    </div>



                    <div class="info-row">

                        <span class="info-label">

                            Capacity

                        </span>


                        <span class="info-value">

                            <?php
                            echo htmlspecialchars(
                                $room["capacity"]
                            );
                            ?>

                        </span>

                    </div>



                    <div class="info-row">

                        <span class="info-label">

                            Allocation Date

                        </span>


                        <span class="info-value">

                            <?php
                            echo htmlspecialchars(
                                $room["allocation_date"]
                            );
                            ?>

                        </span>

                    </div>



                    <br>


                    <span class="status-badge">

                        ✓ Allocated

                    </span>


                <?php else: ?>


                    <p>

                        No room has been allocated
                        to you yet.

                    </p>


                <?php endif; ?>


            </div>



            <!-- ==============================
                 MY FEES SUMMARY
            =============================== -->

            <div class="student-card">


                <div class="student-card-title">


                    <div class="student-card-icon">

                        💰

                    </div>


                    <h2>

                        My Fees

                    </h2>


                </div>



                <?php if (!empty($fee_data)): ?>


                    <div class="fee-summary">

                        ₹<?php
                        echo number_format(
                            $pending_fee,
                            2
                        );
                        ?>

                    </div>


                    <div class="fee-text">

                        Pending Fee

                    </div>



                    <div class="info-row">

                        <span class="info-label">

                            Total Records

                        </span>


                        <span class="info-value">

                            <?php
                            echo count($fee_data);
                            ?>

                        </span>

                    </div>



                    <div class="info-row">

                        <span class="info-label">

                            Total Paid

                        </span>


                        <span class="info-value">

                            ₹<?php
                            echo number_format(
                                $paid_fee,
                                2
                            );
                            ?>

                        </span>

                    </div>



                    <div class="info-row">

                        <span class="info-label">

                            Total Amount

                        </span>


                        <span class="info-value">

                            ₹<?php
                            echo number_format(
                                $total_fee,
                                2
                            );
                            ?>

                        </span>

                    </div>


                <?php else: ?>


                    <p>

                        No fee records found.

                    </p>


                <?php endif; ?>


            </div>


        </div>



        <!-- =================================================
             FEE HISTORY
        ================================================== -->

        <div class="student-wide-card">


            <h2>

                💰 Fee History

            </h2>


            <?php if (!empty($fee_data)): ?>


                <div
                    style="
                    overflow-x:auto;
                    "
                >


                    <table class="fee-table">


                        <thead>

                            <tr>

                                <th>
                                    Fee ID
                                </th>

                                <th>
                                    Amount
                                </th>

                                <th>
                                    Due Date
                                </th>

                                <th>
                                    Payment Date
                                </th>

                                <th>
                                    Status
                                </th>

                            </tr>

                        </thead>



                        <tbody>


                        <?php foreach ($fee_data as $fee): ?>


                            <tr>


                                <td>

                                    <?php
                                    echo $fee["fee_id"];
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

                                    <?php
                                    echo htmlspecialchars(
                                        $fee["due_date"]
                                    );
                                    ?>

                                </td>


                                <td>


                                    <?php
                                    if (
                                        $fee["payment_date"]
                                    ) {

                                        echo htmlspecialchars(
                                            $fee["payment_date"]
                                        );

                                    } else {

                                        echo "Not paid";

                                    }
                                    ?>


                                </td>


                                <td>


                                    <?php
                                    if (
                                        $fee["status"]
                                        == "Paid"
                                    ):
                                    ?>

                                        <span class="paid">

                                            Paid ✓

                                        </span>


                                    <?php
                                    else:
                                    ?>

                                        <span class="pending">

                                            Pending

                                        </span>


                                    <?php endif; ?>


                                </td>


                            </tr>


                        <?php endforeach; ?>


                        </tbody>


                    </table>


                </div>


            <?php else: ?>


                <p>

                    No fee records found.

                </p>


            <?php endif; ?>


        </div>



        <!-- =================================================
             COMPLAINT SECTION
        ================================================== -->

        <div class="complaint-grid">


            <!-- ==============================
                 SUBMIT COMPLAINT
            =============================== -->

            <div class="student-card">


                <div class="student-card-title">


                    <div class="student-card-icon">

                        📝

                    </div>


                    <h2>

                        Submit Complaint

                    </h2>


                </div>



                <p class="complaint-description">

                    Have a problem in the hostel?
                    Submit your complaint below.

                </p>



                <form
                    method="POST"
                    class="complaint-form"
                >


                    <textarea
                        name="complaint_text"
                        placeholder="Write your complaint here..."
                        required
                    ></textarea>



                    <button
                        type="submit"
                        name="submit_complaint"
                        class="complaint-button"
                    >

                        Submit Complaint

                    </button>


                </form>


            </div>



            <!-- ==============================
                 COMPLAINT HISTORY
            =============================== -->

            <div class="student-card">


                <div class="student-card-title">


                    <div class="student-card-icon">

                        📋

                    </div>


                    <h2>

                        Complaint History

                    </h2>


                </div>



                <?php
                if (
                    $complaints
                    && $complaints->num_rows > 0
                ):
                ?>


                    <div
                        style="
                        overflow-x:auto;
                        "
                    >


                        <table
                            class="complaint-table"
                        >


                            <thead>

                                <tr>

                                    <th>
                                        ID
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

                                </tr>

                            </thead>



                            <tbody>


                            <?php
                            while (
                                $complaint =
                                $complaints->fetch_assoc()
                            ):
                            ?>


                                <tr>


                                    <td>

                                        <?php
                                        echo
                                            $complaint[
                                                "complaint_id"
                                            ];
                                        ?>

                                    </td>


                                    <td>

                                        <?php

                                        echo nl2br(
                                            htmlspecialchars(
                                                $complaint[
                                                    "complaint_text"
                                                ]
                                            )
                                        );

                                        ?>

                                    </td>


                                    <td>

                                        <?php

                                        echo htmlspecialchars(
                                            $complaint[
                                                "complaint_date"
                                            ]
                                        );

                                        ?>

                                    </td>


                                    <td>


                                        <?php
                                        if (
                                            $complaint[
                                                "status"
                                            ]
                                            == "Resolved"
                                        ):
                                        ?>


                                            <span class="resolved">

                                                Resolved ✓

                                            </span>


                                        <?php
                                        else:
                                        ?>


                                            <span
                                                class="complaint-pending"
                                            >

                                                Pending

                                            </span>


                                        <?php endif; ?>


                                    </td>


                                </tr>


                            <?php endwhile; ?>


                            </tbody>


                        </table>


                    </div>


                <?php else: ?>


                    <p>

                        You have not submitted
                        any complaints yet.

                    </p>


                <?php endif; ?>


            </div>


        </div>


    </main>


</div>


</body>

</html>