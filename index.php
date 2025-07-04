<?php

$horaAtual = (int)date('H');
$sistemaAtivo = $horaAtual >= 10 && $horaAtual < 19;

include "conectar.php";
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
  <meta charset="UTF-8" />
  <title>ISA1</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <style>
    body {
      margin: 0;
      padding: 0;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      font-family: Arial, sans-serif;
      background: #f9f9f9;
    }

    .logo-central {
      display: flex;
      justify-content: center;
      align-items: center;
      height: 200px;
    }

    .logo-central img {
      width: 100px;
      height: auto;
    }

    h2 {
      margin-top: 10px;
      text-align: center;
      color: #222;
    }

    .usuario {
      display: flex;
      align-items: center;
      gap: 15px;
      margin-bottom: 15px;
      background: #fff;
      padding: 12px 20px;
      border-radius: 10px;
      box-shadow: 0 2px 6px rgb(0 0 0 / 0.1);
      flex-wrap: wrap;
    }

    .usuario span {
      flex: 1 1 200px;
      font-weight: 600;
      font-size: 1rem;
      color: #444;
    }

    .usuario input[type="text"] {
      flex: 1 1 250px;
      padding: 8px 12px;
      font-size: 0.9rem;
      border: 1.8px solid #ccc;
      border-radius: 8px;
      width: 500px;
    }

    .btn {
      padding: 10px 24px;
      border: none;
      color: #fff;
      font-weight: 700;
      border-radius: 8px;
      cursor: pointer;
      min-width: 120px;
    }

    .btn.presente {
      background-color: #28a745;
    }

    .btn.ausente {
      background-color: #dc3545;
    }

    footer {
      text-align: center;
      margin-top: 40px;
      font-size: 0.9rem;
      color: #666;
    }

    .mensagem-inativo {
      margin-top: 20px;
      background: #ffe8e8;
      color: #c00;
      padding: 20px;
      border-radius: 10px;
      font-weight: bold;
    }
  </style>
</head>
<body>

  <div class="logo-central">
    <img src="trf.png" alt="Logo Central" />
  </div>

  <h2>ISA1 - CONTROLE DE PRESENÇA</h2>

  <?php if (!$sistemaAtivo): ?>
    <div class="mensagem-inativo">
      Sistema inativo. Horário de funcionamento: 10:00 às 19:00
    </div>
  <?php else: ?>
    <div id="lista">
      <?php foreach ($usuarios as $u): ?>
        <div class="usuario" data-id="<?= $u['id'] ?>">
          <span>
            <?= htmlspecialchars($u['nome']) ?>
            <input type="text" id="texto-<?= $u['id'] ?>" placeholder="Observação..." value="" />
          </span>
          <button class="btn <?= $u['status'] ?>" onclick="togglePresenca(<?= $u['id'] ?>, this)">
            <?= strtoupper($u['status']) ?>
            <?php if ($u['status'] === 'ausente' && $u['tempo_ausente']): ?>
              (<?= $u['tempo_ausente'] ?>)
            <?php endif; ?>
          </button>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <footer>
    Sistema produzido por <strong>Juliano P. dos Santos</strong> — Programador Backend e Frontend.
  </footer>

  <?php if ($sistemaAtivo): ?>
  <script>
    function togglePresenca(id, btn) {
      const observacao = document.getElementById('texto-' + id).value.trim();
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
        body: `id=${id}&status=${statusAtual}&observacao=${encodeURIComponent(observacao)}&tipo=`
      })
      .then(res => res.json())
      .then(data => {
        btn.classList.remove('presente', 'ausente');
        btn.classList.add(data.status);

        if (data.status === 'ausente') {
          btn.textContent = 'AUSENTE (00 h 00 min)';
          let segundos = 0;
          const intervalId = setInterval(() => {
            segundos++;
            const horas = Math.floor(segundos / 3600);
            const minutos = Math.floor((segundos % 3600) / 60);
            btn.textContent =
              'AUSENTE (' +
              String(horas).padStart(2, '0') +
              ' h ' +
              String(minutos).padStart(2, '0') +
              ' min)';
          }, 60000);
          btn.dataset.intervalId = intervalId;
        } else {
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
  <?php endif; ?>
  <script>
  setInterval(() => {
    const agora = new Date();
    const hora = agora.getHours();
    const minutos = agora.getMinutes();

    if ((hora === 10 && minutos === 0) || (hora === 19 && minutos === 0)) {
      location.reload(); // recarrega página quando der 10:00 ou 19:00
    }
  }, 60000); // verifica a cada minuto
</script>
</body>
</html>
