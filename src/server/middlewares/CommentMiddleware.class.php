<?php

require_once $_SERVER["DOCUMENT_ROOT"].'/server/controllers/CommentController.class.php';
require_once $_SERVER["DOCUMENT_ROOT"].'/server/controllers/UserController.class.php';
header('Content-Type: application/json; charset=utf-8');

$response = array("response" => 400, "data" => array("message" => "Fields are empty."));
if ($_SERVER['REQUEST_METHOD'] === "GET") {
	if (!empty($_GET['query']) && isset($_GET['commentSearch']) && (bool) $_GET['commentSearch']) {
		$response = (new CommentMiddleware())->searchComments([$_GET['query']]);
	} else if (empty($_GET['query'])) {
		$response = (new CommentMiddleware())->loadAllComments();
	}
} else if ($_SERVER['REQUEST_METHOD'] === "POST") {
	if (!empty($_POST['commentId']) && !empty($_POST['type']) && ($_POST['type'] === "voteUp" || $_POST['type'] === "voteDown")) {
		$response = (new CommentMiddleware())->vote([$_POST['commentId'], $_POST['type']]);
	} else if (!empty($_POST['commentId']) && (bool)$_POST['deleteComment']) {
		$response = (new CommentMiddleware())->removeComment($_POST['commentId']);
	}
}

class CommentMiddleware {
    
  public function isLogged() : bool {
		if (isset($_SESSION['IS_AUTHORIZED'])) return true;
		return false;
	}

	public function vote(array $params) : array {
		if (!$this->isLogged()) return array("response" => 403);
		if (!(new UserController())->isEmailConfirmedByUserName($_SESSION['USERNAME'])) return array( "response" => 400, "data" => array("message" => "Email is not verified."));
	
		if ((new UserController())->isAccountDisabled($_SESSION['USERNAME'])) return array( "response" => 400, "data" => array("message" => "Unathorized attempt. Account is disabled."));
		
		$commentId = intval($params[0]);
		if ($commentId <= 0) return array("response" => 403);

		if (!(new CommentController())->isExist($commentId)) return array("response" => 403);

		if ($params[1] === "voteUp" || $params[1] === "voteDown")
			return (new CommentController())->vote([$commentId, $params[1]]);
		
		return array("response" => 403);
	}

	public function searchComments(array $params) : array {
	
		$query = filter_var($params[0], FILTER_SANITIZE_STRING);
		$query = trim($query);
		$query = stripslashes($query);
		$query = htmlspecialchars($query);

		return (new CommentController())->getCommentByQuery($query);
	}

	public function removeComment(int $commentId) : array {
		if (empty($commentId)) {
			return array("response" => 400, "data" => array("message" => "You must click a valid delete button in a valid thread of a valid post of a valid comment."));
		} 

		if ($commentId <= 0) {
			return array("response" => 400, "data" => array("message" => "You must click a valid delete button in a valid thread of a valid post of a valid comment."));
		}
		
		return (new CommentController())->deleteComment([$commentId]);
	}
}

$response = json_encode($response, true);
echo $response;
?>