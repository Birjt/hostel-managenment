<?php

session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "admin") {
    header("Location: ../login.php");
    exit();
}

include "../config/database.php";


/* =====================================================
   SELECT MONTH AND YEAR
===================================================== */

$selected_month = isset($_GET["month"])
    ? intval($_GET["month"])
    : date("m");

$selected_year = isset($_GET["year"])
    ? intval($_GET["year"])
    : date("Y");

if ($selected_month < 1 || $selected_month > 12) {
    $selected_month = date("m");
}


/* =====================================================
   BASIC STATISTICS
===================================================== */

$total_students = 0;
$total_rooms = 0;
$total_capacity = 0;
$occupied_beds = 0;
$vacant_beds = 0;

$result = $conn->query(
    "SELECT COUNT(*) AS total FROM students"
);

if ($result) {
    $total_students = $result->fetch_assoc()["total"];
}


$result = $conn->query(
    "SELECT 
        COUNT(*) AS total_rooms,
        COALESCE(SUM(capacity),0) AS total_capacity
     FROM rooms"
);

if ($result) {

    $room_stats = $result->fetch_assoc();

    $total_rooms = $room_stats["total_rooms"];
    $total_capacity = $room_stats["total_capacity"];
}


/* =====================================================
   OCCUPIED BEDS
===================================================== */

$result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM room_allocations
     WHERE checkout_date IS NULL"
);

if ($result) {
    $occupied_beds = $result->fetch_assoc()["total"];
}

$vacant_beds = max(0, $total_capacity - $occupied_beds);


/* =====================================================
   OCCUPANCY RATE
===================================================== */

$occupancy_rate = 0;

if ($total_capacity > 0) {
    $occupancy_rate =
        ($occupied_beds / $total_capacity) * 100;
}


/* =====================================================
   FEE STATISTICS
===================================================== */

$total_fees = 0;
$paid_fees = 0;
$pending_fees = 0;


/* Total fees */

$result = $conn->query(
    "SELECT COALESCE(SUM(amount),0) AS total
     FROM fees"
);

if ($result) {
    $total_fees = $result->fetch_assoc()["total"];
}


/* Paid fees */

$result = $conn->query(
    "SELECT COALESCE(SUM(amount),0) AS total
     FROM fees
     WHERE status = 'Paid'"
);

if ($result) {
    $paid_fees = $result->fetch_assoc()["total"];
}


/* Pending fees */

$result = $conn->query(
    "SELECT COALESCE(SUM(amount),0) AS total
     FROM fees
     WHERE status = 'Pending'"
);

if ($result) {
    $pending_fees = $result->fetch_assoc()["total"];
}


/* =====================================================
   EXPECTED MONTHLY REVENUE
===================================================== */

$expected_monthly_revenue = 0;

$result = $conn->query(
    "SELECT COALESCE(SUM(room_fee),0) AS total
     FROM rooms"
);

if ($result) {

    $room_fee_total =
        $result->fetch_assoc()["total"];

    /*
       This is an estimated expected revenue based
       on all room master fees.

       It is useful for showing potential revenue.
    */

    $expected_monthly_revenue =
        $room_fee_total;
}


/* =====================================================
   CURRENT VACANCY LOSS
===================================================== */

/*
   Calculate the monthly revenue lost because of
   currently vacant beds.

   Average room fee is used as an estimate.
*/

$average_room_fee = 0;

if ($total_rooms > 0) {

    $result = $conn->query(
        "SELECT COALESCE(AVG(room_fee),0) AS average_fee
         FROM rooms"
    );

    if ($result) {
        $average_room_fee =
            $result->fetch_assoc()["average_fee"];
    }
}

$current_vacancy_loss =
    $vacant_beds * $average_room_fee;


/* =====================================================
   EXPENSE STATISTICS
===================================================== */

$total_expenses = 0;

$result = $conn->query(
    "SELECT COALESCE(SUM(amount),0) AS total
     FROM expenses"
);

