
<?php
class ViewHelper {
    /**
     * Get status badge HTML with proper styling
     */
    public static function getStatusBadge($status) {
        $status = htmlspecialchars($status);
        $badges = [
            'Approved' => ['class' => 'badge-success', 'icon' => 'fa-check'],
            'Pending' => ['class' => 'badge-warning', 'icon' => 'fa-clock'],
            'Rejected' => ['class' => 'badge-danger', 'icon' => 'fa-times']
        ];
        
        $badge = $badges[$status] ?? ['class' => 'badge-secondary', 'icon' => 'fa-question'];
        return sprintf(
            '<span class="badge %s status-badge"><i class="fas %s mr-1"></i>%s</span>',
            $badge['class'],
            $badge['icon'],
            $status
        );
    }

    /**
     * Render action buttons for a record
     */
    public static function renderActionDropdown($id) {
        $id = (int)$id;
        return '
        <div class="dropdown">
            <button class="btn btn-secondary dropdown-toggle" type="button" id="actionDropdown'.$id.'" data-toggle="dropdown">
                Actions
            </button>
            <div class="dropdown-menu">
                <form action="update_status.php" method="POST" class="status-form">
                    <input type="hidden" name="id" value="'.$id.'">
                    <input type="hidden" name="status" value="Approved">
                    <button type="submit" class="dropdown-item text-success">
                        <i class="fas fa-check"></i> Approve
                    </button>
                </form>
                <form action="update_status.php" method="POST" class="status-form">
                    <input type="hidden" name="id" value="'.$id.'">
                    <input type="hidden" name="status" value="Pending">
                    <button type="submit" class="dropdown-item text-warning">
                        <i class="fas fa-clock"></i> Pending
                    </button>
                </form>
                <form action="update_status.php" method="POST" class="status-form">
                    <input type="hidden" name="id" value="'.$id.'">
                    <input type="hidden" name="status" value="Rejected">
                    <button type="submit" class="dropdown-item text-danger">
                        <i class="fas fa-times"></i> Reject
                    </button>
                </form>
                <div class="dropdown-divider"></div>
                <a href="edit.php?id='.$id.'" class="dropdown-item">
                    <i class="fas fa-edit"></i> Edit
                </a>
                <a href="#" class="dropdown-item text-danger delete-record" data-id="'.$id.'">
                    <i class="fas fa-trash"></i> Delete
                </a>
            </div>
        </div>';
    }

    /**
     * Display notification message
     */
    public static function showNotification($message, $type = 'info') {
        $message = htmlspecialchars($message);
        $alertClass = $type === 'success' ? 'alert-success' : 
                     ($type === 'error' ? 'alert-danger' : 'alert-info');
        
        return '
        <div class="alert ' . $alertClass . ' alert-dismissible fade show" role="alert">
            ' . $message . '
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>';
    }
}