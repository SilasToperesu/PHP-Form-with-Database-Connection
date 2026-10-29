<?php
session_start();

$errors  = $_SESSION['errors']  ?? [];
$old     = $_SESSION['old']     ?? [];
$success = $_SESSION['success'] ?? '';
unset($_SESSION['errors'], $_SESSION['old'], $_SESSION['success']);

//CSRF token to protect the form
if(empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

//Helpers
function old(string $field, array $old): string
{
    return htmlspecialchars($old[$field] ?? '', ENT_QUOTES, 'UTF-8');
}

function showError(string $field, array $errors): string
{
    if (!isset($errors[$field])) {
        return '';
    }
    return '<span class="error">' . htmlspecialchars($errors[$field], ENT_QUOTES, 'UTF-8') . '</span>';
}

function invalidClass(string $field, array $errors): string
{
    return isset($errors[$field]) ? 'invalid' : '';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: "Segoe UI", Roboto, Arial, sans-serif;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 50%, #6dd5ed 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px 16px;
        }

        .card {
            background: #ffffff;
            width: 100%;
            max-width: 560px;
            border-radius: 14px;
            box-shadow: 0 20px 45px rgba(0, 0, 0, 0.25);
            overflow: hidden;
        }

        .card-header {
            background: #1e3c72;
            color: #fff;
            padding: 24px 30px;
        }
        .card-header h1 { font-size: 1.6rem; font-weight: 600; }
        .card-header p  { font-size: 0.9rem; opacity: 0.85; margin-top: 4px; }

        form { padding: 26px 30px 30px; }

        .row { display: flex; gap: 16px; }
        .row .form-group { flex: 1; }

        .form-group { margin-bottom: 18px; }

        label {
            display: block;
            font-weight: 600;
            font-size: 0.9rem;
            color: #333;
            margin-bottom: 6px;
        }
        label .req { color: #d93025; }

        input {
            width: 100%;
            padding: 11px 13px;
            font-size: 0.95rem;
            border: 1.5px solid #cfd6e0;
            border-radius: 8px;
            background: #f9fbfd;
            transition: border-color .2s, box-shadow .2s, background .2s;
        }
        input:focus {
            outline: none;
            border-color: #2a5298;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(42, 82, 152, 0.18);
        }

        /* Invalid field styling */
        input.invalid {
            border-color: #d93025;
            background: #fff5f5;
        }
        input.invalid:focus { box-shadow: 0 0 0 3px rgba(217, 48, 37, 0.18); }

        /* Red error message under each field */
        .error {
            display: block;
            color: #d93025;
            font-size: 0.82rem;
            margin-top: 5px;
            font-weight: 500;
        }

        .hint { display: block; color: #777; font-size: 0.78rem; margin-top: 4px; }

        .input-prefix { position: relative; }
        .input-prefix span {
            position: absolute; left: 13px; top: 50%;
            transform: translateY(-50%);
            color: #555; font-weight: 600;
        }
        .input-prefix input { padding-left: 30px; }

        .password-wrap { position: relative; }
        .password-wrap input { padding-right: 70px; }
        .toggle-pass {
            position: absolute; right: 8px; top: 50%;
            transform: translateY(-50%);
            border: none; background: none;
            color: #2a5298; font-weight: 600; cursor: pointer; font-size: 0.82rem;
        }

        .alert {
            padding: 13px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.92rem;
        }
        .alert-success { background: #e6f4ea; color: #1e7e34; border: 1px solid #b7dfc2; }
        .alert-danger  { background: #fdecea; color: #d93025; border: 1px solid #f5c2c0; }

        button[type="submit"] {
            width: 100%;
            padding: 13px;
            font-size: 1rem;
            font-weight: 600;
            color: #fff;
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: transform .1s, box-shadow .2s;
            margin-top: 6px;
        }
        button[type="submit"]:hover  { box-shadow: 0 8px 18px rgba(30, 60, 114, 0.35); }
        button[type="submit"]:active { transform: scale(0.99); }

        @media (max-width: 520px) {
            .row { flex-direction: column; gap: 0; }
            form { padding: 22px 18px 24px; }
            .card-header { padding: 20px 18px; }
        }
    </style>
</head>
<body>

<div class="card">
    <div class="card-header">
        <h1>Student Registration</h1>
        <p>Fields marked <span style="color:#ffb4ab">*</span> are required.</p>
    </div>

    <form action="process.php" method="POST" novalidate>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <?php if (isset($errors['general'])): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($errors['general'], ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

        <div class="row">
            <div class="form-group">
                <label for="name">Name <span class="req">*</span></label>
                <input type="text" id="name" name="name" maxlength="50"
                       class="<?= invalidClass('name', $errors) ?>"
                       value="<?= old('name', $old) ?>" placeholder="e.g. Silas">
                <?= showError('name', $errors) ?>
            </div>

            <div class="form-group">
                <label for="surname">Surname <span class="req">*</span></label>
                <input type="text" id="surname" name="surname" maxlength="50"
                       class="<?= invalidClass('surname', $errors) ?>"
                       value="<?= old('surname', $old) ?>" placeholder="e.g. Tops">
                <?= showError('surname', $errors) ?>
            </div>
        </div>

        <div class="row">
            <div class="form-group">
                <label for="age">Age <span class="req">*</span></label>
                <input type="number" id="age" name="age"
                       class="<?= invalidClass('age', $errors) ?>"
                       value="<?= old('age', $old) ?>" placeholder="16 - 100">
                <?= showError('age', $errors) ?>
            </div>

            <div class="form-group">
                <label for="cell">SA Cell Number <span class="req">*</span></label>
                <input type="tel" id="cell" name="cell" maxlength="16"
                       class="<?= invalidClass('cell', $errors) ?>"
                       value="<?= old('cell', $old) ?>" placeholder="082 123 4567">
                <?= showError('cell', $errors) ?>
            </div>
        </div>

        <div class="form-group">
            <label for="email">Email Address <span class="req">*</span></label>
            <input type="email" id="email" name="email" maxlength="100"
                   class="<?= invalidClass('email', $errors) ?>"
                   value="<?= old('email', $old) ?>" placeholder="you@example.com">
            <?= showError('email', $errors) ?>
        </div>

        <div class="form-group">
            <label for="password">Password <span class="req">*</span></label>
            <div class="password-wrap">
                <input type="password" id="password" name="password" maxlength="64"
                       class="<?= invalidClass('password', $errors) ?>"
                       placeholder="Create a strong password">
                <button type="button" class="toggle-pass" onclick="togglePassword()">Show</button>
            </div>
            <?php if (isset($errors['password'])): ?>
                <?= showError('password', $errors) ?>
            <?php else: ?>
                <span class="hint">8+ characters with uppercase, lowercase, a number and a special character.</span>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="fees">Fees <span class="req">*</span></label>
            <div class="input-prefix">
                <span>R</span>
                <input type="text" id="fees" name="fees" inputmode="decimal"
                       class="<?= invalidClass('fees', $errors) ?>"
                       value="<?= old('fees', $old) ?>" placeholder="0.00">
            </div>
            <?= showError('fees', $errors) ?>
        </div>

        <button type="submit">Submit</button>
    </form>
</div>

<script>
    function togglePassword() {
        const input = document.getElementById('password');
        const btn = document.querySelector('.toggle-pass');
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        btn.textContent = show ? 'Hide' : 'Show';
    }
</script>

</body>
</html>