if ($result) {
    $total_expenses =
        $result->fetch_assoc()["total"];
}


/* =====================================================
   SELECTED MONTH EXPENSES
===================================================== */

$monthly_expenses = 0;

$stmt = $conn->prepare(
    "SELECT COALESCE(SUM(amount),0)
     FROM expenses
     WHERE MONTH(expense_date) = ?
     AND YEAR(expense_date) = ?"
);

$stmt->bind_param(
    "ii",
    $selected_month,
    $selected_year
);

$stmt->execute();

$stmt->bind_result($monthly_expenses);

$stmt->fetch();

$stmt->close();


/* =====================================================
   SELECTED MONTH PAID FEES
===================================================== */

/*
   We check which date column exists in the fees table.
   This makes the report work with common column names.
*/

$fee_date_column = null;

$possible_date_columns = [
    "payment_date",
    "fee_date",
    "date",
    "created_at"
];

foreach ($possible_date_columns as $column) {

    $check = $conn->query(
        "SHOW COLUMNS FROM fees LIKE '$column'"
    );

    if ($check && $check->num_rows > 0) {

        $fee_date_column = $column;
        break;
    }
}


$monthly_paid_fees = 0;


/*
   If fees has a date column, calculate monthly
   collection.
*/

if ($fee_date_column !== null) {

    $sql =
        "SELECT COALESCE(SUM(amount),0)
         FROM fees
         WHERE status = 'Paid'
         AND MONTH($fee_date_column) = ?
         AND YEAR($fee_date_column) = ?";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "ii",
        $selected_month,
        $selected_year
    );

    $stmt->execute();

    $stmt->bind_result($monthly_paid_fees);

    $stmt->fetch();

    $stmt->close();

} else {

    /*
       If there is no fee date column,
       show total paid fees instead.
    */

    $monthly_paid_fees = $paid_fees;
}


/* =====================================================
   MONTHLY PROFIT
===================================================== */

$monthly_profit =
    $monthly_paid_fees - $monthly_expenses;


/* =====================================================
   YEARLY PAID FEES
===================================================== */

$yearly_paid_fees = 0;

if ($fee_date_column !== null) {

    $sql =
        "SELECT COALESCE(SUM(amount),0)
         FROM fees
         WHERE status = 'Paid'
         AND YEAR($fee_date_column) = ?";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "i",
        $selected_year
    );

    $stmt->execute();

    $stmt->bind_result($yearly_paid_fees);

    $stmt->fetch();

    $stmt->close();

} else {

    $yearly_paid_fees = $paid_fees;
}


/* =====================================================
   YEARLY EXPENSES
===================================================== */

$yearly_expenses = 0;

$stmt = $conn->prepare(
    "SELECT COALESCE(SUM(amount),0)
     FROM expenses
     WHERE YEAR(expense_date) = ?"
);

$stmt->bind_param(
    "i",
    $selected_year
);

$stmt->execute();

$stmt->bind_result($yearly_expenses);

$stmt->fetch();

$stmt->close();


$yearly_profit =
    $yearly_paid_fees - $yearly_expenses;


/* =====================================================
   COMPLAINT STATISTICS
===================================================== */

$total_complaints = 0;
$pending_complaints = 0;
$resolved_complaints = 0;


$result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM complaints"
);

if ($result) {
    $total_complaints =
        $result->fetch_assoc()["total"];
}


$result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM complaints
     WHERE status = 'Pending'"
);

if ($result) {
    $pending_complaints =
        $result->fetch_assoc()["total"];
}


$result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM complaints
     WHERE status = 'Resolved'"
);

if ($result) {
    $resolved_complaints =
        $result->fetch_assoc()["total"];
}


/* =====================================================
   CURRENT ALLOCATIONS
===================================================== */

