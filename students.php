<?php

session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "admin") {
    header("Location: ../login.php");
    exit();
}

include "../config/database.php";

$message = "";
$message_type = "";


/* =========================================================
   ADD STUDENT + ROOM ALLOCATION + FEE
========================================================= */

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    /* Student Details */

    $student_name = trim($_POST["student_name"] ?? "");
    $username = trim($_POST["username"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $course = trim($_POST["course"] ?? "");
    $year = (int) ($_POST["year"] ?? 0);
    $password = $_POST["password"] ?? "";

    /* Parent Details */

    $parent_name = trim($_POST["parent_name"] ?? "");
    $parent_phone = trim($_POST["parent_phone"] ?? "");
    $parent_address = trim($_POST["parent_address"] ?? "");

    /* Student Address */

    $address = trim($_POST["address"] ?? "");
    $location = trim($_POST["location"] ?? "");

    /* Room */

    $room_id = (int) ($_POST["room_id"] ?? 0);


    /* =====================================================
       VALIDATION
    ===================================================== */

    if (
        empty($student_name) ||
        empty($username) ||
        empty($email) ||
        empty($phone) ||
        empty($course) ||
        empty($year) ||
        empty($password) ||
        empty($parent_name) ||
        empty($parent_phone) ||
        empty($parent_address) ||
        empty($address) ||
        empty($location) ||
        empty($room_id)
    ) {

        $message = "Please fill in all fields and select a room.";
        $message_type = "error";

    } else {


        /* =================================================
           CHECK USERNAME
        ================================================= */

        $check = $conn->prepare(
            "SELECT user_id FROM users WHERE username = ?"
        );

        $check->bind_param("s", $username);
        $check->execute();

        $check_result = $check->get_result();


        if ($check_result->num_rows > 0) {

            $message = "Username already exists.";
            $message_type = "error";

        } else {


            /* =================================================
               GET SELECTED ROOM
               Fee is taken from rooms.fee
            ================================================= */

            $room_stmt = $conn->prepare(
                "SELECT
                    room_id,
                    room_number,
                    room_type,
                    capacity,
                    fee,
                    status
                 FROM rooms
                 WHERE room_id = ?"
            );

            $room_stmt->bind_param("i", $room_id);
            $room_stmt->execute();

            $room_result = $room_stmt->get_result();


            if ($room_result->num_rows == 0) {

                $message = "Selected room does not exist.";
                $message_type = "error";

            } else {

                $room = $room_result->fetch_assoc();

                $room_number = $room["room_number"];
                $room_type = $room["room_type"];
                $room_capacity = (int) $room["capacity"];

                /* Permanent room fee */
                $room_fee = (float) $room["fee"];

                $room_status = $room["status"];


                /* =================================================
                   COUNT CURRENT OCCUPANTS
                ================================================= */

                $occupancy_stmt = $conn->prepare(
                    "SELECT COUNT(*) AS occupied
                     FROM room_allocations
                     WHERE room_id = ?
                     AND checkout_date IS NULL"
                );

                $occupancy_stmt->bind_param("i", $room_id);
                $occupancy_stmt->execute();

                $occupancy_result =
                    $occupancy_stmt->get_result();

                $occupancy_data =
                    $occupancy_result->fetch_assoc();

                $occupied_beds =
                    (int) $occupancy_data["occupied"];


                $available_beds =
                    $room_capacity - $occupied_beds;


                /* =================================================
                   CHECK ROOM AVAILABILITY
                ================================================= */

                if (
                    $room_status != "Available" ||
                    $available_beds <= 0
                ) {

                    $message =
                        "Selected room is not available.";

                    $message_type = "error";

                } else {


                    /* =================================================
                       START TRANSACTION
                    ================================================= */

                    $conn->begin_transaction();


                    try {


                        /* =============================================
                           CREATE USER ACCOUNT
                        ============================================= */

                        $hashed_password =
                            password_hash(
                                $password,
                                PASSWORD_DEFAULT
                            );

                        $role = "student";


                        $user_stmt = $conn->prepare(
                            "INSERT INTO users
                            (username, password, role)
                            VALUES (?, ?, ?)"
                        );

                        $user_stmt->bind_param(
                            "sss",
                            $username,
                            $hashed_password,
                            $role
                        );

                        $user_stmt->execute();


                        $user_id =
                            $conn->insert_id;


                        /* =============================================
                           CREATE STUDENT PROFILE
                        ============================================= */

                        $student_stmt = $conn->prepare(
                            "INSERT INTO students
                            (
                                user_id,
                                student_name,
                                email,
                                phone,
                                course,
                                year,
                                parent_name,
                                parent_phone,
                                parent_address,
                                address,
                                location
                            )
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                        );


                        $student_stmt->bind_param(
                            "isssissssss",
                            $user_id,
                            $student_name,
                            $email,
                            $phone,
                            $course,
                            $year,
                            $parent_name,
                            $parent_phone,
                            $parent_address,
                            $address,
                            $location
                        );


                        $student_stmt->execute();


                        $student_id =
                            $conn->insert_id;


                        /* =============================================
                           ALLOCATE ROOM
                        ============================================= */

                        $allocation_stmt = $conn->prepare(
                            "INSERT INTO room_allocations
                            (
                                student_id,
                                room_id,
                                allocation_date,
                                checkout_date
                            )
                            VALUES (?, ?, CURDATE(), NULL)"
                        );


                        $allocation_stmt->bind_param(
                            "ii",
                            $student_id,
                            $room_id
                        );


                        $allocation_stmt->execute();


                        /* =============================================
                           CREATE AUTOMATIC FEE
                           Amount comes from rooms.fee
                        ============================================= */

                        $fee_stmt = $conn->prepare(
                            "INSERT INTO fees
                            (
                                student_id,
                                amount,
                                due_date,
                                payment_date,
                                status
                            )
                            VALUES
                            (
                                ?,
                                ?,
                                DATE_ADD(CURDATE(), INTERVAL 30 DAY),
                                NULL,
                                'Pending'
                            )"
                        );


                        $fee_stmt->bind_param(
                            "id",
                            $student_id,
                            $room_fee
                        );


                        $fee_stmt->execute();


                        /* =============================================
                           UPDATE ROOM STATUS
                           ENUM = Available, Full, Maintenance
                        ============================================= */

                        $new_occupied_beds =
                            $occupied_beds + 1;


                        if (
                            $new_occupied_beds >=
                            $room_capacity
                        ) {

                            $status_stmt = $conn->prepare(
                                "UPDATE rooms
                                 SET status = 'Full'
                                 WHERE room_id = ?"
                            );

                            $status_stmt->bind_param(
                                "i",
                                $room_id
                            );

                            $status_stmt->execute();

                        }


                        /* =============================================
                           COMMIT
                        ============================================= */

                        $conn->commit();


                        $message =
                            "Student added successfully! " .
                            "Room " .
                            $room_number .
                            " allocated. " .
                            "Room Fee: ₹" .
                            number_format(
                                $room_fee,
                                2
                            );

                        $message_type = "success";


                    } catch (Exception $e) {

                        $conn->rollback();

                        $message =
                            "Error adding student: " .
                            $e->getMessage();

                        $message_type = "error";
                    }
                }
            }
        }
    }
}


/* =========================================================
   GET ROOMS
========================================================= */

$rooms_sql = "
    SELECT
        r.room_id,
        r.room_number,
        r.room_type,
        r.capacity,
        r.fee,
        r.status,

        (
            SELECT COUNT(*)
            FROM room_allocations ra
            WHERE ra.room_id = r.room_id
            AND ra.checkout_date IS NULL
        ) AS occupied_beds

    FROM rooms r

    ORDER BY r.room_number
";


$rooms_result =
    $conn->query($rooms_sql);


/* =========================================================
   GET AMENITIES
========================================================= */

$amenities_data = array();


$amenities_result = $conn->query(
    "SELECT
        room_type,
        amenity_name,
        quantity
     FROM amenities
     ORDER BY room_type, amenity_name"
);


if ($amenities_result) {

    while (
        $amenity =
        $amenities_result->fetch_assoc()
    ) {

        $type =
            $amenity["room_type"];


        if (
            !isset(
                $amenities_data[$type]
            )
        ) {

            $amenities_data[$type] =
                array();
        }


        $amenities_data[$type][] =
            $amenity["quantity"] .
            " " .
            $amenity["amenity_name"];
    }
}


/* =========================================================
   GET ALL STUDENTS
========================================================= */

$sql = "
    SELECT
        s.student_id,
        s.student_name,
        s.email,
        s.phone,
        s.course,
        s.year,
        s.parent_name,
        s.parent_phone,
        s.parent_address,
        s.address,
        s.location,
        u.username

    FROM students s

    INNER JOIN users u
        ON s.user_id = u.user_id

    ORDER BY s.student_id DESC
";


$result =
    $conn->query($sql);

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Students - Hostel Management System
    </title>

    <link
        rel="stylesheet"
        href="../css/style.css"
    >


    <style>

        .student-form {

            display: grid;

            grid-template-columns:
                repeat(2, 1fr);

            gap: 15px;
        }


        .student-form input,
        .student-form select,
        .student-form textarea {

            width: 100%;

            padding: 12px;

            border: 1px solid #ddd;

            border-radius: 8px;

            box-sizing: border-box;

            font-family: inherit;
        }


        .student-form textarea {

            min-height: 90px;

            resize: vertical;
        }


        .student-form button {

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


        .section-title {

            grid-column: 1 / -1;

            margin-top: 10px;

            margin-bottom: 0;
        }


        .full-width {

            grid-column: 1 / -1;
        }


        /* ROOM DETAILS */

        .room-details {

            grid-column: 1 / -1;

            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 15px;

            margin-top: 5px;
        }


        .room-info-box {

            padding: 15px;

            background: #f8fafc;

            border: 1px solid #e2e8f0;

            border-radius: 8px;
        }


        .room-info-box h4 {

            margin:
                0 0 8px 0;
        }


        .room-fee {

            font-size: 20px;

            font-weight: bold;
        }


        .amenities-box {

            grid-column: 1 / -1;

            padding: 15px;

            background: #f0fdf4;

            border: 1px solid #bbf7d0;

            border-radius: 8px;
        }


        /* TABLE */

        .student-table {

            width: 100%;

            border-collapse:
                collapse;
        }


        .student-table th,
        .student-table td {

            padding: 12px;

            border-bottom:
                1px solid #ddd;

            text-align: left;
        }


        .student-table th {

            background:
                #f3f4f6;
        }


        .table-container {

            overflow-x: auto;
        }


        @media (max-width: 700px) {

            .student-form {

                grid-template-columns:
                    1fr;
            }


            .room-details {

                grid-template-columns:
                    1fr;
            }

        }

    </style>

</head>


<body>


<div class="dashboard-container">


    <!-- =====================================================
         SIDEBAR
    ====================================================== -->

    <aside class="sidebar">

        <h2>
            Hostel Management
        </h2>


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


            <a
                href="students.php"
                class="active"
            >
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


            <a href="reports.php">
                📊 Reports
            </a>


            <a
                href="../logout.php"
                class="logout"
            >
                🚪 Logout
            </a>

        </nav>

    </aside>


    <!-- =====================================================
         MAIN CONTENT
    ====================================================== -->

    <main class="main-content">


        <h1>
            Student Management
        </h1>


        <p>
            Manage hostel student records
            and accounts.
        </p>


        <!-- MESSAGE -->

        <?php if ($message != ""): ?>

            <div
                class="message
                <?php echo $message_type; ?>"
            >

                <?php
                echo htmlspecialchars(
                    $message
                );
                ?>

            </div>

        <?php endif; ?>


        <!-- =================================================
             ADD STUDENT
        ================================================== -->

        <section class="dashboard-section">


            <h2>
                Add New Student
            </h2>


            <form
                method="POST"
                class="student-form"
            >


                <!-- STUDENT DETAILS -->

                <h3 class="section-title">
                    Student Details
                </h3>


                <input
                    type="text"
                    name="student_name"
                    placeholder="Student Name"
                    required
                >


                <input
                    type="text"
                    name="username"
                    placeholder="Login Username"
                    required
                >


                <input
                    type="email"
                    name="email"
                    placeholder="Email"
                    required
                >


                <input
                    type="text"
                    name="phone"
                    placeholder="Phone Number"
                    required
                >


                <input
                    type="text"
                    name="course"
                    placeholder="Course"
                    required
                >


                <select
                    name="year"
                    required
                >

                    <option value="">
                        Select Year
                    </option>

                    <option value="1">
                        1st Year
                    </option>

                    <option value="2">
                        2nd Year
                    </option>

                    <option value="3">
                        3rd Year
                    </option>

                    <option value="4">
                        4th Year
                    </option>

                </select>


                <input
                    type="password"
                    name="password"
                    placeholder="Login Password"
                    required
                >


                <!-- PARENT DETAILS -->

                <h3 class="section-title">
                    Parent Details
                </h3>


                <input
                    type="text"
                    name="parent_name"
                    placeholder="Parent Name"
                    required
                >


                <input
                    type="text"
                    name="parent_phone"
                    placeholder="Parent Phone Number"
                    required
                >


                <textarea
                    name="parent_address"
                    placeholder="Parent Address"
                    required
                ></textarea>


                <!-- ADDRESS -->

                <h3 class="section-title">
                    Student Address
                </h3>


                <textarea
                    name="address"
                    placeholder="Student Address"
                    required
                ></textarea>


                <input
                    type="text"
                    name="location"
                    placeholder="Location / City"
                    required
                >


                <!-- ROOM -->

                <h3 class="section-title">
                    Room Allocation
                </h3>


                <select
                    name="room_id"
                    id="room_id"
                    required
                    onchange="showRoomDetails()"
                >

                    <option value="">
                        Select Available Room
                    </option>


                    <?php

                    if (
                        $rooms_result &&
                        $rooms_result->num_rows > 0
                    ):

                    ?>

                        <?php

                        while (
                            $room_row =
                            $rooms_result->fetch_assoc()
                        ):

                        ?>

                            <?php

                            $occupied =
                                (int)
                                $room_row[
                                    "occupied_beds"
                                ];


                            $capacity =
                                (int)
                                $room_row[
                                    "capacity"
                                ];


                            $available_beds =
                                $capacity -
                                $occupied;


                            $is_available =
                                $room_row["status"]
                                === "Available"
                                &&
                                $available_beds > 0;

                            ?>


                            <?php
                            if ($is_available):
                            ?>

                                <option
                                    value="<?php
                                    echo $room_row["room_id"];
                                    ?>"
                                    data-number="<?php
                                    echo htmlspecialchars(
                                        $room_row["room_number"]
                                    );
                                    ?>"
                                    data-type="<?php
                                    echo htmlspecialchars(
                                        $room_row["room_type"]
                                    );
                                    ?>"
                                    data-capacity="<?php
                                    echo $capacity;
                                    ?>"
                                    data-fee="<?php
                                    echo $room_row["fee"];
                                    ?>"
                                    data-available="<?php
                                    echo $available_beds;
                                    ?>"
                                >

                                    Room
                                    <?php
                                    echo htmlspecialchars(
                                        $room_row["room_number"]
                                    );
                                    ?>

                                    -

                                    <?php
                                    echo htmlspecialchars(
                                        $room_row["room_type"]
                                    );
                                    ?>

                                </option>

                            <?php endif; ?>

                        <?php endwhile; ?>

                    <?php endif; ?>

                </select>


                <!-- =================================================
                     AUTOMATIC ROOM DETAILS
                ================================================== -->

                <div
                    class="room-details"
                    id="roomDetails"
                    style="display:none;"
                >


                    <div class="room-info-box">

                        <h4>
                            Room Number
                        </h4>

                        <span
                            id="displayRoomNumber"
                        >
                            -
                        </span>

                    </div>


                    <div class="room-info-box">

                        <h4>
                            Room Type
                        </h4>

                        <span
                            id="displayRoomType"
                        >
                            -
                        </span>

                    </div>


                    <div class="room-info-box">

                        <h4>
                            Capacity
                        </h4>

                        <span
                            id="displayCapacity"
                        >
                            -
                        </span>

                    </div>


                    <div class="room-info-box">

                        <h4>
                            Available Beds
                        </h4>

                        <span
                            id="displayAvailable"
                        >
                            -
                        </span>

                    </div>


                    <div class="room-info-box">

                        <h4>
                            Room Fee
                        </h4>

                        <span
                            id="displayFee"
                            class="room-fee"
                        >
                            -
                        </span>

                    </div>


                    <div class="amenities-box">

                        <h4>
                            🛏️ Room Amenities
                        </h4>

                        <span
                            id="displayAmenities"
                        >
                            -
                        </span>

                    </div>

                </div>


                <!-- SUBMIT -->

                <button
                    type="submit"
                    class="btn full-width"
                >

                    Add Student &
                    Allocate Room

                </button>


            </form>

        </section>


        <!-- =================================================
             REGISTERED STUDENTS
        ================================================== -->

        <section class="dashboard-section">


            <h2>
                Registered Students
            </h2>


            <div class="table-container">


                <?php

                if (
                    $result &&
                    $result->num_rows > 0
                ):

                ?>

                    <table
                        class="student-table"
                    >

                        <thead>

                            <tr>

                                <th>
                                    ID
                                </th>

                                <th>
                                    Name
                                </th>

                                <th>
                                    Username
                                </th>

                                <th>
                                    Email
                                </th>

                                <th>
                                    Phone
                                </th>

                                <th>
                                    Course
                                </th>

                                <th>
                                    Year
                                </th>

                                <th>
                                    Parent Name
                                </th>

                                <th>
                                    Parent Phone
                                </th>

                                <th>
                                    Parent Address
                                </th>

                                <th>
                                    Student Address
                                </th>

                                <th>
                                    Location
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php

                        while (
                            $student =
                            $result->fetch_assoc()
                        ):

                        ?>

                            <tr>

                                <td>
                                    <?php
                                    echo $student[
                                        "student_id"
                                    ];
                                    ?>
                                </td>


                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $student[
                                            "student_name"
                                        ]
                                    );
                                    ?>
                                </td>


                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $student[
                                            "username"
                                        ]
                                    );
                                    ?>
                                </td>


                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $student[
                                            "email"
                                        ]
                                    );
                                    ?>
                                </td>


                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $student[
                                            "phone"
                                        ]
                                    );
                                    ?>
                                </td>


                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $student[
                                            "course"
                                        ]
                                    );
                                    ?>
                                </td>


                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $student[
                                            "year"
                                        ]
                                    );
                                    ?>
                                </td>


                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $student[
                                            "parent_name"
                                        ]
                                    );
                                    ?>
                                </td>


                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $student[
                                            "parent_phone"
                                        ]
                                    );
                                    ?>
                                </td>


                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $student[
                                            "parent_address"
                                        ]
                                    );
                                    ?>
                                </td>


                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $student[
                                            "address"
                                        ]
                                    );
                                    ?>
                                </td>


                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $student[
                                            "location"
                                        ]
                                    );
                                    ?>
                                </td>

                            </tr>


                        <?php endwhile; ?>


                        </tbody>

                    </table>


                <?php else: ?>

                    <p>
                        No students registered yet.
                    </p>

                <?php endif; ?>


            </div>

        </section>


    </main>

