<?php
session_start();
// Display session message, if any
if (isset($_SESSION['message'])) {
    echo "<div class='message'>" . htmlspecialchars($_SESSION['message']) . "</div>";
    unset($_SESSION['message']); // Clear the message after displaying
}
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "staticweb";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Include PHPMailer
require __DIR__ . '/vendor/phpmailer/phpmailer/src/Exception.php';
require __DIR__ . '/vendor/phpmailer/phpmailer/src/PHPMailer.php';
require __DIR__ . '/vendor/phpmailer/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Handle Login
if (isset($_POST['login'])) {
    $email = $conn->real_escape_string($_POST['email']);
    $password = $conn->real_escape_string($_POST['password']);

    $sql = "SELECT * FROM users WHERE email = '$email'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Check if verified
        if ($user['is_verified'] == 0) {
            $error = "Please verify your email before logging in.";
        } else {
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Extract short name
                $name_parts = explode(' ', $user['fullname']);
                $short_name = $name_parts[0]; // Default to first name

                if (in_array("BIN", $name_parts) || in_array("BINTI", $name_parts)) {
                    $index = array_search("BIN", $name_parts) ?: array_search("BINTI", $name_parts);
                    $short_name = implode(' ', array_slice($name_parts, 0, $index));
                }

                $_SESSION['username'] = $short_name;
                $_SESSION['logged_in'] = true;
                header("Location: index.php");
                exit();
            } else {
                $error = "Incorrect password.";
            }
        }
    } else {
        $error = "No user found with this email.";
    }

    if (isset($error)) {
        echo "<script>alert('$error');</script>";
    }
}

// Handle Signup
if (isset($_POST['signup'])) {
    $fullname = $conn->real_escape_string(trim($_POST['fullname']));
    $email = $conn->real_escape_string(trim($_POST['email']));
    $password = $conn->real_escape_string(trim($_POST['password']));
    $confirm_password = $conn->real_escape_string(trim($_POST['confirm_password']));

    if (empty($fullname) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } else {
        $check_email_query = "SELECT * FROM users WHERE email = '$email'";
        $check_email_result = $conn->query($check_email_query);

        if ($check_email_result->num_rows > 0) {
            $error = "Email is already registered. Please log in.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $verification_token = bin2hex(random_bytes(16));

            $sql = "INSERT INTO users (fullname, email, password, verification_token, is_verified) 
                    VALUES ('$fullname', '$email', '$hashed_password', '$verification_token', 0)";
            if ($conn->query($sql) === TRUE) {
                $verification_link = "http://localhost/LAB%20ICT600/STATIC_WEB_PROJECT/verify.php?token=$verification_token";

                try {
                    $mail = new PHPMailer(true);
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'solehinahmad948@gmail.com';
                    $mail->Password = 'ihbufvjooctrsrea';
                    $mail->SMTPSecure = 'ssl'; // Or PHPMailer::ENCRYPTION_STARTTLS
                    $mail->Port = 465; // 587 for SSL

                    $mail->setFrom('no-reply@gmail.com', 'Cikgu Ros');
                    $mail->addAddress($email, $fullname);
                    $mail->isHTML(true);
                    $mail->Subject = "Verify Your Email Address";
                    $mail->Body = "
                        <h2>Hi $fullname,</h2>
                        <p>Thank you for registering. Please verify your email address:</p>
                        <a href='$verification_link'>$verification_link</a>
                        <p>If you did not register, please ignore this email.</p>
                    ";

                    $mail->send();
                    $_SESSION['message'] = "Sign-up successful. Check your email to verify your account.";
                    header("Location: verify_reminder.php");
                    exit();
                } catch (Exception $e) {
                    $error = "Sign-up successful, but verification email could not be sent.";
                }
            } else {
                $error = "Error: " . $conn->error;
            }
        }
    }

    if (isset($error)) {
        echo "<script>alert('$error');</script>";
    }
}

