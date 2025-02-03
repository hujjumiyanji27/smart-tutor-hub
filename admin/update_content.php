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

if (isset($_GET['get_id']) && !empty($_GET['get_id'])) {
    $get_id = filter_var($_GET['get_id'], FILTER_SANITIZE_STRING);
} else {
    header('location:dashboard.php');
    exit;
}

if (isset($_POST['update'])) {
    $content_id = filter_var($_POST['content_id'], FILTER_SANITIZE_STRING);
    $status = filter_var($_POST['status'], FILTER_SANITIZE_STRING);
    $title = filter_var($_POST['title'], FILTER_SANITIZE_STRING);
    $description = filter_var($_POST['description'], FILTER_SANITIZE_STRING);
    $playlist = filter_var($_POST['playlist'], FILTER_SANITIZE_STRING);

    // Update Content Details
    $update_content = $conn->prepare("UPDATE `content` SET title = ?, description = ?, status = ? WHERE id = ?");
    if (!$update_content->execute([$title, $description, $status, $content_id])) {
        echo "<p style='color:red;'>Error updating content details.</p>";
    }

    // Update Playlist if Provided
    if (!empty($playlist)) {
        $update_playlist = $conn->prepare("UPDATE `content` SET playlist_id = ? WHERE id = ?");
        if (!$update_playlist->execute([$playlist, $content_id])) {
            echo "<p style='color:red;'>Error updating playlist.</p>";
        }
    }

    // Thumbnail Update
    $old_thumb = filter_var($_POST['old_thumb'], FILTER_SANITIZE_STRING);
    if (isset($_FILES['thumb']['tmp_name']) && !empty($_FILES['thumb']['tmp_name'])) {
        $thumb = filter_var($_FILES['thumb']['name'], FILTER_SANITIZE_STRING);
        $thumb_ext = pathinfo($thumb, PATHINFO_EXTENSION);
        $rename_thumb = unique_id() . '.' . $thumb_ext;
        $thumb_folder = '../uploaded_files/' . $rename_thumb;

        if (move_uploaded_file($_FILES['thumb']['tmp_name'], $thumb_folder)) {
            $update_thumb = $conn->prepare("UPDATE `content` SET thumb = ? WHERE id = ?");
            $update_thumb->execute([$rename_thumb, $content_id]);

            if (!empty($old_thumb) && file_exists('../uploaded_files/' . $old_thumb)) {
                unlink('../uploaded_files/' . $old_thumb);
            }
        } else {
            echo "<p style='color:red;'>Failed to upload thumbnail.</p>";
        }
    }

    // File (Video/PDF) Update
    $old_file = filter_var($_POST['old_file'], FILTER_SANITIZE_STRING);
    if (isset($_FILES['file']['tmp_name']) && !empty($_FILES['file']['tmp_name'])) {
        $file = filter_var($_FILES['file']['name'], FILTER_SANITIZE_STRING);
        $file_ext = pathinfo($file, PATHINFO_EXTENSION);
        $rename_file = unique_id() . '.' . $file_ext;
        $file_folder = '../uploaded_files/' . $rename_file;

        if (move_uploaded_file($_FILES['file']['tmp_name'], $file_folder)) {
            $update_file = $conn->prepare("UPDATE `content` SET file = ? WHERE id = ?");
            $update_file->execute([$rename_file, $content_id]);

            if (!empty($old_file) && file_exists('../uploaded_files/' . $old_file)) {
                unlink('../uploaded_files/' . $old_file);
            }
        } else {
            echo "<p style='color:red;'>Failed to upload file.</p>";
        }
    }

    echo "<p style='color:green;'>Content updated successfully!</p>";
}