</div>


<!-- =========================================================
     JAVASCRIPT
========================================================= -->

<script>

const amenities =
    <?php
    echo json_encode(
        $amenities_data
    );
    ?>;


function showRoomDetails() {

    const select =
        document.getElementById(
            "room_id"
        );


    const selected =
        select.options[
            select.selectedIndex
        ];


    const details =
        document.getElementById(
            "roomDetails"
        );


    if (!selected.value) {

        details.style.display =
            "none";

        return;
    }


    const roomNumber =
        selected.getAttribute(
            "data-number"
        );


    const roomType =
        selected.getAttribute(
            "data-type"
        );


    const capacity =
        selected.getAttribute(
            "data-capacity"
        );


    const fee =
        selected.getAttribute(
            "data-fee"
        );


    const available =
        selected.getAttribute(
            "data-available"
        );


    document.getElementById(
        "displayRoomNumber"
    ).textContent =
        roomNumber;


    document.getElementById(
        "displayRoomType"
    ).textContent =
        roomType;


    document.getElementById(
        "displayCapacity"
    ).textContent =
        capacity +
        " bed(s)";


    document.getElementById(
        "displayAvailable"
    ).textContent =
        available +
        " bed(s)";


    document.getElementById(
        "displayFee"
    ).textContent =
        "₹" +
        Number(fee).toLocaleString(
            "en-IN",
            {
                minimumFractionDigits: 2
            }
        );


    let roomAmenities =
        amenities[roomType];


    if (
        roomAmenities &&
        roomAmenities.length > 0
    ) {

        document.getElementById(
            "displayAmenities"
        ).textContent =
            roomAmenities.join(
                ", "
            );

    } else {

        document.getElementById(
            "displayAmenities"
        ).textContent =
            "No amenities configured.";

    }


    details.style.display =
        "grid";
}

</script>


</body>

</html>