// Handle Logout
if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html class="sp-html 
			sp-seedprod sp-h-full" dir="ltr" lang="en-US" prefix="og: https://ogp.me/ns#">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <!-- Default CSS -->
        <link rel='stylesheet' id='seedprod-css-css' href='https://cikgurostuition.com/wp-content/plugins/seedprod-coming-soon-pro-5/public/css/tailwind.min.css?ver=6.18.3' type='text/css' media='all'/>
        <link rel='stylesheet' id='seedprod-fontawesome-css' href='https://cikgurostuition.com/wp-content/plugins/seedprod-coming-soon-pro-5/public/fontawesome/css/all.min.css?ver=6.18.3' type='text/css' media='all'/>
        <link rel='stylesheet' href='https://cikgurostuition.com/wp-content/plugins/seedprod-coming-soon-pro-5/public/css/animate.css?ver=6.18.3' type='text/css' media='all'/>
        <link rel="stylesheet" id='seedprod-gallerylightbox-css' href="https://cikgurostuition.com/wp-content/plugins/seedprod-coming-soon-pro-5/public/css/seedprod-gallery-block.min.css?ver=6.18.3" type='text/css' media='all'/>
        <!-- Google Font -->
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto:400|Passion+One:400&#038;display=swap">
        <!-- Global Styles -->
        <link rel="stylesheet" href="css/navbar.css">
        <link rel="stylesheet" href="assets/css/styles.css">
        <!--=============== REMIXICONS ===============-->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/3.5.0/remixicon.css">
        

        <!--=============== CSS ===============-->
        <link rel="stylesheet" href="assets/css/styles.css">
        <style>
            #sp-page {
                color: #545454
            }

            #sp-page .sp-header-tag-h1,#sp-page .sp-header-tag-h2,#sp-page .sp-header-tag-h3,#sp-page .sp-header-tag-h4,#sp-page .sp-header-tag-h5,#sp-page .sp-header-tag-h6 {
                color: #000000
            }

            #sp-page h1,#sp-page h2,#sp-page h3,#sp-page h4,#sp-page h5,#sp-page h6 {
                color: #000000;
                font-family: 'Passion One';
                font-weight: 400;
                font-style: normal
            }

            #sp-page a {
                color: #D60000
            }

            #sp-page a:hover {
                color: #a30000
            }

            #sp-page .btn {
                background-color: #D60000
            }

            body {
                background-color: #FFFFFF !important;
                background-image: ;
            }

            @media only screen and (max-width: 480px) {
                #sp-d4mo2r {
                    height:40px !important;
                }

                #sp-xx0axb {
                    height: 5px !important;
                }

                .sp-headline-block-v2qwxf, #sp-v2qwxf, #v2qwxf {
                    text-align: center !important;
                }

                .sp-headline-block-rm2ei4, #sp-rm2ei4, #rm2ei4 {
                    text-align: center !important;
                }

                #sp-button-parent-bhr0x8 {
                    text-align: center !important;
                }

                #sp-sgttpm {
                    height: 40px !important;
                }

                #sp-k6lhqf {
                    text-align: center !important;
                }

                .sp-headline-block-jqkyv9, #sp-jqkyv9, #jqkyv9 {
                    text-align: center !important;
                }

                .sp-headline-block-d40p0p, #sp-d40p0p, #d40p0p {
                    text-align: center !important;
                }

                #sp-k3gfhh {
                    height: 40px !important;
                }

                #sp-d1gzrd {
                    text-align: center !important;
                }

                .sp-headline-block-agwuga, #sp-agwuga, #agwuga {
                    text-align: center !important;
                }

                .sp-text-wrapper-a8li2l, #sp-a8li2l, #a8li2l {
                    text-align: left !important;
                }

                .sp-headline-block-tv9gae, #sp-tv9gae, #tv9gae {
                    text-align: center !important;
                }

                .sp-headline-block-fz31q6, #sp-fz31q6, #fz31q6 {
                    text-align: center !important;
                }

                .sp-text-wrapper-ttcm8s, #sp-ttcm8s, #ttcm8s {
                    text-align: left !important;
                }

                .sp-headline-block-s6hikx, #sp-s6hikx, #s6hikx {
                    text-align: center !important;
                }

                .sp-text-wrapper-ogpcss, #sp-ogpcss, #ogpcss {
                    text-align: left !important;
                }

                .sp-headline-block-ogqmnu, #sp-ogqmnu, #ogqmnu {
                    text-align: center !important;
                }

                .sp-text-wrapper-efxurn, #sp-efxurn, #efxurn {
                    text-align: left !important;
                }

                .sp-headline-block-bhttaz, #sp-bhttaz, #bhttaz {
                    text-align: center !important;
                }

                .sp-text-wrapper-q5o0p7, #sp-q5o0p7, #q5o0p7 {
                    text-align: left !important;
                }

                .sp-headline-block-seq4om, #sp-seq4om, #seq4om {
                    text-align: center !important;
                }

                .sp-text-wrapper-guwbs5, #sp-guwbs5, #guwbs5 {
                    text-align: center !important;
                }

                #sp-sta3fk {
                    margin: 0px 0px 0px 0px !important;
                }

                .sp-headline-block-df55r9, #sp-df55r9, #df55r9 {
                    text-align: center !important;
                }

                .sp-text-wrapper-jj5xur, #sp-jj5xur, #jj5xur {
                    text-align: center !important;
                }

                .sp-headline-block-aoc0yv, #sp-aoc0yv, #aoc0yv {
                    text-align: center !important;
                }

                .sp-text-wrapper-ejh871, #sp-ejh871, #ejh871 {
                    text-align: center !important;
                }

                .sp-headline-block-z42i54, #sp-z42i54, #z42i54 {
                    text-align: center !important;
                }

                .sp-text-wrapper-ut25ha, #sp-ut25ha, #ut25ha {
                    text-align: center !important;
                }

                .sp-headline-block-f3q2fd, #sp-f3q2fd, #f3q2fd {
                    text-align: center !important;
                }

                .sp-text-wrapper-k9syjk, #sp-k9syjk, #k9syjk {
                    text-align: center !important;
                }

                .sp-headline-block-hi1kex, #sp-hi1kex, #hi1kex {
                    text-align: center !important;
                }

                .sp-headline-block-wqno76, #sp-wqno76, #wqno76 {
                    text-align: center !important;
                }

                .sp-text-wrapper-w6fspe, #sp-w6fspe, #w6fspe {
                    text-align: center !important;
                }

                .sp-headline-block-olxwqravzrp, #sp-olxwqravzrp, #olxwqravzrp {
                    text-align: center !important;
                }

                .sp-text-wrapper-efqg7q, #sp-efqg7q, #efqg7q {
                    text-align: center !important;
                }

                #sp-z883cof25x3a {
                    height: 40px !important;
                }

                #sp-button-parent-xftguxtwztxn {
                    text-align: center !important;
                }

                .sp-text-wrapper-y1udla, #sp-y1udla, #y1udla {
                    text-align: center !important;
                }
            }

            @media only screen and (min-width: 481px) and (max-width: 1024px) {
                #sp-vw3kvy {
                    padding:0px 0px 0px 0px !important;
                }

                #sp-g0gdbc {
                    padding: 0px 0px 0px 0px !important;
                }

                #sp-d4mo2r {
                    height: 10px !important;
                }

                #sp-xx0axb {
                    height: 10px !important;
                }

                .sp-headline-block-v2qwxf, #sp-v2qwxf, #v2qwxf {
                    text-align: center !important;
                }

                .sp-headline-block-rm2ei4, #sp-rm2ei4, #rm2ei4 {
                    font-size: 20px !important;
                    text-align: center !important;
                }

                #sp-button-parent-bhr0x8 {
                    text-align: center !important;
                }

                #sp-sgttpm {
                    height: 10px !important;
                }

                #sp-k6lhqf {
                    text-align: center !important;
                }

                .sp-headline-block-jqkyv9, #sp-jqkyv9, #jqkyv9 {
                    text-align: center !important;
                }

                .sp-headline-block-d40p0p, #sp-d40p0p, #d40p0p {
                    text-align: center !important;
                }

                #sp-k3gfhh {
                    height: 10px !important;
                }

                #sp-d1gzrd {
                    text-align: center !important;
                }

                .sp-headline-block-agwuga, #sp-agwuga, #agwuga {
                    text-align: center !important;
                }

                .sp-text-wrapper-a8li2l, #sp-a8li2l, #a8li2l {
                    text-align: left !important;
                }

                .sp-headline-block-tv9gae, #sp-tv9gae, #tv9gae {
                    text-align: center !important;
                }

                .sp-headline-block-fz31q6, #sp-fz31q6, #fz31q6 {
                    text-align: center !important;
                }

                .sp-text-wrapper-ttcm8s, #sp-ttcm8s, #ttcm8s {
                    text-align: left !important;
                }

                .sp-headline-block-s6hikx, #sp-s6hikx, #s6hikx {
                    text-align: center !important;
                }

                .sp-text-wrapper-ogpcss, #sp-ogpcss, #ogpcss {
                    text-align: left !important;
                }

                .sp-headline-block-ogqmnu, #sp-ogqmnu, #ogqmnu {
                    text-align: center !important;
                }

                .sp-text-wrapper-efxurn, #sp-efxurn, #efxurn {
                    text-align: left !important;
                }

                .sp-headline-block-bhttaz, #sp-bhttaz, #bhttaz {
                    text-align: center !important;
                }

                .sp-text-wrapper-q5o0p7, #sp-q5o0p7, #q5o0p7 {
                    text-align: left !important;
                }

                .sp-headline-block-seq4om, #sp-seq4om, #seq4om {
                    text-align: center !important;
                }

                .sp-text-wrapper-guwbs5, #sp-guwbs5, #guwbs5 {
                    text-align: center !important;
                }

                .sp-headline-block-df55r9, #sp-df55r9, #df55r9 {
                    text-align: center !important;
                }

                .sp-text-wrapper-jj5xur, #sp-jj5xur, #jj5xur {
                    text-align: center !important;
                }

                .sp-headline-block-aoc0yv, #sp-aoc0yv, #aoc0yv {
                    text-align: center !important;
                }

                .sp-text-wrapper-ejh871, #sp-ejh871, #ejh871 {
                    text-align: center !important;
                }

                .sp-headline-block-z42i54, #sp-z42i54, #z42i54 {
                    text-align: center !important;
                }

                .sp-text-wrapper-ut25ha, #sp-ut25ha, #ut25ha {
                    text-align: center !important;
                }

                .sp-headline-block-f3q2fd, #sp-f3q2fd, #f3q2fd {
                    text-align: center !important;
                }

                .sp-text-wrapper-k9syjk, #sp-k9syjk, #k9syjk {
                    text-align: center !important;
                }

                .sp-headline-block-hi1kex, #sp-hi1kex, #hi1kex {
                    text-align: center !important;
                }

                .sp-headline-block-wqno76, #sp-wqno76, #wqno76 {
                    text-align: center !important;
                }

                .sp-text-wrapper-w6fspe, #sp-w6fspe, #w6fspe {
                    text-align: center !important;
                }

                .sp-headline-block-olxwqravzrp, #sp-olxwqravzrp, #olxwqravzrp {
                    text-align: center !important;
                }

                .sp-text-wrapper-efqg7q, #sp-efqg7q, #efqg7q {
                    text-align: center !important;
                }

                #sp-z883cof25x3a {
                    height: 10px !important;
                }

                #sp-button-parent-xftguxtwztxn {
                    text-align: center !important;
                }

                .sp-text-wrapper-y1udla, #sp-y1udla, #y1udla {
                    text-align: center !important;
                }
            }

            @media screen and (min-width: 1024px) {
            }
            .nav__user {
                margin-right: 1rem;
                font-weight: bold;
                font-size: 0.78rem;
                color: var(--title-color);
            }

            .nav__logout {
                background: none;
                border: none;
                font-weight: bold;
                color: #cf2e2e;
                cursor: pointer;
                transition: color 0.3s;
            }

            .nav__logout:hover {
                color: var(--title-color);
            }
        

        </style>
        <!-- JS -->
        <script>

            var seedprod_api_url = "https://cikgurostuition.com/wp-json/seedprod/v1/";
            var seeprod_enable_recaptcha = 0;
        </script>
        <script src="https://cikgurostuition.com/wp-content/plugins/seedprod-coming-soon-pro-5/public/js/animate-dynamic.js" defer></script>
        <script src="https://cikgurostuition.com/wp-content/plugins/seedprod-coming-soon-pro-5/public/js/sp-scripts.min.js" defer></script>
        <script src="https://cikgurostuition.com/wp-content/plugins/seedprod-coming-soon-pro-5/public/js/dynamic-text.js" defer></script>
        <script src="https://cikgurostuition.com/wp-content/plugins/seedprod-coming-soon-pro-5/public/js/tsparticles.min.js" defer></script>
        <meta name="viewport" content="width=device-width, initial-scale=1"/>
        <style>
            img:is([sizes="auto" i], [sizes^="auto," i]) {
                contain-intrinsic-size: 3000px 1500px
            }
        </style>
        <!-- All in One SEO 4.7.7 - aioseo.com -->
        <title>CikguRosTuition | Cikgu Tuisyen Akaun</title>
        <meta name="description" content="Hi! Saya Cikgu RosCikgu Tuisyen Pakar Subjek Akaun!Hubungi Saya!Tekan Untuk Whatsapp Saya!Perlukan Guru Tuisyen Untuk Anak Anda?Saya Boleh Bantu Anak Anda Kuasai Subjek Akaun Menerusi Kelas Tuisyen 1-to-1 Secara Online Ataupun OfflinePengalaman Lebih 20 Tahun Dalam Bidang PerakaunanSetelah bergraduasi di UUM pada tahun 1997 dalam Bidang Perakaunan (Ijazah Sarjana Muda Perakaunan dengan Kepujian), saya telah"/>
        <meta name="robots" content="max-image-preview:large"/>
        <link rel="canonical" href="https://cikgurostuition.com/"/>
        <meta name="generator" content="All in One SEO (AIOSEO) 4.7.7"/>
        <meta property="og:locale" content="en_US"/>
        <meta property="og:site_name" content="CikguRosTuition.com - Cikgu Pakar Subjek Akaun"/>
        <meta property="og:type" content="article"/>
        <meta property="og:title" content="CikguRosTuition | Cikgu Tuisyen Akaun"/>
        <meta property="og:description" content="Hi! Saya Cikgu RosCikgu Tuisyen Pakar Subjek Akaun!Hubungi Saya!Tekan Untuk Whatsapp Saya!Perlukan Guru Tuisyen Untuk Anak Anda?Saya Boleh Bantu Anak Anda Kuasai Subjek Akaun Menerusi Kelas Tuisyen 1-to-1 Secara Online Ataupun OfflinePengalaman Lebih 20 Tahun Dalam Bidang PerakaunanSetelah bergraduasi di UUM pada tahun 1997 dalam Bidang Perakaunan (Ijazah Sarjana Muda Perakaunan dengan Kepujian), saya telah"/>
        <meta property="og:url" content="https://cikgurostuition.com/"/>
        <meta property="og:image" content="https://cikgurostuition.com/wp-content/uploads/2024/06/1719135571694.jpg"/>
        <meta property="og:image:secure_url" content="https://cikgurostuition.com/wp-content/uploads/2024/06/1719135571694.jpg"/>
        <meta property="og:image:width" content="1024"/>
        <meta property="og:image:height" content="1024"/>
        <meta property="article:published_time" content="2024-06-23T03:25:00+00:00"/>
        <meta property="article:modified_time" content="2024-11-15T14:47:49+00:00"/>
        <meta name="twitter:card" content="summary_large_image"/>
        <meta name="twitter:title" content="CikguRosTuition | Cikgu Tuisyen Akaun"/>
        <meta name="twitter:description" content="Hi! Saya Cikgu RosCikgu Tuisyen Pakar Subjek Akaun!Hubungi Saya!Tekan Untuk Whatsapp Saya!Perlukan Guru Tuisyen Untuk Anak Anda?Saya Boleh Bantu Anak Anda Kuasai Subjek Akaun Menerusi Kelas Tuisyen 1-to-1 Secara Online Ataupun OfflinePengalaman Lebih 20 Tahun Dalam Bidang PerakaunanSetelah bergraduasi di UUM pada tahun 1997 dalam Bidang Perakaunan (Ijazah Sarjana Muda Perakaunan dengan Kepujian), saya telah"/>
        <meta name="twitter:image" content="https://cikgurostuition.com/wp-content/uploads/2024/06/1719135571694.jpg"/>
        <script type="application/ld+json" class="aioseo-schema">
            {
                "@context": "https:\/\/schema.org",
                "@graph": [
                    {
                        "@type": "BreadcrumbList",
                        "@id": "https:\/\/cikgurostuition.com\/#breadcrumblist",
                        "itemListElement": [
                            {
                                "@type": "ListItem",
                                "@id": "https:\/\/cikgurostuition.com\/#listItem",
                                "position": 1,
                                "name": "Home"
                            }
                        ]
                    },
                    {
                        "@type": "Organization",
                        "@id": "https:\/\/cikgurostuition.com\/#organization",
                        "name": "CikguRosTuition.com",
                        "description": "Cikgu Pakar Subjek Akaun",
                        "url": "https:\/\/cikgurostuition.com\/"
                    },
                    {
                        "@type": "WebPage",
                        "@id": "https:\/\/cikgurostuition.com\/#webpage",
                        "url": "https:\/\/cikgurostuition.com\/",
                        "name": "CikguRosTuition | Cikgu Tuisyen Akaun",
                        "description": "Hi! Saya Cikgu RosCikgu Tuisyen Pakar Subjek Akaun!Hubungi Saya!Tekan Untuk Whatsapp Saya!Perlukan Guru Tuisyen Untuk Anak Anda?Saya Boleh Bantu Anak Anda Kuasai Subjek Akaun Menerusi Kelas Tuisyen 1-to-1 Secara Online Ataupun OfflinePengalaman Lebih 20 Tahun Dalam Bidang PerakaunanSetelah bergraduasi di UUM pada tahun 1997 dalam Bidang Perakaunan (Ijazah Sarjana Muda Perakaunan dengan Kepujian), saya telah",
                        "inLanguage": "en-US",
                        "isPartOf": {
                            "@id": "https:\/\/cikgurostuition.com\/#website"
                        },
                        "breadcrumb": {
                            "@id": "https:\/\/cikgurostuition.com\/#breadcrumblist"
                        },
                        "datePublished": "2024-06-23T03:25:00+00:00",
                        "dateModified": "2024-11-15T14:47:49+00:00"
                    },
                    {
                        "@type": "WebSite",
                        "@id": "https:\/\/cikgurostuition.com\/#website",
                        "url": "https:\/\/cikgurostuition.com\/",
                        "name": "CikguRosTuition.com",
                        "description": "Cikgu Pakar Subjek Akaun",
                        "inLanguage": "en-US",
                        "publisher": {
                            "@id": "https:\/\/cikgurostuition.com\/#organization"
                        },
                        "potentialAction": {
                            "@type": "SearchAction",
                            "target": {
                                "@type": "EntryPoint",
                                "urlTemplate": "https:\/\/cikgurostuition.com\/?s={search_term_string}"
                            },
                            "query-input": "required name=search_term_string"
                        }
                    }
                ]
            }</script>
        <!-- All in One SEO -->
        <link rel="alternate" type="application/rss+xml" title="CikguRosTuition.com &raquo; Feed" href="https://cikgurostuition.com/feed/"/>
        <link rel="alternate" type="application/rss+xml" title="CikguRosTuition.com &raquo; Comments Feed" href="https://cikgurostuition.com/comments/feed/"/>
        <!-- This site uses the Google Analytics by MonsterInsights plugin v9.2.4 - Using Analytics tracking - https://www.monsterinsights.com/ -->
        <script src="//www.googletagmanager.com/gtag/js?id=G-P9R7L9K4XQ" data-cfasync="false" data-wpfc-render="false" async></script>
        <script data-cfasync="false" data-wpfc-render="false">
            var mi_version = '9.2.4';
            var mi_track_user = true;
            var mi_no_track_reason = '';
            var MonsterInsightsDefaultLocations = {
                "page_location": "https:\/\/cikgurostuition.com\/"
            };
            if (typeof MonsterInsightsPrivacyGuardFilter === 'function') {
                var MonsterInsightsLocations = (typeof MonsterInsightsExcludeQuery === 'object') ? MonsterInsightsPrivacyGuardFilter(MonsterInsightsExcludeQuery) : MonsterInsightsPrivacyGuardFilter(MonsterInsightsDefaultLocations);
            } else {
                var MonsterInsightsLocations = (typeof MonsterInsightsExcludeQuery === 'object') ? MonsterInsightsExcludeQuery : MonsterInsightsDefaultLocations;
            }

            var disableStrs = ['ga-disable-G-P9R7L9K4XQ', ];

            /* Function to detect opted out users */
            function __gtagTrackerIsOptedOut() {
                for (var index = 0; index < disableStrs.length; index++) {
                    if (document.cookie.indexOf(disableStrs[index] + '=true') > -1) {
                        return true;
                    }
                }

                return false;
            }

            /* Disable tracking if the opt-out cookie exists. */
            if (__gtagTrackerIsOptedOut()) {
                for (var index = 0; index < disableStrs.length; index++) {
                    window[disableStrs[index]] = true;
                }
            }

            /* Opt-out function */
            function __gtagTrackerOptout() {
                for (var index = 0; index < disableStrs.length; index++) {
                    document.cookie = disableStrs[index] + '=true; expires=Thu, 31 Dec 2099 23:59:59 UTC; path=/';
                    window[disableStrs[index]] = true;
                }
            }

            if ('undefined' === typeof gaOptout) {
                function gaOptout() {
                    __gtagTrackerOptout();
                }
            }
            window.dataLayer = window.dataLayer || [];

            window.MonsterInsightsDualTracker = {
                helpers: {},
                trackers: {},
            };
            if (mi_track_user) {
                function __gtagDataLayer() {
                    dataLayer.push(arguments);
                }

                function __gtagTracker(type, name, parameters) {
                    if (!parameters) {
                        parameters = {};
                    }

                    if (parameters.send_to) {
                        __gtagDataLayer.apply(null, arguments);
                        return;
                    }

                    if (type === 'event') {
                        parameters.send_to = monsterinsights_frontend.v4_id;
                        var hookName = name;
                        if (typeof parameters['event_category'] !== 'undefined') {
                            hookName = parameters['event_category'] + ':' + name;
                        }

                        if (typeof MonsterInsightsDualTracker.trackers[hookName] !== 'undefined') {
                            MonsterInsightsDualTracker.trackers[hookName](parameters);
                        } else {
                            __gtagDataLayer('event', name, parameters);
                        }

                    } else {
                        __gtagDataLayer.apply(null, arguments);
                    }
                }

                __gtagTracker('js', new Date());
                __gtagTracker('set', {
                    'developer_id.dZGIzZG': true,
                });
                if (MonsterInsightsLocations.page_location) {
                    __gtagTracker('set', MonsterInsightsLocations);
                }
                __gtagTracker('config', 'G-P9R7L9K4XQ', {
                    "forceSSL": "true",
                    "link_attribution": "true"
                });
                window.gtag = __gtagTracker;
                (function() {
                    /* https://developers.google.com/analytics/devguides/collection/analyticsjs/ */
                    /* ga and __gaTracker compatibility shim. */
                    var noopfn = function() {
                        return null;
                    };
                    var newtracker = function() {
                        return new Tracker();
                    };
                    var Tracker = function() {
                        return null;
                    };
                    var p = Tracker.prototype;
                    p.get = noopfn;
                    p.set = noopfn;
                    p.send = function() {
                        var args = Array.prototype.slice.call(arguments);
                        args.unshift('send');
                        __gaTracker.apply(null, args);
                    }
                    ;
                    var __gaTracker = function() {
                        var len = arguments.length;
                        if (len === 0) {
                            return;
                        }
                        var f = arguments[len - 1];
                        if (typeof f !== 'object' || f === null || typeof f.hitCallback !== 'function') {
                            if ('send' === arguments[0]) {
                                var hitConverted, hitObject = false, action;
                                if ('event' === arguments[1]) {
                                    if ('undefined' !== typeof arguments[3]) {
                                        hitObject = {
                                            'eventAction': arguments[3],
                                            'eventCategory': arguments[2],
                                            'eventLabel': arguments[4],
                                            'value': arguments[5] ? arguments[5] : 1,
                                        }
                                    }
                                }
                                if ('pageview' === arguments[1]) {
                                    if ('undefined' !== typeof arguments[2]) {
                                        hitObject = {
                                            'eventAction': 'page_view',
                                            'page_path': arguments[2],
                                        }
                                    }
                                }
                                if (typeof arguments[2] === 'object') {
                                    hitObject = arguments[2];
                                }
                                if (typeof arguments[5] === 'object') {
                                    Object.assign(hitObject, arguments[5]);
                                }
                                if ('undefined' !== typeof arguments[1].hitType) {
                                    hitObject = arguments[1];
                                    if ('pageview' === hitObject.hitType) {
                                        hitObject.eventAction = 'page_view';
                                    }
                                }
                                if (hitObject) {
                                    action = 'timing' === arguments[1].hitType ? 'timing_complete' : hitObject.eventAction;
                                    hitConverted = mapArgs(hitObject);
                                    __gtagTracker('event', action, hitConverted);
                                }
                            }
                            return;
                        }

                        function mapArgs(args) {
                            var arg, hit = {};
                            var gaMap = {
                                'eventCategory': 'event_category',
                                'eventAction': 'event_action',
                                'eventLabel': 'event_label',
                                'eventValue': 'event_value',
                                'nonInteraction': 'non_interaction',
                                'timingCategory': 'event_category',
                                'timingVar': 'name',
                                'timingValue': 'value',
                                'timingLabel': 'event_label',
                                'page': 'page_path',
                                'location': 'page_location',
                                'title': 'page_title',
                                'referrer': 'page_referrer',
                            };
                            for (arg in args) {
                                if (!(!args.hasOwnProperty(arg) || !gaMap.hasOwnProperty(arg))) {
                                    hit[gaMap[arg]] = args[arg];
                                } else {
                                    hit[arg] = args[arg];
                                }
                            }
                            return hit;
                        }

                        try {
                            f.hitCallback();
                        } catch (ex) {}
                    };
                    __gaTracker.create = newtracker;
                    __gaTracker.getByName = newtracker;
                    __gaTracker.getAll = function() {
                        return [];
                    }
                    ;
                    __gaTracker.remove = noopfn;
                    __gaTracker.loaded = true;
                    window['__gaTracker'] = __gaTracker;
                }
                )();
            } else {
                console.log("");
                (function() {
                    function __gtagTracker() {
                        return null;
                    }

                    window['__gtagTracker'] = __gtagTracker;
                    window['gtag'] = __gtagTracker;
                }
                )();
            }
        </script>
        <!-- / Google Analytics by MonsterInsights -->
        <script>
            window._wpemojiSettings = {
                "baseUrl": "https:\/\/s.w.org\/images\/core\/emoji\/15.0.3\/72x72\/",
                "ext": ".png",
                "svgUrl": "https:\/\/s.w.org\/images\/core\/emoji\/15.0.3\/svg\/",
                "svgExt": ".svg",
                "source": {
                    "concatemoji": "https:\/\/cikgurostuition.com\/wp-includes\/js\/wp-emoji-release.min.js?ver=6.7.1"
                }
            };
            /*! This file is auto-generated */
            !function(i, n) {
                var o, s, e;
                function c(e) {
                    try {
                        var t = {
                            supportTests: e,
                            timestamp: (new Date).valueOf()
                        };
                        sessionStorage.setItem(o, JSON.stringify(t))
                    } catch (e) {}
                }
                function p(e, t, n) {
                    e.clearRect(0, 0, e.canvas.width, e.canvas.height),
                    e.fillText(t, 0, 0);
                    var t = new Uint32Array(e.getImageData(0, 0, e.canvas.width, e.canvas.height).data)
                      , r = (e.clearRect(0, 0, e.canvas.width, e.canvas.height),
                    e.fillText(n, 0, 0),
                    new Uint32Array(e.getImageData(0, 0, e.canvas.width, e.canvas.height).data));
                    return t.every(function(e, t) {
                        return e === r[t]
                    })
                }
                function u(e, t, n) {
                    switch (t) {
                    case "flag":
                        return n(e, "\ud83c\udff3\ufe0f\u200d\u26a7\ufe0f", "\ud83c\udff3\ufe0f\u200b\u26a7\ufe0f") ? !1 : !n(e, "\ud83c\uddfa\ud83c\uddf3", "\ud83c\uddfa\u200b\ud83c\uddf3") && !n(e, "\ud83c\udff4\udb40\udc67\udb40\udc62\udb40\udc65\udb40\udc6e\udb40\udc67\udb40\udc7f", "\ud83c\udff4\u200b\udb40\udc67\u200b\udb40\udc62\u200b\udb40\udc65\u200b\udb40\udc6e\u200b\udb40\udc67\u200b\udb40\udc7f");
                    case "emoji":
                        return !n(e, "\ud83d\udc26\u200d\u2b1b", "\ud83d\udc26\u200b\u2b1b")
                    }
                    return !1
                }
                function f(e, t, n) {
                    var r = "undefined" != typeof WorkerGlobalScope && self instanceof WorkerGlobalScope ? new OffscreenCanvas(300,150) : i.createElement("canvas")
                      , a = r.getContext("2d", {
                        willReadFrequently: !0
                    })
                      , o = (a.textBaseline = "top",
                    a.font = "600 32px Arial",
                    {});
                    return e.forEach(function(e) {
                        o[e] = t(a, e, n)
                    }),
                    o
                }
                function t(e) {
                    var t = i.createElement("script");
                    t.src = e,
                    t.defer = !0,
                    i.head.appendChild(t)
                }
                "undefined" != typeof Promise && (o = "wpEmojiSettingsSupports",
                s = ["flag", "emoji"],
                n.supports = {
                    everything: !0,
                    everythingExceptFlag: !0
                },
                e = new Promise(function(e) {
                    i.addEventListener("DOMContentLoaded", e, {
                        once: !0
                    })
                }
                ),
                new Promise(function(t) {
                    var n = function() {
                        try {
                            var e = JSON.parse(sessionStorage.getItem(o));
                            if ("object" == typeof e && "number" == typeof e.timestamp && (new Date).valueOf() < e.timestamp + 604800 && "object" == typeof e.supportTests)
                                return e.supportTests
                        } catch (e) {}
                        return null
                    }();
                    if (!n) {
                        if ("undefined" != typeof Worker && "undefined" != typeof OffscreenCanvas && "undefined" != typeof URL && URL.createObjectURL && "undefined" != typeof Blob)
                            try {
                                var e = "postMessage(" + f.toString() + "(" + [JSON.stringify(s), u.toString(), p.toString()].join(",") + "));"
                                  , r = new Blob([e],{
                                    type: "text/javascript"
                                })
                                  , a = new Worker(URL.createObjectURL(r),{
                                    name: "wpTestEmojiSupports"
                                });
                                return void (a.onmessage = function(e) {
                                    c(n = e.data),
                                    a.terminate(),
                                    t(n)
                                }
                                )
                            } catch (e) {}
                        c(n = f(s, u, p))
                    }
                    t(n)
                }
                ).then(function(e) {
                    for (var t in e)
                        n.supports[t] = e[t],
                        n.supports.everything = n.supports.everything && n.supports[t],
                        "flag" !== t && (n.supports.everythingExceptFlag = n.supports.everythingExceptFlag && n.supports[t]);
                    n.supports.everythingExceptFlag = n.supports.everythingExceptFlag && !n.supports.flag,
                    n.DOMReady = !1,
                    n.readyCallback = function() {
                        n.DOMReady = !0
                    }
                }).then(function() {
                    return e
                }).then(function() {
                    var e;
                    n.supports.everything || (n.readyCallback(),
                    (e = n.source || {}).concatemoji ? t(e.concatemoji) : e.wpemoji && e.twemoji && (t(e.twemoji),
                    t(e.wpemoji)))
                }))
            }((window,
            document), window._wpemojiSettings);
        </script>
        <style id='wp-emoji-styles-inline-css'>
            img.wp-smiley, img.emoji {
                display: inline !important;
                border: none !important;
                box-shadow: none !important;
                height: 1em !important;
                width: 1em !important;
                margin: 0 0.07em !important;
                vertical-align: -0.1em !important;
                background: none !important;
                padding: 0 !important;
            }
        </style>
        <style id='wp-block-library-inline-css'>
            :root {
                --wp-admin-theme-color: #007cba;
                --wp-admin-theme-color--rgb: 0,124,186;
                --wp-admin-theme-color-darker-10: #006ba1;
                --wp-admin-theme-color-darker-10--rgb: 0,107,161;
                --wp-admin-theme-color-darker-20: #005a87;
                --wp-admin-theme-color-darker-20--rgb: 0,90,135;
                --wp-admin-border-width-focus: 2px;
                --wp-block-synced-color: #7a00df;
                --wp-block-synced-color--rgb: 122,0,223;
                --wp-bound-block-color: var(--wp-block-synced-color)
            }

            @media (min-resolution: 192dpi) {
                :root {
                    --wp-admin-border-width-focus:1.5px
                }
            }

            .wp-element-button {
                cursor: pointer
            }

            :root {
                --wp--preset--font-size--normal: 16px;
                --wp--preset--font-size--huge: 42px
            }

            :root .has-very-light-gray-background-color {
                background-color: #eee
            }

            :root .has-very-dark-gray-background-color {
                background-color: #313131
            }

            :root .has-very-light-gray-color {
                color: #eee
            }

            :root .has-very-dark-gray-color {
                color: #313131
            }

            :root .has-vivid-green-cyan-to-vivid-cyan-blue-gradient-background {
                background: linear-gradient(135deg,#00d084,#0693e3)
            }

            :root .has-purple-crush-gradient-background {
                background: linear-gradient(135deg,#34e2e4,#4721fb 50%,#ab1dfe)
            }

            :root .has-hazy-dawn-gradient-background {
                background: linear-gradient(135deg,#faaca8,#dad0ec)
            }

            :root .has-subdued-olive-gradient-background {
                background: linear-gradient(135deg,#fafae1,#67a671)
            }

            :root .has-atomic-cream-gradient-background {
                background: linear-gradient(135deg,#fdd79a,#004a59)
            }

            :root .has-nightshade-gradient-background {
                background: linear-gradient(135deg,#330968,#31cdcf)
            }

            :root .has-midnight-gradient-background {
                background: linear-gradient(135deg,#020381,#2874fc)
            }

            .has-regular-font-size {
                font-size: 1em
            }

            .has-larger-font-size {
                font-size: 2.625em
            }

            .has-normal-font-size {
                font-size: var(--wp--preset--font-size--normal)
            }

            .has-huge-font-size {
                font-size: var(--wp--preset--font-size--huge)
            }

            .has-text-align-center {
                text-align: center
            }

            .has-text-align-left {
                text-align: left
            }

            .has-text-align-right {
                text-align: right
            }

            #end-resizable-editor-section {
                display: none
            }

            .aligncenter {
                clear: both
            }

            .items-justified-left {
                justify-content: flex-start
            }

            .items-justified-center {
                justify-content: center
            }

            .items-justified-right {
                justify-content: flex-end
            }

            .items-justified-space-between {
                justify-content: space-between
            }

            .screen-reader-text {
                border: 0;
                clip: rect(1px,1px,1px,1px);
                clip-path: inset(50%);
                height: 1px;
                margin: -1px;
                overflow: hidden;
                padding: 0;
                position: absolute;
                width: 1px;
                word-wrap: normal!important
            }

            .screen-reader-text:focus {
                background-color: #ddd;
                clip: auto!important;
                clip-path: none;
                color: #444;
                display: block;
                font-size: 1em;
                height: auto;
                left: 5px;
                line-height: normal;
                padding: 15px 23px 14px;
                text-decoration: none;
                top: 5px;
                width: auto;
                z-index: 100000
            }

            html :where(.has-border-color) {
                border-style: solid
            }

            html :where([style*=border-top-color]) {
                border-top-style: solid
            }

            html :where([style*=border-right-color]) {
                border-right-style: solid
            }

            html :where([style*=border-bottom-color]) {
                border-bottom-style: solid
            }

            html :where([style*=border-left-color]) {
                border-left-style: solid
            }

            html :where([style*=border-width]) {
                border-style: solid
            }

            html :where([style*=border-top-width]) {
                border-top-style: solid
            }

            html :where([style*=border-right-width]) {
                border-right-style: solid
            }

            html :where([style*=border-bottom-width]) {
                border-bottom-style: solid
            }

            html :where([style*=border-left-width]) {
                border-left-style: solid
            }

            html :where(img[class*=wp-image-]) {
                height: auto;
                max-width: 100%
            }

            :where(figure) {
                margin: 0 0 1em
            }

            html :where(.is-position-sticky) {
                --wp-admin--admin-bar--position-offset: var(--wp-admin--admin-bar--height,0px)
            }

            @media screen and (max-width: 600px) {
                html :where(.is-position-sticky) {
                    --wp-admin--admin-bar--position-offset:0px
                }
            }
        </style>
        <style id='global-styles-inline-css'>
            :root {
                --wp--preset--aspect-ratio--square: 1;
                --wp--preset--aspect-ratio--4-3: 4/3;
                --wp--preset--aspect-ratio--3-4: 3/4;
                --wp--preset--aspect-ratio--3-2: 3/2;
                --wp--preset--aspect-ratio--2-3: 2/3;
                --wp--preset--aspect-ratio--16-9: 16/9;
                --wp--preset--aspect-ratio--9-16: 9/16;
                --wp--preset--color--black: #000000;
                --wp--preset--color--cyan-bluish-gray: #abb8c3;
                --wp--preset--color--white: #ffffff;
                --wp--preset--color--pale-pink: #f78da7;
                --wp--preset--color--vivid-red: #cf2e2e;
                --wp--preset--color--luminous-vivid-orange: #ff6900;
                --wp--preset--color--luminous-vivid-amber: #fcb900;
                --wp--preset--color--light-green-cyan: #7bdcb5;
                --wp--preset--color--vivid-green-cyan: #00d084;
                --wp--preset--color--pale-cyan-blue: #8ed1fc;
                --wp--preset--color--vivid-cyan-blue: #0693e3;
                --wp--preset--color--vivid-purple: #9b51e0;
                --wp--preset--color--base: #f9f9f9;
                --wp--preset--color--base-2: #ffffff;
                --wp--preset--color--contrast: #111111;
                --wp--preset--color--contrast-2: #636363;
                --wp--preset--color--contrast-3: #A4A4A4;
                --wp--preset--color--accent: #cfcabe;
                --wp--preset--color--accent-2: #c2a990;
                --wp--preset--color--accent-3: #d8613c;
                --wp--preset--color--accent-4: #b1c5a4;
                --wp--preset--color--accent-5: #b5bdbc;
                --wp--preset--gradient--vivid-cyan-blue-to-vivid-purple: linear-gradient(135deg,rgba(6,147,227,1) 0%,rgb(155,81,224) 100%);
                --wp--preset--gradient--light-green-cyan-to-vivid-green-cyan: linear-gradient(135deg,rgb(122,220,180) 0%,rgb(0,208,130) 100%);
                --wp--preset--gradient--luminous-vivid-amber-to-luminous-vivid-orange: linear-gradient(135deg,rgba(252,185,0,1) 0%,rgba(255,105,0,1) 100%);
                --wp--preset--gradient--luminous-vivid-orange-to-vivid-red: linear-gradient(135deg,rgba(255,105,0,1) 0%,rgb(207,46,46) 100%);
                --wp--preset--gradient--very-light-gray-to-cyan-bluish-gray: linear-gradient(135deg,rgb(238,238,238) 0%,rgb(169,184,195) 100%);
                --wp--preset--gradient--cool-to-warm-spectrum: linear-gradient(135deg,rgb(74,234,220) 0%,rgb(151,120,209) 20%,rgb(207,42,186) 40%,rgb(238,44,130) 60%,rgb(251,105,98) 80%,rgb(254,248,76) 100%);
                --wp--preset--gradient--blush-light-purple: linear-gradient(135deg,rgb(255,206,236) 0%,rgb(152,150,240) 100%);
                --wp--preset--gradient--blush-bordeaux: linear-gradient(135deg,rgb(254,205,165) 0%,rgb(254,45,45) 50%,rgb(107,0,62) 100%);
                --wp--preset--gradient--luminous-dusk: linear-gradient(135deg,rgb(255,203,112) 0%,rgb(199,81,192) 50%,rgb(65,88,208) 100%);
                --wp--preset--gradient--pale-ocean: linear-gradient(135deg,rgb(255,245,203) 0%,rgb(182,227,212) 50%,rgb(51,167,181) 100%);
                --wp--preset--gradient--electric-grass: linear-gradient(135deg,rgb(202,248,128) 0%,rgb(113,206,126) 100%);
                --wp--preset--gradient--midnight: linear-gradient(135deg,rgb(2,3,129) 0%,rgb(40,116,252) 100%);
                --wp--preset--gradient--gradient-1: linear-gradient(to bottom, #cfcabe 0%, #F9F9F9 100%);
                --wp--preset--gradient--gradient-2: linear-gradient(to bottom, #C2A990 0%, #F9F9F9 100%);
                --wp--preset--gradient--gradient-3: linear-gradient(to bottom, #D8613C 0%, #F9F9F9 100%);
                --wp--preset--gradient--gradient-4: linear-gradient(to bottom, #B1C5A4 0%, #F9F9F9 100%);
                --wp--preset--gradient--gradient-5: linear-gradient(to bottom, #B5BDBC 0%, #F9F9F9 100%);
                --wp--preset--gradient--gradient-6: linear-gradient(to bottom, #A4A4A4 0%, #F9F9F9 100%);
                --wp--preset--gradient--gradient-7: linear-gradient(to bottom, #cfcabe 50%, #F9F9F9 50%);
                --wp--preset--gradient--gradient-8: linear-gradient(to bottom, #C2A990 50%, #F9F9F9 50%);
                --wp--preset--gradient--gradient-9: linear-gradient(to bottom, #D8613C 50%, #F9F9F9 50%);
                --wp--preset--gradient--gradient-10: linear-gradient(to bottom, #B1C5A4 50%, #F9F9F9 50%);
                --wp--preset--gradient--gradient-11: linear-gradient(to bottom, #B5BDBC 50%, #F9F9F9 50%);
                --wp--preset--gradient--gradient-12: linear-gradient(to bottom, #A4A4A4 50%, #F9F9F9 50%);
                --wp--preset--font-size--small: 0.9rem;
                --wp--preset--font-size--medium: 0.95rem;
                --wp--preset--font-size--large: clamp(1.39rem, 1.39rem + ((1vw - 0.2rem) * 0.767), 1.85rem);
                --wp--preset--font-size--x-large: clamp(1.85rem, 1.85rem + ((1vw - 0.2rem) * 1.083), 2.5rem);
                --wp--preset--font-size--xx-large: clamp(2.5rem, 2.5rem + ((1vw - 0.2rem) * 1.283), 3.27rem);
                --wp--preset--font-family--body: "Inter", sans-serif;
                --wp--preset--font-family--heading: Cardo;
                --wp--preset--font-family--system-sans-serif: -apple-system, BlinkMacSystemFont, avenir next, avenir, segoe ui, helvetica neue, helvetica, Cantarell, Ubuntu, roboto, noto, arial, sans-serif;
                --wp--preset--font-family--system-serif: Iowan Old Style, Apple Garamond, Baskerville, Times New Roman, Droid Serif, Times, Source Serif Pro, serif, Apple Color Emoji, Segoe UI Emoji, Segoe UI Symbol;
                --wp--preset--spacing--20: min(1.5rem, 2vw);
                --wp--preset--spacing--30: min(2.5rem, 3vw);
                --wp--preset--spacing--40: min(4rem, 5vw);
                --wp--preset--spacing--50: min(6.5rem, 8vw);
                --wp--preset--spacing--60: min(10.5rem, 13vw);
                --wp--preset--spacing--70: 3.38rem;
                --wp--preset--spacing--80: 5.06rem;
                --wp--preset--spacing--10: 1rem;
                --wp--preset--shadow--natural: 6px 6px 9px rgba(0, 0, 0, 0.2);
                --wp--preset--shadow--deep: 12px 12px 50px rgba(0, 0, 0, 0.4);
                --wp--preset--shadow--sharp: 6px 6px 0px rgba(0, 0, 0, 0.2);
                --wp--preset--shadow--outlined: 6px 6px 0px -3px rgba(255, 255, 255, 1), 6px 6px rgba(0, 0, 0, 1);
                --wp--preset--shadow--crisp: 6px 6px 0px rgba(0, 0, 0, 1);
            }

            :root {
                --wp--style--global--content-size: 620px;
                --wp--style--global--wide-size: 1280px;
            }

            :where(body) {
                margin: 0;
            }

            .wp-site-blocks {
                padding-top: var(--wp--style--root--padding-top);
                padding-bottom: var(--wp--style--root--padding-bottom);
            }

            .has-global-padding {
                padding-right: var(--wp--style--root--padding-right);
                padding-left: var(--wp--style--root--padding-left);
            }

            .has-global-padding > .alignfull {
                margin-right: calc(var(--wp--style--root--padding-right) * -1);
                margin-left: calc(var(--wp--style--root--padding-left) * -1);
            }

            .has-global-padding :where(:not(.alignfull.is-layout-flow) > .has-global-padding:not(.wp-block-block, .alignfull)) {
                padding-right: 0;
                padding-left: 0;
            }

            .has-global-padding :where(:not(.alignfull.is-layout-flow) > .has-global-padding:not(.wp-block-block, .alignfull)) > .alignfull {
                margin-left: 0;
                margin-right: 0;
            }

            .wp-site-blocks > .alignleft {
                float: left;
                margin-right: 2em;
            }

            .wp-site-blocks > .alignright {
                float: right;
                margin-left: 2em;
            }

            .wp-site-blocks > .aligncenter {
                justify-content: center;
                margin-left: auto;
                margin-right: auto;
            }

            :where(.wp-site-blocks) > * {
                margin-block-start: 1.2rem; margin-block-end: 0; }

            :where(.wp-site-blocks) > :first-child {
                margin-block-start: 0; }

            :where(.wp-site-blocks) > :last-child {
                margin-block-end: 0; }

            :root {
                --wp--style--block-gap: 1.2rem;
            }

            :root :where(.is-layout-flow) > :first-child {
                margin-block-start: 0;}

            :root :where(.is-layout-flow) > :last-child {
                margin-block-end: 0;}

            :root :where(.is-layout-flow) > * {
                margin-block-start: 1.2rem;margin-block-end: 0;}

            :root :where(.is-layout-constrained) > :first-child {
                margin-block-start: 0;}

            :root :where(.is-layout-constrained) > :last-child {
                margin-block-end: 0;}

            :root :where(.is-layout-constrained) > * {
                margin-block-start: 1.2rem;margin-block-end: 0;}

            :root :where(.is-layout-flex) {
                gap: 1.2rem;
            }

            :root :where(.is-layout-grid) {
                gap: 1.2rem;
            }

            .is-layout-flow > .alignleft {
                float: left;
                margin-inline-start: 0;margin-inline-end: 2em;}

            .is-layout-flow > .alignright {
                float: right;
                margin-inline-start: 2em;margin-inline-end: 0;}

            .is-layout-flow > .aligncenter {
                margin-left: auto !important;
                margin-right: auto !important;
            }

            .is-layout-constrained > .alignleft {
                float: left;
                margin-inline-start: 0;margin-inline-end: 2em;}

            .is-layout-constrained > .alignright {
                float: right;
                margin-inline-start: 2em;margin-inline-end: 0;}

            .is-layout-constrained > .aligncenter {
                margin-left: auto !important;
                margin-right: auto !important;
            }

            .is-layout-constrained > :where(:not(.alignleft):not(.alignright):not(.alignfull)) {
                max-width: var(--wp--style--global--content-size);
                margin-left: auto !important;
                margin-right: auto !important;
            }

            .is-layout-constrained > .alignwide {
                max-width: var(--wp--style--global--wide-size);
            }

            body .is-layout-flex {
                display: flex;
            }

            .is-layout-flex {
                flex-wrap: wrap;
                align-items: center;
            }

            .is-layout-flex > :is(*, div) {
                margin: 0;
            }

            body .is-layout-grid {
                display: grid;
            }

            .is-layout-grid > :is(*, div) {
                margin: 0;
            }

            body {
                background-color: var(--wp--preset--color--base);
                color: var(--wp--preset--color--contrast);
                font-family: var(--wp--preset--font-family--body);
                font-size: var(--wp--preset--font-size--medium);
                font-style: normal;
                font-weight: 400;
                line-height: 1.55;
                --wp--style--root--padding-top: 0px;
                --wp--style--root--padding-right: var(--wp--preset--spacing--50);
                --wp--style--root--padding-bottom: 0px;
                --wp--style--root--padding-left: var(--wp--preset--spacing--50);
            }

            a:where(:not(.wp-element-button)) {
                color: var(--wp--preset--color--contrast);
                text-decoration: underline;
            }

            :root :where(a:where(:not(.wp-element-button)):hover) {
                text-decoration: none;
            }

            h1, h2, h3, h4, h5, h6 {
                color: var(--wp--preset--color--contrast);
                font-family: var(--wp--preset--font-family--heading);
                font-weight: 400;
                line-height: 1.2;
            }

            h1 {
                font-size: var(--wp--preset--font-size--xx-large);
                line-height: 1.15;
            }

            h2 {
                font-size: var(--wp--preset--font-size--x-large);
            }

            h3 {
                font-size: var(--wp--preset--font-size--large);
            }

            h4 {
                font-size: clamp(1.1rem, 1.1rem + ((1vw - 0.2rem) * 0.767), 1.5rem);
            }

            h5 {
                font-size: var(--wp--preset--font-size--medium);
            }

            h6 {
                font-size: var(--wp--preset--font-size--small);
            }

            :root :where(.wp-element-button, .wp-block-button__link) {
                background-color: var(--wp--preset--color--contrast);
                border-radius: .33rem;
                border-color: var(--wp--preset--color--contrast);
                border-width: 0;
                color: var(--wp--preset--color--base);
                font-family: inherit;
                font-size: var(--wp--preset--font-size--small);
                font-style: normal;
                font-weight: 500;
                line-height: inherit;
                padding-top: 0.6rem;
                padding-right: 1rem;
                padding-bottom: 0.6rem;
                padding-left: 1rem;
                text-decoration: none;
            }

            :root :where(.wp-element-button:hover, .wp-block-button__link:hover) {
                background-color: var(--wp--preset--color--contrast-2);
                border-color: var(--wp--preset--color--contrast-2);
                color: var(--wp--preset--color--base);
            }

            :root :where(.wp-element-button:focus, .wp-block-button__link:focus) {
                background-color: var(--wp--preset--color--contrast-2);
                border-color: var(--wp--preset--color--contrast-2);
                color: var(--wp--preset--color--base);
                outline-color: var(--wp--preset--color--contrast);
                outline-offset: 2px;
            }

            :root :where(.wp-element-button:active, .wp-block-button__link:active) {
                background-color: var(--wp--preset--color--contrast);
                color: var(--wp--preset--color--base);
            }

            :root :where(.wp-element-caption, .wp-block-audio figcaption, .wp-block-embed figcaption, .wp-block-gallery figcaption, .wp-block-image figcaption, .wp-block-table figcaption, .wp-block-video figcaption) {
                color: var(--wp--preset--color--contrast-2);
                font-family: var(--wp--preset--font-family--body);
                font-size: 0.8rem;
            }

            .has-black-color {
                color: var(--wp--preset--color--black) !important;
            }

            .has-cyan-bluish-gray-color {
                color: var(--wp--preset--color--cyan-bluish-gray) !important;
            }

            .has-white-color {
                color: var(--wp--preset--color--white) !important;
            }

            .has-pale-pink-color {
                color: var(--wp--preset--color--pale-pink) !important;
            }

            .has-vivid-red-color {
                color: var(--wp--preset--color--vivid-red) !important;
            }

            .has-luminous-vivid-orange-color {
                color: var(--wp--preset--color--luminous-vivid-orange) !important;
            }

            .has-luminous-vivid-amber-color {
                color: var(--wp--preset--color--luminous-vivid-amber) !important;
            }

            .has-light-green-cyan-color {
                color: var(--wp--preset--color--light-green-cyan) !important;
            }

            .has-vivid-green-cyan-color {
                color: var(--wp--preset--color--vivid-green-cyan) !important;
            }

            .has-pale-cyan-blue-color {
                color: var(--wp--preset--color--pale-cyan-blue) !important;
            }

            .has-vivid-cyan-blue-color {
                color: var(--wp--preset--color--vivid-cyan-blue) !important;
            }

            .has-vivid-purple-color {
                color: var(--wp--preset--color--vivid-purple) !important;
            }

            .has-base-color {
                color: var(--wp--preset--color--base) !important;
            }

            .has-base-2-color {
                color: var(--wp--preset--color--base-2) !important;
            }

            .has-contrast-color {
                color: var(--wp--preset--color--contrast) !important;
            }

            .has-contrast-2-color {
                color: var(--wp--preset--color--contrast-2) !important;
            }

            .has-contrast-3-color {
                color: var(--wp--preset--color--contrast-3) !important;
            }

            .has-accent-color {
                color: var(--wp--preset--color--accent) !important;
            }

            .has-accent-2-color {
                color: var(--wp--preset--color--accent-2) !important;
            }

            .has-accent-3-color {
                color: var(--wp--preset--color--accent-3) !important;
            }

            .has-accent-4-color {
                color: var(--wp--preset--color--accent-4) !important;
            }

            .has-accent-5-color {
                color: var(--wp--preset--color--accent-5) !important;
            }

            .has-black-background-color {
                background-color: var(--wp--preset--color--black) !important;
            }

            .has-cyan-bluish-gray-background-color {
                background-color: var(--wp--preset--color--cyan-bluish-gray) !important;
            }

            .has-white-background-color {
                background-color: var(--wp--preset--color--white) !important;
            }

            .has-pale-pink-background-color {
                background-color: var(--wp--preset--color--pale-pink) !important;
            }

            .has-vivid-red-background-color {
                background-color: var(--wp--preset--color--vivid-red) !important;
            }

            .has-luminous-vivid-orange-background-color {
                background-color: var(--wp--preset--color--luminous-vivid-orange) !important;
            }

            .has-luminous-vivid-amber-background-color {
                background-color: var(--wp--preset--color--luminous-vivid-amber) !important;
            }

            .has-light-green-cyan-background-color {
                background-color: var(--wp--preset--color--light-green-cyan) !important;
            }

            .has-vivid-green-cyan-background-color {
                background-color: var(--wp--preset--color--vivid-green-cyan) !important;
            }

            .has-pale-cyan-blue-background-color {
                background-color: var(--wp--preset--color--pale-cyan-blue) !important;
            }

            .has-vivid-cyan-blue-background-color {
                background-color: var(--wp--preset--color--vivid-cyan-blue) !important;
            }

            .has-vivid-purple-background-color {
                background-color: var(--wp--preset--color--vivid-purple) !important;
            }

            .has-base-background-color {
                background-color: var(--wp--preset--color--base) !important;
            }

            .has-base-2-background-color {
                background-color: var(--wp--preset--color--base-2) !important;
            }

            .has-contrast-background-color {
                background-color: var(--wp--preset--color--contrast) !important;
            }

            .has-contrast-2-background-color {
                background-color: var(--wp--preset--color--contrast-2) !important;
            }

            .has-contrast-3-background-color {
                background-color: var(--wp--preset--color--contrast-3) !important;
            }

            .has-accent-background-color {
                background-color: var(--wp--preset--color--accent) !important;
            }

            .has-accent-2-background-color {
                background-color: var(--wp--preset--color--accent-2) !important;
            }

            .has-accent-3-background-color {
                background-color: var(--wp--preset--color--accent-3) !important;
            }

            .has-accent-4-background-color {
                background-color: var(--wp--preset--color--accent-4) !important;
            }

            .has-accent-5-background-color {
                background-color: var(--wp--preset--color--accent-5) !important;
            }

            .has-black-border-color {
                border-color: var(--wp--preset--color--black) !important;
            }

            .has-cyan-bluish-gray-border-color {
                border-color: var(--wp--preset--color--cyan-bluish-gray) !important;
            }

            .has-white-border-color {
                border-color: var(--wp--preset--color--white) !important;
            }

            .has-pale-pink-border-color {
                border-color: var(--wp--preset--color--pale-pink) !important;
            }

            .has-vivid-red-border-color {
                border-color: var(--wp--preset--color--vivid-red) !important;
            }

            .has-luminous-vivid-orange-border-color {
                border-color: var(--wp--preset--color--luminous-vivid-orange) !important;
            }

            .has-luminous-vivid-amber-border-color {
                border-color: var(--wp--preset--color--luminous-vivid-amber) !important;
            }

            .has-light-green-cyan-border-color {
                border-color: var(--wp--preset--color--light-green-cyan) !important;
            }

            .has-vivid-green-cyan-border-color {
                border-color: var(--wp--preset--color--vivid-green-cyan) !important;
            }

            .has-pale-cyan-blue-border-color {
                border-color: var(--wp--preset--color--pale-cyan-blue) !important;
            }

            .has-vivid-cyan-blue-border-color {
                border-color: var(--wp--preset--color--vivid-cyan-blue) !important;
            }

            .has-vivid-purple-border-color {
                border-color: var(--wp--preset--color--vivid-purple) !important;
            }

            .has-base-border-color {
                border-color: var(--wp--preset--color--base) !important;
            }

            .has-base-2-border-color {
                border-color: var(--wp--preset--color--base-2) !important;
            }

            .has-contrast-border-color {
                border-color: var(--wp--preset--color--contrast) !important;
            }

            .has-contrast-2-border-color {
                border-color: var(--wp--preset--color--contrast-2) !important;
            }

            .has-contrast-3-border-color {
                border-color: var(--wp--preset--color--contrast-3) !important;
            }

            .has-accent-border-color {
                border-color: var(--wp--preset--color--accent) !important;
            }

            .has-accent-2-border-color {
                border-color: var(--wp--preset--color--accent-2) !important;
            }

            .has-accent-3-border-color {
                border-color: var(--wp--preset--color--accent-3) !important;
            }

            .has-accent-4-border-color {
                border-color: var(--wp--preset--color--accent-4) !important;
            }

            .has-accent-5-border-color {
                border-color: var(--wp--preset--color--accent-5) !important;
            }

            .has-vivid-cyan-blue-to-vivid-purple-gradient-background {
                background: var(--wp--preset--gradient--vivid-cyan-blue-to-vivid-purple) !important;
            }

            .has-light-green-cyan-to-vivid-green-cyan-gradient-background {
                background: var(--wp--preset--gradient--light-green-cyan-to-vivid-green-cyan) !important;
            }

            .has-luminous-vivid-amber-to-luminous-vivid-orange-gradient-background {
                background: var(--wp--preset--gradient--luminous-vivid-amber-to-luminous-vivid-orange) !important;
            }

            .has-luminous-vivid-orange-to-vivid-red-gradient-background {
                background: var(--wp--preset--gradient--luminous-vivid-orange-to-vivid-red) !important;
            }

            .has-very-light-gray-to-cyan-bluish-gray-gradient-background {
                background: var(--wp--preset--gradient--very-light-gray-to-cyan-bluish-gray) !important;
            }

            .has-cool-to-warm-spectrum-gradient-background {
                background: var(--wp--preset--gradient--cool-to-warm-spectrum) !important;
            }

            .has-blush-light-purple-gradient-background {
                background: var(--wp--preset--gradient--blush-light-purple) !important;
            }

            .has-blush-bordeaux-gradient-background {
                background: var(--wp--preset--gradient--blush-bordeaux) !important;
            }

            .has-luminous-dusk-gradient-background {
                background: var(--wp--preset--gradient--luminous-dusk) !important;
            }

            .has-pale-ocean-gradient-background {
                background: var(--wp--preset--gradient--pale-ocean) !important;
            }

            .has-electric-grass-gradient-background {
                background: var(--wp--preset--gradient--electric-grass) !important;
            }

            .has-midnight-gradient-background {
                background: var(--wp--preset--gradient--midnight) !important;
            }

            .has-gradient-1-gradient-background {
                background: var(--wp--preset--gradient--gradient-1) !important;
            }

            .has-gradient-2-gradient-background {
                background: var(--wp--preset--gradient--gradient-2) !important;
            }

            .has-gradient-3-gradient-background {
                background: var(--wp--preset--gradient--gradient-3) !important;
            }

            .has-gradient-4-gradient-background {
                background: var(--wp--preset--gradient--gradient-4) !important;
            }

            .has-gradient-5-gradient-background {
                background: var(--wp--preset--gradient--gradient-5) !important;
            }

            .has-gradient-6-gradient-background {
                background: var(--wp--preset--gradient--gradient-6) !important;
            }

            .has-gradient-7-gradient-background {
                background: var(--wp--preset--gradient--gradient-7) !important;
            }

            .has-gradient-8-gradient-background {
                background: var(--wp--preset--gradient--gradient-8) !important;
            }

            .has-gradient-9-gradient-background {
                background: var(--wp--preset--gradient--gradient-9) !important;
            }

            .has-gradient-10-gradient-background {
                background: var(--wp--preset--gradient--gradient-10) !important;
            }

            .has-gradient-11-gradient-background {
                background: var(--wp--preset--gradient--gradient-11) !important;
            }

            .has-gradient-12-gradient-background {
                background: var(--wp--preset--gradient--gradient-12) !important;
            }

            .has-small-font-size {
                font-size: var(--wp--preset--font-size--small) !important;
            }

            .has-medium-font-size {
                font-size: var(--wp--preset--font-size--medium) !important;
            }

            .has-large-font-size {
                font-size: var(--wp--preset--font-size--large) !important;
            }

            .has-x-large-font-size {
                font-size: var(--wp--preset--font-size--x-large) !important;
            }

            .has-xx-large-font-size {
                font-size: var(--wp--preset--font-size--xx-large) !important;
            }

            .has-body-font-family {
                font-family: var(--wp--preset--font-family--body) !important;
            }

            .has-heading-font-family {
                font-family: var(--wp--preset--font-family--heading) !important;
            }

            .has-system-sans-serif-font-family {
                font-family: var(--wp--preset--font-family--system-sans-serif) !important;
            }

            .has-system-serif-font-family {
                font-family: var(--wp--preset--font-family--system-serif) !important;
            }

            :where(.wp-site-blocks *:focus) {
                outline-width: 2px;
                outline-style: solid
            }
        </style>
        <style id='wp-block-template-skip-link-inline-css'>
            .skip-link.screen-reader-text {
                border: 0;
                clip: rect(1px,1px,1px,1px);
                clip-path: inset(50%);
                height: 1px;
                margin: -1px;
                overflow: hidden;
                padding: 0;
                position: absolute !important;
                width: 1px;
                word-wrap: normal !important;
            }

            .skip-link.screen-reader-text:focus {
                background-color: #eee;
                clip: auto !important;
                clip-path: none;
                color: #444;
                display: block;
                font-size: 1em;
                height: auto;
                left: 5px;
                line-height: normal;
                padding: 15px 23px 14px;
                text-decoration: none;
                top: 5px;
                width: auto;
                z-index: 100000;
            }
            #loginPopup {
                display: none;
                background: rgba(0, 0, 0, 0.5); /* Semi-transparent background */
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                z-index: 10000; /* High z-index to ensure it appears above other elements */
                display: flex;
                justify-content: center;
                align-items: center;
                padding: 0 15px;
            }

            #loginPopup .popup-content {
                background: white;
                padding: 20px;
                border-radius: 8px;
                text-align: center;
                box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.25);
            }

            #loginPopup h3 {
                margin-bottom: 10px;
                font-size: 1.5rem;
            }

            #loginPopup p {
                margin-bottom: 20px;
                font-size: 1rem;
            }

            #closePopup {
                background-color: gray;
                color: white;
                padding: 10px 20px;
                border: none;
                border-radius: 5px;
                cursor: pointer;
            }
            /* Scroll to Top Button */
            #scrollTopButton {
                display: none;
                position: fixed;
                bottom: 20px;
                right: 20px;
                z-index: 1000;
                background: rgba(127, 133, 138, 0.7); /* Semi-transparent background */
                color: white;
                border: none;
                border-radius: 12px; /* Rounded corners */
                width: 60px;
                height: 60px;
                font-size: 24px;
                cursor: pointer;
                box-shadow: 0px 4px 15px rgba(0, 0, 0, 0.3); /* Subtle shadow */
                backdrop-filter: blur(50px); /* Blur effect for the background */
                -webkit-backdrop-filter: blur(10px); /* Blur for Safari */
                transition: transform 0.2s ease, opacity 0.3s ease; /* Smooth hover effect */
            }

            #scrollTopButton:hover {
                transform: scale(1.1); /* Slightly enlarge on hover */
                background: rgba(103, 105, 111, 0.9); /* Darker on hover */
            }


        </style>
        <script src="https://cikgurostuition.com/wp-includes/js/jquery/jquery.min.js?ver=3.7.1" id="jquery-core-js"></script>
        <script src="https://cikgurostuition.com/wp-includes/js/jquery/jquery-migrate.min.js?ver=3.4.1" id="jquery-migrate-js"></script>
        <script src="https://cikgurostuition.com/wp-content/plugins/google-analytics-for-wordpress/assets/js/frontend-gtag.min.js?ver=9.2.4" id="monsterinsights-frontend-script-js" async data-wp-strategy="async"></script>
        <script data-cfasync="false" data-wpfc-render="false" id='monsterinsights-frontend-script-js-extra'>
            var monsterinsights_frontend = {
                "js_events_tracking": "true",
                "download_extensions": "doc,pdf,ppt,zip,xls,docx,pptx,xlsx",
                "inbound_paths": "[]",
                "home_url": "https:\/\/cikgurostuition.com",
                "hash_tracking": "false",
                "v4_id": "G-P9R7L9K4XQ"
            };
        </script>
        <link rel="https://api.w.org/" href="https://cikgurostuition.com/wp-json/"/>
        <link rel="alternate" title="JSON" type="application/json" href="https://cikgurostuition.com/wp-json/wp/v2/pages/46"/>
        <link rel="EditURI" type="application/rsd+xml" title="RSD" href="https://cikgurostuition.com/xmlrpc.php?rsd"/>
        <meta name="generator" content="WordPress 6.7.1"/>
        <link rel='shortlink' href='https://cikgurostuition.com/'/>
        <link rel="alternate" title="oEmbed (JSON)" type="application/json+oembed" href="https://cikgurostuition.com/wp-json/oembed/1.0/embed?url=https%3A%2F%2Fcikgurostuition.com%2F"/>
        <link rel="alternate" title="oEmbed (XML)" type="text/xml+oembed" href="https://cikgurostuition.com/wp-json/oembed/1.0/embed?url=https%3A%2F%2Fcikgurostuition.com%2F&#038;format=xml"/>
        <style class='wp-fonts-local'>
            @font-face {
                font-family: Inter;
                font-style: normal;
                font-weight: 300 900;
                font-display: fallback;
                src: url('https://cikgurostuition.com/wp-content/themes/twentytwentyfour/assets/fonts/inter/Inter-VariableFont_slnt,wght.woff2') format('woff2');
                font-stretch: normal;
            }

            @font-face {
                font-family: Cardo;
                font-style: normal;
                font-weight: 400;
                font-display: fallback;
                src: url('https://cikgurostuition.com/wp-content/themes/twentytwentyfour/assets/fonts/cardo/cardo_normal_400.woff2') format('woff2');
            }

            @font-face {
                font-family: Cardo;
                font-style: italic;
                font-weight: 400;
                font-display: fallback;
                src: url('https://cikgurostuition.com/wp-content/themes/twentytwentyfour/assets/fonts/cardo/cardo_italic_400.woff2') format('woff2');
            }

            @font-face {
                font-family: Cardo;
                font-style: normal;
                font-weight: 700;
                font-display: fallback;
                src: url('https://cikgurostuition.com/wp-content/themes/twentytwentyfour/assets/fonts/cardo/cardo_normal_700.woff2') format('woff2');
            }
        </style>
        <!-- Google tag (gtag.js) -->
        <script async src="https://www.googletagmanager.com/gtag/js?id=G-FSJNN4E0CD"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag() {
                dataLayer.push(arguments);
            }
            gtag('js', new Date());

            gtag('config', 'G-FSJNN4E0CD');
        </script>
    </head>
    <body class="spBgcover sp-h-full sp-antialiased sp-bg-slideshow">
    <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Responsive Navbar</title>
    <link rel="stylesheet" href="styles.css"> <!-- Link to external CSS -->
</head>
<body>
<header class="header" id="header">
    <nav class="nav container">
        <!-- Logo -->
        <div class="nav__logo" style="display: flex; align-items: center;">
            <img src="assets/img/cikguRos.jpg" alt="Cikgu Ros Logo" style="height: 50px; border-radius: 50px; vertical-align: middle;">
            <span style="margin-left: 10px; font-size: 16px; font-weight: bold; color: #333;">CikguRos</span>
        </div>

        <!-- Navigation Menu -->
        <div class="nav__menu" id="nav-menu">
            <ul class="nav__list">
                <li class="nav__item">
                    <a href="#" class="nav__link">Home</a>
                </li>
                <li class="nav__item">
                    <a href="#about-section" class="nav__link">About</a>
                </li>
                <li class="nav__item">
                    <a href="#contact-section" class="nav__link">Contact Me</a>
                </li>
            </ul>

            <!-- Close button -->
            <div class="nav__close" id="nav-close">
                <i class="ri-close-line"></i>
            </div>
        </div>

        <!-- Navigation Actions -->
        <div class="nav__actions">
            <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true): ?>
                <!-- Show username and logout -->
                <div class="nav__profile" style="display: flex; align-items: center;">
                    <span class="nav__user" style="margin-right: 10px;">Hi, <?= htmlspecialchars($_SESSION['username']); ?>!</span>
                    <i class="ri-logout-box-r-line" id="logout-icon" style="font-size: 20px; color: #FF0000; cursor: pointer;"></i>
                </div>
            <?php else: ?>
                <!-- Icons for search and login -->
                <i class=" nav__search" id="search-btn"></i>
                <i class="ri-user-line nav__login nav__item" id="login-btn"> Login</i>
            <?php endif; ?>

            <!-- Toggle button for mobile view -->
            <div class="nav__toggle" id="nav-toggle">
                <i class="ri-menu-line"></i>
            </div>
        </div>
    </nav>
</header>

<!-- Logout Confirmation Popup -->
<div id="logoutPopup" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 1000; justify-content: center; align-items: center;">
    <div style="background: white; padding: 20px; border-radius: 10px; text-align: center; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);">
        <h3 style="margin-bottom: 15px;">Confirm Logout</h3>
        <p style="margin-bottom: 20px;">Are you sure you want to logout?</p>
        <div style="display: flex; gap: 10px; justify-content: center;">
            <!-- Yes Button -->
            <form action="" method="POST" style="margin: 0;">
                <button type="submit" name="logout" style="background-color: #f44336; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">Yes</button>
            </form>
            <!-- No Button -->
            <button id="closeLogoutPopup" style="background-color: gray; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">No</button>
        </div>
    </div>
</div>

<script>
    // Select the logout icon and popup elements
    const logoutIcon = document.getElementById('logout-icon');
    const logoutPopup = document.getElementById('logoutPopup');
    const closeLogoutPopup = document.getElementById('closeLogoutPopup');

    // Show the popup when the logout icon is clicked
    if (logoutIcon) {
        logoutIcon.addEventListener('click', () => {
            logoutPopup.style.display = 'flex'; // Show popup
        });
    }

    // Hide the popup when "No" is clicked
    if (closeLogoutPopup) {
        closeLogoutPopup.addEventListener('click', () => {
            logoutPopup.style.display = 'none'; // Hide popup
        });
    }

    // Close the popup if clicking outside the popup content
    window.addEventListener('click', (e) => {
        if (e.target === logoutPopup) {
            logoutPopup.style.display = 'none'; // Hide popup
        }
    });
</script>




      <!--==================== SEARCH ====================-->
      <div class="search" id="search">
         <form action="" class="search__form">
            <i class="ri-search-line search__icon"></i>
            <input type="search" placeholder="What are you looking for?" class="search__input">
         </form>

         <i class="ri-close-line search__close" id="search-close"></i>
      </div>

<!--==================== LOGIN ====================-->
    <div class="login" id="login">
            <!-- Log In Form -->
        <form action="" method="POST" class="login__form" id="login-form">
            <h2 class="login__title">Log In</h2>
            <div class="login__group">
                <div>
                    <label for="email" class="login__label">Email</label>
                    <input type="email" name="email" placeholder="Write your email" id="email" class="login__input" required>
                </div>
                <div>
                    <label for="password" class="login__label">Password</label>
                    <input type="password" name="password" placeholder="Enter your password" id="password" class="login__input" required>
                </div>
            </div>
            <div>
                <p for="subscribe" style="font-size: 14px; color: #333;">
                    You do not have an account? <a href="#" style="color:rgb(4, 0, 255);" id="show-signup">Sign up</a>
                </p>
                <button type="submit" name="login" class="login__button">Log In</button>
            </div>
        </form>
    
        <!-- Sign Up Form -->
        <form action="" method="POST" class="signup__form hidden" id="signup-form">
            <h2 class="signup__title">Sign Up</h2>
            <div class="signup__group">
                <div>
                    <label for="signup-fullname" class="signup__label">Full Name</label>
                    <input type="text" name="fullname" placeholder="Enter your full name" id="signup-fullname" class="signup__input" required>
                </div>
                <div>
                    <label for="signup-email" class="signup__label">Email</label>
                    <input type="email" name="email" placeholder="Write your email" id="signup-email" class="signup__input" required>
                </div>
                <div>
                    <label for="signup-password" class="signup__label">Password</label>
                    <input type="password" name="password" placeholder="Enter your password" id="signup-password" class="signup__input" required>
                </div>
                <div>
                    <label for="confirm-password" class="signup__label">Confirm Password</label>
                    <input type="password" name="confirm_password" placeholder="Confirm your password" id="confirm-password" class="signup__input" required>
                </div>
            </div>
              <!-- Terms and Subscription -->
            <div class="signup__checkbox-group" style="margin: 15px 0;">
                <div style="margin-bottom: 10px;">
                    <input type="checkbox" name="terms" id="terms" required>
                    <label for="terms" style="font-size: 14px; color: #333;">
                        I agree to the <a href="#" style="color:rgb(4, 0, 255); text-decoration: underline;">Terms of Service</a>
                    </label>
                </div>
                <p for="subscribe" style="font-size: 14px; color: #333;">
                    Already have an account? <a href="#" style="color:rgb(4, 0, 255); text-decoration: underline;" id="show-login">Log In</a>
                </p>
                <button type="submit" name="signup" class="signup__button">Sign Up</button>
            </div>
        </form>
            <i class="ri-close-line login__close" id="login-close"></i>
    </div>

      <!--=============== MAIN JS ===============-->
      <script src="assets/js/main.js"></script>
</body>
</html>
        <div id="sp-page" class="spBgcover sp-content-1" style="background-color: rgb(255, 255, 255); font-family: Roboto, sans-serif; font-weight: 400;">
            <section id="sp-ks8a8s" class="sp-el-section " style="background-color: rgb(253, 216, 53); width: 100%; max-width: 100%; padding: 0px;">
                <div id="sp-vw3kvy" class="sp-el-row sp-w-full  sp-m-auto sm:sp-flex sp-justify-between" style="padding: 0px; width: 100%; max-width: 100%;">
                    <div id="sp-g0gdbc" class="sp-el-col  sp-w-full" style="background-color: rgb(253, 216, 53); width: calc(50% + 0px); padding: 70px 150px;">
                        <div id="sp-d4mo2r" class="sp-spacer" style="height: 0px;"></div>
                        <div id="sp-xx0axb" class="sp-spacer" style="height: 91px;"></div>
                        <span href="" target="" rel="" class="sp-header-block-link" style="text-decoration: none;">
                            <h1 id="sp-v2qwxf" class="sp-css-target sp-el-block sp-headline-block-v2qwxf sp-type-header" style="font-size: 68px; text-align: center; margin: 0px;">Hi! Saya Cikgu Ros</h1>
                        </span>
                        <span href="" target="" rel="" class="sp-header-block-link" style="text-decoration: none;">
                            <h6 id="sp-rm2ei4" class="sp-css-target sp-el-block sp-headline-block-rm2ei4 sp-type-header" style="font-size: 44px; text-align: center; margin: 0px;">Cikgu Tuisyen Pakar Subjek Akaun!</h6>
                        </span>
                        <!-- WhatsApp Button -->
                        <div id="sp-button-parent-bhr0x8" class="sp-button-wrapper sp-el-block" style="margin: 0px;">
                            <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true): ?>
                                <!-- User is logged in, allow direct access to WhatsApp -->
                                <a href="https://wa.me/60139552215" id="whatsapp-button" class="sp-button sp-css-target sp-text-center sp-inline-block sp-leading-none sp-button-bhr0x8" style="font-size: 22px; background: linear-gradient(-180deg, rgb(67, 160, 71), rgb(56, 135, 60) 90%); color: rgb(255, 255, 255); width: 100%; padding: 16px 20px; border-radius: 4px; border: 1px solid rgb(52, 124, 55); box-shadow: rgba(255, 255, 255, 0.2) 0px 1px 0px inset;">
                                    <span>Hubungi Saya!</span>
                                    <span>
                                        <span id="sp-button-sub-text-bhr0x8" class="sp-button-sub-txt sp-block sp-mt-1 sp-opacity-75" style="font-size: 16px; line-height: 1;">Tekan Untuk Whatsapp Saya!</span>
                                    </span>
                                </a>
                            <?php else: ?>
                                <!-- User is not logged in, show popup -->
                                <button id="whatsapp-button-login" class="sp-button sp-css-target sp-text-center sp-inline-block sp-leading-none sp-button-bhr0x8" style="font-size: 22px; background: linear-gradient(-180deg, rgb(67, 160, 71), rgb(56, 135, 60) 90%); color: rgb(255, 255, 255); width: 100%; padding: 16px 20px; border-radius: 4px; border: 1px solid rgb(52, 124, 55); box-shadow: rgba(255, 255, 255, 0.2) 0px 1px 0px inset;">
                                    <span>Hubungi Saya!</span>
                                    <span>
                                        <span id="sp-button-sub-text-bhr0x8" class="sp-button-sub-txt sp-block sp-mt-1 sp-opacity-75" style="font-size: 16px; line-height: 1;">Tekan Untuk Whatsapp Saya!</span>
                                    </span>
                                </button>
                            <?php endif; ?>
                        </div>

                        <!-- Popup Message -->
                        <div id="loginPopup" style="display: none;"> <!-- Ensure popup is hidden initially -->
                            <div class="popup-content">
                                <h3>Login Required</h3>
                                <p>Anda perlu login untuk menghubungi Cikgu Ros melalui WhatsApp.</p>
                                <button id="closePopup">Close</button>
                            </div>
                        </div>

                        <script>
                        // Select the WhatsApp button for non-logged-in users
                        const whatsappButtonLogin = document.getElementById('whatsapp-button-login');
                        const loginPopup = document.getElementById('loginPopup');
                        const closePopup = document.getElementById('closePopup');

                        // Show popup when WhatsApp button is clicked (for non-logged-in users)
                        if (whatsappButtonLogin) {
                            whatsappButtonLogin.addEventListener('click', (e) => {
                                e.preventDefault(); // Prevent default button behavior
                                loginPopup.style.display = 'flex'; // Show popup
                            });
                        }

                        // Close popup when the "Close" button is clicked
                        if (closePopup) {
                            closePopup.addEventListener('click', () => {
                                loginPopup.style.display = 'none'; // Hide popup
                            });
                        }

                        // Close popup if clicking outside the popup content
                        window.addEventListener('click', (e) => {
                            if (e.target === loginPopup) {
                                loginPopup.style.display = 'none'; // Hide popup
                            }
                        });
                        </script>


                        <div id="sp-sgttpm" class="sp-spacer" style="height: 30px;"></div>
                    </div>
                    <div id="sp-qy8hz8" class="sp-el-col  sp-w-full" style="background-color: rgb(253, 216, 53); width: calc(50% + 0px); padding: 0px;">
                        <figure id="sp-k6lhqf" class="sp-image-wrapper sp-el-block" style="margin: 0px; padding: 0px; text-align: center;">
                            <div>
                                <span>
                                    <img src="https://cikgurostuition.com/wp-content/uploads/2024/06/1719135571694.jpg" alt="" width="1024" height="1024" data-dynamic-tag="" data-image-src="wpmedia" srcset=" https://cikgurostuition.com/wp-content/uploads/2024/06/1719135571694-150x150.jpg 150w, https://cikgurostuition.com/wp-content/uploads/2024/06/1719135571694-300x300.jpg 300w, https://cikgurostuition.com/wp-content/uploads/2024/06/1719135571694.jpg 1024w" class="sp-image-block-k6lhqf custom-preview-class" style="width: 1024%;">
                                </span>
                            </div>
                            <div id="sp-image-dynamic-tags-js-k6lhqf">
                                <script>
                                    jQuery(function() {
                                        image_dynamic_tags('k6lhqf');
                                    });
                                </script>
                            </div>
                        </figure>
                    </div>
                </div>
            </section>
            <section id="sp-zhqqbp" class="sp-el-section " style="width: 100%; max-width: 100%; padding: 100px 10px;">
                <div id="sp-srlns5" class="sp-el-row sp-w-full  sp-m-auto sm:sp-flex sp-justify-between" style="padding: 0px; width: auto; max-width: 1000px;">
                    <div id="sp-j1ifpz" class="sp-el-col  sp-w-full" style="width: calc(100% + 0px);">
                        <style type="text/css">
                            #sp-j1ifpz .sp-col-top .seedprod-shape-fill {
                                fill: undefined;
                            }

                            #sp-j1ifpz .sp-col-top svg {
                                width: undefined%;
                                height: undefinedpx;
                                transform: translateX(-50%);
                            }

                            #sp-j1ifpz .sp-col-bottom .seedprod-shape-fill {
                                fill: undefined;
                            }

                            #sp-j1ifpz .sp-col-bottom svg {
                                width: undefined%;
                                height: undefinedpx;
                                transform: translateX(-50%);
                            }
                        </style>
                        <div class="sp-col-shape sp-col-top" style="z-index: 0;">
                            <div></div>
                        </div>
                        <div class="sp-col-shape sp-col-bottom" style="z-index: 0;">
                            <div></div>
                        </div>
                        <span href="" target="" rel="" class="sp-header-block-link" style="text-decoration: none;">
                            <h1 id="about-section" class="sp-css-target sp-el-block sp-headline-block-jqkyv9 sp-type-header" style="font-size: 40px; text-align: center; margin: 0px;">Perlukan Guru Tuisyen Untuk Anak Anda?</h1>
                        </span>
                        <span href="" target="" rel="" class="sp-header-block-link" style="text-decoration: none;">
                            <h1 id="sp-d40p0p" class="sp-css-target sp-el-block sp-headline-block-d40p0p sp-type-header" style="font-size: 25px; font-weight: 400; font-style: normal; font-family: Roboto; text-align: center; margin: 0px;">Saya Boleh Bantu Anak Anda Kuasai Subjek Akaun Menerusi Kelas Tuisyen 1-to-1 Secara Online Ataupun Offline</h1>
                        </span>
                        <div id="sp-k3gfhh" class="sp-spacer" style="height: 60px;"></div>
                    </div>
                </div>
                <div id="sp-hb262b" class="sp-el-row sp-w-full  sp-m-auto sm:sp-flex sp-justify-between" style="padding: 0px; width: auto; max-width: 1000px;">
                    <div id="sp-p91mqp" class="sp-el-col  sp-w-full" style="width: calc(50% + 0px);">
                        <figure id="sp-d1gzrd" class="sp-image-wrapper sp-el-block" style="margin: 0px; text-align: center;">
                            <div>
                                <span>
                                    <img src="https://cikgurostuition.com/wp-content/uploads/2024/06/cikguros.png" alt="" width="1080" height="1080" data-dynamic-tag="" data-image-src="wpmedia" srcset=" https://cikgurostuition.com/wp-content/uploads/2024/06/cikguros-150x150.png 150w, https://cikgurostuition.com/wp-content/uploads/2024/06/cikguros-300x300.png 300w, https://cikgurostuition.com/wp-content/uploads/2024/06/cikguros-1024x1024.png 1024w, https://cikgurostuition.com/wp-content/uploads/2024/06/cikguros.png 1080w" class="sp-image-block-d1gzrd custom-preview-class" style="width: 1080px;">
                                </span>
                            </div>
                            <div id="sp-image-dynamic-tags-js-d1gzrd">
                                <script>
                                    jQuery(function() {
                                        image_dynamic_tags('d1gzrd');
                                    });
                                </script>
                            </div>
                        </figure>
                    </div>
                    <div id="sp-vo092y" class="sp-el-col  sp-w-full" style="width: calc(50% + 0px);">
                        <span href="" target="" rel="" class="sp-header-block-link" style="text-decoration: none;">
                            <h3 id="sp-agwuga" class="sp-css-target sp-el-block sp-headline-block-agwuga sp-type-header" style="font-size: 30px; text-align: left; margin: 0px;">Pengalaman Lebih 20 Tahun Dalam Bidang Perakaunan</h3>
                        </span>
                        <div id="sp-a8li2l" class="sp-css-target sp-text-wrapper sp-el-block sp-text-wrapper-a8li2l" style="margin: 0px; text-align: left;">
                            <p style="text-align: justify; text-align-last: left;">
                                Setelah bergraduasi di <strong>UUM </strong>
                                pada tahun 1997 dalam Bidang Perakaunan (<strong>Ijazah Sarjana Muda Perakaunan dengan Kepujian),</strong>
                                saya telah bekerja sebagai <strong>Accounts Executive &nbsp;</strong>
                                di sebuah syarikat swasta di Johor.
                            </p>
                            <p style="text-align: justify; text-align-last: left;">&nbsp;</p>
                            <p style="text-align: justify; text-align-last: left;">
                                Kemudian, saya telah berpindah ke Pantai Timur (mengikut suami) dan bertugas pula sebagai seorang <strong>Pensyarah</strong>
                                di sebuah <strong>Kolej Swasta</strong>
                                selama 6 tahun.
                            </p>
                            <p style="text-align: justify; text-align-last: left;">&nbsp;</p>
                            <p style="text-align: justify; text-align-last: left;">
                                Pada tahun 2008, saya telah berpindah dan menetap pula di Puchong, Selangor. Disini, saya memulakan perkhidmatan sebagai <strong>guru tuisyen</strong>
                                di pusat-pusat tuisyen sekitar Puchong.
                            </p>
                            <p style="text-align: justify; text-align-last: left;">&nbsp;</p>
                            <p style="text-align: justify; text-align-last: left;">Namun, bermula 2018 saya lebih selesa mengajar tuisyen secara personal / home tutor di rumah saya.</p>
                        </div>
                    </div>
                </div>
            </section>
            <section id="sp-wt0fgd" class="sp-el-section " style="width: 100%; max-width: 100%;">
                <div id="sp-m3mcx3" class="sp-el-row sp-w-full  sp-m-auto sm:sp-flex sp-justify-between">
                    <div id="sp-kmkrnf" class="sp-el-col  sp-w-full" style="width: calc(100% + 0px);">
                        <span href="" target="" rel="" class="sp-header-block-link" style="text-decoration: none;">
                            <h1 id="sp-tv9gae" class="sp-css-target sp-el-block sp-headline-block-tv9gae sp-type-header" style="font-size: 40px; text-align: center; margin: 0px;">Kelebihan Belajar Dengan Saya!</h1>
                        </span>
                    </div>
                </div>
            </section>
            <section id="sp-exd4uu" class="sp-el-section " style="width: 100%; max-width: 100%; padding: 10px 10px 100px;">
                <div id="sp-dw6ag6" class="sp-el-row sp-w-full  sp-m-auto sm:sp-flex sp-justify-between" style="padding: 0px; width: auto; max-width: 1000px;">
                    <div id="sp-n58112" class="sp-el-col  sp-w-full" style="background-color: rgb(131, 251, 116); width: calc(50% - 30px);">
                        <span href="" target="" rel="" class="sp-header-block-link" style="text-decoration: none;">
                            <h3 id="sp-fz31q6" class="sp-css-target sp-el-block sp-headline-block-fz31q6 sp-type-header" style="font-size: 30px; text-align: center; margin: 0px;">Teknik Pembelajaran Yang Terbaik</h3>
                        </span>
                        <div id="sp-ttcm8s" class="sp-css-target sp-text-wrapper sp-el-block sp-text-wrapper-ttcm8s" style="margin: 0px; text-align: left;">
                            <p style="text-align: justify; text-align-last: left;">Dengan pengalaman lebih 20 tahun dalam bidang akaun dan lebih 10 tahun dalam bidang pendidikan. Saya mempunyai teknik pengajaran yang boleh disesuaikan mengikut tahap-tahap pemahaman dan keperluan anak anda!</p>
                            <p style="text-align: justify; text-align-last: left;">&nbsp;</p>
                            <p style="text-align: justify; text-align-last: left;">Saya juga mempunyai banyak tips &amp;formula yang boleh dikongsikan kepada anak anda agar dia seronok belajar dan mudah faham bagi setiap topik yang dipelajari!</p>
                        </div>
                    </div>
                    <div id="sp-bmczb7" class="sp-el-col  sp-w-full" style="background-color: rgb(255, 224, 61); width: calc(50% - 30px); padding: 50px;">
                        <span href="" target="" rel="" class="sp-header-block-link" style="text-decoration: none;">
                            <h3 id="sp-s6hikx" class="sp-css-target sp-el-block sp-headline-block-s6hikx sp-type-header" style="font-size: 30px; text-align: center; margin: 0px;">Masa &amp;Jadual Yang Fleksibel</h3>
                        </span>
                        <div id="sp-ogpcss" class="sp-css-target sp-text-wrapper sp-el-block sp-text-wrapper-ogpcss" style="margin: 0px; text-align: left;">
                            <p style="text-align: justify; text-align-last: left;">
                                Anda/anak anda boleh pilih sendiri sesi masa yang sesuai dengan jadual diri &amp;keluarga anda! Sesi tuisyen boleh juga dijalankan <strong>secara online </strong>
                                (menerusi zoom/google meet)<strong>ataupun secara offline</strong>
                                di lokasi rumah saya (Puchong, Selangor)
                            </p>
                        </div>
                    </div>
                </div>
                <div id="sp-s64uq1" class="sp-el-row sp-w-full  sp-m-auto sm:sp-flex sp-justify-between" style="padding: 0px; width: auto; max-width: 1000px;">
                    <div id="sp-k3iwjy" class="sp-el-col  sp-w-full" style="background-color: rgb(30, 136, 229); width: calc(50% - 30px); padding: 50px;">
                        <span href="" target="" rel="" class="sp-header-block-link" style="text-decoration: none;">
                            <h3 id="sp-ogqmnu" class="sp-css-target sp-el-block sp-headline-block-ogqmnu sp-type-header" style="font-size: 30px; color: rgb(255, 255, 255); text-align: center; margin: 0px;">Motivasi &amp;Sokongan Moral</h3>
                        </span>
                        <div id="sp-efxurn" class="sp-css-target sp-text-wrapper sp-el-block sp-text-wrapper-efxurn" style="color: rgb(255, 255, 255); margin: 0px; text-align: left;">
                            <p style="text-align: justify; text-align-last: left;">Saya akan sentiasa memberi kata-kata semangat dan perangsang untuk anda/anak anda agar berjaya dalam subjek yang dipelajari.</p>
                            <p style="text-align: justify; text-align-last: left;">&nbsp;</p>
                            <p style="text-align: justify; text-align-last: left;">Segala persoalan berkenaan dengan pembelajaran akan saya layan sama ada melalui panggilan telefon, whatsapp, email atau di facebook saya.</p>
                        </div>
                    </div>
                    <div id="sp-idb51g" class="sp-el-col  sp-w-full" style="background-color: rgb(255, 34, 34); width: calc(50% - 30px); padding: 50px;">
                        <span href="" target="" rel="" class="sp-header-block-link" style="text-decoration: none;">
                            <h3 id="sp-bhttaz" class="sp-css-target sp-el-block sp-headline-block-bhttaz sp-type-header" style="font-size: 30px; text-align: center; margin: 0px;">
                                <span style="color: #ffffff;">Tiada Caj Extra!</span>
                            </h3>
                        </span>
                        <div id="sp-q5o0p7" class="sp-css-target sp-text-wrapper sp-el-block sp-text-wrapper-q5o0p7" style="margin: 0px; text-align: left;">
                            <p style="text-align: justify; text-align-last: left;">
                                <span style="color: #ffffff;">Terdapat beberapa kos-kos lain yang TIDAK dicaj &nbsp;kepada anda/anak anda seperti kos buku-buku latihan, kertas ujian dan nota-nota.</span>
                            </p>
                            <p style="text-align: left;">&nbsp;</p>
                            <p style="text-align: justify; text-align-last: left;">
                                <span style="color: #ffffff;">Satu kelebihan bagi yang belajar tuisyen dengan saya, saya tidak mengenakan sebarang kos pendaftaran berbanding dengan pusat-pusat tuisyen di luar sana. Bayaran hanya dikenakan mengikut jam yang diperuntukkan sahaja.</span>
                            </p>
                        </div>
                    </div>
                </div>
            </section>
            <section id="sp-hkjmrs" class="sp-el-section " style="background-image: radial-gradient(circle, rgb(255, 255, 255) 0%, rgb(204, 204, 204) 100%); width: 100%; max-width: 100%; padding: 96px;">
                <div id="sp-wxccdi" class="sp-el-row sp-w-full  sp-m-auto sm:sp-flex sp-justify-between">
                    <div id="sp-jme9a0" class="sp-el-col  sp-w-full" style="width: calc(100% + 0px);">
                        <span href="" target="" rel="" class="sp-header-block-link" style="text-decoration: none;">
                            <h1 id="sp-seq4om" class="sp-css-target sp-el-block sp-headline-block-seq4om sp-type-header" style="font-size: 40px; text-align: center; margin: 0px;">Subjek &amp;Kadar Bayaran</h1>
                        </span>
                        <div id="sp-guwbs5" class="sp-css-target sp-text-wrapper sp-el-block sp-text-block-style sp-text-wrapper-guwbs5" style="font-size: 20px; margin: 0px; text-align: center;">
                            <p>Berikut adalah subjek serta kadar bayaran yang saya tawarkan.</p>
                        </div>
                    </div>
                </div>
                <div id="sp-f5g63z" class="sp-el-row sp-w-full  sp-m-auto sm:sp-flex sp-justify-between" style="border-radius: 0px;">
                    <div id="sp-sta3fk" class="sp-el-col  sp-w-full" style="border-radius: 0px; width: calc(33.3333% - 10px); padding: 50px; border-width: 2px; border-style: dotted; border-color: rgb(158, 158, 158);">
                        <span href="" target="" rel="" class="sp-header-block-link" style="text-decoration: none;">
                            <h3 id="sp-df55r9" class="sp-css-target sp-el-block sp-headline-block-df55r9 sp-type-header" style="text-align: center; margin: 0px;">Asasi / Diploma</h3>
                        </span>
                        <div id="jj5xur" class="sp-css-target sp-text-wrapper sp-el-block sp-text-block-style sp-text-wrapper-jj5xur mce-content-body html4-captions" style="margin: 0px; text-align: center; position: relative;">
                            <p>Basic Accounting</p>
                            <p>RM80/jam</p>
                        </div>
                    </div>
                    <div id="sp-gr11hs" class="sp-el-col  sp-w-full" style="width: calc(33.3333% - 10px); border-width: 2px; border-style: dotted; border-color: rgb(158, 158, 158);">
                        <span href="" target="" rel="" class="sp-header-block-link" style="text-decoration: none;">
                            <h3 id="sp-aoc0yv" class="sp-css-target sp-el-block sp-headline-block-aoc0yv sp-type-header" style="text-align: center; margin: 0px;">Tingkatan 4 &amp;5</h3>
                        </span>
                        <div id="sp-ejh871" class="sp-css-target sp-text-wrapper sp-el-block sp-text-block-style sp-text-wrapper-ejh871" style="margin: 0px; text-align: center;">
                            <p>Prinsip Perakaunan</p>
                            <p>RM60/jam</p>
                        </div>
                    </div>
                    <div id="sp-y2uklc" class="sp-el-col  sp-w-full" style="width: calc(33.3333% - 10px); border-width: 2px; border-style: dotted; border-color: rgb(158, 158, 158);">
                        <span href="" target="" rel="" class="sp-header-block-link" style="text-decoration: none;">
                            <h3 id="sp-z42i54" class="sp-css-target sp-el-block sp-headline-block-z42i54 sp-type-header" style="text-align: center; margin: 0px;">Darjah 4,5 &amp;6</h3>
                        </span>
                        <div id="sp-ut25ha" class="sp-css-target sp-text-wrapper sp-el-block sp-text-block-style sp-text-wrapper-ut25ha" style="margin: 0px; text-align: center;">
                            <p>Subjek Matematik</p>
                            <p>RM35/jam</p>
                        </div>
                    </div>
                </div>
            </section>
            <section id="sp-jk16vd" class="sp-el-section " style="width: 100%; max-width: 100%;">
                <div id="sp-dtwtm0" class="sp-el-row sp-w-full  sp-m-auto sm:sp-flex sp-justify-between">
                    <div id="sp-q9ngvq" class="sp-el-col  sp-w-full" style="width: calc(100% + 0px);">
                        <span href="" target="" rel="" class="sp-header-block-link" style="text-decoration: none;">
                            <h1 id="sp-f3q2fd" class="sp-css-target sp-el-block sp-headline-block-f3q2fd sp-type-header" style="text-align: center; margin: 0px;">Testimonial</h1>
                        </span>
                        <div id="sp-k9syjk" class="sp-css-target sp-text-wrapper sp-el-block sp-text-block-style sp-text-wrapper-k9syjk" style="font-size: 20px; margin: 0px; text-align: center;">
                            <p>Lihat apa kata mereka yang pernah belajar dengan saya.</p>
                        </div>
                        <div id="sp-mn2yyd" data-autoplay="true" data-speed="5" data-slidetoshow="1" class=" sp-testimonials-wrapper sp-el-block sp-testimonial-block-mn2yyd" style="margin: 0px; text-align: right;">
                            <div class="sp-flex sp-top-0 sp-left-0 sp-z-0 sp-gap-2 sp-text-right">
                                <div id="sp-mn2yyd0" class="sp-testimonial-wrapper sp-w-full" style="transition: opacity 0.3s linear; opacity: 1;">
                                    <p class=" sp-p-4 sp-rounded-md sp-testimonial-comment sp-mb-4 sp-testimonial-comment-mn2yyd" style="background: rgb(243, 243, 243); box-shadow: rgba(0, 0, 0, 0.075) 0px 0.125rem 0.25rem; font-style: italic; color: rgb(68, 68, 68);">
                                        "Alhamdulillah...1st sem Puan Ros yang ajar saya Financial Accounting 1. <strong>Saya score..A+.</strong>
                                        Easy to understand as she always keep it short and simple. Dulu nakal..tak pergi class.. I don't even have my own accounting book. Pakai note saje...That's because I have a good tutor. Thanks!"
                                    </p>
                                    <div style="margin-top: -27px; padding-right: 46px; padding-left: 49px;">
                                        <span style="position: relative; display: inline-block; width: 14px; height: 14px; transform: rotate(45deg); border-bottom: 1px solid transparent; border-right: 1px solid rgba(0, 0, 0, 0.05);"></span>
                                    </div>
                                    <div class="sp-flex sp-items-center sp-justify-start sp-flex-row-reverse">
                                        <img src="https://cikgurostuition.com/wp-content/uploads/2024/06/3.jpg" alt="Siti Fatimah Abdullah" srcset=" https://cikgurostuition.com/wp-content/uploads/2024/06/3.jpg 103w" class="sp-rounded-full sp-object-cover sp-testimonial-img sp-mr-6">
                                        <small class="sp-flex sp-flex-col sp-mx-4 sp-text-left sp-text-right">
                                            <strong class="sp-testimonial-text-mn2yyd">Siti Fatimah Abdullah</strong>
                                            <span class="sp-testimonial-text-mn2yyd">cdfatima@yahoo.com</span>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="sp-o8oevo" data-autoplay="true" data-speed="5" data-slidetoshow="1" class=" sp-testimonials-wrapper sp-el-block sp-testimonial-block-o8oevo" style="margin: 0px; text-align: left;">
                            <div class="sp-flex sp-top-0 sp-left-0 sp-z-0 sp-gap-2 sp-text-left">
                                <div id="sp-o8oevo0" class="sp-testimonial-wrapper sp-w-full" style="transition: opacity 0.3s linear; opacity: 1;">
                                    <p class=" sp-p-4 sp-rounded-md sp-testimonial-comment sp-mb-4 sp-testimonial-comment-o8oevo" style="background: rgb(243, 243, 243); box-shadow: rgba(0, 0, 0, 0.075) 0px 0.125rem 0.25rem; font-style: italic; color: rgb(68, 68, 68);">
                                        "Saya <strong>gagal dalam Peperiksaan Percubaan SPM</strong>
                                        untuk subjek Prinsip Perakaunan. Masalah ini membuatkan saya sangat susah hati. Bagaimanapun saya berasa amat bertuah kerana dapat berguru dengan Cikgu Ros walaupun dalam masa yang singkat sebelum menjelangnya peperiksaan SPM semasa cuti sekolah. Cikgu Ros telah mengajar saya teknik-teknik menjawab soalan dengan betul dan cara-cara mudah memahami sesuatu topik. Alhamdulillah, syukur kepada Allah saya <strong>lulus dengan gred C+</strong>
                                        bagi subjek Prinsip Perakaunan apabila keputusan SPM diumumkan."
                                    </p>
                                    <div style="margin-top: -27px; padding-right: 46px; padding-left: 49px;">
                                        <span style="position: relative; display: inline-block; width: 14px; height: 14px; transform: rotate(45deg); border-bottom: 1px solid transparent; border-right: 1px solid rgba(0, 0, 0, 0.05);"></span>
                                    </div>
                                    <div class="sp-flex sp-items-center sp-justify-start">
                                        <img src="https://cikgurostuition.com/wp-content/uploads/2024/06/1.jpg" alt="Muhammad Hafiz" srcset=" https://cikgurostuition.com/wp-content/uploads/2024/06/1.jpg 110w" class="sp-rounded-full sp-object-cover sp-testimonial-img sp-ml-6">
                                        <small class="sp-flex sp-flex-col sp-mx-4 sp-text-left">
                                            <strong class="sp-testimonial-text-o8oevo">Muhammad Hafiz</strong>
                                            <span class="sp-testimonial-text-o8oevo">014735254</span>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="sp-cvpa4h" data-autoplay="true" data-speed="5" data-slidetoshow="1" class=" sp-testimonials-wrapper sp-el-block sp-testimonial-block-cvpa4h" style="margin: 0px; text-align: right;">
                            <div class="sp-flex sp-top-0 sp-left-0 sp-z-0 sp-gap-2 sp-text-right">
                                <div id="sp-cvpa4h0" class="sp-testimonial-wrapper sp-w-full" style="transition: opacity 0.3s linear; opacity: 1;">
                                    <p class=" sp-p-4 sp-rounded-md sp-testimonial-comment sp-mb-4 sp-testimonial-comment-cvpa4h" style="background: rgb(243, 243, 243); box-shadow: rgba(0, 0, 0, 0.075) 0px 0.125rem 0.25rem; font-style: italic; color: rgb(68, 68, 68);">
                                        "Saya mengenali Puan Ros semasa di kolej dahulu.Masa tu saya mengambil subjek <strong>Basic Accounting</strong>
                                        dalam program Pre-Accounts UiTM. Saya amat berpuashati dan seronok belajar dengan Puan Ros kerana Puan Ros adalah seorang guru yang bersemangat ketika mengajar dan sentiasa melayan segala pertanyaan yang diajukan. Saya sentiasa dapat berbincang dengan baik tentang apa-apa yang saya tidak faham. Pendek kata memang best belajar dengan Puan Ros. Kalau nak tahu, <strong>saya dapat Gred A</strong>
                                        untuk subjek tu. Saya juga<strong>dapat A- dalam subjek Costing</strong>
                                        semasa peringkat diploma.Terima kasih Puan Ros.
                                    </p>
                                    <div style="margin-top: -27px; padding-right: 46px; padding-left: 49px;">
                                        <span style="position: relative; display: inline-block; width: 14px; height: 14px; transform: rotate(45deg); border-bottom: 1px solid transparent; border-right: 1px solid rgba(0, 0, 0, 0.05);"></span>
                                    </div>
                                    <div class="sp-flex sp-items-center sp-justify-start sp-flex-row-reverse">
                                        <img src="https://cikgurostuition.com/wp-content/uploads/2024/06/2.jpg" alt="Adi Haron" srcset=" https://cikgurostuition.com/wp-content/uploads/2024/06/2.jpg 104w" class="sp-rounded-full sp-object-cover sp-testimonial-img sp-mr-6">
                                        <small class="sp-flex sp-flex-col sp-mx-4 sp-text-left sp-text-right">
                                            <strong class="sp-testimonial-text-cvpa4h">Adi Haron</strong>
                                            <span class="sp-testimonial-text-cvpa4h">0129574225</span>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="sp-fzi7r3" data-autoplay="true" data-speed="5" data-slidetoshow="1" class=" sp-testimonials-wrapper sp-el-block sp-testimonial-block-fzi7r3" style="margin: 0px; text-align: left;">
                            <div class="sp-flex sp-top-0 sp-left-0 sp-z-0 sp-gap-2 sp-text-left">
                                <div id="sp-fzi7r30" class="sp-testimonial-wrapper sp-w-full" style="transition: opacity 0.3s linear; opacity: 1;">
                                    <p class=" sp-p-4 sp-rounded-md sp-testimonial-comment sp-mb-4 sp-testimonial-comment-fzi7r3" style="background: rgb(243, 243, 243); box-shadow: rgba(0, 0, 0, 0.075) 0px 0.125rem 0.25rem; font-style: italic; color: rgb(68, 68, 68);">
                                        "Puan Ros yang saya kenal memang penyabar pada pelajar dia...Puan sanggup layan call dari saya walaupun selepas waktu kerja.... Saya memang tak ada asas dalam accounting tapi alhamdullilah berkat dari tunjuk ajar Puan saya faham dan best belajar akaun dengan Puan...akhirnya saya dapat juga <strong>B+ bagi Costing dan Financial Accounting</strong>
                                        ...thanks a lot Puan...You are the best...:).
                                    </p>
                                    <div style="margin-top: -27px; padding-right: 46px; padding-left: 49px;">
                                        <span style="position: relative; display: inline-block; width: 14px; height: 14px; transform: rotate(45deg); border-bottom: 1px solid transparent; border-right: 1px solid rgba(0, 0, 0, 0.05);"></span>
                                    </div>
                                    <div class="sp-flex sp-items-center sp-justify-start">
                                        <img src="https://cikgurostuition.com/wp-content/uploads/2024/06/5.jpg" alt="Azie Zainal" srcset=" https://cikgurostuition.com/wp-content/uploads/2024/06/5.jpg 123w" class="sp-rounded-full sp-object-cover sp-testimonial-img sp-ml-6">
                                        <small class="sp-flex sp-flex-col sp-mx-4 sp-text-left">
                                            <strong class="sp-testimonial-text-fzi7r3">Azie Zainal</strong>
                                            <span class="sp-testimonial-text-fzi7r3">zie901@yahoo.com</span>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <span href="" target="" rel="" class="sp-header-block-link" style="text-decoration: none;">
                            <h3 id="sp-hi1kex" class="sp-css-target sp-el-block sp-headline-block-hi1kex sp-type-header" style="text-align: center; margin: 0px;">Dan Banyak Lagi Testimonial Di Terima Melalui Whatsapp</h3>
                        </span>
                        <div id="sp-aju75r" class=" sp-full-gallery-wrapper sp-el-block sp-full-gallery-block-aju75r animated flipInX ani_flipInX" style="padding: 0px; margin: 0px; text-align: center;">
                            <style type="text/css"></style>
                            <style type="text/css">
                                #sp-aju75r .sp-gallery-items .sp-gallery-item-img ,#sp-aju75r .sp-gallery-items .sp-gallery-bg-overlay {
                                }

                                #sp-aju75r .sp-gallery-items div.sp-gallery-item-img {
                                }

                                #sp-aju75r .sp-gallery-tabs a {
                                }

                                #sp-aju75r .sp-gallery-tabs a.sp-gallery-tab-title {
                                    padding-top: 4px;
                                    padding-bottom: 4px;
                                    padding-left: 10px;
                                    padding-right: 10px;
                                    border-bottom-width: 2px;
                                    margin-left: 5px
                                }

                                #sp-aju75r .sp-gallery-tabs a.sp-gallery-tab-title {
                                }

                                #sp-aju75r .sp-gallery-tabs a.sp-gallery-tab-title {
                                }

                                #sp-aju75r .sp-gallery-tabs a.sp-gallery-tab-title:hover {
                                }

                                #sp-aju75r .sp-gallery-tabs a.sp-gallery-tab-title.sp-tab-active {
                                }

                                #sp-aju75r .sp-gallery-tabs {
                                    justify-content: center
                                }

                                #sp-aju75r .sp-gallery-items .sp-gallery-item-block, #sp-aju75r .sp-gallery-items .sp-gallery-item-block div {
                                    color: #ffffff
                                }

                                #sp-aju75r .sp-gallery-items:hover .sp-gallery-bg-overlay {
                                    background-color: rgba(0,0,0,0.7)
                                }
                            </style>
                            <div class="sp-full-gallery-wrapper sp-top-0 sp-left-0 sp-z-0 sp-w-full sp-text-center">
                                <div style="--aspect-ratio: 100%; --hgap: 1px; --vgap: 1px; --columns: 3;">
                                    <div id="sp-gallery-preview-aju75r">
                                        <div class="sp-grid sp-custom-grid sp-gallery-block">
                                            <div class="sp-gallery-items" data-tags="all,0">
                                                <div class="sp-gallery-item-img" style="background-image:url('https://cikgurostuition.com/wp-content/uploads/2024/06/84120145_3082415598455750_9076214074903101440_n-300x300.jpg');"></div>
                                            </div>
                                            <div class="sp-gallery-items" data-tags="all,0">
                                                <div class="sp-gallery-item-img" style="background-image:url('https://cikgurostuition.com/wp-content/uploads/2024/06/87370304_3082415495122427_8247164990251859968_n-300x300.jpg');"></div>
                                            </div>
                                            <div class="sp-gallery-items" data-tags="all,0">
                                                <div class="sp-gallery-item-img" style="background-image:url('https://cikgurostuition.com/wp-content/uploads/2024/06/88173940_3082415051789138_4731243880584839168_n-300x300.jpg');"></div>
                                            </div>
                                            <div class="sp-gallery-items" data-tags="all,0">
                                                <div class="sp-gallery-item-img" style="background-image:url('https://cikgurostuition.com/wp-content/uploads/2024/06/88184749_3082414948455815_5735232961796636672_n-300x300.jpg');"></div>
                                            </div>
                                            <div class="sp-gallery-items" data-tags="all,0">
                                                <div class="sp-gallery-item-img" style="background-image:url('https://cikgurostuition.com/wp-content/uploads/2024/06/88217501_3082415188455791_2200243451465826304_n-300x300.jpg');"></div>
                                            </div>
                                            <div class="sp-gallery-items" data-tags="all,0">
                                                <div class="sp-gallery-item-img" style="background-image:url('https://cikgurostuition.com/wp-content/uploads/2024/06/88228462_3082415365122440_7041549919164825600_n-300x300.jpg');"></div>
                                            </div>
                                            <div class="sp-gallery-items" data-tags="all,0">
                                                <div class="sp-gallery-item-img" style="background-image:url('https://cikgurostuition.com/wp-content/uploads/2024/06/194222689_4383566248340672_8541474795134359560_n-286x300.jpg');"></div>
                                            </div>
                                            <div class="sp-gallery-items" data-tags="all,0">
                                                <div class="sp-gallery-item-img" style="background-image:url('https://cikgurostuition.com/wp-content/uploads/2024/06/352903741_708389424628971_4108624813333255859_n-300x300.jpg');"></div>
                                            </div>
                                            <div class="sp-gallery-items" data-tags="all,0">
                                                <div class="sp-gallery-item-img" style="background-image:url('https://cikgurostuition.com/wp-content/uploads/2024/06/1719132266547-300x266.jpg');"></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <script>
                                            jQuery(function() {
                                                seedprod_add_gallery_js("aju75r");
                                            });
                                        </script>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <section id="sp-eu479b" class="sp-el-section " style="background-color: rgb(0, 0, 0); width: 100%; max-width: 100%;">
                <div id="sp-ia4pi9" class="sp-el-row sp-w-full  sp-m-auto sm:sp-flex sp-justify-between">
                    <div id="sp-kbhs7q" class="sp-el-col  sp-w-full" style="width: calc(100% + 0px);">
                        <span href="" target="" rel="" class="sp-header-block-link" style="text-decoration: none;">
                            <h1 id="sp-wqno76" class="sp-css-target sp-el-block sp-headline-block-wqno76 sp-type-header" style="color: rgb(255, 255, 255); text-align: center; margin: 0px;">Lokasi</h1>
                        </span>
                        <div id="sp-w6fspe" class="sp-css-target sp-text-wrapper sp-el-block sp-text-block-style sp-text-wrapper-w6fspe" style="font-size: 25px; color: rgb(255, 255, 255); margin: 0px; text-align: center;">
                            <p>
                                Lokasi rumah @ tempat saya mengajar.<br>(Jika pilih belajar secara offline)
                            </p>
                        </div>
                        <div id="sp-de1v5i" class="sp-map-control sp-flex sp-el-block sp-google-maps-block-de1v5i" style="justify-content: center; margin: 0px;">
                            <style type="text/css">
                                #sp-de1v5i .sp-map-responsive {
                                    padding-bottom: 56.25%;
                                    overflow: hidden;
                                }
                            </style>
                            <div class="sp-map-wrapper" style="width: 100%; max-width: 100%;">
                                <div class="sp-map-responsive">
                                    <iframe width="100%" frameborder="0" scrolling="no" src="https://maps.google.com/maps?q=Cikgu%20Ros%20Tuition&amp;t=m&amp;z=10&amp;output=embed&amp;iwloc=near" allowfullscreen="allowfullscreen"></iframe>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <section id="sp-j15p5qbrah4e" class="sp-el-section  spBgcover" style="width: 100%; max-width: 100%; padding: 10px;">
                <div id="sp-vip11unw1ov" class="sp-el-row sp-w-full  sp-m-auto spBgcover sm:sp-flex sp-justify-between" style="padding: 0px; width: auto; max-width: 1000px;">
                    <div id="sp-cptdeir5qgc8" class="sp-el-col  spBgcover sp-w-full" style="width: calc(100% + 0px);">
                        <style type="text/css">
                            #sp-cptdeir5qgc8 .sp-col-top .seedprod-shape-fill {
                                fill: undefined;
                            }

                            #sp-cptdeir5qgc8 .sp-col-top svg {
                                width: undefined%;
                                height: undefinedpx;
                                transform: translateX(-50%);
                            }

                            #sp-cptdeir5qgc8 .sp-col-bottom .seedprod-shape-fill {
                                fill: undefined;
                            }

                            #sp-cptdeir5qgc8 .sp-col-bottom svg {
                                width: undefined%;
                                height: undefinedpx;
                                transform: translateX(-50%);
                            }
                        </style>
                        <div class="sp-col-shape sp-col-top" style="z-index: 0;">
                            <div></div>
                        </div>
                        <div class="sp-col-shape sp-col-bottom" style="z-index: 0;">
                            <div></div>
                        </div>
                        <span href="" target="" rel="" class="sp-header-block-link" style="text-decoration: none;">
                            <h1 id="contact-section" class="sp-css-target sp-el-block sp-headline-block-olxwqravzrp sp-type-header" style="text-align: center; margin: 0px;">Berminat Untuk Saya Bantu Anak Anda?</h1>
                        </span>
                        <div id="sp-efqg7q" class="sp-css-target sp-text-wrapper sp-el-block sp-text-block-style sp-text-wrapper-efqg7q" style="font-size: 25px; margin: 0px; text-align: center;">
                            <p>
                                Hubungi saya segera sebelum jadual mengajar saya padat dan penuh. :)<br>Boleh belajar Secara Offline (di rumah saya di Puchong) atau Secara Online (menerusi zoom/google meet)
                            </p>
                        </div>
                        <div id="sp-z883cof25x3a" class="sp-spacer" style="height: 22px;"></div>
                        <div id="sp-button-parent-xftguxtwztxn" class="sp-button-wrapper sp-el-block animated bounce aniIn_bounce ani_bounce" style="margin: 0px;">
                            <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true): ?>
                                <!-- User is logged in, allow direct access to WhatsApp -->
                                <a href="https://wa.me/60139552215" id="sp-xftguxtwztxn" target="_blank" rel="noopener noreferrer" class="sp-button sp-css-target sp-text-center sp-inline-block sp-leading-none sp-button-xftguxtwztxn" style="font-size: 22px; background: linear-gradient(-180deg, rgb(67, 160, 71), rgb(56, 135, 60) 90%); color: rgb(255, 255, 255); width: 100%; padding: 16px 20px; border-radius: 4px; border: 1px solid rgb(52, 124, 55); box-shadow: rgba(255, 255, 255, 0.2) 0px 1px 0px inset;">
                                    <span>Hubungi Saya</span>
                                    <span>
                                        <span id="sp-button-sub-text-xftguxtwztxn" class="sp-button-sub-txt sp-block sp-mt-1 sp-opacity-75" style="font-size: 16px; line-height: 1;">Klik Untuk Whatsapp</span>
                                    </span>
                                </a>
                            <?php else: ?>
                                <!-- User is not logged in, show popup -->
                                <button id="whatsapp-button-xftguxtwztxn" class="sp-button sp-css-target sp-text-center sp-inline-block sp-leading-none sp-button-xftguxtwztxn" style="font-size: 22px; background: linear-gradient(-180deg, rgb(67, 160, 71), rgb(56, 135, 60) 90%); color: rgb(255, 255, 255); width: 100%; padding: 16px 20px; border-radius: 4px; border: 1px solid rgb(52, 124, 55); box-shadow: rgba(255, 255, 255, 0.2) 0px 1px 0px inset;">
                                    <span>Hubungi Saya</span>
                                    <span>
                                        <span id="sp-button-sub-text-xftguxtwztxn" class="sp-button-sub-txt sp-block sp-mt-1 sp-opacity-75" style="font-size: 16px; line-height: 1;">Klik Untuk Whatsapp</span>
                                    </span>
                                </button>
                            <?php endif; ?>
                        </div>

                        <!-- Popup Message -->
                        <div id="loginPopup-xftguxtwztxn" style="display: none; background: rgba(0, 0, 0, 0.5); position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 10000; justify-content: center; align-items: center; padding: 0 15px;">
                            <div class="popup-content" style="background: white; padding: 20px; border-radius: 8px; text-align: center; box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.25);">
                                <h3 style="margin-bottom: 10px; font-size: 1.5rem;">Login Required</h3>
                                <p style="margin-bottom: 20px; font-size: 1rem;">Anda perlu login untuk menghubungi Cikgu Ros melalui WhatsApp.</p>
                                <button id="closePopup-xftguxtwztxn" style="background-color: gray; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">Close</button>
                            </div>
                        </div>

                        <script>
                            // Ensure popup does not display unless the button is clicked
                            const whatsappButtonLoginX = document.getElementById('whatsapp-button-xftguxtwztxn');
                            const loginPopupX = document.getElementById('loginPopup-xftguxtwztxn');
                            const closePopupX = document.getElementById('closePopup-xftguxtwztxn');

                            // Show popup when WhatsApp button is clicked (for non-logged-in users)
                            if (whatsappButtonLoginX) {
                                whatsappButtonLoginX.addEventListener('click', (e) => {
                                    e.preventDefault(); // Prevent default button behavior
                                    loginPopupX.style.display = 'flex'; // Show popup
                                });
                            }

                            // Close popup when the "Close" button is clicked
                            if (closePopupX) {
                                closePopupX.addEventListener('click', () => {
                                    loginPopupX.style.display = 'none'; // Hide popup
                                });
                            }

                            // Close popup if clicking outside the popup content
                            window.addEventListener('click', (e) => {
                                if (e.target === loginPopupX) {
                                    loginPopupX.style.display = 'none'; // Hide popup
                                }
                            });
                        </script>
                    </div>
                </div>
            </section>
            <section id="sp-odgyyn" class="sp-el-section " style="width: 100%; max-width: 100%;">
                <div id="sp-d3agd8" class="sp-el-row sp-w-full  sp-m-auto sm:sp-flex sp-justify-between">
                    <div id="sp-tk38d1" class="sp-el-col  sp-w-full" style="width: calc(100% + 0px);">
                        <div id="sp-y1udla" class="sp-css-target sp-text-wrapper sp-el-block sp-text-block-style sp-text-wrapper-y1udla" style="margin: 0px; text-align: center;">
                            <p>
                                <strong>CikguRosTuition.com</strong>
                                <br>No 12, Jalan PP 7/9, Taman Putra Perdana, 47130 Puchong, Selangor.
                            </p>
                        </div>
                        <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true): ?>
                        <div id="sp-cjdhwk" class="sp-socialprofiles-wrapper sp-flex sp-el-block sp-socialprofiles-style-1 sp-socialprofiles-block-cjdhwk" style="text-align: center; justify-content: center; margin: 0px;">
                            <a href="https://www.instagram.com/cikgurostuition/" target="_blank" class="sp-sp sp-whitespace-no-wrap sp-sp-instagram sp-sp-md" style="width: auto; height: auto; color: rgb(255, 255, 255); background-color: transparent; margin: 0px 10px 0px 0px;">
                                <i class="fa-fw fa-instagram fab" style="font-size: 24px;"></i>
                            </a>
                            <a href="https://www.facebook.com/CikguRosTuition" target="_blank" class="sp-sp sp-whitespace-no-wrap sp-sp-facebook sp-sp-md" style="width: auto; height: auto; color: rgb(255, 255, 255); background-color: transparent;">
                                <i class="fa-fw fa-facebook fab" style="font-size: 24px;"></i>
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        </div>
         <!-- Scroll to Top Button -->
        <button id="scrollTopButton">
            <i class="fas fa-arrow-up"></i>
        </button>

        <!-- JavaScript -->
        <script>
            // JavaScript for Scroll to Top Button
            const scrollTopButton = document.getElementById('scrollTopButton');

            // Show button when scrolling down
            window.onscroll = () => {
                if (window.scrollY > 200) {
                    scrollTopButton.style.display = 'block';
                } else {
                    scrollTopButton.style.display = 'none';
                }
            };

            // Smooth scroll to top when button is clicked
            scrollTopButton.addEventListener('click', () => {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
        </script>
        <div class="tv">
            <div class="screen mute" id="tv"></div>
        </div>
        <script>

            var sp_subscriber_callback_url = "https:\/\/cikgurostuition.com\/wp-admin\/admin-ajax.php?action=seedprod_pro_subscribe_callback&_wpnonce=228ea168a4";
            if (sp_subscriber_callback_url.indexOf(location.hostname) === -1) {
                sp_subscriber_callback_url = "\/wp-admin\/admin-ajax.php?action=seedprod_pro_subscribe_callback&_wpnonce=228ea168a4";
            }

            var sp_is_mobile = false;
        </script>
        <script id="wp-block-template-skip-link-js-after">
            (function() {
                var skipLinkTarget = document.querySelector('main'), sibling, skipLinkTargetID, skipLink;

                // Early exit if a skip-link target can't be located.
                if (!skipLinkTarget) {
                    return;
                }

                /*
		 * Get the site wrapper.
		 * The skip-link will be injected in the beginning of it.
		 */
                sibling = document.querySelector('.wp-site-blocks');

                // Early exit if the root element was not found.
                if (!sibling) {
                    return;
                }

                // Get the skip-link target's ID, and generate one if it doesn't exist.
                skipLinkTargetID = skipLinkTarget.id;
                if (!skipLinkTargetID) {
                    skipLinkTargetID = 'wp--skip-link--target';
                    skipLinkTarget.id = skipLinkTargetID;
                }

                // Create the skip link.
                skipLink = document.createElement('a');
                skipLink.classList.add('skip-link', 'screen-reader-text');
                skipLink.href = '#' + skipLinkTargetID;
                skipLink.innerHTML = 'Skip to content';

                // Inject the skip link.
                sibling.parentElement.insertBefore(skipLink, sibling);
            }());
        </script>

         <!-- ===== IONICONS ===== -->
        <script src="https://unpkg.com/ionicons@5.1.2/dist/ionicons.js"></script>

        <!--===== MAIN JS =====-->
        <script src="assets/js/main.js"></script>
    </body>
</html>