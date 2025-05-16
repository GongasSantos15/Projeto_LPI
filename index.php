<?php
    include 'C:\xampp\htdocs\lpi\Projeto_LPI\basedados\basedados.h'; // *** Adjust path as needed ***
    
    // Start the session at the very beginning of the PHP file
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    // Check login status using $_SESSION['user_id']
    $isLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);

    // If logged in, get user name (assuming it's stored in session,
    // otherwise, you'd query your database using $_SESSION['user_id'] here)
    $username = $isLoggedIn ? ($_SESSION['nome'] ?? 'Utilizador') : ''; // Uses $_SESSION['user_name'] if available

    // Variável para armazenar o saldo da carteira, inicializada como 0.00
$wallet_balance = 0.00;
$user_id = null; // Inicializa o user_id

// Verifica se o utilizador NÃO está logado
if (!isset($_SESSION['user_id'])) {
    // Redireciona para a página de login se não estiver logado
    // Depending on your site structure, you might want to show a different navbar for logged out users
    // or just not show the wallet part. For this example, we'll assume a logged-in state is required.
    // header('Location: entrar.php'); // Uncomment if you want to force login for any page using this include
    // exit();
    // If not logged in, balance remains 0.00 or you could set it to null and handle display accordingly.
    // For now, we'll proceed with 0.00 and maybe hide the options later via CSS/JS if desired.
} else {
    // Se chegou aqui, o utilizador ESTÁ logado
    $user_id = $_SESSION['user_id']; // Obtém o ID do utilizador da sessão

    // --- Buscar saldo da carteira usando o user_id ---

    // Verifica se a conexão com a base de dados é válida
    if ($conn) {
        // SQL para buscar o saldo da carteira
        // Assumindo tabela 'user' e coluna 'wallet_balance'
        $sql = "SELECT carteira FROM user WHERE id = ?";
        $stmt = $conn->prepare($sql);

        if ($stmt) { // Verifica se a preparação da query foi bem-sucedida
            // Liga o parâmetro (id do utilizador) à query
            // "i" indica que o parâmetro é um inteiro
            $stmt->bind_param("i", $user_id);

            // Executa a query
            $stmt->execute();

            // Obtém o resultado da query
            $result = $stmt->get_result();

            // Verifica se encontrou o utilizador
            if ($result && $result->num_rows > 0) {
                // Obtém a linha de resultado como um array associativo
                $row = $result->fetch_assoc();
                // Atribui o saldo à variável, formatando-o
                $wallet_balance = number_format($row['carteira'], 2, ',', '.'); // Format as currency
            } else {
                // Opcional: Lidar com o caso em que o user ID está na sessão, mas não na DB
                error_log("User ID {$user_id} not found in database when fetching wallet.");
                // The balance will remain 0.00 as initialized.
            }

            // Fecha o statement
            $stmt->close();
        } else {
            // Lidar com erro na preparação da query
            error_log("Database prepare error (wallet): " . $conn->error);
            // The balance will remain 0.00.
        }

        // Fecha a conexão com a base de dados no final
        $conn->close();
    } else {
        // Lidar com falha na conexão (se não for tratada em basedados.h)
        error_log("Database connection failed when fetching wallet.");
        // The balance will remain 0.00.
    }
}

// Helper function to check if user is logged in for conditional display in HTML
function isUserLoggedIn() {
    return isset($_SESSION['user_id']);
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>FelixBus</title>
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

    <style>
        .dropdown:hover > .dropdown-menu {
            display: block;
        }
    </style>

    <script src="js/main.js" defer></script>
</head>

<body>
    <div id="spinner" class="show bg-white position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
            <span class="sr-only">Loading...</span>
        </div>
    </div>
    <div class="container-fluid position-relative p-0">
        <nav class="navbar navbar-expand-lg navbar-light px-5 px-lg-5 py-3 py-lg-3">
            <a href="" class="navbar-brand p-0">
                <h1 class="text-primary m-0"><i class="fa fa-map-marker-alt me-3"></i>FelixBus</h1>
                </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
                <span class="fa fa-bars"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarCollapse">
                <div class="navbar-nav ms-auto py-0">
                    <a href="index.php" class="nav-item nav-link">Home</a>
                </div>

                <?php if ($isLoggedIn): ?>
                    <div class="nav-item dropdown">
                    <a href="#" class="nav-link dropdown-toggle" id="walletDropdownLink" role="button" aria-expanded="false">
                            <i class="fa fa-wallet"></i> <?php echo $wallet_balance; ?> €
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="walletDropdownLink">
                            <li><a class="dropdown-item" href="adicionar_saldo.php"><i class="fas fa-plus-circle"></i> 💰 Adicionar</a></li>
                            <li><a class="dropdown-item" href="remover_saldo.php"><i class="fas fa-minus-circle"></i> 💸 Remover</a></li>
                        </ul>
                    </div>
                    <div class="nav-item dropdown">
                        <a href="#" class="nav-link d-flex align-items-center text-primary me-3 dropdown-toggle" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa fa-user-circle fa-2x me-2"></i>
                            <span><?php echo htmlspecialchars($username); ?></span>
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="consultar_dados.php"><i class="fas fa-user-cog me-2"></i> Consultar Dados</a></li>
                            <li><a class="dropdown-item" href="sair.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="entrar.php" class="btn btn-primary rounded-pill py-2 px-4">Entrar</a>
                <?php endif; ?>
            </div>
        </nav>

        <div class="container-fluid bg-primary py-5 mb-5 hero-header">
            <div class="container py-5">
                <div class="row justify-content-center py-5">
                    <div class="col-lg-10 pt-lg-5 mt-lg-5 text-center">
                        <h1 class="display-3 text-white mb-3 animated slideInDown">A tua próxima viagem começa aqui!</h1>
                        <p class="fs-4 text-white mb-4 animated slideInDown">Viaja com a FelixBus e descobre novos destinos em Portugal!</p>
                        <div class="bg-gradient position-relative w-75 mx-auto mt-5 animated slideInDown">
                            <form method="POST" class="d-flex flex-wrap p-4 rounded text-light justify-content-center" style="gap: 2rem 0.5rem;">
                                <div class="me-4">
                                    <label class="form-label">Origem:</label>
                                    <select name="origem" id="origem" class="form-select bg-dark text-light border-primary">
                                        <option>A carregar...</option>
                                    </select>
                                </div>

                                <div class="me-4">
                                    <label class="form-label">Destino:</label>
                                    <select name="destino"  id="destino" class="form-select bg-dark text-light border-primary">
                                        <option>A carregar...</option>
                                    </select>
                                </div>

                                <div class="me-4">
                                    <label class="form-label">Data de viagem:</label>
                                    <input name="data" id="data" type="date" class="form-control bg-dark text-light border-primary" autocomplete="off" />
                                </div>

                                <div class="w-100 text-center mt-2">
                                    <input type="submit" value="Procurar" class="btn btn-primary text-light px-5 py-2 rounded-pill" />
                                </div>
                            </form>
                        </div>

                    </div>
                </div>
            </div>
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