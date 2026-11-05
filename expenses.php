<?php
session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "admin") {
    header("Location: ../login.php");
    exit();
}

include "../config/database.php";

$message = "";

/* ADD EXPENSE */
if (isset($_POST["add_expense"])) {

    $expense_title = trim($_POST["expense_title"]);
    $category = trim($_POST["category"]);
    $amount = floatval($_POST["amount"]);
    $expense_date = $_POST["expense_date"];
    $description = trim($_POST["description"]);

    if ($expense_title == "" || $category == "" || $amount <= 0 || $expense_date == "") {
        $message = "Please fill all required fields correctly.";
    } else {

        $stmt = $conn->prepare(
            "INSERT INTO expenses
            (expense_title, category, amount, expense_date, description)
            VALUES (?, ?, ?, ?, ?)"
        );

        $stmt->bind_param(
            "ssdss",
            $expense_title,
            $category,
            $amount,
            $expense_date,
            $description
        );

        if ($stmt->execute()) {
            $message = "Expense added successfully.";
        } else {
            $message = "Error adding expense.";
        }

        $stmt->close();
    }
}

/* DELETE EXPENSE */
if (isset($_GET["delete"])) {

    $expense_id = intval($_GET["delete"]);

    $stmt = $conn->prepare(
        "DELETE FROM expenses WHERE expense_id = ?"
    );

    $stmt->bind_param("i", $expense_id);
    $stmt->execute();
    $stmt->close();

    header("Location: expenses.php");
    exit();
}

/* GET EXPENSES */
$expenses = $conn->query(
    "SELECT *
     FROM expenses
     ORDER BY expense_date DESC, expense_id DESC"
);

/* TOTAL EXPENSE */
$total_expense = $conn->query(
    "SELECT COALESCE(SUM(amount), 0) AS total
     FROM expenses"
)->fetch_assoc()["total"];
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Expenses - Hostel Management</title>

    <link rel="stylesheet" href="../css/style.css">

    <style>

        .expense-container {
            padding: 30px;
        }

        .page-title {
            margin-bottom: 25px;
        }

        .expense-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }

        .expense-form {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 18px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: 600;
            margin-bottom: 7px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 11px;
            border: 1px solid #ddd;
            border-radius: 7px;
            font-size: 14px;
        }

        .form-group textarea {
            resize: vertical;
        }

        .full-width {
            grid-column: 1 / -1;
        }

        .add-btn {
            background: #6c5ce7;
            color: white;
            border: none;
            padding: 12px 22px;
            border-radius: 7px;
            cursor: pointer;
            font-size: 15px;
        }

        .total-box {
            background: #f5f3ff;
            border-left: 5px solid #6c5ce7;
            padding: 18px;
            border-radius: 8px;
            margin-bottom: 25px;
        }

        .total-box h3 {
            margin: 0;
        }

        .expense-table {
            width: 100%;
            border-collapse: collapse;
        }

        .expense-table th,
        .expense-table td {
            padding: 13px;
            border-bottom: 1px solid #eee;
            text-align: left;
        }

        .expense-table th {
            background: #f7f7f7;
        }

        .delete-btn {
            color: #e74c3c;
            text-decoration: none;
            font-weight: 600;
        }

        .message {
            padding: 12px;
            background: #eafaf1;
            color: #218c5a;
            border-radius: 7px;
            margin-bottom: 20px;
        }

    </style>

</head>

<body>

