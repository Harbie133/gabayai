<?php
session_start();

// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "gabayai";

// Contact limit
define('MAX_CONTACTS', 5);

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]));
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Function to get contact count for user
function getContactCount($conn, $user_id) {
    $sql = "SELECT COUNT(*) as count FROM emergency_contacts WHERE user_id='$user_id'";
    $result = $conn->query($sql);
    if ($result && $row = $result->fetch_assoc()) {
        return (int)$row['count'];
    }
    return 0;
}

// Handle POST requests (Add, Update, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        // Check if user has reached maximum contacts
        $contactCount = getContactCount($conn, $user_id);
        if ($contactCount >= MAX_CONTACTS) {
            echo json_encode(['success' => false, 'message' => 'Maximum limit of ' . MAX_CONTACTS . ' contacts reached']);
            exit();
        }

        // Add new emergency contact
        $contact_name = $conn->real_escape_string(trim($_POST['contactName']));
        $relationship = $conn->real_escape_string($_POST['relationship']);
        $phone = $conn->real_escape_string(trim($_POST['contactNumber'] ?? ''));
        $alternate_phone = $conn->real_escape_string(trim($_POST['alternateNumber'] ?? ''));
        $email = $conn->real_escape_string(trim($_POST['email'] ?? ''));
        $address = $conn->real_escape_string(trim($_POST['address'] ?? ''));

        // Validate that at least one contact method is provided
        if (empty($phone) && empty($email)) {
            echo json_encode(['success' => false, 'message' => 'Please provide at least one contact method (phone or email)']);
            exit();
        }

        // Validate contact name
        if (empty($contact_name)) {
            echo json_encode(['success' => false, 'message' => 'Contact name is required']);
            exit();
        }

        // Validate relationship
        if (empty($relationship)) {
            echo json_encode(['success' => false, 'message' => 'Relationship is required']);
            exit();
        }

        $sql = "INSERT INTO emergency_contacts (user_id, contact_name, relationship, phone, alternate_phone, email, address, created_at) 
                VALUES ('$user_id', '$contact_name', '$relationship', '$phone', '$alternate_phone', '$email', '$address', NOW())";

        if ($conn->query($sql) === TRUE) {
            echo json_encode(['success' => true, 'message' => 'Emergency contact added successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error adding contact: ' . $conn->error]);
        }
    } 
    elseif ($action === 'update') {
        // Update existing emergency contact
        $contact_id = (int)$_POST['contactId'];
        $contact_name = $conn->real_escape_string(trim($_POST['contactName']));
        $relationship = $conn->real_escape_string($_POST['relationship']);
        $phone = $conn->real_escape_string(trim($_POST['contactNumber'] ?? ''));
        $alternate_phone = $conn->real_escape_string(trim($_POST['alternateNumber'] ?? ''));
        $email = $conn->real_escape_string(trim($_POST['email'] ?? ''));
        $address = $conn->real_escape_string(trim($_POST['address'] ?? ''));

        // Validate that at least one contact method is provided
        if (empty($phone) && empty($email)) {
            echo json_encode(['success' => false, 'message' => 'Please provide at least one contact method (phone or email)']);
            exit();
        }

        // Validate contact name
        if (empty($contact_name)) {
            echo json_encode(['success' => false, 'message' => 'Contact name is required']);
            exit();
        }

        // Validate relationship
        if (empty($relationship)) {
            echo json_encode(['success' => false, 'message' => 'Relationship is required']);
            exit();
        }

        $sql = "UPDATE emergency_contacts 
                SET contact_name='$contact_name', 
                    relationship='$relationship', 
                    phone='$phone', 
                    alternate_phone='$alternate_phone', 
                    email='$email', 
                    address='$address', 
                    updated_at=NOW() 
                WHERE id=$contact_id AND user_id='$user_id'";

        if ($conn->query($sql) === TRUE) {
            if ($conn->affected_rows > 0) {
                echo json_encode(['success' => true, 'message' => 'Emergency contact updated successfully!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'No changes made or contact not found']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Error updating contact: ' . $conn->error]);
        }
    } 
    elseif ($action === 'delete') {
        // Delete emergency contact (no minimum restriction)
        $contact_id = (int)$_POST['id'];

        $sql = "DELETE FROM emergency_contacts WHERE id=$contact_id AND user_id='$user_id'";

        if ($conn->query($sql) === TRUE) {
            if ($conn->affected_rows > 0) {
                echo json_encode(['success' => true, 'message' => 'Contact deleted successfully!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Contact not found']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Error deleting contact: ' . $conn->error]);
        }
    }
    else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} 
// Handle GET requests (Retrieve contacts)
elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';

    if ($action === 'get') {
        if (isset($_GET['id'])) {
            // Get single contact by ID
            $contact_id = (int)$_GET['id'];
            $sql = "SELECT * FROM emergency_contacts WHERE id=$contact_id AND user_id='$user_id'";
            $result = $conn->query($sql);

            if ($result && $result->num_rows > 0) {
                $contact = $result->fetch_assoc();
                // Map database columns to expected field names
                $contact['contact_number'] = $contact['phone'];
                $contact['alternate_number'] = $contact['alternate_phone'];
                echo json_encode(['success' => true, 'contact' => $contact]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Contact not found']);
            }
        } else {
            // Get all contacts for the logged-in user
            $sql = "SELECT * FROM emergency_contacts WHERE user_id='$user_id' ORDER BY created_at DESC";
            $result = $conn->query($sql);

            $contacts = [];
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    // Map database columns to expected field names
                    $row['contact_number'] = $row['phone'];
                    $row['alternate_number'] = $row['alternate_phone'];
                    $contacts[] = $row;
                }
            }

            echo json_encode(['success' => true, 'contacts' => $contacts]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}
else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>
