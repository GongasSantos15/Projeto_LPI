<?php
    session_start();

    // Include conexão à BD
    include("basedados\basedados.h");

    // Verifica se o utilizador tem sessão iniciada, senão tiver redireciona para a página de login
    if (!isset($_SESSION['id_utilizador'])) {
        header("Location: entrar.php");
        exit();
    }   

    // Variável para armazenar mensagens de erro PHP (conexão, query, etc.)
    $mensagem_erro = '';
    $mensagem_sucesso = '';

    // Verifica se há uma mensagem de sucesso na sessão
    if (isset($_SESSION['mensagem_sucesso'])) {
        $mensagem_sucesso = $_SESSION['mensagem_sucesso'];
        // Limpa a variável de sessão para não exibir a mensagem novamente em carregamentos futuros
        unset($_SESSION['mensagem_sucesso']);
    }

    $id_utilizador = $_SESSION['id_utilizador'];
    $bilhetes = [];

    if ($conn) {
        // Consulta SQL para encontrar todos os bilhetes do utilizador
        $sql = "SELECT
                    b.id AS id_bilhete,
                    b.data_compra,
                    v.preco,
                    v.data_hora,
                    r.origem,
                    r.destino,
                    v.id AS id_viagem
                FROM
                    bilhete b
                JOIN
                    viagem v ON b.id_viagem = v.id
                JOIN
                    rota r ON v.id_rota = r.id
                WHERE
                    b.id_utilizador = ?
                ORDER BY
                    b.data_compra DESC";

        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("i", $id_utilizador);

            if($stmt->execute()) {
                $resultado = $stmt->get_result();
                $bilhetes = $resultado->fetch_all(MYSQLI_ASSOC);
            } else {
                $mensagem_erro = "Erro ao executar a consulta: " . $stmt->error;
            }

            $stmt->close();
        } else {
            $mensagem_erro = "Erro ao preparar a consulta: " . $conn->$connect_error;
        }
        
        $conn->close();
    } else {
        $mensagem_erro = "Erro na conexão à base de dados.";
    }

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>FelixBus - Meus Bilhetes</title>
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
        <div class="p-5 rounded shadow" style="max-width: 900px; width: 100%;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="text-white m-0">Os Meus Bilhetes</h3>
                <a href="index.php" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-arrow-left me-2"></i>Voltar ao Início
                </a>
            </div>

            <?php
                if (!empty($mensagem_erro)) {
                    echo '<div class="alert alert-danger">' . htmlspecialchars($mensagem_erro) . '</div>';
                }
                if (!empty($mensagem_sucesso)) {
                    echo '<div class="alert alert-success">' . htmlspecialchars($mensagem_sucesso) . '</div>';
                }
            ?>

            <?php if (!empty($bilhetes)): ?>
                <div class="row g-3">
                    <?php foreach ($bilhetes as $bilhete): ?>
                        <div class="col-12">
                            <div class="bg-gradient mb-3 position-relative mx-auto mt-3 animated slideInDown">
                                <div class="card-body p-4">
                                    <div class="row align-items-center">
                                        <div class="col-md-8">
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="fas fa-ticket-alt text-primary me-2 fa-lg"></i>
                                                <h5 class="card-title text-primary mb-0">Bilhete #<?php echo htmlspecialchars($bilhete['id_bilhete']); ?></h5>
                                            </div>
                                            
                                            <div class="row">
                                                <div class="col-sm-6">
                                                    <p class="card-text mb-1"><strong><i class="fas fa-map-marker-alt text-success me-1"></i>De:</strong> <?php echo htmlspecialchars($bilhete['origem']); ?></p>
                                                    <p class="card-text mb-1"><strong><i class="fas fa-map-marker-alt text-danger me-1"></i>Para:</strong> <?php echo htmlspecialchars($bilhete['destino']); ?></p>
                                                </div>
                                                <div class="col-sm-6">
                                                    <p class="card-text mb-1"><strong><i class="fas fa-calendar-alt text-info me-1"></i>Viagem:</strong> <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($bilhete['data_hora']))); ?></p>
                                                    <p class="card-text mb-1"><strong><i class="fas fa-shopping-cart text-warning me-1"></i>Data:</strong> <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($bilhete['data_compra']))); ?></p>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-4 text-md-end">
                                            <div class="mb-3">
                                                <span class="badge bg-success fs-6 px-3 py-2">
                                                    <i class="fas fa-euro-sign me-1"></i>
                                                    <?php echo number_format($bilhete['preco'], 2, ',', '.'); ?> €
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
            <?php else: ?>
                <div class="text-center">
                    <div class="mb-4">
                        <i class="fas fa-ticket-alt text-light" style="font-size: 4rem; opacity: 0.5;"></i>
                    </div>
                    <p class="text-center text-white fs-5 mb-4">Ainda não possui bilhetes comprados.</p>
                </div>
            <?php endif; ?>
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