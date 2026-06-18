<?php
require_once 'Database.php';

$scores = [];
$dbError = false;

try {
    $pdo    = Database::getInstance();
    $stmt   = $pdo->query("SELECT word, score, played_at FROM word ORDER BY score DESC, played_at DESC LIMIT 20");
    $scores = $stmt->fetchAll();
} catch (PDOException $e) {
    $dbError = true;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Wall of Fame — MOTUS</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Black+Han+Sans&family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css"/>
</head>
<body>

  <header class="header">
    <div class="header-inner">
      <div class="logo">M<span>OTUS</span></div>
      <nav>
        <a href="motus.php" class="nav-link">🎮 Jouer</a>
        <a href="score.php" class="nav-link active">🏆Wall of Fame</a>
      </nav>
    </div>
  </header>

  <main class="main">

    <div class="game-header">
      <h1 class="game-title"> Wall of <em>Fame</em></h1>
      <p class="game-subtitle">Les meilleures parties enregistrées</p>
    </div>

    <?php if ($dbError): ?>
      <div class="alert alert-error">
        ⚠️ Impossible de se connecter à la base de données.<br>
        <small>Vérifie que MySQL est actif et que la BDD <strong>motus</strong> existe (voir <code>init.sql</code>).</small>
      </div>

    <?php elseif (empty($scores)): ?>
      <div class="empty-state">
        <div class="empty-icon">🎮</div>
        <p>Aucun score enregistré pour l'instant.</p>
        <a href="motus.php" class="btn-play-again">Commencer à jouer !</a>
      </div>

    <?php else: ?>
      <div class="scores-table-wrapper">
        <table class="scores-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Mot trouvé</th>
              <th>Score</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($scores as $i => $row): ?>
              <tr class="<?= $i < 3 ? 'top-'  . ($i+1) : '' ?>">
                <td class="rank">
                  <?php if ($i === 0) echo '1-🥇';
                  elseif ($i === 1)   echo '2-🥈';
                  elseif ($i === 2)   echo '3-🥉';
                  else                echo $i + 1; ?>
                </td>
                <td class="score-word"><?= strtoupper(htmlspecialchars($row['word'])) ?></td>
                <td class="score-pts">
                  <span class="score-badge"><?= $row['score'] ?> pt<?= $row['score'] > 1 ? 's' : '' ?></span>
                </td>
                <td class="score-date">
                  <?= date('d/m/Y H:i', strtotime($row['played_at'])) ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>

    <div class="end-actions" style="margin-top:2rem;">
      <a href="motus.php" class="btn-play-again"> Jouer une partie</a>
      <a href="motus.php?reset=1" class="btn-scores"> Nouvelle partie</a>
    </div>

  </main>

  <footer class="footer">
    <p>MOTUS · &nbsp;|&nbsp; <a href="motus.php">Retour au jeu .</a></p>
  </footer>

</body>
</html>
