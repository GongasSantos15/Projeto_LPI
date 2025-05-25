<?php

    // Include da conexão à BD
    include '..\basedados\basedados.h';

    // Query SQL para selecionar todos os distritos na BD e executar a mesma
    $sql = "SELECT DISTINCT origem FROM rota ORDER BY origem ASC";
    $resultado = $conn->query($sql);

    if ($resultado) {
        echo '<option value="">Escolha uma opção</option>';
        while ($linha = $resultado->fetch_assoc()) {
            echo '<option value="' . htmlspecialchars($linha['origem']) . '">' . htmlspecialchars($linha['origem']) . '</option>';
        }
    } else {
        echo '<option disabled>Erro ao carregar distritos: ' . $conn->$connect_error . '</option>';
    }
?>

?>