if (isset($_POST['delete_content'])) {
    $delete_id = filter_var($_POST['content_id'], FILTER_SANITIZE_STRING);

    // Delete Thumbnail
    $delete_thumb = $conn->prepare("SELECT thumb FROM `content` WHERE id = ? LIMIT 1");
    $delete_thumb->execute([$delete_id]);
    $fetch_thumb = $delete_thumb->fetch(PDO::FETCH_ASSOC);

    if (!empty($fetch_thumb['thumb']) && file_exists('../uploaded_files/' . $fetch_thumb['thumb'])) {
        unlink('../uploaded_files/' . $fetch_thumb['thumb']);
    }

    // Delete File
    $delete_file = $conn->prepare("SELECT file FROM `content` WHERE id = ? LIMIT 1");
    $delete_file->execute([$delete_id]);
    $fetch_file = $delete_file->fetch(PDO::FETCH_ASSOC);

    if (!empty($fetch_file['file']) && file_exists('../uploaded_files/' . $fetch_file['file'])) {
        unlink('../uploaded_files/' . $fetch_file['file']);
    }

    // Delete Related Data
    $conn->prepare("DELETE FROM `likes` WHERE content_id = ?")->execute([$delete_id]);
    $conn->prepare("DELETE FROM `comments` WHERE content_id = ?")->execute([$delete_id]);
    $conn->prepare("DELETE FROM `content` WHERE id = ?")->execute([$delete_id]);

    header('location:contents.php');
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Content</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <link rel="stylesheet" href="../css/admin_style.css">
</head>
<body>

<?php include '../components/admin_header.php'; ?>

<section class="video-form">
    <h1 class="heading">Update Content</h1>
    <?php
    $select_content = $conn->prepare("SELECT * FROM `content` WHERE id = ? AND tutor_id = ?");
    $select_content->execute([$get_id, $tutor_id]);
    if ($select_content->rowCount() > 0) {
        while ($fetch_content = $select_content->fetch(PDO::FETCH_ASSOC)) {
            $thumb_src = !empty($fetch_content['thumb']) && file_exists("../uploaded_files/" . $fetch_content['thumb'])
                         ? "../uploaded_files/" . $fetch_content['thumb']
                         : "../uploaded_files/default_thumbnail.jpg";

            $file_src = !empty($fetch_content['file']) && file_exists("../uploaded_files/" . $fetch_content['file'])
                         ? "../uploaded_files/" . $fetch_content['file']
                         : '';
    ?>
    <form action="" method="post" enctype="multipart/form-data">
        <input type="hidden" name="content_id" value="<?= $fetch_content['id']; ?>">
        <input type="hidden" name="old_thumb" value="<?= $fetch_content['thumb']; ?>">
        <input type="hidden" name="old_file" value="<?= $fetch_content['file']; ?>">
        <p>Update Status <span>*</span></p>
        <select name="status" class="box" required>
            <option value="<?= htmlspecialchars($fetch_content['status']); ?>" selected><?= ucfirst(htmlspecialchars($fetch_content['status'])); ?></option>
            <option value="active">Active</option>
            <option value="deactive">Deactive</option>
        </select>
        <p>Update Title <span>*</span></p>
        <input type="text" name="title" maxlength="100" required placeholder="Enter content title" class="box" value="<?= $fetch_content['title']; ?>">
        <p>Update Description <span>*</span></p>
        <textarea name="description" class="box" required placeholder="Write description" maxlength="1000" cols="30" rows="10"><?= $fetch_content['description']; ?></textarea>
        <p>Update Playlist</p>
        <select name="playlist" class="box">
            <option value="<?= $fetch_content['playlist_id']; ?>" selected>--Select Playlist--</option>
            <?php
            $select_playlists = $conn->prepare("SELECT * FROM `playlist` WHERE tutor_id = ?");
            $select_playlists->execute([$tutor_id]);
            if ($select_playlists->rowCount() > 0) {
                while ($fetch_playlist = $select_playlists->fetch(PDO::FETCH_ASSOC)) {
            ?>
            <option value="<?= $fetch_playlist['id']; ?>"><?= $fetch_playlist['title']; ?></option>
            <?php
                }
            } else {
                echo '<option value="" disabled>No playlist created yet!</option>';
            }
            ?>
        </select>
        <img src="<?= $thumb_src; ?>" alt="Thumbnail">
        <p>Update Thumbnail</p>
        <input type="file" name="thumb" accept="image/*" class="box">
        <?php if ($file_src) { ?>
            <p>Current File: <a href="<?= $file_src; ?>" target="_blank">View File</a></p>
        <?php } else { ?>
            <p>No file uploaded yet!</p>
        <?php } ?>
        <p>Update File</p>
        <input type="file" name="file" accept="video/*,application/pdf" class="box">
        <input type="submit" value="Update Content" name="update" class="btn">
        <div class="flex-btn">
            <a href="view_content.php?get_id=<?= $fetch_content['id']; ?>" class="option-btn">View Content</a>
            <input type="submit" value="Delete Content" name="delete_content" class="delete-btn">
        </div>
    </form>
    <?php
        }
    } else {
        echo '<p class="empty">Content not found! <a href="add_content.php" class="btn" style="margin-top: 1.5rem;">Add Content</a></p>';
    }
    ?>
</section>

<?php include '../components/footer.php'; ?>
<script src="../js/admin_script.js"></script>
</body>
</html>
