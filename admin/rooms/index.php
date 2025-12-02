<?php
require_once __DIR__ . '/../inc/db_connect.php';
require_once __DIR__ . '/../inc/room_handler.php';

$roomHandler = new RoomHandler($conn);

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $roomNumber = $_POST['room_number'] ?? '';
        $campus = $_POST['campus'] ?? '';
        $capacity = (int)($_POST['capacity'] ?? 30);
        
        if ($roomHandler->addRoom($roomNumber, $campus, $capacity)) {
            $message = "Room added successfully!";
            $messageType = "success";
        } else {
            $message = "Failed to add room. Room may already exist.";
            $messageType = "danger";
        }
    } elseif ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $roomNumber = $_POST['room_number'] ?? '';
        $campus = $_POST['campus'] ?? '';
        $capacity = (int)($_POST['capacity'] ?? 30);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        if ($roomHandler->updateRoom($id, $roomNumber, $campus, $capacity, $isActive)) {
            $message = "Room updated successfully!";
            $messageType = "success";
        } else {
            $message = "Failed to update room.";
            $messageType = "danger";
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        
        if ($roomHandler->deleteRoom($id)) {
            $message = "Room deleted successfully!";
            $messageType = "success";
        } else {
            $message = "Failed to delete room. It may be in use.";
            $messageType = "danger";
        }
    }
}

// Get all rooms
$rooms = $roomHandler->getAllRooms();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Room Management</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="container-fluid">
        <?php if (isset($message)): ?>
        <div class="alert alert-<?php echo $messageType ?> alert-dismissible fade show mt-3" role="alert">
            <?php echo htmlspecialchars($message) ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php endif; ?>
        
        <div class="card mt-4">
            <div class="card-header" style="background-color:#5E0A14; color:white;">
                <h3 class="mb-0">
                    <i class="fas fa-door-open"></i> Room Management
                </h3>
            </div>
            <div class="card-body">
                <button type="button" class="btn btn-primary mb-3" data-toggle="modal" data-target="#addRoomModal">
                    <i class="fas fa-plus"></i> Add New Room
                </button>
                
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead style="background-color:#5E0A14; color:white;">
                            <tr>
                                <th>ID</th>
                                <th>Room Number</th>
                                <th>Campus</th>
                                <th>Capacity</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($rooms)): ?>
                            <tr>
                                <td colspan="7" class="text-center">No rooms found</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($rooms as $room): ?>
                            <tr>
                                <td><?php echo $room['id'] ?></td>
                                <td><?php echo htmlspecialchars($room['room_number']) ?></td>
                                <td><?php echo htmlspecialchars($room['campus']) ?></td>
                                <td><?php echo $room['capacity'] ?></td>
                                <td>
                                    <?php if ($room['is_active']): ?>
                                        <span class="badge badge-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($room['created_at'])) ?></td>
                                <td>
                                    <button class="btn btn-sm btn-info" onclick="editRoom(<?php echo htmlspecialchars(json_encode($room)) ?>)">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="deleteRoom(<?php echo $room['id'] ?>, '<?php echo htmlspecialchars($room['room_number']) ?>')">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Room Modal -->
    <div class="modal fade" id="addRoomModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header" style="background-color:#5E0A14; color:white;">
                        <h5 class="modal-title">Add New Room</h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="form-group">
                            <label>Room Number</label>
                            <input type="text" class="form-control" name="room_number" required placeholder="e.g., Room 101">
                        </div>
                        <div class="form-group">
                            <label>Campus</label>
                            <select class="form-control" name="campus" required>
                                <option value="">Select Campus</option>
                                <option value="ZPPSU MAIN">ZPPSU MAIN</option>
                                <option value="Gregorio Campus (Vitali)">Gregorio Campus (Vitali)</option>
                                <option value="ZPPSU Campus (Kabasalan)">ZPPSU Campus (Kabasalan)</option>
                                <option value="Anna Banquial Campus (Malangas)">Anna Banquial Campus (Malangas)</option>
                                <option value="Timuay Tubod M. Mandi Campus (Siay)">Timuay Tubod M. Mandi Campus (Siay)</option>
                                <option value="ZPPSU Campus (Bayog)">ZPPSU Campus (Bayog)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Capacity</label>
                            <input type="number" class="form-control" name="capacity" required min="1" value="30">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Room</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Room Modal -->
    <div class="modal fade" id="editRoomModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header" style="background-color:#5E0A14; color:white;">
                        <h5 class="modal-title">Edit Room</h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="form-group">
                            <label>Room Number</label>
                            <input type="text" class="form-control" name="room_number" id="edit_room_number" required>
                        </div>
                        <div class="form-group">
                            <label>Campus</label>
                            <select class="form-control" name="campus" id="edit_campus" required>
                                <option value="">Select Campus</option>
                                <option value="ZPPSU MAIN">ZPPSU MAIN</option>
                                <option value="Gregorio Campus (Vitali)">Gregorio Campus (Vitali)</option>
                                <option value="ZPPSU Campus (Kabasalan)">ZPPSU Campus (Kabasalan)</option>
                                <option value="Anna Banquial Campus (Malangas)">Anna Banquial Campus (Malangas)</option>
                                <option value="Timuay Tubod M. Mandi Campus (Siay)">Timuay Tubod M. Mandi Campus (Siay)</option>
                                <option value="ZPPSU Campus (Bayog)">ZPPSU Campus (Bayog)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Capacity</label>
                            <input type="number" class="form-control" name="capacity" id="edit_capacity" required min="1">
                        </div>
                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" name="is_active" id="edit_is_active">
                                <label class="custom-control-label" for="edit_is_active">Active</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Room</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Delete Confirmation Form -->
    <form method="POST" id="deleteForm" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="delete_id">
    </form>
    
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
    function editRoom(room) {
        $('#edit_id').val(room.id);
        $('#edit_room_number').val(room.room_number);
        $('#edit_campus').val(room.campus);
        $('#edit_capacity').val(room.capacity);
        $('#edit_is_active').prop('checked', room.is_active == 1);
        $('#editRoomModal').modal('show');
    }
    
    function deleteRoom(id, roomNumber) {
        if (confirm('Are you sure you want to delete ' + roomNumber + '? This action cannot be undone.')) {
            $('#delete_id').val(id);
            $('#deleteForm').submit();
        }
    }
    </script>
</body>
</html>