$allocations = $conn->query(
    "SELECT
        s.student_name,
        r.room_number,
        r.room_type,
        ra.allocation_date
     FROM room_allocations ra

     INNER JOIN students s
     ON ra.student_id = s.student_id

     INNER JOIN rooms r
     ON ra.room_id = r.room_id

     WHERE ra.checkout_date IS NULL

     ORDER BY ra.allocation_id DESC"
);


/* =====================================================
   ROOM OCCUPANCY
===================================================== */

$room_occupancy = $conn->query(
    "SELECT
        r.room_number,
        r.room_type,
        r.capacity,
        r.room_fee,
        COUNT(ra.allocation_id) AS occupied

     FROM rooms r

     LEFT JOIN room_allocations ra
     ON r.room_id = ra.room_id
     AND ra.checkout_date IS NULL

     GROUP BY
        r.room_id,
        r.room_number,
        r.room_type,
        r.capacity,
        r.room_fee

     ORDER BY r.room_number"
);


/* =====================================================
   MONTHLY YEARLY TABLE
===================================================== */

$monthly_report = [];

$month_names = [
    1 => "January",
    2 => "February",
    3 => "March",
    4 => "April",
    5 => "May",
    6 => "June",
    7 => "July",
    8 => "August",
    9 => "September",
    10 => "October",
    11 => "November",
    12 => "December"
];


for ($m = 1; $m <= 12; $m++) {

    $month_paid = 0;
    $month_expense = 0;

    /* Fees */

    if ($fee_date_column !== null) {

        $sql =
            "SELECT COALESCE(SUM(amount),0)
             FROM fees
             WHERE status = 'Paid'
             AND MONTH($fee_date_column) = ?
             AND YEAR($fee_date_column) = ?";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param(
            "ii",
            $m,
            $selected_year
        );

        $stmt->execute();

        $stmt->bind_result($month_paid);

        $stmt->fetch();

        $stmt->close();
    }


    /* Expenses */

    $stmt = $conn->prepare(
        "SELECT COALESCE(SUM(amount),0)
         FROM expenses
         WHERE MONTH(expense_date) = ?
         AND YEAR(expense_date) = ?"
    );

    $stmt->bind_param(
        "ii",
        $m,
        $selected_year
    );

    $stmt->execute();

    $stmt->bind_result($month_expense);

    $stmt->fetch();

    $stmt->close();


    $month_profit =
        $month_paid - $month_expense;


    $monthly_report[] = [
        "month" => $month_names[$m],
        "paid" => $month_paid,
        "expense" => $month_expense,
        "profit" => $month_profit
    ];
}


/* =====================================================
   STUDENT STATEMENT
===================================================== */

$student_statement = null;
$student_fees = null;

if (isset($_GET["student_id"]) && $_GET["student_id"] != "") {

    $student_id =
        intval($_GET["student_id"]);


    $stmt = $conn->prepare(
        "SELECT
            s.student_id,
            s.student_name,
            r.room_number,
            r.room_type,
            r.room_fee

         FROM students s

         LEFT JOIN room_allocations ra
         ON s.student_id = ra.student_id
         AND ra.checkout_date IS NULL

         LEFT JOIN rooms r
         ON ra.room_id = r.room_id

         WHERE s.student_id = ?

         LIMIT 1"
    );

    $stmt->bind_param(
        "i",
        $student_id
    );

    $stmt->execute();

    $student_statement =
        $stmt->get_result()->fetch_assoc();

    $stmt->close();


    /*
       Find student fees.
    */

    $fee_student_column = null;

    $possible_student_columns = [
        "student_id",
        "studentId"
    ];

    foreach ($possible_student_columns as $column) {

        $check = $conn->query(
            "SHOW COLUMNS FROM fees LIKE '$column'"
        );

        if ($check && $check->num_rows > 0) {

            $fee_student_column = $column;
            break;
        }
    }


    if ($fee_student_column !== null) {

        $sql =
            "SELECT *
             FROM fees
             WHERE $fee_student_column = ?
             ORDER BY fee_id DESC";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param(
            "i",
            $student_id
        );

        $stmt->execute();

        $student_fees =
            $stmt->get_result();

        $stmt->close();
    }
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Reports - Hostel Management System</title>

