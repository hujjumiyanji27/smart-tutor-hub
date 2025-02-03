<?php

include '../components/connect.php';

function unique_id() {
    return uniqid('id_', true);
}

if (isset($_COOKIE['tutor_id'])) {
    $tutor_id = $_COOKIE['tutor_id'];
} else {
    $tutor_id = '';
    header('location:login.php');
    exit;
}

if (isset($_POST['submit'])) {
    $id = unique_id();
    $status = filter_var($_POST['status'], FILTER_SANITIZE_STRING);
    $title = filter_var($_POST['title'], FILTER_SANITIZE_STRING);
    $description = filter_var($_POST['description'], FILTER_SANITIZE_STRING);
    $playlist = filter_var($_POST['playlist'], FILTER_SANITIZE_STRING);

    $upload_dir = '../uploaded_files/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $file_uploaded = false;
    $file_type = '';
    $thumbnail_uploaded = false;

    // Handle content file upload (video/PDF)
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $file_ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
        $rename_file = unique_id() . '.' . $file_ext;
        $file_tmp_name = $_FILES['file']['tmp_name'];
        $file_folder = $upload_dir . $rename_file;

        $allowed_types = ['mp4', 'pdf'];
        if (in_array($file_ext, $allowed_types)) {
            if (move_uploaded_file($file_tmp_name, $file_folder)) {
                $file_uploaded = true;
                $file_type = $file_ext === 'mp4' ? 'video' : 'pdf';
            } else {
                echo "<p style='color:red;'>Failed to move uploaded file.</p>";
            }
        } else {
            echo "<p style='color:red;'>Invalid file type. Only MP4 and PDF files are allowed.</p>";
        }
    } else {
        echo "<p style='color:red;'>Error uploading file: " . $_FILES['file']['error'] . "</p>";
    }

    // Handle thumbnail upload
    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
        $thumb_ext = strtolower(pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION));
        $rename_thumb = unique_id() . '.' . $thumb_ext;
        $thumb_tmp_name = $_FILES['thumbnail']['tmp_name'];
        $thumb_folder = $upload_dir . $rename_thumb;

        $allowed_image_types = ['jpg', 'jpeg', 'png'];
        if (in_array($thumb_ext, $allowed_image_types)) {
            if (move_uploaded_file($thumb_tmp_name, $thumb_folder)) {
                $thumbnail_uploaded = true;
            } else {
                echo "<p style='color:red;'>Failed to move uploaded thumbnail.</p>";
            }
        } else {
            echo "<p style='color:red;'>Invalid thumbnail type. Only JPG, JPEG, and PNG images are allowed.</p>";
        }
    } else {
        echo "<p style='color:red;'>Error uploading thumbnail: " . $_FILES['thumbnail']['error'] . "</p>";
    }

    // Insert into database
    if ($file_uploaded) {
        $thumbnail_name = $thumbnail_uploaded ? $rename_thumb : 'default_thumbnail.jpg';

        // Check if default thumbnail exists
        if (!$thumbnail_uploaded && !file_exists($upload_dir . $thumbnail_name)) {
            $thumbnail_name = ''; // Set to empty if no default thumbnail is available
        }

        $add_content = $conn->prepare("INSERT INTO `content` (id, tutor_id, playlist_id, title, description, file, file_type, thumb, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if ($add_content->execute([$id, $tutor_id, $playlist, $title, $description, $rename_file, $file_type, $thumbnail_name, $status])) {
            echo '<p style="color:green;">Content uploaded successfully!</p>';

            // Update playlist if it exists
            $update_playlist = $conn->prepare("UPDATE `playlist` SET total_videos = total_videos + 1 WHERE id = ?");
            $update_playlist->execute([$playlist]);
        } else {
            $errorInfo = $add_content->errorInfo();
            echo "<p style='color:red;'>Database error: {$errorInfo[2]}</p>";
        }
    } else {
        echo "<p style='color:red;'>File upload failed. Please check your file and try again.</p>";
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
    <link rel="stylesheet" href="../css/admin_style.css">
</head>
<body>

<?php include '../components/admin_header.php'; ?>

<section class="video-form">
    <h1 class="heading">Upload Content</h1>
    <form action="" method="post" enctype="multipart/form-data">
        <p>Content Status <span>*</span></p>
        <select name="status" class="box" required>
            <option value="" selected disabled>-- Select Status --</option>
            <option value="active">Active</option>
            <option value="deactive">Deactive</option>
        </select>
        <p>Content Title <span>*</span></p>
        <input type="text" name="title" maxlength="100" required placeholder="Enter content title" class="box">
        <p>Content Description <span>*</span></p>
        <textarea name="description" class="box" required placeholder="Write description" maxlength="1000" cols="30" rows="10"></textarea>
        <p>Content Playlist <span>*</span></p>
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
        <p>Select File (Video/PDF) <span>*</span></p>
        <input type="file" name="file" accept="video/*,application/pdf" required class="box">
        <p>Upload Thumbnail (Optional)</p>
        <input type="file" name="thumbnail" accept="image/*" class="box">
        <input type="hidden" name="MAX_FILE_SIZE" value="209715200"> <!-- 200MB -->
        <input type="submit" value="Upload Content" name="submit" class="btn">
    </form>
</section>

<?php include '../components/footer.php'; ?>

<script src="../js/admin_script.js"></script>

</body>
</html>
