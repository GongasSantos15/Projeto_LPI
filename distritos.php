<?php
    include 'C:\xampp\htdocs\lpi\Projeto_LPI\basedados\basedados.h'; // conexão ao BD

    ini_set('display_errors', 1);
    error_reporting(E_ALL);


    $sql = "SELECT distrito FROM distritos ORDER BY distrito ASC";
    $result = mysqli_query($conn, $sql);

    if ($result) {
        echo '<option value="">Escolha uma opção</option>';
        while ($row = mysqli_fetch_assoc($result)) {
            echo '<option value="' . htmlspecialchars($row['distrito']) . '">' . htmlspecialchars($row['distrito']) . '</option>';
        }
    } else {
        echo '<option disabled>Erro ao carregar distritos</option>';
    }
?>
