<?php 
class Message {
	private $user_obj;
	private $con;

	public function __construct($con, $user){
		$this->con = $con;
		$this->user_obj = new User($con, $user);
	}
	public function getMostRecentUser() {
		$userLoggedIn = $this->user_obj->getUsername();

		$query = mysqli_query($this->con, "SELECT USER_TO,USER_FROM FROM MESSAGES WHERE USER_TO='$userLoggedIn' OR USER_FROM='$userLoggedIn' ORDER BY ID DESC LIMIT 1");

		if(mysqli_num_rows($query) == 0)
			return false;
		$row = mysqli_fetch_array($query);
		$user_to = $row['USER_TO'];
		$user_from = $row['USER_FROM'];

		if($user_to != $userLoggedIn)
			return $user_to;
		else
			return $user_from;
			
	}

	public function sendMessage($user_to, $body, $date) {

		if($body != "") {
			$userLoggedIn = $this->user_obj->getUsername();
			$query = mysqli_query($this->con, "INSERT INTO MESSAGES VALUES('','$user_to','$userLoggedIn','$body','$date','no','no','no')");
		}
	}


	public function getMessages($otherUser) {
		$userLoggedIn = $this->user_obj->getUsername();
		$data = "";
 
		$query = mysqli_query($this->con, "UPDATE MESSAGES SET OPENED='yes' WHERE USER_TO='$userLoggedIn' AND USER_FROM='$otherUser'");
 
		$get_messages_query = mysqli_query($this->con, "SELECT * FROM MESSAGES WHERE (USER_TO='$userLoggedIn' AND USER_FROM='$otherUser') OR (USER_FROM='$userLoggedIn' AND USER_TO='$otherUser')");
 
		while($row = mysqli_fetch_array($get_messages_query)) {
			$user_to = $row['USER_TO'];
			$user_from = $row['USER_FROM'];
			$body = $row['BODY'];
			$id = $row['ID'];
 
			$div_top = ($user_to == $userLoggedIn) ? "<div class='message' id='green'>" : "<div class='message' id='blue'>";
			$data = $data . $div_top . $body . "</div><br><br><br>";
		}
		return $data;
	}

	public function getLatestMessage($userLoggedIn, $user2) {
		$details_array = array();

		$query = mysqli_query($this->con, "SELECT BODY,USER_TO,DATE FROM MESSAGES WHERE (USER_TO='$userLoggedIn' AND USER_FROM='$user2') OR (USER_TO='$user2' AND USER_FROM='$userLoggedIn') ORDER BY ID DESC LIMIT 1");

		$row = mysqli_fetch_array($query);
		$sent_by = ($row['USER_TO'] == $userLoggedIn) ? "They said: " : "You said: ";


		//TimeFrame
		$date_time_now = date("Y-m-d H:i:s");
		$start_date = new DateTime($row['DATE']); // Time of post
		$end_date = new DateTime($date_time_now); // Current time
		$interval = $start_date->diff($end_date); // Difference between dates
		if ($interval->y >= 1) {
			if ($interval == 1)
				$time_message = $interval->y . " year ago"; //1 year ago
			else
				$time_message = $interval->y . " years ago"; //1+ year ago
		}
		else if ($interval->m >= 1) {
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
		else if ($interval->d >= 1) {
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

		array_push($details_array, $sent_by);
		array_push($details_array, $row['BODY']);
		array_push($details_array, $time_message);

		return $details_array;
		
		
	}

	public function getConvos() {
		$userLoggedIn  = $this->user_obj->getUsername();
		$return_string = "";
		$convos = array();

		$query = mysqli_query($this->con, "SELECT USER_TO,USER_FROM FROM MESSAGES WHERE USER_TO='$userLoggedIn' OR USER_FROM='$userLoggedIn' ORDER BY ID DESC");

		while ($row= mysqli_fetch_array($query)) {
			$user_to_push = ($row['USER_TO'] != $userLoggedIn) ? $row['USER_TO'] :$row['USER_FROM'];

			if(!in_array($user_to_push, $convos)) {
				array_push($convos, $user_to_push);
			}
		}

		foreach($convos as $username) {
			$user_found_obj = new User($this->con, $username);
			$latest_message_details = $this->getLatestMessage($userLoggedIn, $username);

			$dots = (strlen($latest_message_details[1]) >= 12) ? "..." : "";
			$split = str_split($latest_message_details[1], 12);
			$split = $split[0] . $dots;

			$return_string .= "<a href='messages.php?u=$username'> <div class='user_found_messages'>
								<img src='" . $user_found_obj->getProfilePic() . "' style='border-radius: 5px; margin-right: 5px;'>
								" . $user_found_obj->getName() . "
								<span class='timestamp_smaller' id='grey'> " . $latest_message_details[2] . "</span>
								<p id='grey' style='margin: 0;'>" . $latest_message_details[0] . $split . "</p>
								</div>
								</a>";
		}

		return $return_string;

	}
}

?>

