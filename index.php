<?php include "conectar.php"; ?>
<link rel="icon" href="./trf.png" type="image/png">
<?php
$result = $conn->query("SELECT * FROM presenca");
$usuarios = [];
while ($row = $result->fetch_assoc()) {
  if ($row['status'] === 'ausente' && $row['atualizado_em']) {
    $atualizadoEm = new DateTime($row['atualizado_em']);
    $agora = new DateTime();
    $diff = $agora->getTimestamp() - $atualizadoEm->getTimestamp();

    $horas = floor($diff / 3600);
    $minutos = floor(($diff % 3600) / 60);

    $row['tempo_ausente'] = sprintf('%02d h %02d min', $horas, $minutos);
} else {
    $row['tempo_ausente'] = '';
}
$usuarios[] = $row;

}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    
  <meta charset="UTF-8">
  <title>ISA1</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body { font-family: Arial; padding: 20px; }
    .usuario {
      display: flex;
      align-items: center;
      gap: 15px;
      margin-bottom: 10px;
    }
    .btn {
      padding: 10px 20px;
      border: none;
      color: #fff;
      font-weight: bold;
      border-radius: 8px;
      cursor: pointer;
      transition: 0.3s;
    }
    .presente { background-color: #28a745; }
    .ausente { background-color: #dc3545; }
  </style>
</head>
<body>
<div class="logo-central">
  <img src="trf.png" alt="Logo TRF">
</div><style>.logo-central {
  display: flex;
  justify-content: center;
  align-items: center;
  margin-bottom: 30px;
}

.logo-central img {
  max-width: 99px;
  height: auto;
}</style>
  <h2>ISA1 - CONTROLE DE PRESENÇA</h2><br>
__________________________________________
  <br><br><div id="lista"><br>
    <?php foreach ($usuarios as $u): ?>
      <div class="usuario" data-id="<?= $u['id'] ?>"><br>
        <span>
            
            <?= $u['nome'] ?>     <input type="text"           id="texto-<?= $u['id'] ?>"     placeholder="Observação..."
                   
            </span>
  <button class="btn <?= $u['status'] ?>" onclick="togglePresenca(<?= $u['id'] ?>, this)">
  <?= strtoupper($u['status']) ?>
  <?php if ($u['status'] === 'ausente' && $u['tempo_ausente']): ?>
    (<?= $u['tempo_ausente'] ?>)
  <?php endif; ?>
</button><br> <br> <br>________________________________________
      </div>
    <?php endforeach; ?>
  </div>

<script>
 function togglePresenca(id, btn) {
  const observacao = document.getElementById('texto-' + id).value.trim();
  const radios = document.getElementsByName('tipo-' + id);
  let tipo = '';
  for (let r of radios) {
    if (r.checked) {
      tipo = r.value;
      break;
    }
  }

  const statusAtual = btn.classList.contains('presente') ? 'presente' : 'ausente';
  const novoStatus = statusAtual === 'presente' ? 'ausente' : 'presente';

  if (novoStatus === 'ausente' && observacao === '') {
    alert('Por favor, preencha a observação antes de marcar como AUSENTE.');
    return;
  }

  btn.disabled = true;

  fetch('registrar.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `id=${id}&status=${statusAtual}&observacao=${encodeURIComponent(observacao)}&tipo=${encodeURIComponent(tipo)}`
  })
  .then(res => res.json())
  .then(data => {
    btn.classList.remove('presente', 'ausente');
    btn.classList.add(data.status);

    if (data.status === 'ausente') {
      // Começa a contagem do tempo
      let segundos = 0;
      const intervalId = setInterval(() => {
        segundos++;
        const horas = Math.floor(segundos / 3600);
        const minutos = Math.floor((segundos % 3600) / 60);
        btn.textContent = 'AUSENTE (' + String(horas).padStart(2, '0') + ' h ' + String(minutos).padStart(2, '0') + ' min)';
      }, 60000); // atualiza a cada minuto

      // Mostrar logo 00 h 00 min
      btn.textContent = 'AUSENTE (00 h 00 min)';

      // Salva no botão o ID do interval pra parar se necessário depois
      btn.dataset.intervalId = intervalId;
    } else {
      // Se voltou pra presente, limpa o contador se existir
      if (btn.dataset.intervalId) {
        clearInterval(btn.dataset.intervalId);
        delete btn.dataset.intervalId;
      }
      btn.textContent = 'PRESENTE';
    }
  })
  .finally(() => {
    btn.disabled = false;
  });
}


</script>

</body>
</html>