<link rel="stylesheet"
      href="../css/style.css">

<style>

/* ================================
   REPORT PAGE
================================ */

.report-grid {

    display: grid;

    grid-template-columns:
    repeat(3, 1fr);

    gap: 20px;

    margin-top: 25px;
}


.report-card {

    background: white;

    padding: 22px;

    border-radius: 14px;

    box-shadow:
    0 3px 12px rgba(0,0,0,0.06);
}


.report-card h3 {

    margin-bottom: 10px;

    font-size: 16px;
}


.report-number {

    font-size: 28px;

    font-weight: bold;
}


.report-section {

    background: white;

    padding: 25px;

    margin-top: 30px;

    border-radius: 14px;

    box-shadow:
    0 3px 12px rgba(0,0,0,0.06);
}


.report-section h2 {

    margin-bottom: 15px;
}


.report-table {

    width: 100%;

    border-collapse: collapse;

    margin-top: 15px;
}


.report-table th,
.report-table td {

    padding: 12px;

    border-bottom:
    1px solid #e5e7eb;

    text-align: left;
}


.report-table th {

    background: #f3f4f6;

    font-weight: bold;
}


.report-table tr:hover {

    background: #f9fafb;
}


.money {

    font-weight: bold;
}


.filter-box {

    display: flex;

    gap: 15px;

    align-items: end;

    flex-wrap: wrap;

    margin-top: 15px;
}


.filter-box label {

    display: block;

    font-weight: bold;

    margin-bottom: 5px;
}


.filter-box select,
.filter-box input {

    padding: 10px;

    border: 1px solid #ddd;

    border-radius: 7px;
}


.report-button {

    padding: 10px 18px;

    border: none;

    border-radius: 7px;

    cursor: pointer;

    background: #333;

    color: white;
}


.profit {

    font-weight: bold;
}


.summary-box {

    display: grid;

    grid-template-columns:
    repeat(3, 1fr);

    gap: 15px;

    margin-top: 20px;
}


.summary-item {

    padding: 18px;

    border-radius: 10px;

    background: #f7f7f7;
}


.progress-container {

    width: 100%;

    background: #e5e7eb;

    border-radius: 10px;

    height: 20px;

    margin-top: 10px;
}


.progress-bar {

    height: 20px;

    border-radius: 10px;

    background: #333;
}


.student-search {

    display: flex;

    gap: 10px;

    margin-top: 15px;
}


.student-search select {

    padding: 10px;

    min-width: 250px;

    border: 1px solid #ddd;

    border-radius: 7px;
}


.print-button {

    margin-top: 15px;

    padding: 10px 18px;

    border: none;

    border-radius: 7px;

    cursor: pointer;
}


/* ================================
   PRINT
================================ */

@media print {

    .sidebar,
    .filter-box,
    .student-search,
    .print-button {

        display: none !important;
    }

    .main-content {

        margin: 0;

        width: 100%;
    }

    .report-section,
    .report-card {

        box-shadow: none;

        border: 1px solid #ddd;
    }
}


/* ================================
   RESPONSIVE
================================ */

@media (max-width: 900px) {

    .report-grid {

        grid-template-columns:
        repeat(2, 1fr);
    }

    .summary-box {

        grid-template-columns:
        repeat(2, 1fr);
    }
}


@media (max-width: 600px) {

    .report-grid {

        grid-template-columns: 1fr;
    }

    .summary-box {

        grid-template-columns: 1fr;
    }
}

</style>

</head>


<body>

<div class="dashboard-container">


<!-- ==================================================
     SIDEBAR
================================================== -->

<aside class="sidebar">

    <h2>Hostel Management</h2>

    <p class="admin-name">

        Welcome,

        <?php
        echo htmlspecialchars(
            $_SESSION["username"]
        );
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

        <a href="visitors.php">
            👥 Visitors
        </a>

        <a href="reports.php"
           class="active">

            📊 Reports

        </a>

        <a href="../logout.php"
           class="logout">

            🚪 Logout

        </a>

    </nav>

