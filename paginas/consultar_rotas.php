<?php
    session_start();

    // Include conexão à BD
    include("../basedados/basedados.h"); 

    // Variável para armazenar mensagens de erro PHP (conexão, query, etc.)
    $mensagem_erro = '';
    $mensagem_sucesso = '';

    // Verifica se o utilizador tem o login feito   
    $tem_login = isset($_SESSION['id_utilizador']) && !empty($_SESSION['id_utilizador']);
    
    // Determina a página inicial correta baseada no tipo de utilizador
    $pagina_inicial = 'index.php'; // Página padrão se não tiver login
    if ($tem_login && isset($_SESSION['tipo_utilizador'])) {
        switch ($_SESSION['tipo_utilizador']) {
            case 1: // Admin
                $pagina_inicial = 'pagina_inicial_admin.php';
                break;
            case 2: // Funcionário
                $pagina_inicial = 'pagina_inicial_func.php';
                break;
            case 3: // Cliente
                $pagina_inicial = 'pagina_inicial_cliente.php';
                break;
            default:
                $pagina_inicial = 'index.php';
        }
    }   

    // Verifica se há uma mensagem de erro na sessão
    if (isset($_SESSION['mensagem_erro'])) {
        $mensagem_erro = $_SESSION['mensagem_erro'];
        // Limpa a variável de sessão para não exibir a mensagem novamente em carregamentos futuros
        unset($_SESSION['mensagem_erro']);
    }

    // Verifica se há uma mensagem de sucesso na sessão
    if (isset($_SESSION['mensagem_sucesso'])) {
        $mensagem_sucesso = $_SESSION['mensagem_sucesso'];
        // Limpa a variável de sessão para não exibir a mensagem novamente em carregamentos futuros
        unset($_SESSION['mensagem_sucesso']);
    }

    $rotas = []; // Inicializa a variável rotas

    // CORREÇÃO: Para consultar rotas, não precisamos dos parâmetros de origem/destino/data
    // Só executa a consulta se houver conexão e não houver mensagem de sucesso
    if ($conn && empty($mensagem_sucesso)) {
        // Consulta SQL para obter todas as rotas
        $sql = "SELECT id, origem, destino FROM rota ORDER BY origem, destino";

        $stmt = $conn->prepare($sql);

        if ($stmt) {
            if($stmt->execute()) {
                $resultado = $stmt->get_result();
                $rotas = $resultado->fetch_all(MYSQLI_ASSOC);
            } else {
                $mensagem_erro = "Erro ao executar a consulta das rotas.";
            }
            $stmt->close();
        } else {
            $mensagem_erro = "Erro ao preparar a consulta das rotas.";
        }
    }
    
    if ($conn) {
        $conn->close();
    }
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>FelixBus - Rotas</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="" name="keywords">
    <meta content="" name="description">

    <link href="favicon.ico" rel="icon">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600&family=Nunito:wght@600;700;800&display=swap" rel="stylesheet">

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <link href="animate.min.css" rel="stylesheet">
    <link href="owl.carousel.min.css" rel="stylesheet">
    <link href="tempusdominus-bootstrap-4.min.css" rel="stylesheet" />

    <link href="bootstrap.min.css" rel="stylesheet">

    <link href="style.css" rel="stylesheet">
    
    <style>
        /* CSS personalizado para scroll nas rotas */
        .rotas-scroll-container {
            max-height: 400px; /* Ajuste esta altura conforme necessário */
            overflow-y: auto;
            overflow-x: hidden;
            padding-right: 10px; /* Espaço para a scrollbar */
        }
        
        /* Personalizar a scrollbar */
        .rotas-scroll-container::-webkit-scrollbar {
            width: 8px;
        }
        
        .rotas-scroll-container::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
        }
        
        .rotas-scroll-container::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 10px;
        }
        
        .rotas-scroll-container::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.5);
        }
        
        /* Para Firefox */
        .rotas-scroll-container {
            scrollbar-width: thin;
            scrollbar-color: rgba(255, 255, 255, 0.3) rgba(255, 255, 255, 0.1);
        }
    </style>
</head>

<body>
    <div id="spinner" class="show bg-white position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
            <span class="sr-only">Loading...</span>
        </div>
    </div>
    <div class="container-fluid hero-header text-light min-vh-100 d-flex align-items-center justify-content-center">
    <div class="p-5 rounded shadow" style="max-width: 700px; width: 100%;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="text-white m-0">Consultar Rotas</h3>
            <a href="<?php echo htmlspecialchars($pagina_inicial); ?>" class="btn btn-outline-light btn-sm">
                <i class="fas fa-arrow-left me-2"></i>Voltar ao Início
            </a>
        </div>

        <?php
            if (!empty($mensagem_erro)) {
                echo '<div class="alert alert-danger">' . htmlspecialchars($mensagem_erro) . '</div>';
                echo '<script>
                    setTimeout(function() {
                        window.location.href = "' . htmlspecialchars($pagina_inicial) . '";
                    }, 4000);
                </script>';
            }
            if (!empty($mensagem_sucesso)) {
                echo '<div class="alert alert-success">' . htmlspecialchars($mensagem_sucesso) . '</div>';
                echo '<script>
                    setTimeout(function() {
                        window.location.href = "' . htmlspecialchars($pagina_inicial) . '";
                    }, 2000);
                </script>';
            }
        ?>

        <?php if (!empty($mensagem_sucesso)): ?>
            <!-- Se há mensagem de sucesso, não mostra mais nada -->
        <?php elseif (!empty($rotas)): ?>
            <!-- Container com scroll aplicado apenas às rotas -->
            <div class="rotas-scroll-container">
                <div class="row g-3">
                    <?php foreach ($rotas as $rota): ?>
                        <div class="col-12">
                            <div class="bg-gradient position-relative mx-auto mt-3 animated slideInDown">
                                <div class="card-body d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="card-title text-primary">Rota #<?php echo htmlspecialchars($rota['id']); ?></h5> 
                                        <p class="card-text"><strong>De:</strong> <?php echo htmlspecialchars($rota['origem']); ?> </p>
                                        <p class="card-text"><strong>Para:</strong> <?php echo htmlspecialchars($rota['destino']); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php else: ?>
            <p class="text-center text-white">Nenhuma rota encontrada.</p>
        <?php endif; ?>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="wow.min.js"></script>
    <script src="easing.min.js"></script>
    <script src="waypoints.min.js"></script>
    <script src="owl.carousel.min.js"></script>
    <script src="moment.min.js"></script>
    <script src="moment-timezone.min.js"></script>
    <script src="tempusdominus-bootstrap-4.min.js"></script>

    <script src="main.js"></script>
</body>

</html>