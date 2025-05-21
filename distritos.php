<?php
    // Include da conexão à BD
    include 'C:\xampp\htdocs\lpi\Projeto_LPI\basedados\basedados.h';

    // Query SQL para selecionar todos os distritos na BD e executar a mesma
    $sql = "SELECT distrito FROM distritos ORDER BY distrito ASC";
    $result = mysqli_query($conn, $sql);

    // Se existir resultado, apresenta o mesmo num select, em que as opções são os distritos presentes na BD, senão exibe uma mensagem de erro
    if ($result) {
        echo '<option value="">Escolha uma opção</option>';
        while ($row = mysqli_fetch_assoc($result)) {
            echo '<option value="' . htmlspecialchars($row['distrito']) . '">' . htmlspecialchars($row['distrito']) . '</option>';
        }
    } else {
        echo '<option disabled>Erro ao carregar distritos</option>';
    }
?>
