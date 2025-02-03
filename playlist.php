<?php
include 'components/connect.php';

if (isset($_COOKIE['user_id'])) {
    $user_id = $_COOKIE['user_id'];
} else {
    $user_id = '';
}

if (isset($_GET['get_id'])) {
    $get_id = $_GET['get_id'];

    // Use $get_id to fetch playlist details
    $select_playlist = $conn->prepare("SELECT * FROM playlist WHERE id = ? AND status = ? LIMIT 1");
    $select_playlist->execute([$get_id, 'active']);

    if ($select_playlist->rowCount() > 0) {
        $fetch_playlist = $select_playlist->fetch(PDO::FETCH_ASSOC);
        $playlist_id = $fetch_playlist['id']; // Assign value to $playlist_id
        $price = $fetch_playlist['price'];
    } else {
        echo '<p class="empty">This playlist was not found!</p>';
        exit();
    }
} else {
    echo '<p class="empty">Invalid playlist ID!</p>';
    exit();
}

// Check if the user has paid for the course
$is_paid = false;
$check_payment = $conn->prepare("SELECT * FROM payments WHERE user_id = ? AND playlist_id = ? AND payment_status = 'completed'");
$check_payment->execute([$user_id, $playlist_id]);
$is_paid = $check_payment->rowCount() > 0;

// Fetch tutor details
$select_tutor = $conn->prepare("SELECT * FROM tutors WHERE id = ? LIMIT 1");
$select_tutor->execute([$fetch_playlist['tutor_id']]);
$fetch_tutor = $select_tutor->fetch(PDO::FETCH_ASSOC);

// Fetch total videos
$count_videos = $conn->prepare("SELECT * FROM content WHERE playlist_id = ? AND status = ? ORDER BY date DESC");
$count_videos->execute([$playlist_id, 'active']);
$total_videos = $count_videos->rowCount();

// Fetch the first video to allow free access
$select_first_video = $conn->prepare("SELECT * FROM content WHERE playlist_id = ? AND status = ? ORDER BY date ASC LIMIT 1");
$select_first_video->execute([$playlist_id, 'active']);
$first_video = $select_first_video->fetch(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Playlist</title>

    <!-- Font Awesome CDN Link -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">

    <!-- Custom CSS File -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php include 'components/user_header.php'; ?>

<section class="playlist">
    <h1 class="heading">Playlist Details</h1>

    <div class="row">
        <div class="col">
            <form action="" method="post" class="save-list">
                <input type="hidden" name="list_id" value="<?= $playlist_id; ?>">
                <button type="submit" name="save_list">
                    <i class="<?= isset($is_bookmarked) && $is_bookmarked ? 'fas fa-bookmark' : 'far fa-bookmark'; ?>"></i>
                    <span><?= isset($is_bookmarked) && $is_bookmarked ? 'Saved' : 'Save Playlist'; ?></span>
                </button>
            </form>
            <div class="thumb">
                <span><?= $total_videos; ?> videos</span>
                <img src="uploaded_files/<?= $fetch_playlist['thumb']; ?>" alt="">
            </div>
        </div>

        <div class="col">
            <div class="tutor">
                <img src="uploaded_files/<?= $fetch_tutor['image']; ?>" alt="">
                <div>
                    <h3><?= $fetch_tutor['name']; ?></h3>
                    <span><?= $fetch_tutor['profession']; ?></span>
                </div>
            </div>
            <div class="details">
                <h3><?= $fetch_playlist['title']; ?></h3>
                <p><?= $fetch_playlist['description']; ?></p>
                <div class="date"><i class="fas fa-calendar"></i><span><?= $fetch_playlist['date']; ?></span></div>
                <div class="price">
                    <i class="fas fa-pound-sign"></i>
                    <span>£<?= $price; ?></span>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="videos-container">
    <h1 class="heading">Playlist Videos</h1>

    <div class="box-container">
        <?php
        // Fetch all active content for this playlist
        $select_content = $conn->prepare("SELECT * FROM content WHERE playlist_id = ? AND status = ? ORDER BY date DESC");
        $select_content->execute([$playlist_id, 'active']);

        if ($select_content->rowCount() > 0) {
            $is_first_video = true;  // Flag for first video
            while ($fetch_content = $select_content->fetch(PDO::FETCH_ASSOC)) {
                if ($is_first_video) {
                    // Display the first video without restriction
                    echo "<a href='watch_video.php?get_id={$fetch_content['id']}' class='box'>
                            <i class='fas fa-play'></i>
                            <img src='uploaded_files/{$fetch_content['thumb']}' alt=''>
                            <h3>{$fetch_content['title']}</h3>
                        </a>";
                    $is_first_video = false;  // Mark that the first video has been displayed
                } else {
                    if (!$is_paid) {
                        // Display restricted message for videos after the first one if not paid
                        echo "<div class='restricted'>
                                <h3>Restricted Content</h3>
                                <p>To access all videos, <a href='payment_handler.php?playlist_id={$get_id}&amount={$price}'>pay £{$price}</a>.</p>
                              </div>";
                        continue;
                    }
                    // Display video for paid users
                    echo "<a href='watch_video.php?get_id={$fetch_content['id']}' class='box'>
                            <i class='fas fa-play'></i>
                            <img src='uploaded_files/{$fetch_content['thumb']}' alt=''>
                            <h3>{$fetch_content['title']}</h3>
                        </a>";
                }
            }
        } else {
            echo '<p class="empty">No videos added yet!</p>';
        }
        ?>
    </div>
</section>

<?php include 'components/footer.php'; ?>

<script src="js/script.js"></script>

</body>
</html>
