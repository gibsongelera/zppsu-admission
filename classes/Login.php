<?php
require_once '../config.php';
class Login extends DBConnection {
	private $settings;
	public function __construct(){
		global $_settings;
		$this->settings = $_settings;

		parent::__construct();
		ini_set('display_error', 1);
	}
	public function __destruct(){
		parent::__destruct();
	}
	public function index(){
		echo "<h1>Access Denied</h1> <a href='".base_url."'>Go Back.</a>";
	}
	public function login(){
		extract($_POST);
		
		// Sanitize inputs
		$username = trim($username ?? '');
		$password = trim($password ?? '');
		
		if(empty($username) || empty($password)){
			return json_encode(array('status'=>'incorrect','msg'=>'Username and password are required'));
		}

		// Escape inputs for safety
		$escaped_username = $this->conn->real_escape_string($username);
		$escaped_password = $this->conn->real_escape_string($password);
		
		// Use case-insensitive username comparison (works with both MySQL and PostgreSQL)
		$qry = $this->conn->query("SELECT * from users where LOWER(username) = LOWER('$escaped_username') and password = md5('$escaped_password')");
		
		if($qry && $qry->num_rows > 0){
			foreach($qry->fetch_array() as $k => $v){
				if(!is_numeric($k) && $k != 'password'){
					$this->settings->set_userdata($k,$v);
				}
			}
			// Set login_type based on role or type column (database might have either)
			$userRole = $this->settings->userdata('role') ?? $this->settings->userdata('type') ?? 1;
			$this->settings->set_userdata('login_type', $userRole);
			return json_encode(array('status'=>'success'));
		}else{
			return json_encode(array('status'=>'incorrect','msg'=>'Incorrect username or password'));
		}
	}
	public function logout(){
		if($this->settings->sess_des()){
			redirect('admin/login.php');
		}
	}
	function login_user(){
		extract($_POST);
		
		// Sanitize inputs
		$username = trim($username ?? '');
		$password = trim($password ?? '');
		
		if(empty($username) || empty($password)){
			$resp['status'] = 'incorrect';
			$resp['msg'] = 'Username and password are required';
			return json_encode($resp);
		}
		
		// Escape inputs for safety
		$escaped_username = $this->conn->real_escape_string($username);
		$escaped_password = $this->conn->real_escape_string($password);
		
		// Use case-insensitive username comparison and check for both 'role' and 'type' columns
		$qry = $this->conn->query("SELECT * from users where LOWER(username) = LOWER('$escaped_username') and password = md5('$escaped_password') and (role = 2 OR type = 2)");
		
		if($qry && $qry->num_rows > 0){
			foreach($qry->fetch_array() as $k => $v){
				$this->settings->set_userdata($k,$v);
			}
			$this->settings->set_userdata('login_type', 2);
			$resp['status'] = 'success';
		}else{
			$resp['status'] = 'incorrect';
			$resp['msg'] = 'Incorrect username or password';
		}
		if($this->conn->error){
			$resp['status'] = 'failed';
			$resp['_error'] = $this->conn->error;
		}
		return json_encode($resp);
	}
	public function logout_user(){
		if($this->settings->sess_des()){
			redirect('./');
		}
	}
}
$action = !isset($_GET['f']) ? 'none' : strtolower($_GET['f']);
$auth = new Login();
switch ($action) {
	case 'login':
		echo $auth->login();
		break;
	case 'login_user':
		echo $auth->login_user();
		break;
	case 'logout':
		echo $auth->logout();
		break;
	case 'logout_user':
		echo $auth->logout_user();
		break;
	default:
		echo $auth->index();
		break;
}

