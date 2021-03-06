<?php
	require 'config/config.php'; 
	include("includes/classes/User.php");
	include("includes/classes/Post.php");
	include("includes/classes/Notification.php");

	if (isset($_SESSION['username'])){
		$userLoggedIn = $_SESSION['username'];
		$user_details_query = mysqli_query($con, "SELECT * FROM USER WHERE USERNAME='$userLoggedIn'");
		$user = mysqli_fetch_array($user_details_query);
	}
	else {
		header("Location: register.php");
	}

?>

<!DOCTYPE html>
<html>
<head>
	<link rel="stylesheet" type="text/css" href="assets/css/style.css">
</head>
<body>

	<style type="text/css">
	* {
		font-size: 12px;
		font-style: Arial, Helvetica, Sans-serif;
	}

	</style>
	
	<script>
		function toggle() {
			var element = document.getElementById("comment_section");

			if(element.style.display == "block") 
				element.style.display = "none";
			else 
				element.style.display = "block";
		}
	</script>

	<?php  
	//Get id of post
	if(isset($_GET['post_id'])) {

		$post_id = $_GET['post_id'];
	}

	$user_query = mysqli_query($con , "SELECT ADDED_BY,USER_TO FROM MEDIA WHERE ID='$post_id'");
	$row = mysqli_fetch_array($user_query);

	$posted_to = $row['ADDED_BY'];
	$user_to = $row['USER_TO'];

	if(isset($_POST['postComment' . $post_id])) {
		$post_body = $_POST['post_body'];
		$post_body = mysqli_escape_string($con, $post_body);
		$date_time_now = date("Y-m-d H:i:s");
		$insert_post = mysqli_query($con, "INSERT INTO MEDIA_COMMENTS VALUES('','$post_body','$userLoggedIn','$posted_to','$date_time_now','no','$post_id')");

		if($posted_to != $userLoggedIn) {
			$notification = new Notification($con, $userLoggedIn);
			$notification->insertNotification($post_id, $posted_to, "comment");
		}
		
		if($user_to != 'none' && $user_to != $userLoggedIn) {
			$notification = new Notification($con, $userLoggedIn);
			$notification->insertNotification($post_id, $user_to, "profile_comment");	
		}

		$get_commenters = mysqli_query($con, "SELECT * FROM MEDIA_COMMENTS WHERE POST_ID='$post_id'");
		$notified_users = array();
		while($row = mysqli_fetch_array($get_commenters)) {

			if($row['POSTED_BY'] != $posted_to && $row['POSTED_BY'] != $user_to
				&& $row['POSTED_BY'] != $userLoggedIn && !in_array($row['POSTED_BY'], $notified_users)) {
				$notification = new Notification($con, $userLoggedIn);
				$notification->insertNotification($post_id, $row['POSTED_BY'], "comment_non_owner"); 

				array_push($notified_users, $row['POSTED_BY']);
			}

		}

		echo "<p>Comment Posted! </p>";
	}
	?>
	<form action="comment_frame.php?post_id=<?php echo $post_id; ?>" id="comment_form" name="postComment<?php echo $post_id; ?>" method="POST">
		<textarea name="post_body"></textarea>
		<input type="submit" name="postComment<?php echo $post_id; ?>" vlaue="Post">
	</form>

	<!-- Load comments -->
	<?php  
	$get_comments = mysqli_query($con, "SELECT * FROM MEDIA_COMMENTS WHERE POST_ID='$post_id' ORDER BY ID DESC");
	$count = mysqli_num_rows($get_comments);

	if($count != 0) {

		while($comment = mysqli_fetch_array($get_comments)) {

			$comment_body = $comment['POST_BODY'];
			$posted_to = $comment['POSTED_TO'];
			$posted_by = $comment['POSTED_BY'];
			$date_added = $comment['DATE_ADDED'];
			$removed = $comment['REMOVED'];

			//TimeFrame
			$date_time_now = date("Y-m-d H:i:s");
			$start_date = new DateTime($date_added); // Time of post
			$end_date = new DateTime($date_time_now); // Current time
			$interval = $start_date->diff($end_date); // Difference between dates
			if ($interval->y >= 1) {
				if ($interval == 1)
					$time_message = $interval->y . " year ago"; //1 year ago
				else
					$time_message = $interval->y . " years ago"; //1+ year ago
			}
			else if ($interval-> m >=1) {
				if ($interval->d == 0) {
					$days = " ago";
				}
				else if($interval->d == 1) {
					$days = $interval->d . " day ago";
				}
				else {
					$days = $interval->d . " days ago";
				}

				if ($interval->m == 1) {
					$time_message = $interval->m . " month". $days;
				}
				else {
					$time_message = $interval->m . " months". $days;
				}

			}
			else if($interval->d >=1) {
				if($interval->d == 1) {
					$time_message = "Yesterday";
				}
				else {
					$time_message = $interval->d . " days ago";
				}
			}
			else if ($interval->h >= 1) {
				if($interval->h == 1) {
					$time_message = $interval->h . " hour ago";
				}
				else {
					$time_message = $interval->h . " hours ago";
				}
			}
			else if ($interval->i >= 1) {
				if($interval->i == 1) {
					$time_message = $interval->i . " minute ago";
				}
				else {
					$time_message = $interval->i . " minutes ago";
				}
			}

			else {
				if($interval->s < 30) {
					$time_message = " Just now";
				}
				else {
					$time_message = $interval->s . " seconds ago";
				} 
			}

			$user_obj = new User($con, $posted_by);

			?>

			<div class="comment_section">
				<a href="<?php echo $posted_by?>" target="_parent"><img src="<?php echo $user_obj->getProfilePic();?>" title="<?php echo $posted_by; ?>" style="float:left;" height="30"></a>
				<a href="<?php echo $posted_by?>" target="_parent"> <b><?php echo "@" . $user_obj->getUsername(); ?></b></a>
				&nbsp;&nbsp;&nbsp;&nbsp; <?php echo $time_message . "<br>" . $comment_body; ?>
				<hr>
			</div>

			<?php
		}
	}
	else {
		echo "<center><br><br>No Comments to Show!</center>";
	}

	?>

	






</body>
</html>