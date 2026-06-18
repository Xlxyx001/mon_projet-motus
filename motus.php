<?php
require_once 'Database.php';
require_once 'Game.php';

$game = new Game();

// Traitement AJAX de la tentative
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guess'])) {
    header('Content-Type: application/json');
    $result = $game->handleGuess($_POST['guess']);
    $result['attempts']          = $game->getAttempts();
    $result['remainingAttempts'] = $game->getRemainingAttempts();
    echo json_encode($result);
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>MOTUS — Jeu de mots</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Black+Han+Sans&family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css"/>
</head>
<body>

  <header class="header">
    <div class="header-inner">
      <div class="logo">M<span>OTUS</span></div>
      <nav>
        <a href="motus.php" class="nav-link active">🎮 Jouer</a>
        <a href="score.php" class="nav-link">🏆 Wall of Fame</a>
      </nav>
    </div>
  </header>

  <main class="main">

    <div class="game-header">
      <h1 class="game-title">Mo mo mo <em>MOTUS !</em></h1>
      <p class="game-subtitle">
        Trouve le mot de <strong><?= $game->getWordLength() ?> lettres</strong> en
        <strong><?= $game->getMaxAttempts() ?> tentatives</strong>
      </p>
    </div>

    <!-- Barre de tentatives -->
    <div class="attempts-bar">
      <span id="attempts-count">
        <?php if (!$game->isGameOver()): ?>
          <?= $game->getRemainingAttempts() ?> tentative(s) restante(s)
        <?php elseif ($game->isWon()): ?>
          🎉 Gagné !
        <?php else: ?>
          💀 Perdu — le mot était : <strong><?= strtoupper($game->getSecretWord()) ?></strong>
        <?php endif; ?>
      </span>
      <div class="dots">
        <?php for ($i = 0; $i < $game->getMaxAttempts(); $i++): ?>
          <span class="dot <?= $i < count($game->getAttempts()) ? 'used' : 'active' ?>"></span>
        <?php endfor; ?>
      </div>
    </div>

    <!-- Grille de jeu -->
    <div class="grid-wrapper">
      <div class="grid" id="game-grid">

        <?php
        $wordLen  = $game->getWordLength();
        $maxAttempts = $game->getMaxAttempts();
        $attempts = $game->getAttempts();

        for ($row = 0; $row < $maxAttempts; $row++):
        ?>
          <div class="grid-row" id="row-<?= $row ?>">
            <?php for ($col = 0; $col < $wordLen; $col++):
              $letter = '';
              $colorClass = '';

              if (isset($attempts[$row])) {
                  $letter     = strtoupper($attempts[$row]['word'][$col]);
                  $colorClass = $attempts[$row]['colors'][$col]; // correct / misplaced / wrong
              } elseif ($row === 0 && $col === 0 && !isset($attempts[0])) {
                  // 1ère lettre révélée sur la ligne vierge uniquement si aucune tentative
                  $letter     = $game->getFirstLetter();
                  $colorClass = 'first-letter';
              } elseif ($row === count($attempts) && $col === 0 && !$game->isGameOver()) {
                  // Ligne courante : révèle la première lettre
                  $letter     = $game->getFirstLetter();
                  $colorClass = 'first-letter';
              }
            ?>
              <div class="tile <?= $colorClass ?>" id="tile-<?= $row ?>-<?= $col ?>">
                <?= $letter ?>
              </div>
            <?php endfor; ?>
          </div>
        <?php endfor; ?>

      </div>
    </div>

    <!-- Zone de saisie -->
    <?php if (!$game->isGameOver()): ?>
    <div class="input-zone" id="input-zone">
      <div class="input-group">
        <input
          type="text"
          id="guess-input"
          class="guess-input"
          placeholder="<?= $game->getFirstLetter() ?>..."
          maxlength="<?= $game->getWordLength() ?>"
          autocomplete="off"
          autocorrect="off"
          spellcheck="false"
          autofocus
        />
        <button class="btn-submit" id="btn-submit">Valider</button>
      </div>
      <p class="input-hint" id="input-hint"></p>
    </div>
    <?php else: ?>
    <div class="end-actions">
      <a href="motus.php?reset=1" class="btn-play-again">Rejouer</a>
      <a href="score.php" class="btn-scores">🏆 Wall of Fame</a>
    </div>
    <?php endif; ?>

    <!-- Clavier virtuel -->
    <div class="keyboard" id="keyboard">
      <?php
      $rows = [
          ['a','z','e','r','t','y','u','i','o','p'],
          ['q','s','d','f','g','h','j','k','l','m'],
          ['Enter','w','x','c','v','b','n','Backspace'],
      ];
      foreach ($rows as $row):
      ?>
        <div class="keyboard-row">
          <?php foreach ($row as $key):
            $label = match($key) {
                'Enter'     => '↵',
                'Backspace' => '⌫',
                default     => strtoupper($key),
            };
            $wide = in_array($key, ['Enter','Backspace']) ? ' key-wide' : '';
          ?>
            <button class="key<?= $wide ?>" data-key="<?= $key ?>"><?= $label ?></button>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Légende -->
    <div class="legend">
      <div class="legend-item"><span class="tile correct" style="width:36px;height:36px;font-size:.9rem;">A</span><span>Bonne lettre, bonne place</span></div>
      <div class="legend-item"><span class="tile misplaced" style="width:36px;height:36px;font-size:.9rem;">A</span><span>Bonne lettre, mauvaise place</span></div>
      <div class="legend-item"><span class="tile wrong" style="width:36px;height:36px;font-size:.9rem;">A</span><span>Lettre absente</span></div>
    </div>

  </main>

  <footer class="footer">
    <p>MOTUS · &nbsp;|&nbsp; <a href="score.php">Wall of Fame</a></p>
  </footer>

  <!-- Données PHP → JS -->
  <script>
    const WORD_LENGTH        = <?= $game->getWordLength() ?>;
    const MAX_ATTEMPTS       = <?= $game->getMaxAttempts() ?>;
    const CURRENT_ROW        = <?= count($game->getAttempts()) ?>;
    const FIRST_LETTER       = "<?= $game->getFirstLetter() ?>";
    const GAME_OVER          = <?= $game->isGameOver() ? 'true' : 'false' ?>;
  </script>
  <script src="script.js"></script>
</body>
</html>
