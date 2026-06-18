<?php
class Game {
    private string $secretWord;
    private int    $maxAttempts = 6;
    private array  $attempts    = [];
    private bool   $won         = false;

    // Charge un mot aléatoire depuis mots.json et démarre la partie
    public function __construct() {
        session_start();

        // Nouvelle partie si aucune session active ou si reset demandé
        if (!isset($_SESSION['secret_word']) || isset($_GET['reset'])) {
            $this->secretWord = $this->pickRandomWord();
            $_SESSION['secret_word'] = $this->secretWord;
            $_SESSION['attempts']    = [];
            $_SESSION['won']         = false;
        }

        $this->secretWord = $_SESSION['secret_word'];
        $this->attempts   = $_SESSION['attempts'];
        $this->won        = $_SESSION['won'];
    }

    // Choisit un mot aléatoire dans mots.json
    private function pickRandomWord(): string {
        $json  = file_get_contents(__DIR__ . '/mots.json');
        $words = json_decode($json, true);
        $index = array_rand($words);
        return strtolower($words[$index]['mot']);
    }

    // Traite une tentative soumise par le joueur
    public function handleGuess(string $guess): array {
        $guess  = strtolower(trim($guess));
        $result = ['status' => '', 'message' => '', 'colors' => []];

        // Validations
        if ($this->isGameOver()) {
            $result['status']  = 'error';
            $result['message'] = 'La partie est déjà terminée.';
            return $result;
        }

        if (strlen($guess) !== strlen($this->secretWord)) {
            $result['status']  = 'error';
            $result['message'] = 'Le mot doit contenir ' . strlen($this->secretWord) . ' lettres.';
            return $result;
        }

        if (!ctype_alpha($guess)) {
            $result['status']  = 'error';
            $result['message'] = 'Uniquement des lettres, sans accents.';
            return $result;
        }

        // Calcul des couleurs lettre par lettre
        $colors = $this->checkGuess($guess);

        // Enregistre la tentative
        $this->attempts[] = ['word' => $guess, 'colors' => $colors];
        $_SESSION['attempts'] = $this->attempts;

        // Victoire ?
        if ($guess === $this->secretWord) {
            $this->won         = true;
            $_SESSION['won']   = true;
            $score             = $this->maxAttempts - count($this->attempts) + 1;
            $this->saveScore($this->secretWord, $score);
            $result['status']  = 'win';
            $result['message'] = 'Bravo ! Tu as trouvé le mot en ' . count($this->attempts) . ' tentative(s) !';
        } elseif (count($this->attempts) >= $this->maxAttempts) {
            $result['status']  = 'lose';
            $result['message'] = 'Perdu ! Le mot était : ' . strtoupper($this->secretWord);
        } else {
            $result['status']  = 'continue';
            $result['message'] = (($this->maxAttempts - count($this->attempts)) . ' tentative(s) restante(s)');
        }

        $result['colors'] = $colors;
        return $result;
    }

    // Algorithme de vérification lettre par lettre
    private function checkGuess(string $guess): array {
        $secret = str_split($this->secretWord);
        $guessArr = str_split($guess);
        $colors   = array_fill(0, strlen($this->secretWord), 'wrong');
        $remaining = $secret;

        // 1er passage : lettres correctes et bien placées
        foreach ($guessArr as $i => $letter) {
            if ($letter === $secret[$i]) {
                $colors[$i] = 'correct';
                $remaining[$i] = null; // marquée comme utilisée
            }
        }

        // 2ème passage : lettres présentes mais mal placées
        foreach ($guessArr as $i => $letter) {
            if ($colors[$i] === 'correct') continue;
            $pos = array_search($letter, $remaining);
            if ($pos !== false) {
                $colors[$i]      = 'misplaced';
                $remaining[$pos] = null;
            }
        }

        return $colors;
    }

    // Sauvegarde le score en BDD
    private function saveScore(string $word, int $score): void {
        try {
            $pdo  = Database::getInstance();
            $stmt = $pdo->prepare("INSERT INTO word (word, score, played_at) VALUES (:word, :score, NOW())");
            $stmt->execute([':word' => $word, ':score' => $score]);
        } catch (PDOException $e) {
            // Silencieux : BDD optionnelle
        }
    }

    // Getters
    public function getSecretWord(): string  { return $this->secretWord; }
    public function getAttempts(): array     { return $this->attempts; }
    public function getMaxAttempts(): int    { return $this->maxAttempts; }
    public function getWordLength(): int     { return strlen($this->secretWord); }
    public function isWon(): bool            { return $this->won; }

    public function isGameOver(): bool {
        return $this->won || count($this->attempts) >= $this->maxAttempts;
    }

    public function getRemainingAttempts(): int {
        return $this->maxAttempts - count($this->attempts);
    }

    // Première lettre révélée par défaut
    public function getFirstLetter(): string {
        return strtoupper($this->secretWord[0]);
    }
}