<div class="dashboard-container">

    <!-- SIDEBAR -->
    <aside class="sidebar">

        <h2>Hostel Admin</h2>

        <a href="dashboard.php">🏠 Dashboard</a>

        <a href="students.php">👨‍🎓 Students</a>

        <a href="rooms.php">🛏️ Rooms</a>

        <a href="room_allocation.php">📋 Room Allocation</a>

        <a href="fees.php">💰 Fees</a>

        <a href="expenses.php" class="active">💸 Expenses</a>

        <a href="complaints.php">📢 Complaints</a>

        <a href="visitors.php">👥 Visitors</a>

        <a href="reports.php">📊 Reports</a>

        <a href="../logout.php">🚪 Logout</a>

    </aside>


    <!-- MAIN CONTENT -->
    <main class="main-content">

        <div class="expense-container">

            <h1 class="page-title">💸 Expense Management</h1>

            <?php if ($message != "") { ?>

                <div class="message">
                    <?php echo htmlspecialchars($message); ?>
                </div>

            <?php } ?>


            <!-- TOTAL EXPENSE -->
            <div class="total-box">

                <h3>
                    Total Expenses:
                    ₹<?php echo number_format($total_expense, 2); ?>
                </h3>

            </div>


            <!-- ADD EXPENSE -->
            <div class="expense-card">

                <h2>Add New Expense</h2>

                <br>

                <form method="POST">

                    <div class="expense-form">

                        <div class="form-group">

                            <label>Expense Title *</label>

                            <input
                                type="text"
                                name="expense_title"
                                placeholder="Example: Electricity Bill"
                                required
                            >

                        </div>


                        <div class="form-group">

                            <label>Category *</label>

                            <select name="category" required>

                                <option value="">Select Category</option>

                                <option value="Electricity">Electricity</option>
                                <option value="Water">Water</option>
                                <option value="Maintenance">Maintenance</option>
                                <option value="Salary">Salary</option>
                                <option value="Internet">Internet</option>
                                <option value="Food">Food</option>
                                <option value="Cleaning">Cleaning</option>
                                <option value="Other">Other</option>

                            </select>

                        </div>


                        <div class="form-group">

                            <label>Amount (₹) *</label>

                            <input
                                type="number"
                                name="amount"
                                min="1"
                                step="0.01"
                                placeholder="Enter amount"
                                required
                            >

                        </div>


                        <div class="form-group">

                            <label>Expense Date *</label>

                            <input
                                type="date"
                                name="expense_date"
                                value="<?php echo date('Y-m-d'); ?>"
                                required
                            >

                        </div>


                        <div class="form-group full-width">

                            <label>Description</label>

                            <textarea
                                name="description"
                                rows="3"
                                placeholder="Enter expense details..."
                            ></textarea>

                        </div>


                        <div class="full-width">

                            <button
                                type="submit"
                                name="add_expense"
                                class="add-btn"
                            >
                                ➕ Add Expense
                            </button>

                        </div>

                    </div>

                </form>

            </div>


            <!-- EXPENSE LIST -->
            <div class="expense-card">

                <h2>Expense History</h2>

                <br>

                <div style="overflow-x:auto;">

                    <table class="expense-table">

                        <thead>

                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Amount</th>
                                <th>Date</th>
                                <th>Description</th>
                                <th>Action</th>
                            </tr>

                        </thead>


                        <tbody>

                        <?php

                        if ($expenses && $expenses->num_rows > 0) {

                            while ($row = $expenses->fetch_assoc()) {

                        ?>

                            <tr>

                                <td><?php echo $row["expense_id"]; ?></td>

                                <td>
                                    <?php echo htmlspecialchars($row["expense_title"]); ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($row["category"]); ?>
                                </td>

                                <td>
                                    ₹<?php echo number_format($row["amount"], 2); ?>
                                </td>

                                <td>
                                    <?php echo date("d M Y", strtotime($row["expense_date"])); ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($row["description"]); ?>
                                </td>

                                <td>
                                    <a
                                        href="expenses.php?delete=<?php echo $row["expense_id"]; ?>"
                                        class="delete-btn"
                                        onclick="return confirm('Are you sure you want to delete this expense?');"
                                    >
                                        Delete
                                    </a>
                                </td>

                            </tr>

                        <?php

                            }

                        } else {

                        ?>

                            <tr>

                                <td colspan="7" style="text-align:center;">
                                    No expenses recorded yet.
                                </td>

                            </tr>

                        <?php } ?>

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </main>

</div>

</body>

</html>