</aside>


<!-- ==================================================
     MAIN CONTENT
================================================== -->

<main class="main-content">


<h1>📊 Hostel Reports & Analytics</h1>

<p>
    Detailed financial, occupancy, student and hostel
    performance analysis.
</p>


<!-- ==================================================
     SUMMARY CARDS
================================================== -->

<div class="report-grid">


<div class="report-card">

    <h3>👨‍🎓 Total Students</h3>

    <div class="report-number">

        <?php
        echo $total_students;
        ?>

    </div>

</div>


<div class="report-card">

    <h3>🏠 Total Rooms</h3>

    <div class="report-number">

        <?php
        echo $total_rooms;
        ?>

    </div>

</div>


<div class="report-card">

    <h3>🛏️ Total Beds</h3>

    <div class="report-number">

        <?php
        echo $total_capacity;
        ?>

    </div>

</div>


<div class="report-card">

    <h3>🟢 Occupied Beds</h3>

    <div class="report-number">

        <?php
        echo $occupied_beds;
        ?>

    </div>

</div>


<div class="report-card">

    <h3>⚪ Vacant Beds</h3>

    <div class="report-number">

        <?php
        echo $vacant_beds;
        ?>

    </div>

</div>


<div class="report-card">

    <h3>📈 Occupancy Rate</h3>

    <div class="report-number">

        <?php
        echo number_format(
            $occupancy_rate,
            1
        );
        ?>%

    </div>

</div>


<div class="report-card">

    <h3>💰 Total Fees</h3>

    <div class="report-number money">

        ₹<?php
        echo number_format(
            $total_fees,
            2
        );
        ?>

    </div>

</div>


<div class="report-card">

    <h3>✅ Paid Fees</h3>

    <div class="report-number money">

        ₹<?php
        echo number_format(
            $paid_fees,
            2
        );
        ?>

    </div>

</div>


<div class="report-card">

    <h3>⏳ Pending Fees</h3>

    <div class="report-number money">

        ₹<?php
        echo number_format(
            $pending_fees,
            2
        );
        ?>

    </div>

</div>


<div class="report-card">

    <h3>📉 Estimated Vacancy Loss</h3>

    <div class="report-number money">

        ₹<?php
        echo number_format(
            $current_vacancy_loss,
            2
        );
        ?>

    </div>

    <small>
        Estimated monthly loss from vacant beds
    </small>

</div>


<div class="report-card">

    <h3>💸 Total Expenses</h3>

    <div class="report-number money">

        ₹<?php
        echo number_format(
            $total_expenses,
            2
        );
        ?>

    </div>

</div>


<div class="report-card">

    <h3>📝 Total Complaints</h3>

    <div class="report-number">

        <?php
        echo $total_complaints;
        ?>

    </div>

</div>


</div>


<!-- ==================================================
     OCCUPANCY
================================================== -->

<section class="report-section">

<h2>🏠 Occupancy Analysis</h2>

<p>

    Current occupancy:

    <strong>
        <?php
        echo $occupied_beds;
        ?>
    </strong>

    out of

    <strong>
        <?php
        echo $total_capacity;
        ?>
    </strong>

    beds.

</p>


<div class="progress-container">

    <div class="progress-bar"
         style="width: <?php echo min(100, $occupancy_rate); ?>%;">

    </div>

</div>


<p>

    <strong>
        <?php
        echo number_format(
            $occupancy_rate,
            1
        );
        ?>%
    </strong>

    of hostel capacity is currently occupied.

</p>

</section>


<!-- ==================================================
     MONTH FILTER
================================================== -->

<section class="report-section">

<h2>📅 Monthly Statement</h2>

