<?php

include '../components/connect.php';

// Check if tutor_id is set in cookies
if (isset($_COOKIE['tutor_id'])) {
    $tutor_id = $_COOKIE['tutor_id'];
} else {
    $tutor_id = '';
    header('location:login.php');
    exit;
}

// Check if get_id is set
if (isset($_GET['get_id'])) {
    $get_id = $_GET['get_id'];
} else {
    $get_id = '';
    header('location:contents.php');
    exit;
}

// Delete video functionality
if (isset($_POST['delete_video'])) {
    $delete_id = filter_var($_POST['video_id'], FILTER_SANITIZE_STRING);

    // Fetch and delete the file (video or pdf)
    $delete_file = $conn->prepare("SELECT file, file_type FROM `content` WHERE id = ? LIMIT 1");
    $delete_file->execute([$delete_id]);
    $fetch_file = $delete_file->fetch(PDO::FETCH_ASSOC);

    if ($fetch_file) {
        $file_path = '../uploaded_files/' . $fetch_file['file'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }

    // Fetch and delete the thumbnail
    $delete_video_thumb = $conn->prepare("SELECT thumb FROM `content` WHERE id = ? LIMIT 1");
    $delete_video_thumb->execute([$delete_id]);
    $fetch_thumb = $delete_video_thumb->fetch(PDO::FETCH_ASSOC);
    if (!empty($fetch_thumb['thumb']) && file_exists('../uploaded_files/' . $fetch_thumb['thumb'])) {
        unlink('../uploaded_files/' . $fetch_thumb['thumb']);
    }

    // Delete likes, comments, and content entry
    $delete_likes = $conn->prepare("DELETE FROM `likes` WHERE content_id = ?");
    $delete_likes->execute([$delete_id]);
    $delete_comments = $conn->prepare("DELETE FROM `comments` WHERE content_id = ?");
    $delete_comments->execute([$delete_id]);
    $delete_content = $conn->prepare("DELETE FROM `content` WHERE id = ?");
    $delete_content->execute([$delete_id]);

    header('location:contents.php');
    exit;
}

// Delete comment functionality
if (isset($_POST['delete_comment'])) {
    $delete_id = filter_var($_POST['comment_id'], FILTER_SANITIZE_STRING);

    $verify_comment = $conn->prepare("SELECT * FROM `comments` WHERE id = ?");
    $verify_comment->execute([$delete_id]);

    if ($verify_comment->rowCount() > 0) {
        $delete_comment = $conn->prepare("DELETE FROM `comments` WHERE id = ?");
        $delete_comment->execute([$delete_id]);
        $message[] = 'Comment deleted successfully!';
    } else {
        $message[] = 'Comment already deleted!';
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Content</title>

    <!-- Font Awesome CDN Link -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <!-- Custom CSS File -->
    <link rel="stylesheet" href="../css/admin_style.css">
</head>
<body>

<?php include '../components/admin_header.php'; ?>

<section class="view-content">
    <?php
    // Fetch content details
    $select_content = $conn->prepare("SELECT * FROM `content` WHERE id = ? AND tutor_id = ?");
    $select_content->execute([$get_id, $tutor_id]);
    if ($select_content->rowCount() > 0) {
        while ($fetch_content = $select_content->fetch(PDO::FETCH_ASSOC)) {
            $file_path = "../uploaded_files/" . $fetch_content['file'];
            $file_type = $fetch_content['file_type'];
            $thumb_path = !empty($fetch_content['thumb']) && file_exists("../uploaded_files/" . $fetch_content['thumb']) 
                          ? "../uploaded_files/" . $fetch_content['thumb'] 
                          : "../uploaded_files/default_thumbnail.jpg";

            // Count likes and comments
            $count_likes = $conn->prepare("SELECT * FROM `likes` WHERE tutor_id = ? AND content_id = ?");
            $count_likes->execute([$tutor_id, $fetch_content['id']]);
            $total_likes = $count_likes->rowCount();

            $count_comments = $conn->prepare("SELECT * FROM `comments` WHERE tutor_id = ? AND content_id = ?");
            $count_comments->execute([$tutor_id, $fetch_content['id']]);
            $total_comments = $count_comments->rowCount();
    ?>
    <div class="container">
        <!-- Display File Based on Type -->
        <?php if ($file_type == 'video') { ?>
            <video 
                src="<?= file_exists($file_path) ? $file_path : 'default_video.mp4'; ?>" 
                controls 
                autoplay 
                poster="<?= $thumb_path; ?>" 
                class="video">
            </video>
        <?php } elseif ($file_type == 'pdf') { ?>
            <iframe 
                src="<?= file_exists($file_path) ? $file_path : 'default_document.pdf'; ?>" 
                class="pdf-viewer" 
                style="width: 100%; height: 600px; border: none;">
            </iframe>
        <?php } else { ?>
            <p style="color:red;">Unsupported file type!</p>
        <?php } ?>

        <!-- Display Thumbnail -->
        <div class="thumbnail-container" style="margin: 10px 0;">
            <img 
                src="<?= $thumb_path; ?>" 
                alt="Thumbnail" 
                style="width: 150px; height: auto; border-radius: 5px;">
        </div>

        <!-- Debug Paths -->
        <p style="color: #888;">File Path: <?= file_exists($file_path) ? $file_path : 'Not Available'; ?></p>
        <p style="color: #888;">Thumbnail Path: <?= $thumb_path; ?></p>

        <div class="date"><i class="fas fa-calendar"></i><span><?= $fetch_content['date']; ?></span></div>
        <h3 class="title"><?= $fetch_content['title']; ?></h3>
        <div class="flex">
            <div><i class="fas fa-heart"></i><span><?= $total_likes; ?></span></div>
            <div><i class="fas fa-comment"></i><span><?= $total_comments; ?></span></div>
        </div>
        <div class="description"><?= $fetch_content['description']; ?></div>
        <form action="" method="post">
            <div class="flex-btn">
                <input type="hidden" name="video_id" value="<?= $fetch_content['id']; ?>">
                <a href="update_content.php?get_id=<?= $fetch_content['id']; ?>" class="option-btn">Update</a>
                <input type="submit" value="Delete" class="delete-btn" onclick="return confirm('Delete this content?');" name="delete_video">
            </div>
        </form>
    </div>
    <?php
        }
    } else {
        echo '<p class="empty">No content found! <a href="add_content.php" class="btn" style="margin-top: 1.5rem;">Add Content</a></p>';
    }
    ?>
</section>

<section class="comments">
    <h1 class="heading">User Comments</h1>
    <div class="show-comments">
        <?php
        $select_comments = $conn->prepare("SELECT * FROM `comments` WHERE content_id = ?");
        $select_comments->execute([$get_id]);
        if ($select_comments->rowCount() > 0) {
            while ($fetch_comment = $select_comments->fetch(PDO::FETCH_ASSOC)) {
                $select_commentor = $conn->prepare("SELECT * FROM `users` WHERE id = ?");
                $select_commentor->execute([$fetch_comment['user_id']]);
                $fetch_commentor = $select_commentor->fetch(PDO::FETCH_ASSOC);
        ?>
        <div class="box">
            <div class="user">
                <img src="../uploaded_files/<?= !empty($fetch_commentor['image']) ? $fetch_commentor['image'] : 'default_user.jpg'; ?>" alt="">
                <div>
                    <h3><?= $fetch_commentor['name']; ?></h3>
                    <span><?= $fetch_comment['date']; ?></span>
                </div>
            </div>
            <p class="text"><?= $fetch_comment['comment']; ?></p>
            <form action="" method="post" class="flex-btn">
                <input type="hidden" name="comment_id" value="<?= $fetch_comment['id']; ?>">
                <button type="submit" name="delete_comment" class="inline-delete-btn" onclick="return confirm('Delete this comment?');">Delete Comment</button>
            </form>
        </div>
        <?php
            }
        } else {
            echo '<p class="empty">No comments added yet!</p>';
        }
        ?>
    </div>
</section>

<?php include '../components/footer.php'; ?>
<script src="../js/admin_script.js"></script>
</body>
</html>
