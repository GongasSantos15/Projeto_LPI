<?php
    include "../basedados/basedados.h";

    session_start();

    // Verificar se é admin
    if (!isset($_SESSION['tipo_utilizador']) || $_SESSION['tipo_utilizador'] != 1) {
        $_SESSION['mensagem_erro'] = "Acesso não autorizado!";
        header('Location: consultar_rotas.php');
        exit();
    }

    if ($_SERVER["REQUEST_METHOD"] == "GET") {
        $origem = filter_input(INPUT_GET, 'origem');
        $destino = filter_input(INPUT_GET, 'destino');

        if (empty($origem) || empty($destino)) {
            $mensagem_erro = 'Por favor, preencha todos os campos.';
        } else {
            // Inserir a rota na base de dados
            $sql = "INSERT INTO rota (origem, destino, estado) VALUES (?, ?, 1)";
            $stmt = $conn->prepare($sql);

            if ($stmt) {
                $stmt->bind_param("ss", $origem, $destino);
                if ($stmt->execute()) {
                    $_SESSION['mensagem_sucesso'] = 'Rota adicionada com sucesso!';
                    header("Location: consultar_rotas.php");
                } else {
                    $_SESSION['mensagem_erro'] = 'Erro ao adicionar a rota: ' . $stmt->$connect_error;
                }
                $stmt->close();
            } else {
                $_SESSION['mensagem_erro'] = 'Erro ao preparar a consulta: ' . $conn->$connect_error;
            }
        }
    }

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>FelixBus - Adicionar Rota</title>
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
        .dropdown:hover > .dropdown-menu {
            display: block;
        }
    </style>

    <script src="main.js" defer></script>
</head>

<body>
    <div id="spinner" class="show bg-white position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
            <span class="sr-only">Loading...</span>
        </div>
    </div>
    <div class="container-fluid position-relative p-0">
        <nav class="navbar navbar-expand-lg navbar-light px-5 px-lg-5 py-3 py-lg-3">
            <a href="pagina_inicial_admin.php" class="navbar-brand p-0">
                <h1 class="text-primary m-0"><i class="fa fa-map-marker-alt me-3"></i>FelixBus</h1>
                </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
                <span class="fa fa-bars"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarCollapse">
                <div class="navbar-nav ms-auto py-0">
                    <a href="consultar_rotas.php" class="nav-item nav-link">Rotas</a>
                </div>
            </div>
        </nav>

        <div class="container-fluid hero-header text-light min-vh-100 d-flex align-items-center justify-content-center">
            <div class="p-5 rounded shadow" style="max-width: 900px; width: 100%;">
                <div class="d-flex justify-content-center align-items-center mb-4">
                    <h3 class="text-white m-0">Adicionar Rotas</h3>
                </div>
                <div class="bg-gradient position-relative w-75 mx-auto mt-5 animated slideInDown">
                    <form method="GET" action="adicionar_rota.php" class="d-flex flex-wrap p-4 rounded text-light justify-content-center" style="gap: 2rem 0.5rem;">
                        <div class="me-4">
                            <label class="form-label">Origem:</label>
                            <input type="text" name="origem" id="origem" class="form-control bg-dark text-light border-primary" placeholder="Insira a origem da rota" required />
                        </div>

                        <div class="me-4">
                            <label class="form-label">Destino:</label>
                                <input type="text" name="destino" id="destino" class="form-control bg-dark text-light border-primary" placeholder="Insira o destino da rota" required />

                        </div>

                        <div class="w-100 text-center mt-2">
                            <input type="submit" value="Adicionar" class="btn btn-primary text-light px-5 py-2 rounded-pill" />
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>

    <!-- Footer Start -->
    <div class="container-fluid bg-dark d-flex justify-content-center text-light footer pt-5 wow fadeIn" data-wow-delay="0.1s">
        <div class="container py-5">
            <div class="row">
                <div class="col-lg-4">
                    <h4 class="text-white mb-3">Localização</h4>
                    <p class="mb-2"><i class="fa fa-map-marker-alt me-3"></i>Rua 123, Castelo Branco, Portugal</p>
                </div>
                <div class="col-lg-4">
                    <h4 class="text-white mb-3">Contactos</h4>
                    <p class="mb-2"><i class="fa fa-phone me-3"></i><strong>Telemóvel:</strong> +351 925 887 788</p>
                    <p class="mb-2"><i class="fa fa-phone me-3"></i><strong>Telefone:</strong> +351 272 999 888</p>
                </div>
                <div class="col-lg-4">
                    <h4 class="text-white mb-3">Horário de Funcionamento</h4>
                    <p class="mb-2"><i class="fa fa-clock me-3"></i><strong>Segunda a Sexta:</strong> 9:00 - 17:00</p>
                    <p class="mb-2"><i class="fa fa-clock me-3"></i><strong>Sábados:</strong> 10:00 - 16:00</p>
                    <p class="mb-2"><i class="fa fa-clock me-3"></i><strong>Domingos e Feriados:</strong> 9:00 - 13:00</p>
                </div>
            </div>
        </div>
    </div>
    <!-- Footer End -->

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