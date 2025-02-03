<?php
include '../components/connect.php';

// Check if the tutor is logged in
if(isset($_COOKIE['tutor_id'])){
   $tutor_id = $_COOKIE['tutor_id'];
}else{
   $tutor_id = '';
   header('location:login.php');
   exit; // Ensure that the script stops after redirecting
}

// Trim any spaces in the tutor_id to avoid potential mismatches
$tutor_id = trim($tutor_id);

// Fetch tutor profile data
$select_profile = $conn->prepare("SELECT * FROM tutors WHERE id = ?");
$select_profile->execute([$tutor_id]);
$fetch_profile = $select_profile->fetch(PDO::FETCH_ASSOC);

// Fetching content data
$select_contents = $conn->prepare("SELECT * FROM content WHERE tutor_id = ?");
$select_contents->execute([$tutor_id]);
$total_contents = $select_contents->rowCount();

// Fetching playlists data
$select_playlists = $conn->prepare("SELECT * FROM playlist WHERE tutor_id = ?");
$select_playlists->execute([$tutor_id]);
$total_playlists = $select_playlists->rowCount();

// Fetching likes data
$select_likes = $conn->prepare("SELECT * FROM likes WHERE tutor_id = ?");
$select_likes->execute([$tutor_id]);
$total_likes = $select_likes->rowCount();

// Fetching comments data
$select_comments = $conn->prepare("SELECT * FROM comments WHERE tutor_id = ?");
$select_comments->execute([$tutor_id]);
$total_comments = $select_comments->rowCount();

// Fetching student ratings (assuming there's a ratings table)
$select_ratings = $conn->prepare("SELECT AVG(rating) AS avg_rating FROM ratings WHERE tutor_id = ?");
$select_ratings->execute([$tutor_id]);
$average_rating = $select_ratings->fetch(PDO::FETCH_ASSOC)['avg_rating'];

// Fetching total earnings (adjusted for payments table)
$select_payments = $conn->prepare("SELECT SUM(amount) AS total_earnings FROM payments WHERE user_id = ?");
$select_payments->execute([$tutor_id]);
$total_earnings = $select_payments->fetch(PDO::FETCH_ASSOC)['total_earnings'];

// Fetching payment history (new query)
$select_payment_history = $conn->prepare("SELECT * FROM payments WHERE user_id = ? ORDER BY payment_date DESC");
$select_payment_history->execute([$tutor_id]);
$payment_history = $select_payment_history->fetchAll(PDO::FETCH_ASSOC);

// Fetching students for chat selection
$select_students = $conn->prepare("SELECT * FROM students");
$select_students->execute();
$students = $select_students->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Dashboard</title>

   <!-- font awesome cdn link -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">

   <!-- custom css file link -->
   <link rel="stylesheet" href="../css/admin_style.css">

   <!-- Pusher CDN -->
   <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
</head>
<body>

<?php include '../components/admin_header.php'; ?>