<form method="GET"
      class="filter-box">

    <div>

        <label>Month</label>

        <select name="month">

            <?php foreach ($month_names as $number => $name): ?>

                <option value="<?php echo $number; ?>"
                    <?php
                    if ($selected_month == $number) {
                        echo "selected";
                    }
                    ?>>

                    <?php echo $name; ?>

                </option>

            <?php endforeach; ?>

        </select>

    </div>


    <div>

        <label>Year</label>

        <select name="year">

            <?php

            $current_year = date("Y");

            for (
                $y = $current_year - 2;
                $y <= $current_year + 1;
                $y++
            ):

            ?>

                <option value="<?php echo $y; ?>"
                    <?php
                    if ($selected_year == $y) {
                        echo "selected";
                    }
                    ?>>

                    <?php echo $y; ?>

                </option>

            <?php endfor; ?>

        </select>

    </div>


    <button
        type="submit"
        class="report-button">

        Generate Report

    </button>

</form>


<div class="summary-box">


<div class="summary-item">

    <strong>
        Fee Collection
    </strong>

    <h2>
        ₹<?php
        echo number_format(
            $monthly_paid_fees,
            2
        );
        ?>
    </h2>

</div>


<div class="summary-item">

    <strong>
        Expenses
    </strong>

    <h2>
        ₹<?php
        echo number_format(
            $monthly_expenses,
            2
        );
        ?>
    </h2>

</div>


<div class="summary-item">

    <strong>
        Net Profit / Loss
    </strong>

    <h2>

        ₹<?php
        echo number_format(
            $monthly_profit,
            2
        );
        ?>

    </h2>

</div>


</div>


<table class="report-table">

<thead>

<tr>

    <th>Particular</th>

    <th>Amount</th>

</tr>

</thead>


<tbody>

<tr>

    <td>
        Expected Monthly Revenue
    </td>

    <td class="money">

        ₹<?php
        echo number_format(
            $expected_monthly_revenue,
            2
        );
        ?>

    </td>

</tr>


<tr>

    <td>
        Actual Fee Collection
    </td>

    <td class="money">

        ₹<?php
        echo number_format(
            $monthly_paid_fees,
            2
        );
        ?>

    </td>

</tr>


<tr>

    <td>
        Pending Fees
    </td>

    <td class="money">

        ₹<?php
        echo number_format(
            $pending_fees,
            2
        );
        ?>

    </td>

</tr>


<tr>

    <td>
        Estimated Vacancy Loss
    </td>

    <td class="money">

        ₹<?php
        echo number_format(
            $current_vacancy_loss,
            2
        );
        ?>

    </td>

</tr>


<tr>

    <td>
        Monthly Expenses
    </td>

    <td class="money">

        ₹<?php
        echo number_format(
            $monthly_expenses,
            2
        );
        ?>

    </td>

</tr>


<tr>

    <td>
        <strong>
            Net Profit / Loss
        </strong>
    </td>

    <td class="money">

        <strong>

            ₹<?php
            echo number_format(
                $monthly_profit,
                2
            );
            ?>

        </strong>

    </td>

</tr>

</tbody>

</table>

</section>


<!-- ==================================================
     YEARLY STATEMENT
================================================== -->

<section class="report-section">

<h2>
    📆 Yearly Statement - <?php echo $selected_year; ?>
</h2>


<table class="report-table">

<thead>

<tr>

    <th>Month</th>

    <th>Fee Collection</th>

    <th>Expenses</th>

    <th>Net Profit / Loss</th>

</tr>

</thead>


<tbody>

<?php foreach ($monthly_report as $row): ?>

<tr>

    <td>
        <?php echo $row["month"]; ?>
    </td>


    <td class="money">

        ₹<?php
        echo number_format(
            $row["paid"],
            2
        );
        ?>

    </td>


    <td class="money">

        ₹<?php
        echo number_format(
            $row["expense"],
            2
        );
        ?>

    </td>


    <td class="money">

        ₹<?php
        echo number_format(
            $row["profit"],
            2
        );
        ?>

    </td>

</tr>

<?php endforeach; ?>


