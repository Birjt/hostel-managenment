<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Hostel Management System</title>

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Segoe UI", Arial, sans-serif;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            background: #f8f7ff;
            color: #25223a;
        }

        /* ================= HEADER ================= */

        header {
            width: 100%;
            padding: 20px 7%;
            display: flex;
            justify-content: space-between;
            align-items: center;

            background: rgba(255,255,255,0.95);

            box-shadow: 0 4px 20px rgba(0,0,0,0.06);

            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .logo {
            font-size: 24px;
            font-weight: 800;
            color: #6c5ce7;
            letter-spacing: 0.5px;
        }

        nav {
            display: flex;
            gap: 30px;
            align-items: center;
        }

        nav a {
            text-decoration: none;
            color: #44405a;
            font-weight: 600;
            transition: 0.3s;
        }

        nav a:hover {
            color: #6c5ce7;
        }

        .login-nav {
            background: #6c5ce7;
            color: white !important;
            padding: 11px 24px;
            border-radius: 25px;
            box-shadow: 0 8px 20px rgba(108,92,231,0.25);
        }

        .login-nav:hover {
            background: #5848d6;
            color: white !important;
            transform: translateY(-2px);
        }

        /* ================= HERO ================= */

        .hero {
            min-height: 650px;

            display: flex;
            align-items: center;
            justify-content: center;

            text-align: center;

            padding: 80px 7%;

            background:
                radial-gradient(circle at top left, #e9e4ff, transparent 35%),
                radial-gradient(circle at bottom right, #dff8ff, transparent 35%),
                linear-gradient(135deg, #faf9ff, #f0f8ff);

            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: "";
            position: absolute;

            width: 300px;
            height: 300px;

            background: rgba(108,92,231,0.10);

            border-radius: 50%;

            top: -100px;
            right: -80px;
        }

        .hero::after {
            content: "";
            position: absolute;

            width: 250px;
            height: 250px;

            background: rgba(0,184,148,0.08);

            border-radius: 50%;

            bottom: -100px;
            left: -60px;
        }

        .hero-content {
            max-width: 850px;
            position: relative;
            z-index: 2;
        }

        .hero-icon {
            width: 90px;
            height: 90px;

            margin: 0 auto 25px;

            display: flex;
            justify-content: center;
            align-items: center;

            background: white;

            border-radius: 25px;

            font-size: 45px;

            box-shadow: 0 15px 40px rgba(108,92,231,0.15);
        }

        .hero h1 {
            font-size: 58px;
            line-height: 1.1;

            margin-bottom: 25px;

            background: linear-gradient(
                90deg,
                #6c5ce7,
                #8e7dff,
                #00b894
            );

            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero p {
            font-size: 20px;
            line-height: 1.7;

            color: #625f76;

            max-width: 700px;

            margin: 0 auto 35px;
        }

        .btn {
            display: inline-block;

            padding: 15px 32px;

            background: #6c5ce7;

            color: white;

            text-decoration: none;

            border-radius: 30px;

            font-size: 16px;
            font-weight: 700;

            box-shadow: 0 12px 25px rgba(108,92,231,0.28);

            transition: 0.3s;
        }

        .btn:hover {
            background: #5848d6;
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(108,92,231,0.35);
        }

        /* ================= STATS ================= */

        .stats {
            width: 86%;

            margin: -55px auto 80px;

            position: relative;
            z-index: 10;

            display: grid;

            grid-template-columns: repeat(4, 1fr);

            background: white;

            border-radius: 25px;

            box-shadow: 0 15px 45px rgba(40,35,80,0.10);

            overflow: hidden;
        }

        .stat {
            padding: 30px 20px;
            text-align: center;

            border-right: 1px solid #eeeaf7;
        }

        .stat:last-child {
            border-right: none;
        }

        .stat-icon {
            font-size: 28px;
            margin-bottom: 8px;
        }

        .stat h3 {
            color: #6c5ce7;
            font-size: 25px;
            margin-bottom: 5px;
        }

        .stat p {
            color: #77738b;
            font-size: 14px;
        }

        /* ================= ABOUT ================= */

        .about {
            padding: 70px 7% 100px;

            text-align: center;

            background: white;
        }

        .section-tag {
            color: #6c5ce7;
            font-size: 14px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 2px;

            margin-bottom: 10px;
        }

        .about h2 {
            font-size: 38px;
            margin-bottom: 18px;
            color: #292640;
        }

        .about-intro {
            max-width: 700px;
            margin: auto;

            color: #706c80;

            line-height: 1.7;
            font-size: 17px;
        }

        /* ================= FEATURES ================= */

        .features {
            max-width: 1100px;

            margin: 55px auto 0;

            display: grid;

            grid-template-columns: repeat(4, 1fr);

            gap: 25px;
        }

        .feature {
            background: #faf9ff;

            padding: 35px 25px;

            border-radius: 22px;

            border: 1px solid #eeeaff;

            transition: 0.3s;

            text-align: left;
        }

        .feature:hover {
            transform: translateY(-8px);

            box-shadow: 0 15px 35px rgba(108,92,231,0.12);

            background: white;
        }

        .feature-icon {
            width: 55px;
            height: 55px;

            display: flex;
            justify-content: center;
            align-items: center;

            background: #eeeaff;

            border-radius: 16px;

            font-size: 27px;

            margin-bottom: 20px;
        }

        .feature h3 {
            font-size: 19px;
            margin-bottom: 10px;

            color: #302c49;
        }

        .feature p {
            color: #77738b;
            line-height: 1.6;
            font-size: 14px;
        }

        /* ================= HOW IT WORKS ================= */

        .how-it-works {
            padding: 90px 7%;

            background: linear-gradient(
                135deg,
                #f4f1ff,
                #f0fbff
            );

            text-align: center;
        }

        .how-it-works h2 {
            font-size: 38px;
            margin-bottom: 50px;
        }

        .steps {
            max-width: 1000px;

            margin: auto;

            display: grid;

            grid-template-columns: repeat(3, 1fr);

            gap: 30px;
        }

        .step {
            background: white;

            padding: 35px 25px;

            border-radius: 22px;

            box-shadow: 0 10px 30px rgba(40,35,80,0.07);
        }

        .step-number {
            width: 50px;
            height: 50px;

            margin: 0 auto 20px;

            display: flex;
            justify-content: center;
            align-items: center;

            background: #6c5ce7;

            color: white;

            border-radius: 50%;

            font-weight: 800;
            font-size: 18px;
        }

        .step h3 {
            margin-bottom: 10px;
        }

        .step p {
            color: #77738b;
            line-height: 1.6;
            font-size: 14px;
        }

        /* ================= CTA ================= */

        .cta {
            padding: 90px 7%;

            text-align: center;

            background: white;
        }

        .cta-box {
            max-width: 900px;

            margin: auto;

            padding: 60px 30px;

            border-radius: 30px;

            background: linear-gradient(
                135deg,
                #6c5ce7,
                #8e7dff
            );

            color: white;

            box-shadow: 0 20px 45px rgba(108,92,231,0.25);
        }

        .cta-box h2 {
            font-size: 36px;
            margin-bottom: 15px;
        }

        .cta-box p {
            font-size: 16px;
            opacity: 0.9;

            margin-bottom: 30px;
        }

        .cta-btn {
            display: inline-block;

            background: white;

            color: #6c5ce7;

            padding: 14px 30px;

            border-radius: 30px;

            text-decoration: none;

            font-weight: 800;

            transition: 0.3s;
        }

        .cta-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }

        /* ================= FOOTER ================= */

        footer {
            background: #242139;

            color: white;

            padding: 35px 7%;

            text-align: center;
        }

        footer .footer-logo {
            font-size: 21px;
            font-weight: 800;

            margin-bottom: 10px;
        }

        footer p {
            color: #b8b4c9;
            font-size: 14px;
        }

        /* ================= RESPONSIVE ================= */

        @media (max-width: 900px) {

            .hero h1 {
                font-size: 45px;
            }

            .features {
                grid-template-columns: repeat(2, 1fr);
            }

            .stats {
                grid-template-columns: repeat(2, 1fr);
            }

            .stat:nth-child(2) {
                border-right: none;
            }

            .steps {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 600px) {

            header {
                padding: 18px 5%;
            }

            .logo {
                font-size: 18px;
            }

            nav {
                gap: 12px;
            }

            nav a {
                font-size: 13px;
            }

            .login-nav {
                padding: 8px 15px;
            }

            .hero {
                min-height: 580px;
                padding: 60px 5%;
            }

            .hero h1 {
                font-size: 38px;
            }

            .hero p {
                font-size: 16px;
            }

            .stats {
                width: 90%;
            }

            .features {
                grid-template-columns: 1fr;
            }

            .about h2,
            .how-it-works h2 {
                font-size: 30px;
            }

            .cta-box h2 {
                font-size: 28px;
            }
        }

    </style>

</head>


<body>


<!-- ================= HEADER ================= -->

<header>

    <div class="logo">
        🏠 HostelHub
    </div>

    <nav>

        <a href="index.php">
            Home
        </a>

        <a href="#about">
            About
        </a>

        <a href="#features">
            Features
        </a>

        <a href="login.php" class="login-nav">
            Login
        </a>

    </nav>

</header>


<!-- ================= HERO ================= -->

<section class="hero">

    <div class="hero-content">

        <div class="hero-icon">
            🏠
        </div>

        <h1>
            Smart Hostel<br>
            Management System
        </h1>

        <p>
            Manage students, rooms, fees, complaints and hostel
            activities easily — all from one simple and organized system.
        </p>

        <a href="login.php" class="btn">
            🔐 Login to System
        </a>

    </div>

</section>


<!-- ================= STATS ================= -->

<section class="stats">

    <div class="stat">

        <div class="stat-icon">
            👨‍🎓
        </div>

        <h3>
            Students
        </h3>

        <p>
            Easy student management
        </p>

    </div>


    <div class="stat">

        <div class="stat-icon">
            🛏️
        </div>

        <h3>
            Rooms
        </h3>

        <p>
            Simple room allocation
        </p>

    </div>


    <div class="stat">

        <div class="stat-icon">
            💰
        </div>

        <h3>
            Fees
        </h3>

        <p>
            Track hostel payments
        </p>

    </div>


    <div class="stat">

        <div class="stat-icon">
            📝
        </div>

        <h3>
            Complaints
        </h3>

        <p>
            Manage complaints easily
        </p>

    </div>

</section>


<!-- ================= ABOUT ================= -->

<section class="about" id="about">

    <div class="section-tag">
        About The System
    </div>

    <h2>
        Everything You Need in One Place
    </h2>

    <p class="about-intro">

        The Hostel Management System is designed to make hostel
        administration faster, easier and more organized.
        Administrators can manage hostel operations while students
        can view their personal information, room details and fees.

    </p>


    <!-- FEATURES -->

    <div class="features" id="features">


        <div class="feature">

            <div class="feature-icon">
                👨‍🎓
            </div>

            <h3>
                Student Management
            </h3>

            <p>
                Add, update and manage student records including
                name, email, phone, course and year.
            </p>

        </div>


        <div class="feature">

            <div class="feature-icon">
                🛏️
            </div>

            <h3>
                Room Management
            </h3>

            <p>
                Create hostel rooms, manage room capacity and
                allocate rooms to students.
            </p>

        </div>


        <div class="feature">

            <div class="feature-icon">
                💰
            </div>

            <h3>
                Fee Management
            </h3>

            <p>
                Keep track of hostel fees, due dates, payments
                and pending amounts.
            </p>

        </div>


        <div class="feature">

            <div class="feature-icon">
                📝
            </div>

            <h3>
                Complaint Management
            </h3>

            <p>
                Students can submit complaints and administrators
                can monitor and resolve them.
            </p>

        </div>


    </div>

</section>


<!-- ================= HOW IT WORKS ================= -->

<section class="how-it-works">

    <div class="section-tag">
        Simple Process
    </div>

    <h2>
        How It Works
    </h2>


    <div class="steps">


        <div class="step">

            <div class="step-number">
                1
            </div>

            <h3>
                Login
            </h3>

            <p>
                Administrators and students log in using their
                registered accounts.
            </p>

        </div>


        <div class="step">

            <div class="step-number">
                2
            </div>

            <h3>
                Manage
            </h3>

            <p>
                Administrators can manage students, rooms,
                fees and hostel activities.
            </p>

        </div>


        <div class="step">

            <div class="step-number">
                3
            </div>

            <h3>
                Access
            </h3>

            <p>
                Students can easily view their hostel information
                from their dashboard.
            </p>

        </div>


    </div>

</section>


<!-- ================= CALL TO ACTION ================= -->

<section class="cta">

    <div class="cta-box">

        <h2>
            Ready to Manage Your Hostel?
        </h2>

        <p>
            Access the Hostel Management System and manage
            everything from one convenient dashboard.
        </p>

        <a href="login.php" class="cta-btn">
            Login to System →
        </a>

    </div>

</section>


<!-- ================= FOOTER ================= -->

<footer>

    <div class="footer-logo">
        🏠 HostelHub
    </div>

    <p>
        Hostel Management System
    </p>

    <p>
        ©️ 2026 Hostel Management System. All Rights Reserved.
    </p>

</footer>


</body>

</html>