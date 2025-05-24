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
    $dados_utilizador = [];

    if ($conn) {
        // Consulta SQL para encontrar os dados do utilizador
        $sql = "SELECT nome_utilizador, nome_proprio FROM utilizador WHERE id = ?";
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("i", $id_utilizador);

            if($stmt->execute()) {
                $resultado = $stmt->get_result();
                if ($resultado && $resultado->num_rows > 0) {
                    $dados_utilizador = $resultado->fetch_assoc();
                }
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
    <title>FelixBus - Consultar e Editar Dados</title>
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
                <h3 class="text-white m-0">Consultar e Editar Dados</h3>
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
                    echo '<div class="alert alert-success">' . htmlspecialchars($mensagem_sucesso) . '</div>';
                        echo '<script>
                            setTimeout(function() {
                                window.location.href = "index.php";
                            }, 2000);
                        </script>';
                }
            ?>

            <?php if (!empty($dados_utilizador)): ?>
                <div class="row g-3">
                    <div class="col-12">
                        <div class="bg-gradient mb-3 position-relative mx-auto mt-3 animated slideInDown">
                            <div class="card-body p-4">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <div class="d-flex align-items-center mb-3">
                                            <i class="fas fa-user text-primary me-2 fa-lg"></i>
                                            <h5 class="card-title text-primary mb-0">Dados do Utilizador</h5>
                                        </div>
                                        
                                        <div>
                                            <p class="card-text mb-2 text-white"><strong><i class="fas fa-id-card text-info me-1"></i>Nome Próprio:</strong> <?php echo htmlspecialchars($dados_utilizador['nome_proprio']); ?></p>
                                            <p class="card-text mb-2 text-white"><strong><i class="fas fa-user-tag text-warning me-1"></i>Nome de Utilizador:</strong> <?php echo htmlspecialchars($dados_utilizador['nome_utilizador']); ?></p>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4 text-md-end">
                                        <button type="button" id="botao-edicao" class="btn btn-warning rounded-pill py-2 px-4">
                                            <i class="fas fa-edit me-2"></i>Editar Dados
                                        </button>
                                    </div>
                                </div>

                                <!-- Formulário de edição (inicialmente oculto) -->
                                <div id="formulario-edicao" style="display: none;">
                                    <hr class="text-white my-4">
                                    <form id="profileEditForm" action="editar_dados.php" method="POST">
                                        <input type="hidden" name="id_utilizador" value="<?php echo htmlspecialchars($id_utilizador); ?>">
                                        
                                        <div class="row">
                                            <div class="col-12">
                                                <div class="mb-3">
                                                    <label for="nome-proprio" class="form-label text-white">
                                                        <i class="fas fa-id-card me-1"></i>Nome Próprio:
                                                    </label>
                                                    <input name="nome-proprio" id="nome-proprio" type="text" 
                                                           class="text-dark form-control" 
                                                           value="<?php echo htmlspecialchars($dados_utilizador['nome_proprio']); ?>" 
                                                           required />
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="mb-3">
                                                    <label for="nome" class="form-label text-white">
                                                        <i class="fas fa-user-tag me-1"></i>Nome de Utilizador:
                                                    </label>
                                                    <input name="nome" id="nome" type="text" 
                                                           class="text-dark form-control" 
                                                           value="<?php echo htmlspecialchars($dados_utilizador['nome_utilizador']); ?>" 
                                                           required />
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="d-flex justify-content-end gap-2 mt-3">
                                            <button type="button" id="botao-cancelar" class="btn btn-outline-danger rounded-pill">
                                                <i class="fas fa-times me-2"></i>Cancelar
                                            </button>
                                            <button type="submit" class="btn btn-success rounded-pill">
                                                <i class="fas fa-save me-2"></i>Guardar Alterações
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
            <?php else: ?>
                <div class="text-center">
                    <div class="mb-4">
                        <i class="fas fa-user-times text-light" style="font-size: 4rem; opacity: 0.5;"></i>
                    </div>
                    <p class="text-center text-white fs-5 mb-4">Não foi possível carregar os seus dados.</p>
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

    <script>
        $(document).ready(function() {
            // Botão de edição
            $('#botao-edicao').click(function() {
                $('#formulario-edicao').slideDown();
                $(this).hide();
            });

            // Botão de cancelar
            $('#botao-cancelar').click(function() {
                $('#formulario-edicao').slideUp();
                $('#botao-edicao').show();
                
                // Restaurar valores originais
                $('#nome-proprio').val('<?php echo htmlspecialchars($dados_utilizador['nome_proprio'] ?? ''); ?>');
                $('#nome').val('<?php echo htmlspecialchars($dados_utilizador['nome_utilizador'] ?? ''); ?>');
            });

            // Redirecionar após sucesso
            <?php if (!empty($mensagem_sucesso)): ?>
            setTimeout(function() {
                window.location.href = "index.php";
            }, 2000);
            <?php endif; ?>
        });
    </script>
</body>

</html>