<tr>

    <td>
        <strong>TOTAL</strong>
    </td>

    <td class="money">

        <strong>
            ₹<?php
            echo number_format(
                $yearly_paid_fees,
                2
            );
            ?>
        </strong>

    </td>


    <td class="money">

        <strong>
            ₹<?php
            echo number_format(
                $yearly_expenses,
                2
            );
            ?>
        </strong>

    </td>


    <td class="money">

        <strong>
            ₹<?php
            echo number_format(
                $yearly_profit,
                2
            );
            ?>
        </strong>

    </td>

</tr>

</tbody>

</table>

</section>


<!-- ==================================================
     STUDENT STATEMENT
================================================== -->

<section class="report-section">

<h2>
    👨‍🎓 Individual Student Statement
</h2>


<form method="GET"
      class="student-search">

    <select name="student_id">

        <option value="">
            Select Student
        </option>

        <?php

        $students_list = $conn->query(
            "SELECT student_id, student_name
             FROM students
             ORDER BY student_name"
        );

        while (
            $student =
            $students_list->fetch_assoc()
        ):

        ?>

            <option
                value="<?php echo $student["student_id"]; ?>"
                <?php

                if (
                    isset($_GET["student_id"])
                    &&
                    $_GET["student_id"]
                    == $student["student_id"]
                ) {

                    echo "selected";
                }

                ?>>

                <?php
                echo htmlspecialchars(
                    $student["student_name"]
                );
                ?>

            </option>

        <?php endwhile; ?>

    </select>


    <input type="hidden"
           name="month"
           value="<?php echo $selected_month; ?>">


    <input type="hidden"
           name="year"
           value="<?php echo $selected_year; ?>">


    <button
        type="submit"
        class="report-button">

        View Statement

    </button>

</form>


<?php if ($student_statement): ?>


<div class="summary-box">


<div class="summary-item">

    <strong>
        Student
    </strong>

    <h3>

        <?php
        echo htmlspecialchars(
            $student_statement["student_name"]
        );
        ?>

    </h3>

</div>


<div class="summary-item">

    <strong>
        Room
    </strong>

    <h3>

        <?php

        echo htmlspecialchars(
            $student_statement["room_number"]
            ?? "Not Allocated"
        );

        ?>

    </h3>

</div>


<div class="summary-item">

    <strong>
        Monthly Fee
    </strong>

    <h3>

        ₹<?php

        echo number_format(
            $student_statement["room_fee"] ?? 0,
            2
        );

        ?>

    </h3>

</div>

</div>


<?php

$student_total_paid = 0;
$student_total_pending = 0;

if ($student_fees):

?>

<table class="report-table">

<thead>

<tr>

    <th>Fee ID</th>

    <th>Amount</th>

    <th>Status</th>

</tr>

</thead>


<tbody>

<?php while (
    $fee = $student_fees->fetch_assoc()
):

?>

<tr>

    <td>

        <?php
        echo htmlspecialchars(
            $fee["fee_id"] ?? "-"
        );
        ?>

    </td>


    <td class="money">

        ₹<?php

        echo number_format(
            $fee["amount"] ?? 0,
            2
        );

        ?>

    </td>


    <td>

        <?php
        echo htmlspecialchars(
            $fee["status"] ?? "-"
        );
        ?>

    </td>

</tr>


<?php

if (
    isset($fee["status"])
    &&
    strtolower($fee["status"]) == "paid"
) {

    $student_total_paid +=
        floatval($fee["amount"]);

} else {

    $student_total_pending +=
        floatval($fee["amount"]);
}

?>


<?php endwhile; ?>

</tbody>

</table>


<div class="summary-box">


<div class="summary-item">

    <strong>
        Total Paid
    </strong>

    <h3>

        ₹<?php
        echo number_format(
            $student_total_paid,
            2
        );
        ?>

    </h3>

</div>


<div class="summary-item">

    <strong>
        Total Pending
    </strong>

    <h3>

        ₹<?php
        echo number_format(
            $student_total_pending,
            2
        );
        ?>

    </h3>

