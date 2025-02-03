
<?php
include 'components/connect.php';

if(isset($_COOKIE['user_id'])){
   $user_id = $_COOKIE['user_id'];
}else{
   $user_id = '';
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>courses</title>

   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">

   <!-- custom css file link  -->
   <link rel="stylesheet" href="css/style.css">

</head>
<body>

<?php include 'components/user_header.php'; ?>

<!-- courses section starts  -->

<section class="courses">

   <h1 class="heading">all courses</h1>

   <div class="box-container">

      <?php
         $select_courses = $conn->prepare("SELECT * FROM `playlist` WHERE status = ? ORDER BY date DESC");
         $select_courses->execute(['active']);
         if($select_courses->rowCount() > 0){
            while($fetch_course = $select_courses->fetch(PDO::FETCH_ASSOC)){
               $course_id = $fetch_course['id'];
               $price = $fetch_course['price'];

               $select_tutor = $conn->prepare("SELECT * FROM `tutors` WHERE id = ?");
               $select_tutor->execute([$fetch_course['tutor_id']]);
               $fetch_tutor = $select_tutor->fetch(PDO::FETCH_ASSOC);
      ?>

      <div class="box">
         <div class="tutor">
            <img src="uploaded_files/<?= $fetch_tutor['image']; ?>" alt="">
            <div>
               <h3><?= $fetch_tutor['name']; ?></h3>
               <span><?= $fetch_course['title']; ?></span>
            </div>
         </div>
         <div class="details">
            <p><?= substr($fetch_course['description'], 0, 100); ?>...</p>
            <p><strong>Price:</strong> <?= ($price > 0) ? "£$price" : "Free"; ?></p>
         </div>
         <a href="watch_video.php?course_id=<?= $course_id; ?>" class="btn"><?= ($price > 0) ? 'Start Course' : 'Watch Intro Video'; ?></a>
      </div>

      <?php
            }
         }else{
            echo '<p>No courses available at the moment.</p>';
         }
      ?>

   </div>

</section>

<!-- courses section ends -->

<?php include 'components/footer.php'; ?>

</body>
</html>
