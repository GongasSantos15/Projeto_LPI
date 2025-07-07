<?php
    include "../basedados/basedados.h";
    include 'const_utilizadores.php';

    session_start();

    $mensagem_erro = '';
    $mensagem_sucesso = '';
    $saldo_inicial = 0;

    if($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Verifique os nomes dos campos exatamente como estão no formulário HTML
        if(!empty($_POST['nome_utilizador']) && !empty($_POST['nome_proprio']) && !empty($_POST['palavra_passe']) && !empty($_POST['confirmar_palavra_passe'])) {
            
            // Use o mesmo nome que está no formulário (com underscore)
            $nome_utilizador_form = $_POST['nome_utilizador'];
            $nome_proprio_form = $_POST['nome_proprio'];
            $palavra_passe_form = $_POST['palavra_passe'];
            $confirmar_palavra_passe_form = $_POST['confirmar_palavra_passe'];

            if($palavra_passe_form === $confirmar_palavra_passe_form) {
                // Escapar strings para segurança antes de usar em consultas SQL (Mesmo com prepared statements, boa prática para valores complexos)
                $nome_utilizador_escaped = mysqli_real_escape_string($conn, $nome_utilizador_form);
                $nome_proprio_escaped = mysqli_real_escape_string($conn, $nome_proprio_form);
                $palavra_passe_encriptada = md5($palavra_passe_form);

                $tipo_utilizador = CLIENTE_NAO_VALIDO;

                // 1. Inserir o novo utilizador na tabela `utilizador`
                $sql_inserir_utilizador = "INSERT INTO utilizador (nome_utilizador, nome_proprio, palavra_passe, tipo_utilizador) VALUES (?, ?, ?, ?)";
                $stmt_inserir_utilizador = $conn->prepare($sql_inserir_utilizador);
                
                if (!$stmt_inserir_utilizador) {
                    $mensagem_erro = "Erro ao preparar a consulta para registar utilizador: " . $conn->error;
                } else {
                    $stmt_inserir_utilizador->bind_param("sssi", $nome_utilizador_escaped, $nome_proprio_escaped, $palavra_passe_encriptada, $tipo_utilizador);

                    if ($stmt_inserir_utilizador->execute()){
                        $novo_id_utilizador = $conn->insert_id;
                        
                        // 2. Inserir a nova carteira na tabela `carteira` com o saldo inicial
                        $sql_inserir_carteira = "INSERT INTO carteira (id_carteira, saldo) VALUES (?, ?)";
                        $stmt_inserir_carteira = $conn->prepare($sql_inserir_carteira);

                        if (!$stmt_inserir_carteira) {
                            $mensagem_erro = "Erro ao preparar a consulta para criar a carteira: " . $conn->error;
                            // Se a carteira não pode ser criada, reverte a criação do utilizador
                            $conn->query("DELETE FROM utilizador WHERE id = $novo_id_utilizador");
                        } else {
                            $stmt_inserir_carteira->bind_param("id", $novo_id_utilizador, $saldo_inicial);

                            if($stmt_inserir_carteira->execute()) {
                                // 3. Atualizar o utilizador com o `id_carteira` recém-criado
                                $sql_atualizar_utilizador_carteira = "UPDATE utilizador SET id_carteira = ? WHERE id = ?";
                                $stmt_atualizar_utilizador_carteira = $conn->prepare($sql_atualizar_utilizador_carteira);

                                if (!$stmt_atualizar_utilizador_carteira) {
                                    $mensagem_erro = "Erro ao preparar a consulta para atualizar o ID da carteira do utilizador: " . $conn->error;
                                    // Se a atualização falhar, pode ser necessário reverter tudo
                                    $conn->query("DELETE FROM carteira WHERE id_carteira = $novo_id_utilizador");
                                    $conn->query("DELETE FROM utilizador WHERE id = $novo_id_utilizador");
                                } else {
                                    $stmt_atualizar_utilizador_carteira->bind_param("ii", $novo_id_utilizador, $novo_id_utilizador);

                                    if ($stmt_atualizar_utilizador_carteira->execute()) {
                                        $mensagem_sucesso = "Utilizador e carteira registados com sucesso!";
                                        // Redireciona apenas após o sucesso completo
                                        header("Location: entrar.php");
                                        exit();
                                    } else {
                                        $mensagem_erro = "Erro ao atualizar o ID da carteira do utilizador: " . $stmt_atualizar_utilizador_carteira->error;
                                        $conn->query("DELETE FROM carteira WHERE id_carteira = $novo_id_utilizador");
                                        $conn->query("DELETE FROM utilizador WHERE id = $novo_id_utilizador");
                                    }
                                    $stmt_atualizar_utilizador_carteira->close();
                                }
                            } else {
                                $mensagem_erro = "Erro ao criar a carteira do utilizador: " . $stmt_inserir_carteira->error;
                                // Se a carteira não pode ser criada, reverte a criação do utilizador
                                $conn->query("DELETE FROM utilizador WHERE id = $novo_id_utilizador");
                            }
                            $stmt_inserir_carteira->close();
                        }
                    } else {
                        $mensagem_erro = "Erro ao registar utilizador: " . $stmt_inserir_utilizador->error;
                    }
                    $stmt_inserir_utilizador->close();
                }
            } else {
                $mensagem_erro = "As palavras-passe não coincidem.";
            }
        } else {
             $mensagem_erro = "Por favor, preencha todos os campos.";
        }
    }
    
    // Fechar a conexão
    if ($conn) {
        $conn->close();
    }
