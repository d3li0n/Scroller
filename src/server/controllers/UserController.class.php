<?php 
@session_start();
include $_SERVER["DOCUMENT_ROOT"].'/server/helpers/Controller.class.php';
include $_SERVER["DOCUMENT_ROOT"].'/server/controllers/TokenController.class.php';

class UserController extends Controller {
		
	/**
	 * register
	 *
	 * @param  array $params
	 * @return array
	 */
	public function register(array $params) : array {
		$conn = (new DatabaseConnector())->getConnection();

		$characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
		$hash = '';
		
		for($i = 0; $i < 10; $i++)
			$hash .= $characters[mt_rand(0, 61)];
        
		$password = hash('sha256', $params[2] . $hash);
		
		$sql = "INSERT INTO users(username, email, password, salt) VALUES ('$params[0]', '$params[1]', '$password', '$hash')";
		mysqli_query($conn, $sql);

		$sql = "SELECT id FROM users WHERE username='$params[0]' LIMIT 1";
		$result = mysqli_query($conn, $sql);
		
		$row = mysqli_fetch_row($result);
		
		$end_token = time() + 86400;
		$token = '';

		for ($i = 0; $i < 60; $i++)
			$token .= $characters[mt_rand(0, 61)];

		$token = (new TokenController())->post([$token, $end_token, $row[0], 1]);

		mysqli_close($conn);
		return array("response" => 200);
	}
		
	/**
	 * login
	 *
	 * @param  array $params
	 * @return array
	 */
	public function login(array $params) : array {
		$result = array("response" => 400, "data" => array("message" => "User doesn't exist."));
		$conn = (new DatabaseConnector())->getConnection();

		$sql = "SELECT username, email, password, is_email_confirmed, salt, is_account_disabled, avatar_url, is_admin FROM users WHERE email='$params[0]'";
		$response = mysqli_query($conn, $sql);
		
		while($row = mysqli_fetch_assoc($response)) {
			if (!$row['is_email_confirmed']) {
				$result["data"] = array("message" => "Email is not verified");
				break;
			}

			if ($row['is_account_disabled']) {
				$result["data"] = array("message" => "Account is disabled by Administrator.");
				break;
			}

			$auth_password = hash('sha256', $params[1] . $row['salt']);

			if ($row['password'] === $auth_password) {
				$result =array("response" => 200);
				$_SESSION['IS_AUTHORIZED'] = true;
				$_SESSION['USERNAME'] = $row['username'];
				$_SESSION['USER_IMAGE'] = 'http://'.$_SERVER['HTTP_HOST'].'/client/img/'.$row['avatar_url'];
				$_SESSION['IS_ADMIN'] = $row['is_admin'];
				break;
			} else {
				$result["response"] = 400;
				$result["data"] = array("message" => "Password is not correct.");
				break;
			}
		}
		mysqli_close($conn);
		return $result;
	}

	public function get(array $params) : array {
		return array();
	}

	public function post(array $params) : array {
		return array();
	}

	public function update(array $params) : array {
		return array();
	}

	public function delete(array $params) : array {
		return array();
	}

	public function findById(int $id) : array {
		return array();
	}

	public function findByEmail(string $email) : bool {
		$conn = (new DatabaseConnector())->getConnection();

		$sql = "SELECT email FROM users WHERE email='$email'";
		$response = mysqli_query($conn, $sql);

		while($row = mysqli_fetch_assoc($response)) {
			mysqli_close($conn);
			return true;
		}
		mysqli_close($conn);
		return false;
	}

	public function isEmailConfirmed(string $email) : bool {
		$conn = (new DatabaseConnector())->getConnection();

		$sql = "SELECT is_email_confirmed FROM users WHERE email='$email' LIMIT 1";
		$result = mysqli_query($conn, $sql);
		
		$row = mysqli_fetch_row($result);

		mysqli_close($conn);
		return $row[0];
	}

	public function findByUsername(string $username) : bool {
		$conn = (new DatabaseConnector())->getConnection();

		$sql = "SELECT username FROM users WHERE username='$username'";
		$response = mysqli_query($conn, $sql);

		while($row = mysqli_fetch_assoc($response)) {
			mysqli_close($conn);
			return true;
		}
		mysqli_close($conn);
		return false;
	}

	public function findAll(array $params) : array {
		return array();
	}

	public function findPostsAndComments(/*array $params*/) : array {
		
		$conn = (new DatabaseConnector())->getConnection();
		
		$sql = "(SELECT body, threads.thread_url, 'comments' as body_comments FROM comments JOIN threads ON comments.thread_id = threads.thread_id WHERE user_id = 17 ORDER BY comments.created_at) UNION (SELECT body, threads.thread_url, 'posts' as body_posts FROM posts JOIN threads ON posts.thread_id = threads.thread_id WHERE user_id = 17 ORDER BY posts.created_at)";
		$response = mysqli_query($conn, $sql);

		$result = array();

		while($row = mysqli_fetch_assoc($response)) {
			array_push($result, array(
				"content" => $row['body'], 
				"url" =>  $row['thread_url'], 
				"type" => $row['body_comments'])
			);
		}
		mysqli_close($conn);
		return $result;
	}
}
?>