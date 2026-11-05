<?php

session_start();

include "config/database.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = trim($_POST["username"]);
    $password = $_POST["password"];

    $sql = "SELECT * FROM users WHERE username = ?";

    $stmt = $conn->prepare($sql);

    if ($stmt) {

        $stmt->bind_param("s", $username);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows == 1) {

            $user = $result->fetch_assoc();

            if (password_verify($password, $user["password"])) {

                $_SESSION["user_id"] = $user["user_id"];
                $_SESSION["username"] = $user["username"];
                $_SESSION["role"] = $user["role"];

                if ($user["role"] == "admin") {

                    header("Location: admin/dashboard.php");
                    exit();

                } else {

                    header("Location: student/dashboard.php");
                    exit();

                }

            } else {

                $message = "Incorrect password. Please try again.";

            }

        } else {

            $message = "Username not found.";

        }

        $stmt->close();

    } else {

        $message = "Database error. Please try again later.";

    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login | Hostel Management System</title>

    <link rel="stylesheet" href="css/style.css">

    <style>

        /* ================================
           LOGIN PAGE
        ================================= */

        .login-page {

            min-height: calc(100vh - 75px);

            display: flex;

            align-items: center;

            justify-content: center;

            padding: 50px 20px;

            background:
                linear-gradient(
                    135deg,
                    #eef2ff,
                    #ffffff,
                    #f5f7ff
                );

        }


        .login-container {

            width: 100%;

            max-width: 1000px;

            display: grid;

            grid-template-columns: 1fr 1fr;

            background: white;

            border-radius: 25px;

            overflow: hidden;

            box-shadow:
                0 20px 60px rgba(0, 0, 0, 0.12);

        }


        /* ================================
           LEFT SIDE
        ================================= */

        .login-welcome {

            padding: 60px 45px;

            background: linear-gradient(
                145deg,
                #243b6b,
                #18294c
            );

            color: white;

            display: flex;

            flex-direction: column;

            justify-content: center;

        }


        .login-icon {

            font-size: 65px;

            margin-bottom: 20px;

        }


        .login-welcome h1 {

            font-size: 38px;

            line-height: 1.2;

            margin-bottom: 18px;

        }


        .login-welcome h1 span {

            color: #dbe5ff;

        }


        .login-welcome p {

            color: #d9e1f3;

            line-height: 1.7;

            margin-bottom: 25px;

        }


        .login-features {

            list-style: none;

            padding: 0;

            margin: 0;

        }


        .login-features li {

            margin: 13px 0;

            color: #edf1fa;

        }


        /* ================================
           RIGHT SIDE
        ================================= */

        .login-form-container {

            padding: 60px 50px;

            display: flex;

            flex-direction: column;

            justify-content: center;

        }


        .login-form-container h2 {

            font-size: 32px;

            color: #222;

            margin-bottom: 8px;

        }


        .login-subtitle {

            color: #777;

            margin-bottom: 30px;

        }


        /* ================================
           ERROR MESSAGE
        ================================= */

        .login-error {

            background: #fff1f1;

            color: #c62828;

            border: 1px solid #ffcaca;

            padding: 12px 15px;

            border-radius: 8px;

            margin-bottom: 20px;

            font-size: 14px;

        }


        /* ================================
           INPUTS
        ================================= */

        .input-group {

            margin-bottom: 20px;

        }


        .input-group label {

            display: block;

            font-size: 14px;

            font-weight: 600;

            color: #333;

            margin-bottom: 8px;

        }


        .input-wrapper {

            position: relative;

        }


        .input-icon {

            position: absolute;

            left: 15px;

            top: 50%;

            transform: translateY(-50%);

            font-size: 18px;

        }


        .input-wrapper input {

            width: 100%;

            padding: 14px 15px 14px 45px;

            border: 1px solid #ddd;

            border-radius: 9px;

            outline: none;

            font-size: 15px;

            transition: 0.3s;

        }


        .input-wrapper input:focus {

            border-color: #243b6b;

            box-shadow:
                0 0 0 3px rgba(36, 59, 107, 0.1);

        }


        /* ================================
           LOGIN BUTTON
        ================================= */

        .login-submit {

            width: 100%;

            border: none;

            padding: 14px;

            border-radius: 9px;

            background: #243b6b;

            color: white;

            font-size: 16px;

            font-weight: bold;

            cursor: pointer;

            transition: 0.3s;

            margin-top: 5px;

        }


        .login-submit:hover {

            background: #18294c;

            transform: translateY(-2px);

            box-shadow:
                0 8px 20px rgba(36, 59, 107, 0.25);

        }


        /* ================================
           BACK HOME
        ================================= */

        .back-home {

            text-align: center;

            margin-top: 25px;

        }


        .back-home a {

            color: #243b6b;

            font-size: 14px;

            font-weight: 600;

        }


        .back-home a:hover {

            text-decoration: underline;

        }


        /* ================================
           MOBILE
        ================================= */

        @media (max-width: 750px) {

            .login-container {

                grid-template-columns: 1fr;

            }

            .login-welcome {

                padding: 40px 30px;

                text-align: center;

            }

            .login-form-container {

                padding: 40px 30px;

            }

            .login-welcome h1 {

                font-size: 30px;

            }

        }

    </style>

</head>


<body>


<!-- ================================
     NAVIGATION
================================= -->

<header class="navbar">

    <div class="logo">

        🏠 Hostel Management System

    </div>


    <nav>

        <a href="index.php">Home</a>

        <a href="index.php#about">About</a>

        <a href="index.php#features">Features</a>

    </nav>

</header>



<!-- ================================
     LOGIN SECTION
================================= -->

<section class="login-page">


    <div class="login-container">


        <!-- LEFT SIDE -->

        <div class="login-welcome">


            <div class="login-icon">

                🏠

            </div>


            <h1>

                Welcome Back!

            </h1>


            <p>

                Login to access your Hostel Management
                System account and manage your hostel
                activities easily.

            </p>


            <ul class="login-features">

                <li>✓ Manage hostel information</li>

                <li>✓ Check room allocation</li>

                <li>✓ Track hostel fees</li>

                <li>✓ Submit and manage complaints</li>

                <li>✓ Stay updated with notices</li>

            </ul>


        </div>



        <!-- RIGHT SIDE -->

        <div class="login-form-container">


            <h2>

                Sign In

            </h2>


            <p class="login-subtitle">

                Enter your credentials to continue.

            </p>



            <?php if ($message != ""): ?>

                <div class="login-error">

                    ⚠️ <?php echo htmlspecialchars($message); ?>

                </div>

            <?php endif; ?>



            <form method="POST" action="">


                <!-- USERNAME -->

                <div class="input-group">

                    <label for="username">

                        Username

                    </label>


                    <div class="input-wrapper">

                        <span class="input-icon">

                            👤

                        </span>


                        <input
                            type="text"
                            id="username"
                            name="username"
                            placeholder="Enter your username"
                            required
                            autocomplete="username"
                        >

                    </div>

                </div>



                <!-- PASSWORD -->

                <div class="input-group">

                    <label for="password">

                        Password

                    </label>


                    <div class="input-wrapper">

                        <span class="input-icon">

                            🔒

                        </span>


                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Enter your password"
                            required
                            autocomplete="current-password"
                        >

                    </div>

                </div>



                <!-- LOGIN BUTTON -->

                <button
                    type="submit"
                    class="login-submit"
                >

                    Login to Dashboard →

                </button>


            </form>



            <div class="back-home">

                <a href="index.php">

                    ← Back to Home

                </a>

            </div>


        </div>


    </div>


</section>


</body>

</html>