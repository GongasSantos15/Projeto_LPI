<?php
// Start the session at the very beginning
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include the database connection file
// *** Adjust path as needed ***
include 'C:\xampp\htdocs\lpi\Projeto_LPI\basedados\basedados.h';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // If not logged in, redirect to the login page
    $_SESSION['message-add'] = 'Precisa de estar autenticado para remover fundos.';
    $_SESSION['message_type-add'] = 'danger'; // Use a message type for styling
    header('Location: entrar.php');
    exit();
}

// Get the logged-in user's ID
$user_id = $_SESSION['user_id'];
$message = ''; // Initialize message variable
$message_type = ''; // Initialize message type variable

// Process the form submission for removing funds
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize the input amount
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);

    // Check if the amount is valid (a positive float)
    if ($amount === false || $amount <= 0) {
        $message = 'Por favor, insira um valor positivo válido.';
        $message_type = 'warning';
    } else {
        // Amount is valid, proceed with checks and database update
        if ($conn) {
            // --- START: Check current balance ---
            $sql_check_balance = "SELECT carteira FROM user WHERE id = ?";
            $stmt_check = $conn->prepare($sql_check_balance);

            if ($stmt_check) {
                $stmt_check->bind_param("i", $user_id);
                $stmt_check->execute();
                $result_check = $stmt_check->get_result();

                if ($result_check && $result_check->num_rows > 0) {
                    $row_check = $result_check->fetch_assoc();
                    $current_balance = $row_check['carteira'];

                    // Check if current balance is sufficient
                    if ($current_balance >= $amount) {
                        // Balance is sufficient, proceed with removal
                        $stmt_check->close(); // Close the check statement

                        // --- START: Proceed with fund removal ---
                        $sql_remove = "UPDATE user SET carteira = carteira - ? WHERE id = ?";
                        $stmt_remove = $conn->prepare($sql_remove);

                        if ($stmt_remove) {
                            $stmt_remove->bind_param("di", $amount, $user_id);

                            if ($stmt_remove->execute()) {
                                if ($stmt_remove->affected_rows > 0) {
                                    $message = 'Fundos removidos com sucesso!';
                                    $message_type = 'success';
                                    header("refresh:2; url = index.php");
                                } else {
                                    $message = 'Erro: Utilizador não encontrado ou saldo não atualizado.';
                                    $message_type = 'warning';
                                    error_log("Attempted to remove funds for non-existent user ID or zero affected rows: " . $user_id);
                                }
                            } else {
                                $message = 'Erro ao remover fundos na base de dados.';
                                $message_type = 'danger';
                                error_log("Database execute error (remove funds): " . $stmt_remove->error);
                            }
                            $stmt_remove->close(); // Close the remove statement

                        } else {
                            $message = 'Erro interno ao preparar a query de remoção.';
                            $message_type = 'danger';
                            error_log("Database prepare error (remove funds): " . $conn->error);
                        }
                        // --- END: Proceed with fund removal ---

                    } else {
                        // Balance is insufficient
                        $message = 'Saldo insuficiente. Não pode remover mais do que tem na carteira.';
                        $message_type = 'warning';
                        $stmt_check->close(); // Close the check statement
                    }

                } else {
                    // User not found during balance check (shouldn't happen if user_id is valid)
                    $message = 'Erro: Não foi possível verificar o seu saldo atual.';
                    $message_type = 'danger';
                     error_log("Could not fetch balance for user ID: " . $user_id);
                     if ($stmt_check) $stmt_check->close();
                }

            } else {
                // Handle prepare error for balance check
                $message = 'Erro interno ao preparar a query de verificação de saldo.';
                $message_type = 'danger';
                error_log("Database prepare error (check balance): " . $conn->error);
            }
            // --- END: Check current balance ---

            // Close the database connection if it's still open
            //if ($conn) {
            //     $conn->close();
            //}
        } else {
            // Handle connection error (if not handled in basedados.h)
            $message = 'Erro: Falha na conexão com a base de dados.';
            $message_type = 'danger';
            error_log("Database connection failed when removing funds.");
        }

        // --- Adicione ESTE código AQUI para guardar a mensagem na sessão ---
        if (!empty($message)) { // Só guarda se uma mensagem foi definida
            $_SESSION['message-add'] = $message;
            $_SESSION['message_type-add'] = $message_type;
        }
    }
    //exit();
}

// If not a POST request, just show the form
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>FelixBus - Remover Fundos</title>    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="" name="keywords">
    <meta content="" name="description">

    <link href="img/favicon.ico" rel="icon">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600&family=Nunito:wght@600;700;800&display=swap" rel="stylesheet">

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <link href="lib/animate/animate.min.css" rel="stylesheet">
    <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
    <link href="lib/tempusdominus/css/tempusdominus-bootstrap-4.min.css" rel="stylesheet" />

    <link href="css/bootstrap.min.css" rel="stylesheet">

    <link href="css/style.css" rel="stylesheet">
</head>

<body>
    <div id="spinner" class="show bg-white position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
            <span class="sr-only">Loading...</span>
        </div>
    </div>
    <div class="container-fluid hero-header text-light min-vh-100 d-flex align-items-center justify-content-center">
    <div class="p-5 rounded shadow" style="max-width: 700px; width: 100%;">
            <h3 class="text-center text-white mb-4">Remover Saldo</h3>

            <?php
            // Display session message if set
            if (isset($_SESSION['message-add'])): ?>
                <div class="alert alert-<?php echo $_SESSION['message_type-add']; ?> alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['message-add']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php
                // Unset the session message after displaying it
                unset($_SESSION['message-add']);
                unset($_SESSION['message_type-add']);
            endif;
            ?>

            <form action="remover_saldo.php" method="POST">
                <div class="mb-3">
                    <label for="amount" class="form-label">Quanto dinheiro (€) pretende remover?</label>
                    <input name="amount" id="amount" type="number" step="0.01" min="0.01" class="form-control text-dark" required/>
                </div>
                <div class="d-flex justify-content-center">
                    <input type="submit" value="Remover Saldo" class="btn btn-primary rounded-pill py-2 px-5">
                </div>
            </form>
        </div>
</div>
<script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="lib/wow/wow.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/waypoints/waypoints.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>
    <script src="lib/tempusdominus/js/moment.min.js"></script>
    <script src="lib/tempusdominus/js/moment-timezone.min.js"></script>
    <script src="lib/tempusdominus/js/tempusdominus-bootstrap-4.min.js"></script>

    <script src="js/main.js"></script>
</body>

</html>