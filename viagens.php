<?php
    // Include conexão à BD
    include("basedados\basedados.h");

    // Verifica se o formulário foi submetido
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Obtém os dados do formulário
        $origem = isset($_GET['origem']) ? $_GET['origem'] : '';
        $destino = isset($_GET['destino']) ? $_GET['destino'] : '';
        $data_viagem = isset($_GET['data']) ? $_GET['data'] : '';

        $resultados = [];

        if ($conn) {
            // Consulta SQL para encontrar as viagens correspondentes
            $sql = "SELECT
                        v.id AS id_viagem,
                        v.data_hora,
                        r.origem,
                        r.destino
                    FROM
                        viagem v
                    JOIN
                        rota r ON v.id_rota = r.id
                    WHERE
                        r.origem = ? AND r.destino = ? AND DATE(v.data_hora) = ?
                    ORDER BY
                        v.data_hora";

            $stmt = $conn->prepare($sql);

            if ($stmt) {
                    $stmt->bind_param("sss", $origem, $destino, $data_viagem);

                if($stmt->execute()) {
                    $resultado = $stmt->get_result();
                    $resultados = $resultado->fetch_all(MYSQLI_ASSOC);
                    $_SESSION['origem'] = $origem;
                    $_SESSION['destino'] = $destino;
                    $_SESSION['data_viagem'] = $data_viagem;
                }

                $stmt->close();
            }
        }
        
        $conn->close();
    }

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>FelixBus - Viagens</title>
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
        <h3 class="text-center text-white mb-4">Viagens Encontradas</h3>

        <?php if (!empty($resultados)): ?>
            <div class="row g-3">
                <?php foreach ($resultados as $viagem): ?>
                    <div class="col-12">
                        <div class="bg-gradient position-relative mx-auto mt-3 animated slideInDown">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="card-title text-primary">Viagem #<?php echo htmlspecialchars($viagem['id_viagem']); ?></h5>
                                    <p class="card-text"><strong>De:</strong> <?php echo htmlspecialchars($viagem['origem']); ?> </p>
                                    <p class="card-text"><strong>Para:</strong> <?php echo htmlspecialchars($viagem['destino']); ?></p>
                                    <p class="card-text"><strong>Data e Hora:</strong> <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($viagem['data_hora']))); ?></p>
                                </div>
                                <div>
                                    <a href="comprar_bilhetes.php" class="btn btn-primary text-light px-5 py-2 rounded-pill">Comprar</a> 
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-center text-white">Nenhuma viagem encontrada para os critérios selecionados.</p>
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