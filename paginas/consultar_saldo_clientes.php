<?php
    session_start();

    include("../basedados/basedados.h");

    if (!isset($_SESSION['id_utilizador'])) {
        header("Location: entrar.php");
        exit();
    }   

    $mensagem_erro = '';
    $mensagem_sucesso = '';

    if (isset($_SESSION['mensagem_sucesso'])) {
        $mensagem_sucesso = $_SESSION['mensagem_sucesso'];
        unset($_SESSION['mensagem_sucesso']);
    }

    $clientes = [];

    if ($conn) {
        $sql = "SELECT
                    u.id AS id_utilizador,
                    u.nome_proprio,
                    u.nome_utilizador,
                    c.id_carteira,
                    c.saldo
                FROM
                    utilizador u
                LEFT JOIN
                    carteira c ON u.id = c.id_carteira
                WHERE
                    u.tipo_utilizador = 3
                ORDER BY
                    u.nome_proprio ASC";

        $stmt = $conn->prepare($sql);

        if ($stmt) {
            if($stmt->execute()) {
                $resultado = $stmt->get_result();
                $clientes = $resultado->fetch_all(MYSQLI_ASSOC); 
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
<html lang="pt">

<head>
    <meta charset="utf-8">
    <title>FelixBus - Gestão de Carteiras</title>
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
</head>

<body>
    <div id="spinner" class="show bg-white position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
            <span class="sr-only">Loading...</span>
        </div>
    </div>
    
    <div class="container-fluid hero-header text-light min-vh-100 d-flex align-items-center justify-content-center">
        <div class="p-5 rounded shadow" style="max-width: 1200px; width: 100%;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="text-white m-0">Gestão de Carteiras dos Clientes</h3>
                <a href="pagina_inicial_func.php" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-arrow-left me-2"></i>Voltar ao Início
                </a>
            </div>

            <?php if (!empty($mensagem_erro)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($mensagem_erro); ?></div>
            <?php endif; ?>
            
            <?php if (!empty($mensagem_sucesso)): ?>
                <div class="alert alert-success" id="mensagem-sucesso">
                    <?php echo htmlspecialchars($mensagem_sucesso); ?>
                </div>
                <script>
                    // Esconde a mensagem de sucesso após 3 segundos
                    setTimeout(function() {
                        document.getElementById('mensagem-sucesso').style.display = 'none';
                    }, 3000);
                </script>
            <?php endif; ?>

            <?php if (!empty($clientes)): ?>
                <div class="row g-3">
                    <?php foreach ($clientes as $cliente): ?>
                        <div class="col-12">
                            <div class="bg-gradient mb-3 position-relative mx-auto mt-3 animated slideInDown">
                                <div class="card-body p-4">
                                    <div class="row align-items-center">
                                        <div class="col-md-8">
                                            <div class="d-flex align-items-center mb-4">
                                                <i class="fas fa-wallet text-primary me-2 fa-lg"></i>
                                                <h5 class="card-title text-primary mb-0">
                                                    <?php echo htmlspecialchars($cliente['nome_proprio']); ?> 
                                                </h5>
                                            </div>
                                            
                                            <div class="row">
                                                <div class="col-sm-6">
                                                    <p class="card-text mb-1"><strong><i class="fas fa-user text-info me-1"></i>ID Cliente:</strong> #<?php echo htmlspecialchars($cliente['id_utilizador']); ?></p>
                                                    <p class="card-text mb-1"><strong><i class="fas fa-credit-card text-warning me-1"></i>ID Carteira:</strong> #<?php echo htmlspecialchars($cliente['id_carteira'] ?? 'N/A'); ?></p>
                                                </div>
                                                <div class="col-sm-6">
                                                    <p class="card-text mb-1">
                                                        <strong><i class="fas fa-coins me-1"></i>Saldo Atual:</strong>
                                                        <span class="badge bg-<?php echo ($cliente['saldo'] ?? 0) > 0 ? 'success' : 'secondary'; ?> fs-6">
                                                            <?php echo number_format($cliente['saldo'] ?? 0, 2, ',', '.'); ?> €
                                                        </span>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-4 text-md-end">
                                            <div class="mb-3">
                                                <button type="button" class="btn btn-warning rounded-pill py-2 px-4 botao-edicao" data-cliente-id="<?php echo $cliente['id_utilizador']; ?>">
                                                    <i class="fas fa-edit me-2"></i>Editar Saldo
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Formulário de edição (inicialmente oculto) -->
                                    <div id="formulario-edicao-<?php echo $cliente['id_utilizador']; ?>" class="formulario-edicao" style="display: none;">
                                        <hr class="text-white my-4">
                                        <form action="editar_saldo_clientes.php" method="POST">
                                            <input type="hidden" name="id_utilizador" value="<?php echo htmlspecialchars($cliente['id_utilizador']); ?>">
                                            
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <div class="mb-3">
                                                        <label class="form-label text-white">
                                                            <i class="fas fa-euro-sign text-success me-1"></i>Novo Saldo:
                                                        </label>
                                                        <div class="input-group">
                                                            <input type="number" 
                                                                   name="novo_saldo" 
                                                                   class="form-control bg-dark text-light border-primary" 
                                                                   value="<?php echo htmlspecialchars($cliente['saldo'] ?? 0); ?>" 
                                                                   step="0.01" 
                                                                   min="0" 
                                                                   required>
                                                            <span class="input-group-text bg-primary text-white">€</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="d-flex justify-content-end gap-2 mt-3">
                                                <button type="button" class="btn btn-outline-danger rounded-pill botao-cancelar" data-cliente-id="<?php echo $cliente['id_utilizador']; ?>">
                                                    <i class="fas fa-times me-2"></i>Cancelar
                                                </button>
                                                <button type="submit" class="btn btn-success rounded-pill">
                                                    <i class="fas fa-save me-2"></i>Atualizar Saldo
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
            <?php else: ?>
                <div class="text-center">
                    <div class="mb-4">
                        <i class="fas fa-users text-light" style="font-size: 4rem; opacity: 0.5;"></i>
                    </div>
                    <p class="text-center text-white fs-5 mb-4">Nenhum cliente encontrado.</p>
                </div>
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
    
    <script>
        $(document).ready(function() {
            // Botão de edição - para cada cliente
            $('.botao-edicao').click(function() {
                var clienteId = $(this).data('cliente-id');
                $('#formulario-edicao-' + clienteId).slideDown();
                $(this).hide();
            });

            // Botão de cancelar - para cada cliente
            $(document).on('click', '.botao-cancelar', function() {
                var clienteId = $(this).data('cliente-id');
                $('#formulario-edicao-' + clienteId).slideUp();
                $('.botao-edicao[data-cliente-id="' + clienteId + '"]').show();
            });
            
            // Confirmação antes de submeter
            $('form').submit(function(e) {
                var novoSaldo = $(this).find('input[name="novo_saldo"]').val();
                var nomeCliente = $(this).closest('.card-body').find('.card-title').text().trim();
                
                if (!confirm('Tem certeza que deseja alterar o saldo de ' + nomeCliente + ' para ' + novoSaldo + '€?')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>