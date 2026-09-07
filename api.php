<?php
/**
 * api.php
 * -------
 * Single endpoint that handles all CRUD operations via AJAX.
 * Always returns JSON: { success: bool, message: string, ...extra }
 *
 * Actions (passed as ?action= or POST 'action'):
 *   list    (GET)  -> returns all records
 *   create  (POST) -> insert a new record
 *   update  (POST) -> update an existing record
 *   delete  (POST) -> delete a record by id
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/config/database.php';

$action = $_REQUEST['action'] ?? '';

/**
 * Validate record fields (server-side — never trust the client).
 * Returns an array of error messages, empty array = valid.
 */
function validateRecord(array $data): array
{
    $errors = [];

    $name = trim($data['name'] ?? '');
    if ($name === '') {
        $errors['name'] = 'Name is required.';
    } elseif (mb_strlen($name) < 2) {
        $errors['name'] = 'Name must be at least 2 characters.';
    } elseif (!preg_match('/^[a-zA-Z\s.\'-]+$/u', $name)) {
        $errors['name'] = 'Name contains invalid characters.';
    }

    $email = trim($data['email'] ?? '');
    if ($email === '') {
        $errors['email'] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    }

    $phone = trim($data['phone'] ?? '');
    if ($phone === '') {
        $errors['phone'] = 'Phone is required.';
    } elseif (!preg_match('/^[0-9+\-\s()]{7,20}$/', $phone)) {
        $errors['phone'] = 'Please enter a valid phone number.';
    }

    $age = $data['age'] ?? '';
    if ($age === '' || $age === null) {
        $errors['age'] = 'Age is required.';
    } elseif (!ctype_digit((string) $age) || (int) $age < 1 || (int) $age > 120) {
        $errors['age'] = 'Age must be a number between 1 and 120.';
    }

    $address = trim($data['address'] ?? '');
    if ($address === '') {
        $errors['address'] = 'Address is required.';
    }

    return $errors;
}

function respond(bool $success, string $message, array $extra = []): void
{
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra));
    exit;
}

switch ($action) {

    // -----------------------------------------------------------------
    case 'list':
        try {
            $stmt = $pdo->query('SELECT id, name, email, phone, age, address, created_at, updated_at FROM records ORDER BY id ASC');
            $records = $stmt->fetchAll();
            respond(true, 'OK', ['records' => $records]);
        } catch (Exception $e) {
            respond(false, 'Failed to load records.');
        }
        break;

    // -----------------------------------------------------------------
    case 'create':
        $errors = validateRecord($_POST);
        if (!empty($errors)) {
            respond(false, 'Please fix the highlighted fields.', ['errors' => $errors]);
        }

        try {
            $stmt = $pdo->prepare(
                'INSERT INTO records (name, email, phone, age, address) VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                trim($_POST['name']),
                trim($_POST['email']),
                trim($_POST['phone']),
                (int) $_POST['age'],
                trim($_POST['address']),
            ]);
            respond(true, 'Record created successfully.');
        } catch (Exception $e) {
            respond(false, 'Something went wrong. Please try again.');
        }
        break;

    // -----------------------------------------------------------------
    case 'update':
        $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);
        if (!$id) {
            respond(false, 'Invalid record ID.');
        }

        $errors = validateRecord($_POST);
        if (!empty($errors)) {
            respond(false, 'Please fix the highlighted fields.', ['errors' => $errors]);
        }

        try {
            $stmt = $pdo->prepare(
                'UPDATE records SET name = ?, email = ?, phone = ?, age = ?, address = ? WHERE id = ?'
            );
            $stmt->execute([
                trim($_POST['name']),
                trim($_POST['email']),
                trim($_POST['phone']),
                (int) $_POST['age'],
                trim($_POST['address']),
                $id,
            ]);

            if ($stmt->rowCount() === 0) {
                respond(false, 'Record not found or no changes made.');
            }

            respond(true, 'Record updated successfully.');
        } catch (Exception $e) {
            respond(false, 'Something went wrong. Please try again.');
        }
        break;

    // -----------------------------------------------------------------
    case 'delete':
        $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);
        if (!$id) {
            respond(false, 'Invalid record ID.');
        }

        try {
            $stmt = $pdo->prepare('DELETE FROM records WHERE id = ?');
            $stmt->execute([$id]);

            if ($stmt->rowCount() === 0) {
                respond(false, 'Record not found.');
            }

            respond(true, 'Record deleted successfully.');
        } catch (Exception $e) {
            respond(false, 'Something went wrong. Please try again.');
        }
        break;

    // -----------------------------------------------------------------
    default:
        respond(false, 'Unknown action.');
}
