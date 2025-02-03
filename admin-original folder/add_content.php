<?php

include '../components/connect.php';

// Check if the tutor_id cookie exists
if (isset($_COOKIE['tutor_id'])) {
    $tutor_id = $_COOKIE['tutor_id'];
} else {
    $tutor_id = '';
    header('location:login.php');
    exit;
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Handle form submission
if (isset($_POST['submit'])) {
    // Collect and sanitize input data
    $id = unique_id(); // Use the function from connect.php
    $status = filter_var($_POST['status'], FILTER_SANITIZE_STRING);
    $title = filter_var($_POST['title'], FILTER_SANITIZE_STRING);
    $description = filter_var($_POST['description'], FILTER_SANITIZE_STRING);
    $playlist = filter_var($_POST['playlist'], FILTER_SANITIZE_STRING);

    // Upload directory
    $upload_dir = '../uploaded_files/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Handle thumbnail upload
    $thumb_uploaded = false;
    if ($_FILES['thumb']['error'] === UPLOAD_ERR_OK) {
        $thumb_ext = pathinfo($_FILES['thumb']['name'], PATHINFO_EXTENSION);
        $rename_thumb = unique_id() . '.' . $thumb_ext;
        $thumb_tmp_name = $_FILES['thumb']['tmp_name'];
        $thumb_folder = $upload_dir . $rename_thumb;

        if (move_uploaded_file($thumb_tmp_name, $thumb_folder)) {
            $thumb_uploaded = true;
        }
    }

    // Handle video upload
    $video_uploaded = false;
    if ($_FILES['video']['error'] === UPLOAD_ERR_OK) {
        $video_ext = pathinfo($_FILES['video']['name'], PATHINFO_EXTENSION);
        $rename_video = unique_id() . '.' . $video_ext;
        $video_tmp_name = $_FILES['video']['tmp_name'];
        $video_folder = $upload_dir . $rename_video;

        if (move_uploaded_file($video_tmp_name, $video_folder)) {
            $video_uploaded = true;
        }
    }

    // Insert data into the database if uploads succeeded
    if ($thumb_uploaded && $video_uploaded) {
        $add_content = $conn->prepare("INSERT INTO `content` (id, tutor_id, playlist_id, title, description, video, thumb, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $result = $add_content->execute([$id, $tutor_id, $playlist, $title, $description, $rename_video, $rename_thumb, $status]);

        if ($result) {
            // Update playlist video count
            $update_playlist = $conn->prepare("UPDATE `playlist` SET total_videos = total_videos + 1 WHERE id = ?");
            $update_playlist->execute([$playlist]);
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Content</title>
    <!-- Font Awesome CDN Link -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <!-- Custom CSS File -->
    <link rel="stylesheet" href="../css/admin_style.css">
</head>
<body>

<?php include '../components/admin_header.php'; ?>

<section class="video-form">
    <h1 class="heading">Upload Content</h1>

    <form action="" method="post" enctype="multipart/form-data">
        <p>Video Status <span>*</span></p>
        <select name="status" class="box" required>
            <option value="" selected disabled>-- Select Status --</option>
            <option value="active">Active</option>
            <option value="deactive">Deactive</option>
        </select>
        <p>Video Title <span>*</span></p>
        <input type="text" name="title" maxlength="100" required placeholder="Enter video title" class="box">
        <p>Video Description <span>*</span></p>
        <textarea name="description" class="box" required placeholder="Write description" maxlength="1000" cols="30" rows="10"></textarea>
        <p>Video Playlist <span>*</span></p>
        <select name="playlist" class="box" required>
            <option value="" disabled selected>-- Select Playlist --</option>
            <?php
            $select_playlists = $conn->prepare("SELECT * FROM `playlist` WHERE tutor_id = ?");
            $select_playlists->execute([$tutor_id]);
            if ($select_playlists->rowCount() > 0) {
                while ($fetch_playlist = $select_playlists->fetch(PDO::FETCH_ASSOC)) {
                    echo '<option value="' . $fetch_playlist['id'] . '">' . $fetch_playlist['title'] . '</option>';
                }
            } else {
                echo '<option value="" disabled>No playlists created yet!</option>';
            }
            ?>
        </select>
        <p>Select Thumbnail <span>*</span></p>
        <input type="file" name="thumb" accept="image/*" required class="box">
        <p>Select Video <span>*</span></p>
        <input type="file" name="video" accept="video/*" required class="box">
        <input type="submit" value="Upload Video" name="submit" class="btn">
    </form>
</section>

<?php include '../components/footer.php'; ?>

<script src="../js/admin_script.js"></script>

</body>
</html>
