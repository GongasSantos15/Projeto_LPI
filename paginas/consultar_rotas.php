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

    // Só executa a consulta se houver conexão e não houver mensagem de sucesso
    if ($conn) {
        // Consulta SQL para obter todas as rotas
        $sql = "SELECT id, origem, destino FROM rota WHERE estado = 1 ORDER BY origem";

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
    <div class="p-5 rounded shadow" style="max-width: 900px; width: 100%;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="text-white m-0">Consultar Rotas</h3>
            <a href="<?php echo htmlspecialchars($pagina_inicial); ?>" class="btn btn-outline-light btn-sm">
                <i class="fas fa-arrow-left me-2"></i>Voltar ao Início
            </a>
        </div>
        <?php if (isset($_SESSION['tipo_utilizador']) && $_SESSION['tipo_utilizador'] == 1): ?>
            <div class="text-center my-5">
                <h5 class="text-white">Pretende adicionar uma nova rota? <a href="adicionar_rota.php" class="text-info"> Clique aqui</a></h5>
            </div>
        <?php endif; ?>

        <?php if (!empty($mensagem_erro)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($mensagem_erro); ?></div>
        <?php endif; ?>
        
        <?php if (!empty($mensagem_sucesso)): ?>
            <div class="alert alert-success" id="mensagem-sucesso">
                <?php echo htmlspecialchars($mensagem_sucesso); ?>
            </div>
            <script>
                // Esconde a mensagem de sucesso após 2 segundos
                setTimeout(function() {
                    document.getElementById('mensagem-sucesso').style.display = 'none';
                }, 2000);
            </script>
        <?php endif; ?>

        <?php if (!empty($rotas)): ?>
            <!-- Container com scroll aplicado apenas às rotas -->
            <div class="rotas-scroll-container">
                <div class="row g-3">
                    <?php foreach ($rotas as $rota): ?>
                        <div class="col-12">
                            <div class="bg-gradient position-relative mx-auto mt-3 animated slideInDown">
                                <div class="card-body p-4">
                                    <div class="row align-items-center">
                                        <div class="col-md-8">
                                            <div class="d-flex align-items-center mb-3">
                                                <i class="fas fa-route text-primary me-2 fa-lg"></i>
                                                <h5 class="card-title text-primary mb-0">Rota #<?php echo htmlspecialchars($rota['id']); ?></h5>
                                            </div>
                                            <div class="row">
                                                <div class="col-sm-6">
                                                    <p class="card-text mb-1"><strong><i class="fas fa-map-marker-alt text-success me-1"></i>De:</strong> <?php echo htmlspecialchars($rota['origem']); ?></p>
                                                </div>
                                                <div class="col-sm-6">
                                                    <p class="card-text mb-1"><strong><i class="fas fa-map-marker-alt text-info me-1"></i>Para:</strong> <?php echo htmlspecialchars($rota['destino']); ?></p>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <?php if (isset($_SESSION['tipo_utilizador']) && $_SESSION['tipo_utilizador'] == 1): ?>
                                        <div class="col-md-4 text-md-end">
                                            <div class="d-flex flex-column gap-2">
                                                <button type="button" class="btn btn-warning rounded-pill py-2 px-4 botao-edicao" data-rota-id="<?php echo $rota['id']; ?>">
                                                    <i class="fas fa-edit me-2"></i>Editar
                                                </button>
                                                <button type="button" class="btn btn-danger rounded-pill py-2 px-4 botao-anular" data-rota-id="<?php echo $rota['id']; ?>">
                                                    <i class="fas fa-times me-2"></i>Anular
                                                </button>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if (isset($_SESSION['tipo_utilizador']) && $_SESSION['tipo_utilizador'] == 1): ?>
                                    <!-- Formulário de edição (inicialmente oculto) -->
                                    <div id="formulario-edicao-<?php echo $rota['id']; ?>" class="formulario-edicao" style="display: none;">
                                        <hr class="text-white my-4">
                                        <form action="editar_rota.php" method="POST">
                                            <input type="hidden" id="id" name="id" value="<?php echo htmlspecialchars($rota['id']); ?>">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label text-white">
                                                            <i class="fas fa-map-marker-alt text-success me-1"></i>Origem:
                                                        </label>
                                                        <div class="view-mode">
                                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($rota['origem']); ?>" readonly>
                                                        </div>
                                                        <div class="edit-mode" style="display: none;">
                                                            <select name="origem" id="origem" class="form-select bg-dark text-light border-primary" required>
                                                                <option>A carregar...</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label text-white">
                                                            <i class="fas fa-map-marker-alt text-danger me-1"></i>Destino:
                                                        </label>
                                                        <div class="view-mode">
                                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($rota['destino']); ?>" readonly>
                                                        </div>
                                                        <div class="edit-mode" style="display: none;">
                                                            <select name="destino"  id="destino" class="form-select bg-dark text-light border-primary" required>
                                                                <option>A carregar...</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="d-flex justify-content-end gap-2 mt-3">
                                                <button type="button" class="btn btn-outline-danger rounded-pill botao-cancelar" data-rota-id="<?php echo $rota['id']; ?>">
                                                    <i class="fas fa-times me-2"></i>Cancelar
                                                </button>
                                                <button type="submit" class="btn btn-success rounded-pill">
                                                    <i class="fas fa-save me-2"></i>Guardar Alterações
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="text-center">
                <div class="mb-4">
                    <i class="fas fa-route text-light" style="font-size: 4rem; opacity: 0.5;"></i>
                </div>
                <p class="text-center text-white fs-5 mb-4">Nenhuma rota encontrada.</p>
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

<?php if (isset($_SESSION['tipo_utilizador']) && $_SESSION['tipo_utilizador'] == 1): ?>
    <script>
        function carregarDistritosRota(rotaId) {
            fetch('rotas.php')
                .then(response => response.text())
                .then(data => {
                    // Atualiza apenas os selects da rota específica
                    document.querySelector(`#formulario-edicao-${rotaId} #origem`).innerHTML = data;
                    document.querySelector(`#formulario-edicao-${rotaId} #destino`).innerHTML = data;
                })
                .catch(error => {
                    console.error('Erro ao carregar distritos:', error);
                });
        }

        $(document).ready(function() {
            // Botão de edição - para cada rota
            $('.botao-edicao').click(function() {
                var rotaId = $(this).data('rota-id');
                $('#formulario-edicao-' + rotaId).slideDown();
                $(this).parent().hide(); // Esconde os botões de ação
                
                // Mostrar inputs de edição e esconder view mode
                $('#formulario-edicao-' + rotaId + ' .edit-mode').show();
                $('#formulario-edicao-' + rotaId + ' .view-mode').hide();
                
                // Carregar distritos para este formulário
                carregarDistritosRota(rotaId);
            });

            // Botão de cancelar - para cada rota
            $(document).on('click', '.botao-cancelar', function() {
                var rotaId = $(this).data('rota-id');
                $('#formulario-edicao-' + rotaId).slideUp();
                $('.botao-edicao[data-rota-id="' + rotaId + '"]').parent().show(); // Mostra os botões de ação
                
                // Mostrar view mode e esconder inputs de edição
                $('#formulario-edicao-' + rotaId + ' .view-mode').show();
                $('#formulario-edicao-' + rotaId + ' .edit-mode').hide();
            });
        });

        // Botão para anular rota
        $(document).on('click', '.botao-anular', function() {
            var rotaId = $(this).data('rota-id');
            if(confirm('Tem certeza que deseja anular esta rota? Esta ação pode afetar viagens já agendadas.')) {
                $.ajax({
                    url: 'anular_rota.php',
                    method: 'POST',
                    data: { id: rotaId },
                    success: function(response) {
                        location.reload();
                    },
                    error: function() {
                        alert('Erro ao anular rota');
                    }
                });
            }
        });
    </script>
<?php endif; ?>

</body>

</html>