?>

<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="utf-8">
    <title>FelixBus - Registar</title>
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
        <div class="p-5 rounded shadow" style="max-width: 700px; width: 100%;">
            <h3 class="text-center text-white mb-4">Registar</h3>

            <?php if (!empty($mensagem_erro)) { ?>
                <div class="alert alert-danger" role="alert"><?= $mensagem_erro ?></div>
            <?php } ?>
            <?php if (!empty($mensagem_sucesso)) { ?>
                <div class="alert alert-success" role="alert"><?= $mensagem_sucesso ?></div>
            <?php } ?>

            <form action="registar.php" method="POST">
                <div class="mb-3">
                    <label for="nome_proprio" class="form-label">Nome Próprio:</label>
                    <input name="nome_proprio" id="nome_proprio" type="text" class="form-control text-dark" required value="<?= htmlspecialchars($_POST['nome_proprio'] ?? '') ?>" />
                </div>
                <div class="mb-3">
                    <label for="nome_utilizador" class="form-label">Nome de Utilizador:</label>
                    <input name="nome_utilizador" id="nome_utilizador" type="text" class="form-control text-dark" required value="<?= htmlspecialchars($_POST['nome_utilizador'] ?? '') ?>" />
                </div>
                <div class="mb-3">
                    <label for="palavra_passe" class="form-label">Palavra-passe:</label>
                    <div class="input-group">
                        <input name="palavra_passe" id="palavra_passe" type="password" class="form-control text-dark" required />
                        <button class="btn btn-outline-light border-start-0" type="button" id="mostraPalavraPasse">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="confirmar_palavra_passe" class="form-label">Confirmar Palavra-passe:</label>
                    <div class="input-group">
                        <input name="confirmar_palavra_passe" id="confirmar_palavra_passe" type="password" class="form-control text-dark" required />
                        <button class="btn btn-outline-light border-start-0" type="button" id="mostraConfirmarPalavraPasse">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                <div class="d-flex mt-5 justify-content-center">
                    <input type="submit" value="Registar" class="btn btn-primary rounded-pill py-2 px-5">
                </div>
            </form>
            <div class="text-center mt-5">
                <span>Já tem conta? <a href="entrar.php" class="text-info"> Inicie sessão aqui</a></span>
            </div>
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
        document.addEventListener('DOMContentLoaded', function() {
            const ativarMostrarPalavraPasse = document.querySelector('#mostraPalavraPasse');
            const ativarMostrarConfirmarPalavraPasse = document.querySelector("#mostraConfirmarPalavraPasse");
            const palavra_passe = document.querySelector('#palavra_passe');
            const confirmar_palavra_passe = document.querySelector('#confirmar_palavra_passe');
            
            ativarMostrarPalavraPasse.addEventListener('click', function(e) {
                e.preventDefault();
                const tipo = palavra_passe.getAttribute('type') === 'password' ? 'text' : 'password';
                palavra_passe.setAttribute('type', tipo);
                
                // Alterna o ícone
                const icone = this.querySelector('i');
                icone.classList.toggle('fa-eye');
                icone.classList.toggle('fa-eye-slash');
                
                // Mantém o foco no campo de password
                palavra_passe.focus();
            });

            ativarMostrarConfirmarPalavraPasse.addEventListener('click', function(e) {
                e.preventDefault();
                const tipo = confirmar_palavra_passe.getAttribute('type') === 'password' ? 'text' : 'password';
                confirmar_palavra_passe.setAttribute('type', tipo);
                
                // Alterna o ícone
                const icone2 = this.querySelector('i');
                icone2.classList.toggle('fa-eye');
                icone2.classList.toggle('fa-eye-slash');
                
                // Mantém o foco no campo de password
                confirmar_palavra_passe.focus();
            });
        });
    </script>
</body>

</html>