<?php
require_once('../config.php');
class Users extends DBConnection {
	private $settings;
	public function __construct(){
		global $_settings;
		$this->settings = $_settings;
		parent::__construct();
	}
	public function __destruct(){
		parent::__destruct();
	}
	public function save_users(){
		extract($_POST);
		$oid = $id;
		$data = '';
		
		// Check if this is an avatar-only update
		$isAvatarUpdate = isset($_POST['avatar_only']) && $_POST['avatar_only'] == '1';
		
		// Validate required fields for new users (relax for admin editors)
		if(empty($id)) {
			$editorRole = (int)$this->settings->userdata('role');
			if($editorRole === 1) {
				// Admins can create users with minimal required fields
				if(empty($firstname) || empty($lastname) || empty($username) || empty($password) || empty($role)) {
					return 5; // Minimal required fields missing
				}
			} else {
				if(empty($firstname) || empty($lastname) || empty($username) || empty($password) || empty($email) || empty($phone) || empty($role)) {
					return 5; // Missing required fields
				}
			}
		}
		
		if(isset($oldpassword)){
			if(md5($oldpassword) != $this->settings->userdata('password')){
				return 4;
			}
		}
		
		// Get current user's role and the target user's current username
		$currentUserRole = $this->settings->userdata('role');
		$currentUsername = '';
		if(!empty($id)){
			$current = $this->conn->query("SELECT username FROM `users` WHERE id = '{$id}'")->fetch_assoc();
			$currentUsername = $current['username'] ?? '';
		}
		
		// Skip username validation if:
		// 1. It's just an avatar update (no username change) OR
		// 2. It's a student updating their own profile AND username hasn't changed
		$skipUsernameCheck = $isAvatarUpdate || ($currentUserRole == 3 && !empty($id) && $username === $currentUsername);
		
		if (!$skipUsernameCheck && isset($username) && !empty($username)) {
			$chk = $this->conn->query("SELECT * FROM `users` where username ='{$username}' ".($id>0? " and id!= '{$id}' " : ""))->num_rows;
			if($chk > 0){
				return 3;
				exit;
			}
		}
		
		// Handle avatar removal request (admin Edit User)
		if (isset($_POST['remove_avatar']) && $_POST['remove_avatar'] === '1' && !empty($id)) {
			$cur = $this->conn->query("SELECT avatar FROM users WHERE id = ".(int)$id." LIMIT 1");
			if ($cur && $cur->num_rows) {
				$row = $cur->fetch_assoc();
				$avatarPath = isset($row['avatar']) ? explode('?', $row['avatar'])[0] : '';
				if ($avatarPath && is_file(base_app.$avatarPath)) {
					@unlink(base_app.$avatarPath);
				}
			}
			$this->conn->query("UPDATE users SET avatar = '' WHERE id = ".(int)$id);
			if($id == $this->settings->userdata('id')){
				$this->settings->set_userdata('avatar','');
			}
		}

		// Skip data updates for avatar-only updates
		if (!$isAvatarUpdate) {
			// Ensure lrn column exists
			try {
				$this->conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS lrn VARCHAR(20) NULL AFTER phone");
			} catch (Exception $e) {}
			// Handle basic user fields
			$basicFields = ['firstname', 'middlename', 'lastname', 'username', 'email', 'phone', 'lrn', 'role'];
			foreach($basicFields as $field) {
				if(isset($_POST[$field])) {
					if(!empty($data)) $data .= ", ";
					$data .= " `{$field}` = '" . $this->conn->real_escape_string($_POST[$field]) . "' ";
				}
			}
			
			// Handle student-specific fields
			if(isset($_POST['role']) && $_POST['role'] == '3') {
				if(isset($_POST['course'])) {
					if(!empty($data)) $data .= ", ";
					$data .= " `course` = '" . $this->conn->real_escape_string($_POST['course']) . "' ";
				}
				if(isset($_POST['year_level'])) {
					if(!empty($data)) $data .= ", ";
					$data .= " `year_level` = '" . $this->conn->real_escape_string($_POST['year_level']) . "' ";
				}
			}
			
			// Set date fields
			if(empty($id)) {
				if(!empty($data)) $data .= ", ";
				$data .= " `date_added` = NOW() ";
			}
			if(!empty($data)) $data .= ", ";
			$data .= " `date_updated` = NOW() ";
		}
		
		if(!empty($password)){
			$password = md5($password);
			if(!empty($data)) $data .=" , ";
			$data .= " `password` = '{$password}' ";
		}

		$status = 0; // Initialize status
		
		if(empty($id)){
			// Prevent invalid SQL when no fields were collected (e.g., oversized upload wiped POST)
			if(trim($data) === ''){
				return 5; // Treat as missing required fields
			}
			$qry = $this->conn->query("INSERT INTO users set {$data}");
			if($qry){
				$id = $this->conn->insert_id;
				$this->settings->set_flashdata('success','User Details successfully saved.');
				$status = 1;
			}else{
				$status = 2;
			}

		}else{
			if(empty($data) && !isset($_FILES['img'])) {
				return 1; // Nothing to update
			}
			$qry = $this->conn->query("UPDATE users set ".(!empty($data) ? $data : "id=id")." where id = {$id}");
			if($qry){
				$this->settings->set_flashdata('success','User Details successfully updated.');
				if($id == $this->settings->userdata('id')){
					foreach($_POST as $k => $v){
						if($k != 'id'){
							if(!empty($data)) $data .=" , ";
							$this->settings->set_userdata($k,$v);
						}
					}
				}
				$status = 1;
			}else{
				$status = 2;
			}
		}
		
		// Handle user_meta table for role (legacy support) - only if table exists
		if($status == 1 && !$isAvatarUpdate && isset($_POST['role'])){
			// Check if user_meta table exists
			$tableCheck = $this->conn->query("SHOW TABLES LIKE 'user_meta'");
			if($tableCheck && $tableCheck->num_rows > 0){
				// Remove old role from user_meta
				$this->conn->query("DELETE FROM `user_meta` where user_id = '{$id}' AND meta_field = 'role'");
				
				// Insert new role to user_meta
				$roleValue = $this->conn->real_escape_string($_POST['role']);
				$this->conn->query("INSERT INTO `user_meta` (user_id, meta_field, meta_value) VALUES ('{$id}', 'role', '{$roleValue}')");
			}
		}
		
		// Handle other user_meta fields (if any) - only if table exists
        if($status == 1 && !$isAvatarUpdate){
			// Check if user_meta table exists
			$tableCheck = $this->conn->query("SHOW TABLES LIKE 'user_meta'");
			if($tableCheck && $tableCheck->num_rows > 0){
				$data="";
				foreach($_POST as $k => $v){
					if(!in_array($k,array('id','firstname','middlename','lastname','username','password','role','course','year_level','oldpassword','avatar_only','lrn'))){
						if(!empty($data)) $data .=", ";
						$v = $this->conn->real_escape_string($v);
						$data .= "('{$id}','{$k}', '{$v}')";
					}
				}
				if(!empty($data)){
					// Remove old meta data for this user (except role)
					$this->conn->query("DELETE FROM `user_meta` where user_id = '{$id}' AND meta_field != 'role'");
					$save = $this->conn->query("INSERT INTO `user_meta` (user_id,`meta_field`,`meta_value`) VALUES {$data}");
					// Do not downgrade success or delete the user if meta insert fails
					if(!$save){
						// Optionally log error; keep user creation successful
						// error_log('Failed to save user_meta for user ID '.$id.': '.$this->conn->error);
					}
				}
			}
        }
		
		if(isset($_FILES['img']) && $_FILES['img']['tmp_name'] != ''){
			// Ensure uploads directory exists
			$uploadsDirFs = base_app.'uploads';
			if(!is_dir($uploadsDirFs)){
				@mkdir($uploadsDirFs, 0775, true);
			}
			
			$originalName = isset($_FILES['img']['name']) ? $_FILES['img']['name'] : 'avatar.png';
			$originalExt = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
			$allowedExts = array('png','jpg','jpeg');
			$upload = $_FILES['img']['tmp_name'];
			
			// Safely detect mime type if function exists; fallback to extension
			$type = function_exists('mime_content_type') ? mime_content_type($upload) : '';
			$allowedMimes = array('image/png','image/jpeg','image/jpg');
			$extIsAllowed = in_array($originalExt, $allowedExts);
			$mimeIsAllowed = empty($type) ? true : in_array($type, $allowedMimes);
			
			$resp = array('msg' => '');
			
			if(!$extIsAllowed || !$mimeIsAllowed){
				$resp['msg'] .= " But image failed to upload due to invalid file type.";
			}else{
				$targetExt = $extIsAllowed ? $originalExt : 'png';
				$fname = 'uploads/avatar-'.$id.'.'.$targetExt;
				$dir_path = base_app.$fname;
				
				$uploaded_img = false;
				
				// Prefer resizing if GD is available; otherwise move as-is
				if(function_exists('imagecreatetruecolor') && function_exists('getimagesize')){
					try {
						$new_height = 200;
						$new_width = 200;
						list($width, $height) = getimagesize($upload);
						$t_image = imagecreatetruecolor($new_width, $new_height);
						imagealphablending($t_image, false);
						imagesavealpha($t_image, true);
						
						// Create source image based on available type/ext
						$src = null;
						if(($type === 'image/png') || $targetExt === 'png'){
							if(function_exists('imagecreatefrompng')) $src = @imagecreatefrompng($upload);
						}else{
							if(function_exists('imagecreatefromjpeg')) $src = @imagecreatefromjpeg($upload);
						}
						
						if($src){
							imagecopyresampled($t_image, $src, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
							if(is_file($dir_path)) @unlink($dir_path);
							// Save according to target extension
							if($targetExt === 'png' && function_exists('imagepng')){
								$uploaded_img = imagepng($t_image, $dir_path);
							}else if(function_exists('imagejpeg')){
								$uploaded_img = imagejpeg($t_image, $dir_path, 90);
							}
							if(is_resource($src)) imagedestroy($src);
							if(is_resource($t_image)) imagedestroy($t_image);
						}
					} catch (Exception $e) {
						$resp['msg'] .= " But image failed to process. Error: ".$e->getMessage();
					}
				}
				
				// Fallback: move uploaded file if resize did not run
				if(!$uploaded_img){
					if(is_file($dir_path)) @unlink($dir_path);
					$uploaded_img = @move_uploaded_file($upload, $dir_path);
				}
				
				if(!$uploaded_img){
					$resp['msg'] .= " But image failed to upload.";
				}else{
					$this->conn->query("UPDATE users set `avatar` = CONCAT('{$fname}','?v=',unix_timestamp(CURRENT_TIMESTAMP)) where id = '{$id}' ");
					if($id == $this->settings->userdata('id')){
						$this->settings->set_userdata('avatar',$fname);
					}
				}
			}
		}
		if(isset($resp['msg']))
			$this->settings->set_flashdata('success',$resp['msg']);
		elseif($isAvatarUpdate)
			$this->settings->set_flashdata('success','Profile picture updated successfully.');
		return $status;
	}
	public function delete_users(){
		extract($_POST);
		$avatar = $this->conn->query("SELECT avatar FROM users where id = '{$id}'")->fetch_array()['avatar'];
		$qry = $this->conn->query("DELETE FROM users where id = $id");
		if($qry){
			$avatar = explode("?",$avatar)[0];
			$this->settings->set_flashdata('success','User Details successfully deleted.');
			if(is_file(base_app.$avatar))
				unlink(base_app.$avatar);
			$resp['status'] = 'success';
		}else{
			$resp['status'] = 'failed';
		}
		return json_encode($resp);
	}
	
	public function save_susers(){
		extract($_POST);
		$data = "";
		foreach($_POST as $k => $v){
			if(!in_array($k, array('id','password'))){
				if(!empty($data)) $data .= ", ";
				$data .= " `{$k}` = '{$v}' ";
			}
		}

			if(!empty($password))
			$data .= ", `password` = '".md5($password)."' ";
		
			if(isset($_FILES['img']) && $_FILES['img']['tmp_name'] != ''){
				$fname = 'uploads/'.strtotime(date('y-m-d H:i')).'_'.$_FILES['img']['name'];
				$move = move_uploaded_file($_FILES['img']['tmp_name'],'../'. $fname);
				if($move){
					$data .=" , avatar = '{$fname}' ";
					if(isset($_SESSION['userdata']['avatar']) && is_file('../'.$_SESSION['userdata']['avatar']))
						unlink('../'.$_SESSION['userdata']['avatar']);
				}
			}
			$sql = "UPDATE students set {$data} where id = $id";
			$save = $this->conn->query($sql);

			if($save){
			$this->settings->set_flashdata('success','User Details successfully updated.');
			foreach($_POST as $k => $v){
				if(!in_array($k,array('id','password'))){
					if(!empty($data)) $data .=" , ";
					$this->settings->set_userdata($k,$v);
				}
			}
			if(isset($fname) && isset($move))
			$this->settings->set_userdata('avatar',$fname);
			return 1;
			}else{
				$resp['error'] = $sql;
				return json_encode($resp);
			}

	} 
	
}

$users = new users();
$action = !isset($_GET['f']) ? 'none' : strtolower($_GET['f']);
switch ($action) {
	case 'save':
		echo $users->save_users();
	break;
	// Removed undefined method call
	case 'fsave':
		echo json_encode(['status' => 'error', 'message' => 'Undefined method save_fusers']);
	break;
	case 'ssave':
		echo $users->save_susers();
	break;
	case 'delete':
		echo $users->delete_users();
	break;
	default:
		// echo $sysset->index();
		break;
}