<?php
session_start();
require_once __DIR__ . '/db.php';

//Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

//Send errors + previously typed values back to the form
function backToForm(array $errors, array $old): void
{
    $_SESSION['errors'] = $errors;
    $_SESSION['old']    = $old;   
    header('Location: index.php');
    exit;
}

//CSRF check
if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
    backToForm(['general' => 'Your session expired. Please try again.'], []);
}

//Capture input 
$name     = trim($_POST['name']     ?? '');
$surname  = trim($_POST['surname']  ?? '');
$age      = trim($_POST['age']      ?? '');
$cell     = trim($_POST['cell']     ?? '');
$email    = trim($_POST['email']    ?? '');
$password = $_POST['password']      ?? '';
$fees     = trim($_POST['fees']     ?? '');

$old = [
    'name'    => $name,
    'surname' => $surname,
    'age'     => $age,
    'cell'    => $cell,
    'email'   => $email,
    'fees'    => $fees,
];

$errors = [];

//Validation

//Name and Surname: letters, spaces, hyphens, apostrophes (2–50 chars)
$namePattern = "/^\p{L}[\p{L}\s'\-]{1,49}$/u";

if ($name === '') {
    $errors['name'] = 'Name is required.';
} elseif (!preg_match($namePattern, $name)) {
    $errors['name'] = 'Name must be 2–50 letters (spaces, hyphens and apostrophes allowed).';
}

if ($surname === '') {
    $errors['surname'] = 'Surname is required.';
} elseif (!preg_match($namePattern, $surname)) {
    $errors['surname'] = 'Surname must be 2–50 letters (spaces, hyphens and apostrophes allowed).';
}

//Age whole number 16–100
if ($age === '') {
    $errors['age'] = 'Age is required.';
} elseif (!ctype_digit($age)) {
    $errors['age'] = 'Age must be a whole number.';
} elseif ((int)$age < 16 || (int)$age > 100) {
    $errors['age'] = 'Age must be between 16 and 100.';
}

//SA cell number: 0XX XXX XXXX or +27 XX XXX XXXX, starting with 6, 7 or 8
$cellNormalised = '';
$cellClean = preg_replace('/[\s\-()]/', '', $cell);

if ($cell === '') {
    $errors['cell'] = 'Cell number is required.';
} elseif (!preg_match('/^(?:\+27|27|0)([678]\d{8})$/', $cellClean, $match)) {
    $errors['cell'] = 'Enter a valid SA cell number, e.g. 082 123 4567 or +27 82 123 4567.';
} else {
    $cellNormalised = '0' . $match[1];   
}

//Email
if ($email === '') {
    $errors['email'] = 'Email address is required.';
} elseif (strlen($email) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Enter a valid email address, e.g. name@example.com.';
}

//Password 8–64 chars, upper, lower, digit, special character
if ($password === '') {
    $errors['password'] = 'Password is required.';
} elseif (strlen($password) < 8 || strlen($password) > 64) {
    $errors['password'] = 'Password must be between 8 and 64 characters.';
} elseif (!preg_match('/[A-Z]/', $password)) {
    $errors['password'] = 'Password must contain at least one uppercase letter.';
} elseif (!preg_match('/[a-z]/', $password)) {
    $errors['password'] = 'Password must contain at least one lowercase letter.';
} elseif (!preg_match('/\d/', $password)) {
    $errors['password'] = 'Password must contain at least one number.';
} elseif (!preg_match('/[^A-Za-z\d]/', $password)) {
    $errors['password'] = 'Password must contain at least one special character (e.g. @, #, !).';
}

//Fees positive amount, max 2 decimals (allows "R 1 500.50")
$feesClean = preg_replace('/^R/i', '', str_replace(' ', '', $fees));

if ($fees === '') {
    $errors['fees'] = 'Fees amount is required.';
} elseif (!preg_match('/^\d{1,8}(\.\d{1,2})?$/', $feesClean)) {
    $errors['fees'] = 'Fees must be a valid amount, e.g. 1500 or 1500.50.';
} elseif ((float)$feesClean <= 0) {
    $errors['fees'] = 'Fees must be greater than R0.00.';
}

if ($errors) {
    backToForm($errors, $old);
}


//Save to database 
try {
    $pdo = getDbConnection();

    //Email must be unique
    $check = $pdo->prepare('SELECT 1 FROM students WHERE EmailAddress = :email LIMIT 1');
    $check->execute([':email' => $email]);
    if($check->fetchColumn()) {
        backToForm(['email' => 'This email address is already registered.'], $old);
    }

    $sql = 'INSERT INTO students (Name, Surname, Age, CellNumber, EmailAddress, Password, Fees)
            VALUES (:name, :surname, :age, :cell, :email, :password, :fees)';

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':name'     => $name,
        ':surname'  => $surname,
        ':age'      => (int)$age,
        ':cell'     => $cellNormalised,
        ':email'    => $email,
        ':password' => password_hash($password, PASSWORD_DEFAULT), 
        ':fees'     => number_format((float)$feesClean, 2, '.', ''),
    ]);

    $studentNumber = $pdo->lastInsertId();

    //Fresh token for the next submission
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['success'] = "Registration successful! Your student number is {$studentNumber}.";
    header('Location: index.php');
    exit;

} catch (PDOException $e) {
    error_log('Registration error: ' . $e->getMessage());

    //23000 = integrity constraint (e.g. duplicate email)
    if ($e->getCode() === '23000') {
        backToForm(['email' => 'This email address is already registered.'], $old);
    }
    backToForm(['general' => 'Something went wrong while saving. Please try again later.'], $old);
}
