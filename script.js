/**
 * MOTUS — script.js
 * Gère : saisie clavier physique + virtuel, envoi AJAX, animations tuiles, mise à jour clavier
 */

document.addEventListener('DOMContentLoaded', () => {

  if (GAME_OVER) return; // Partie déjà terminée, rien à faire

  const input      = document.getElementById('guess-input');
  const btnSubmit  = document.getElementById('btn-submit');
  const hintEl     = document.getElementById('input-hint');
  const grid       = document.getElementById('game-grid');
  const dotsEl     = document.querySelectorAll('.dot');
  const attemptsEl = document.getElementById('attempts-count');

  let currentRow = CURRENT_ROW;

  // ── Mise en évidence de la ligne active ──────────────────
  function highlightCurrentRow() {
    const rows = grid.querySelectorAll('.grid-row');
    rows.forEach((r, i) => r.classList.toggle('active-row', i === currentRow));

    const firstTile = document.getElementById(`tile-${currentRow}-0`);
    if (firstTile && firstTile.textContent.trim() !== '') {
      // La première lettre est déjà là
    }
  }
  highlightCurrentRow();

  // ── Anime la saisie dans les tuiles en temps réel ────────
  input.addEventListener('input', () => {
    let val = input.value.replace(/[^a-zA-Z]/g, '').toUpperCase();
    input.value = val;
    updateCurrentRowTiles(val);
  });

  function updateCurrentRowTiles(val) {
    for (let col = 0; col < WORD_LENGTH; col++) {
      const tile = document.getElementById(`tile-${currentRow}-${col}`);
      if (!tile) continue;
      if (col === 0) {
        tile.textContent = FIRST_LETTER; // toujours visible
        tile.className   = 'tile first-letter' + (val.length > 0 ? ' current' : '');
        continue;
      }
      if (col < val.length) {
        tile.textContent = val[col] || '';
        tile.className   = 'tile current';
      } else {
        tile.textContent = '';
        tile.className   = 'tile';
      }
    }
  }

  // ── Soumission ───────────────────────────────────────────
  async function submitGuess() {
    const guess = input.value.trim();

    if (guess.length !== WORD_LENGTH) {
      showHint(`Le mot doit contenir ${WORD_LENGTH} lettres.`, 'error');
      shakeRow(currentRow);
      return;
    }

    setLoading(true);

    try {
      const formData = new FormData();
      formData.append('guess', guess);

      const resp = await fetch('motus.php', {
        method : 'POST',
        body   : formData,
      });
      const data = await resp.json();

      // Applique les couleurs aux tuiles
      applyColors(currentRow, guess, data.colors);

      // Met à jour le clavier virtuel
      updateKeyboard(guess, data.colors);

      // Mise à jour compteur
      currentRow++;
      updateDots(data.remainingAttempts);

      input.value = '';
      clearCurrentRowTiles();

      if (data.status === 'win' || data.status === 'lose') {
        showHint(data.message, data.status === 'win' ? 'success' : 'error');
        if (data.status === 'win') celebrateAnimation(currentRow - 1);
        setTimeout(() => {
          attemptsEl.innerHTML = data.status === 'win'
            ? '🎉 Gagné !'
            : `💀 Perdu — le mot était : <strong>${guess.toUpperCase()}</strong>`;
          // Affiche les boutons de fin
          showEndButtons(data.status === 'win');
        }, 800);
      } else {
        showHint(data.message, '');
        highlightCurrentRow();
        // Pré-rempli la 1ère lettre de la nouvelle ligne
        const nextFirst = document.getElementById(`tile-${currentRow}-0`);
        if (nextFirst) {
          nextFirst.textContent = FIRST_LETTER;
          nextFirst.className   = 'tile first-letter';
        }
        attemptsEl.textContent = data.message;
        input.focus();
      }

    } catch (err) {
      showHint('Erreur réseau. Réessaie.', 'error');
    }

    setLoading(false);
  }

  btnSubmit.addEventListener('click', submitGuess);
  input.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') submitGuess();
  });

  // ── Clavier virtuel ──────────────────────────────────────
  document.querySelectorAll('.key').forEach(btn => {
    btn.addEventListener('click', () => {
      const key = btn.dataset.key;
      if (key === 'Enter') {
        submitGuess();
      } else if (key === 'Backspace') {
        if (input.value.length > 0) {
          input.value = input.value.slice(0, -1);
          updateCurrentRowTiles(input.value.toUpperCase());
        }
      } else {
        if (input.value.length < WORD_LENGTH) {
          input.value += key.toUpperCase();
          updateCurrentRowTiles(input.value.toUpperCase());
        }
      }
    });
  });

  // ── Helpers ──────────────────────────────────────────────

  function applyColors(row, word, colors) {
    for (let col = 0; col < WORD_LENGTH; col++) {
      const tile = document.getElementById(`tile-${row}-${col}`);
      if (!tile) continue;
      setTimeout(() => {
        tile.textContent = word[col].toUpperCase();
        tile.className   = `tile ${colors[col]}`;
      }, col * 80); // effet cascade
    }
  }

  function updateKeyboard(word, colors) {
    const priority = { correct: 3, misplaced: 2, wrong: 1 };
    for (let i = 0; i < word.length; i++) {
      const letter = word[i].toLowerCase();
      const btn    = document.querySelector(`.key[data-key="${letter}"]`);
      if (!btn) continue;
      const current  = priority[btn.dataset.state] || 0;
      const incoming = priority[colors[i]] || 0;
      if (incoming > current) {
        btn.dataset.state = colors[i];
        btn.classList.remove('correct', 'misplaced', 'wrong');
        btn.classList.add(colors[i]);
      }
    }
  }

  function clearCurrentRowTiles() {
    for (let col = 1; col < WORD_LENGTH; col++) {
      const tile = document.getElementById(`tile-${currentRow}-${col}`);
      if (tile) { tile.textContent = ''; tile.className = 'tile'; }
    }
  }

  function updateDots(remaining) {
    dotsEl.forEach((dot, i) => {
      dot.classList.toggle('active', i < remaining);
      dot.classList.toggle('used',   i >= remaining);
    });
  }

  function shakeRow(row) {
    for (let col = 0; col < WORD_LENGTH; col++) {
      const tile = document.getElementById(`tile-${row}-${col}`);
      if (tile) {
        tile.classList.add('shake');
        tile.addEventListener('animationend', () => tile.classList.remove('shake'), { once: true });
      }
    }
  }

  function celebrateAnimation(row) {
    for (let col = 0; col < WORD_LENGTH; col++) {
      setTimeout(() => {
        const tile = document.getElementById(`tile-${row}-${col}`);
        if (tile) {
          tile.style.transition = 'transform .2s';
          tile.style.transform  = 'scale(1.2)';
          setTimeout(() => { tile.style.transform = 'scale(1)'; }, 200);
        }
      }, col * 60);
    }
  }

  function showHint(msg, type) {
    hintEl.textContent  = msg;
    hintEl.className    = `input-hint ${type}`;
    setTimeout(() => {
      if (hintEl.textContent === msg) hintEl.textContent = '';
    }, 3000);
  }

  function setLoading(loading) {
    btnSubmit.disabled   = loading;
    btnSubmit.textContent = loading ? '...' : 'Valider ↵';
    if (input) input.disabled = loading;
  }

  function showEndButtons(won) {
    const zone = document.getElementById('input-zone');
    if (!zone) return;
    zone.innerHTML = `
      <div class="end-actions">
        <a href="motus.php?reset=1" class="btn-play-again">🔄 Rejouer</a>
        <a href="score.php"         class="btn-scores">🏆 Wall of Fame</a>
      </div>`;
  }

});