<section class="dashboard">

   <h1 class="heading">dashboard</h1>

   <div class="box-container">

      <div class="box">
         <h3>welcome!</h3>
         <p><?= isset($fetch_profile['name']) ? $fetch_profile['name'] : 'No Name Available'; ?></p>
         <a href="profile.php" class="btn">view profile</a>
      </div>

      <div class="box">
         <h3><?= $total_contents; ?></h3>
         <p>total contents</p>
         <a href="add_content.php" class="btn">add new content</a>
      </div>

      <div class="box">
         <h3><?= $total_playlists; ?></h3>
         <p>total playlists</p>
         <a href="add_playlist.php" class="btn">add new playlist</a>
      </div>

      <div class="box">
         <h3><?= $total_likes; ?></h3>
         <p>total likes</p>
         <a href="contents.php" class="btn">view contents</a>
      </div>

      <div class="box">
         <h3><?= $total_comments; ?></h3>
         <p>total comments</p>
         <a href="comments.php" class="btn">view comments</a>
      </div>

      <!-- Performance Metrics -->
      <div class="box">
         <h3>Performance Metrics</h3>
         <p>Lessons Taught: <?= $total_contents; ?></p>
         <p>Average Rating: <?= $average_rating !== null ? round($average_rating, 2) : 'No Rating Yet'; ?> / 5</p>
         <p>Total Earnings: $<?= number_format($total_earnings, 2); ?></p>
         <p>Likes: <?= $total_likes; ?></p>
         <p>Comments: <?= $total_comments; ?></p>
      </div>

      <!-- Payment History -->
      <div class="box">
         <h3>Payment History</h3>
         <?php if ($payment_history): ?>
            <table>
               <thead>
                  <tr>
                     <th>Amount</th>
                     <th>Status</th>
                     <th>Date</th>
                  </tr>
               </thead>
               <tbody>
                  <?php foreach ($payment_history as $payment): ?>
                     <tr>
                        <td><?= $payment['amount']; ?> <?= $payment['currency']; ?></td>
                        <td><?= ucfirst($payment['payment_status']); ?></td>
                        <td><?= $payment['payment_date']; ?></td>
                     </tr>
                  <?php endforeach; ?>
               </tbody>
            </table>
         <?php else: ?>
            <p>No payments found.</p>
         <?php endif; ?>
      </div>

      <!-- Real-time Chat -->
      <div class="box">
         <h3>Real-Time Chat</h3>
         <label for="student-select">Select Student:</label>
         <select id="student-select">
            <option value="">Select a student</option>
            <?php foreach ($students as $student): ?>
               <option value="<?= $student['id'] ?>"><?= $student['name'] ?></option>
            <?php endforeach; ?>
         </select>
         <div id="chat-box">
            <div id="messages"></div>
            <textarea id="message-input" placeholder="Type your message..."></textarea>
            <button id="send-btn">Send</button>
         </div>
      </div>

   </div>

</section>


<!-- test section -->

<a href="schedule.php" id="">schedule booking</a>
<?php include '../components/footer.php'; ?>

<script src="../js/admin_script.js"></script>

<script>
   // Enable pusher logging - don't include this in production
   Pusher.logToConsole = true;

   var pusher = new Pusher('3279cb90f7ca8767cc0f', {
      cluster: 'eu'  // Replace with your cluster
   });

   // Function to subscribe to a specific student's channel
   function subscribeToStudentChannel(studentId) {
      var channel = pusher.subscribe('chat-channel-' + studentId);
      channel.bind('new-message', function(data) {
         var message = data.message;
         var messagesContainer = document.getElementById("messages");
         var messageElement = document.createElement("div");
         messageElement.textContent = message;
         messagesContainer.appendChild(messageElement);
      });
   }

   // Handle the selection of a student
   document.getElementById("student-select").addEventListener("change", function() {
      var selectedStudentId = this.value;

      if (selectedStudentId) {
         subscribeToStudentChannel(selectedStudentId);  // Subscribe to the selected student's channel
      }
   });

   // Send message to selected student and broadcast via Pusher
   function sendMessage() {
      var messageInput = document.getElementById("message-input");
      var message = messageInput.value.trim();
      var selectedStudentId = document.getElementById("student-select").value;

      if (message && selectedStudentId) {
         messageInput.value = '';  // Clear the input field after sending

         // Send message to the backend (PHP)
         var xhr = new XMLHttpRequest();
         xhr.open("POST", "send_message.php", true);
         xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
         xhr.onload = function () {
            if (xhr.status == 200) {
                // Trigger Pusher event to the selected student
                pusher.trigger('chat-channel-' + selectedStudentId, 'new-message', {
                    message: message,
                    sender: "Tutor"
                });
            }
         };
         xhr.send("message=" + message + "&student_id=" + selectedStudentId);
      }
   }

   // Add click event for Send button
   document.getElementById("send-btn").addEventListener("click", sendMessage);
</script>

</body>
</html>
