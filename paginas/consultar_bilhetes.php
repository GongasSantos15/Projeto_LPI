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

    $id_utilizador = $_SESSION['id_utilizador'];
    $bilhetes = [];

    if ($conn) {
        $sql = "SELECT
                    b.id AS id_bilhete,
                    b.data_compra,
                    v.preco,
                    v.data_hora,
                    r.origem,
                    r.destino,
                    v.id AS id_viagem,
                    b.estado
                FROM
                    bilhete b
                JOIN
                    viagem v ON b.id_viagem = v.id
                JOIN
                    rota r ON v.id_rota = r.id
                WHERE
                    b.id_utilizador = ? AND b.estado = 1
                ORDER BY
                    b.data_compra ASC";

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
<html lang="pt">

<head>
    <meta charset="utf-8">
    <title>FelixBus - Meus Bilhetes</title>
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
        <div class="p-5 rounded shadow" style="max-width: 900px; width: 100%;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="text-white m-0">Os Meus Bilhetes</h3>
                <a href="pagina_inicial_cliente.php" class="btn btn-outline-light btn-sm">
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
                    // Esconde a mensagem de sucesso após 2 segundos
                    setTimeout(function() {
                        document.getElementById('mensagem-sucesso').style.display = 'none';
                    }, 2000);
                </script>
            <?php endif; ?>

            <?php if (!empty($bilhetes)): ?>
                <div class="row g-3">
                    <?php foreach ($bilhetes as $bilhete): ?>
                        <?php if ($bilhete['estado'] == 1): ?>
                        <div class="col-12">
                            <div class="bg-gradient mb-3 position-relative mx-auto mt-3 animated slideInDown">
                                <div class="card-body p-4">
                                    <div class="row align-items-center">
                                        <div class="col-md-8">
                                            <div class="d-flex align-items-center mb-4">
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
                                                <span class="badge bg-info text-dark fs-6 px-3 py-2">
                                                    <i class="fas fa-euro-sign me-1"></i>
                                                    <?php echo number_format($bilhete['preco'], 2, ',', '.'); ?> €
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mt-4">
                                        <div class="col-sm-6 d-flex justify-content-center">
                                            <div class="mb-3">
                                                <button type="button" class="btn btn-warning rounded-pill py-2 px-4 botao-edicao" data-bilhete-id="<?php echo $bilhete['id_bilhete']; ?>">
                                                    <i class="fas fa-edit me-2"></i>Editar
                                                </button>
                                            </div>
                                        </div>
                                        <div class="col-sm-6 d-flex justify-content-center">
                                            <div class="mb-3">
                                                <button type="button" class="btn btn-danger rounded-pill py-2 px-4 botao-anular" data-bilhete-id="<?php echo $bilhete['id_bilhete']; ?>">
                                                    <i class="fas fa-times me-2"></i>Anular
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Formulário de edição (inicialmente oculto) -->
                                    <div id="formulario-edicao-<?php echo $bilhete['id_bilhete']; ?>" class="formulario-edicao" style="display: none;">
                                        <hr class="text-white my-4">
                                        <form action="editar_bilhete.php" method="GET">
                                            <input type="hidden" name="id_bilhete" value="<?php echo htmlspecialchars($bilhete['id_bilhete']); ?>">
                                            
                                            <!-- Dentro do formulário de edição -->
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label text-white">
                                                            <i class="fas fa-map-marker-alt text-success me-1"></i>Origem:
                                                        </label>
                                                        <div class="view-mode">
                                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($bilhete['origem']); ?>" readonly>
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
                                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($bilhete['destino']); ?>" readonly>
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
                                                <button type="button" class="btn btn-outline-danger rounded-pill botao-cancelar" data-bilhete-id="<?php echo $bilhete['id_bilhete']; ?>">
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
                        <?php endif ?>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center">
                    <div class="mb-4">
                        <i class="fas fa-ticket-alt text-light" style="font-size: 4rem; opacity: 0.5;"></i>
                    </div>
                    <p class="text-center text-white fs-5 mb-4">Ainda não possui bilhetes comprados.</p>
                    <a href="comprar_bilhete.php" class="btn btn-primary rounded-pill py-2 px-4">
                        <i class="fas fa-ticket-alt me-2"></i>Comprar Bilhete
                    </a>
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
        function carregarDistritosBilhete(bilheteId) {
            fetch('rotas.php')
                .then(response => response.text())
                .then(data => {
                    // Atualiza apenas os selects do bilhete específico
                    document.querySelector(`#formulario-edicao-${bilheteId} #origem`).innerHTML = data;
                    document.querySelector(`#formulario-edicao-${bilheteId} #destino`).innerHTML = data;
                })
                .catch(error => {
                    console.error('Erro ao carregar distritos:', error);
                });
        }

        $(document).ready(function() {
            // Botão de edição - para cada bilhete
            $('.botao-edicao').click(function() {
                var bilheteId = $(this).data('bilhete-id');
                $('#formulario-edicao-' + bilheteId).slideDown();
                $(this).hide();
                
                // Mostrar selects e esconder inputs
                $('#formulario-edicao-' + bilheteId + ' .edit-mode').show();
                $('#formulario-edicao-' + bilheteId + ' .view-mode').hide();
                
                // Carregar distritos para este formulário
                carregarDistritosBilhete(bilheteId);
            });

            // Botão de cancelar - para cada bilhete
            $(document).on('click', '.botao-cancelar', function() {
                var bilheteId = $(this).data('bilhete-id');
                $('#formulario-edicao-' + bilheteId).slideUp();
                $('.botao-edicao[data-bilhete-id="' + bilheteId + '"]').show();
                
                // Mostrar inputs e esconder selects
                $('#formulario-edicao-' + bilheteId + ' .view-mode').show();
                $('#formulario-edicao-' + bilheteId + ' .edit-mode').hide();
            });
        });
        
        // Botão para anular bilhete
        $(document).on('click', '.botao-anular', function() {
            var bilheteId = $(this).data('bilhete-id');
            if(confirm('Tem certeza que deseja anular este bilhete?')) {
                $.ajax({
                    url: 'anular_bilhete.php',
                    method: 'POST',
                    data: { id_bilhete: bilheteId },
                    success: function(response) {
                        location.reload();
                    },
                    error: function() {
                        alert('Erro ao anular bilhete');
                    }
                });
            }
        });
    </script>
</body>
</html>