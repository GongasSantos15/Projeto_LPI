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
    $_SESSION['message'] = 'Precisa de estar autenticado para adicionar fundos.';
    $_SESSION['message_type'] = 'danger'; // Use a message type for styling
    header('Location: entrar.php');
    exit();
}

// Get the logged-in user's ID
$user_id = $_SESSION['user_id'];
$message = ''; // Initialize message variable
$message_type = ''; // Initialize message type variable

// Process the form submission for adding funds
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize the input amount
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);

    // Check if the amount is valid (a positive float)
    if ($amount === false || $amount <= 0) {
        $message = 'Por favor, insira um valor positivo válido.';
        $message_type = 'warning';
    } else {
        // Amount is valid, proceed with database update
        if ($conn) {
            // Prepare the SQL query to add funds
            $sql = "UPDATE user SET carteira = carteira + ? WHERE id = ?";
            $stmt = $conn->prepare($sql);

            if ($stmt) {
                // Bind the parameters: 'd' for double (float), 'i' for integer
                $stmt->bind_param("di", $amount, $user_id);

                // Execute the statement
                if ($stmt->execute()) { // <-- Correctly placed if for execute
                    // Check if any rows were affected (user found and updated)
                    if ($stmt->affected_rows > 0) {
                        $message = 'Fundos adicionados com sucesso!';
                        $message_type = 'success';
                    } else {
                         // This case might happen if user_id in session doesn't exist in DB
                        $message = 'Erro: Utilizador não encontrado ou saldo não atualizado.';
                        $message_type = 'warning';
                        error_log("Attempted to add funds for non-existent user ID: " . $user_id);
                    }
                } else { // <-- Correctly placed else for execute failure
                    // Handle execution error
                    $message = 'Erro ao adicionar fundos na base de dados.';
                    $message_type = 'danger';
                    error_log("Database execute error (add funds): " . $stmt->error);
                }

                // Close the statement
                $stmt->close();
            } else {
                // Handle prepare error
                $message = 'Erro interno ao preparar a query.';
                $message_type = 'danger';
                error_log("Database prepare error (add funds): " . $conn->error);
            }

            // Close the database connection
            $conn->close();
        } else {
            // Handle connection error (if not handled in basedados.h)
            $message = 'Erro: Falha na conexão com a base de dados.';
            $message_type = 'danger';
            error_log("Database connection failed when adding funds.");
        }
    }

    // Store message in session and redirect to avoid form resubmission
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $message_type;
    // Redirect to the same page or index after processing to show the message
    // Changed back to index.php as per your original code, but consider
    // redirecting to adicionar_saldo.php if you want the message on this page.
    header('Location: index.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>FelixBus - Entrar</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
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
            <h3 class="text-center text-white mb-4">Adicionar Saldo</h3>

            <?php
            // Display session message if set
            // This part is already correct for displaying messages saved in session
            if (isset($_SESSION['message'])): ?>
                <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['message']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php
                // Unset the session message after displaying it
                unset($_SESSION['message']);
                unset($_SESSION['message_type']);
            endif;
            ?>

            <form action="adicionar_saldo.php" method="POST">
                <div class="mb-3">
                    <label for="amount" class="form-label">Quanto dinheiro (€) pretende adicionar?</label>
                    <input name="amount" id="amount" type="number" step="0.01" min="0.01" class="form-control text-dark" required/>
                </div>
                <div class="d-flex justify-content-center">
                    <input type="submit" value="Adicionar Saldo" class="btn btn-primary rounded-pill py-2 px-5">
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