</div>


</div>


<?php else: ?>

<p>
    No fee records found for this student.
</p>

<?php endif; ?>


<button
    onclick="window.print()"
    class="print-button">

    🖨️ Print Student / Report

</button>


<?php elseif (isset($_GET["student_id"])): ?>

<p>
    Student information could not be found.
</p>

<?php endif; ?>

</section>


<!-- ==================================================
     ROOM OCCUPANCY
================================================== -->

<section class="report-section">

<h2>
    🛏️ Room-wise Occupancy Analysis
</h2>


<div style="overflow-x:auto;">

<table class="report-table">

<thead>

<tr>

    <th>Room</th>

    <th>Type</th>

    <th>Capacity</th>

    <th>Occupied</th>

    <th>Vacant</th>

    <th>Occupancy</th>

    <th>Room Fee</th>

</tr>

</thead>


<tbody>

<?php if (
    $room_occupancy
    &&
    $room_occupancy->num_rows > 0
):

?>


<?php while (
    $room =
    $room_occupancy->fetch_assoc()
):


$room_vacant =
    max(
        0,
        $room["capacity"]
        -
        $room["occupied"]
    );


$room_occupancy_rate = 0;

if ($room["capacity"] > 0) {

    $room_occupancy_rate =
        (
            $room["occupied"]
            /
            $room["capacity"]
        ) * 100;
}

?>


<tr>

    <td>
        <?php
        echo htmlspecialchars(
            $room["room_number"]
        );
        ?>
    </td>


    <td>
        <?php
        echo htmlspecialchars(
            $room["room_type"]
        );
        ?>
    </td>


    <td>
        <?php
        echo $room["capacity"];
        ?>
    </td>


    <td>
        <?php
        echo $room["occupied"];
        ?>
    </td>


    <td>
        <?php
        echo $room_vacant;
        ?>
    </td>


    <td>

        <?php
        echo number_format(
            $room_occupancy_rate,
            1
        );
        ?>%

    </td>


    <td class="money">

        ₹<?php

        echo number_format(
            $room["room_fee"],
            2
        );

        ?>

    </td>

</tr>


<?php endwhile; ?>


<?php else: ?>


<tr>

    <td colspan="7">

        No room information available.

    </td>

</tr>


<?php endif; ?>

</tbody>

</table>

</div>

</section>


<!-- ==================================================
     COMPLAINT ANALYSIS
================================================== -->

<section class="report-section">

<h2>
    📝 Complaint Analysis
</h2>


<table class="report-table">

<thead>

<tr>

    <th>Total Complaints</th>

    <th>Pending</th>

    <th>Resolved</th>

</tr>

</thead>


<tbody>

<tr>

    <td>
        <?php
        echo $total_complaints;
        ?>
    </td>

    <td>
        <?php
        echo $pending_complaints;
        ?>
    </td>

    <td>
        <?php
        echo $resolved_complaints;
        ?>
    </td>

</tr>

</tbody>

</table>

</section>


<!-- ==================================================
     CURRENT ALLOCATIONS
================================================== -->

<section class="report-section">

<h2>
    📋 Current Room Allocations
</h2>


<div style="overflow-x:auto;">

<?php if (
    $allocations
    &&
    $allocations->num_rows > 0
):

?>

<table class="report-table">

<thead>

<tr>

    <th>Student</th>

    <th>Room</th>

    <th>Room Type</th>

    <th>Allocation Date</th>

</tr>

</thead>


<tbody>


<?php while (
    $allocation =
    $allocations->fetch_assoc()
):

?>

<tr>

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
        echo htmlspecialchars(
            $allocation["allocation_date"]
        );
        ?>

    </td>

</tr>

<?php endwhile; ?>


</tbody>

</table>


<?php else: ?>


<p>
    No current room allocations.
</p>


<?php endif; ?>

</div>

</section>


</main>

</div>

</body>

</html>