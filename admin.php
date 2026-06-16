<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/db.php';

$messages = [];
$errors = [];
$teams = [];
$players = [];

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function redirect_admin(): void
{
    header('Location: admin.php');
    exit;
}

function ensure_upload_dir(): void
{
    if (!is_dir(UPLOAD_DIR) && !mkdir(UPLOAD_DIR, 0775, true) && !is_dir(UPLOAD_DIR)) {
        throw new RuntimeException('Could not create upload directory.');
    }
}

function validate_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
        throw new RuntimeException('Invalid form token. Refresh the page and try again.');
    }
}

function normalize_amount_input(string $value, string $label = 'Amount'): float
{
    $amount = filter_var($value, FILTER_VALIDATE_FLOAT);
    if ($amount === false || $amount < 0 || $amount > 999999999) {
        throw new RuntimeException($label . ' must be between 0 and 999,999,999.');
    }

    return round((float) $amount, 2);
}

function ensure_team_budget_column(PDO $pdo): void
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*)
         FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = :table_name
            AND COLUMN_NAME = :column_name'
    );
    $stmt->execute([
        'table_name' => 'teams',
        'column_name' => 'budget_total',
    ]);

    if ((int) $stmt->fetchColumn() === 0) {
        $pdo->exec("ALTER TABLE teams ADD COLUMN budget_total DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Auction wallet allotted to this team' AFTER name");
    }
}

function team_commitments(PDO $pdo, int $teamId): array
{
    $stmt = $pdo->prepare(
        'SELECT
            COALESCE(SUM(
                CASE
                    WHEN is_sold = 1 THEN GREATEST(COALESCE(sold_amount, 0), COALESCE(current_bid, 0), COALESCE(base_bid, 0))
                    ELSE 0
                END
            ), 0) AS spent_amount,
            COALESCE(SUM(
                CASE
                    WHEN COALESCE(is_sold, 0) = 0 THEN GREATEST(COALESCE(current_bid, 0), COALESCE(base_bid, 0))
                    ELSE 0
                END
            ), 0) AS reserved_amount
         FROM players
         WHERE team_id = :team_id AND is_active = 1'
    );
    $stmt->execute(['team_id' => $teamId]);
    $row = $stmt->fetch() ?: ['spent_amount' => 0, 'reserved_amount' => 0];

    return [
        'spent' => (float) $row['spent_amount'],
        'reserved' => (float) $row['reserved_amount'],
        'committed' => (float) $row['spent_amount'] + (float) $row['reserved_amount'],
    ];
}

function save_player_image(array $file): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Please upload a valid player image.');
    }

    if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
        throw new RuntimeException('Player image must be 5 MB or smaller.');
    }

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Only JPG, PNG, WEBP, or GIF images are allowed.');
    }

    ensure_upload_dir();

    $filename = sprintf(
        '%s-%s.%s',
        date('YmdHis'),
        bin2hex(random_bytes(6)),
        $allowed[$mime]
    );
    $target = UPLOAD_DIR . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file($file['tmp_name'], $target)) {
        throw new RuntimeException('Could not save uploaded player image.');
    }

    return UPLOAD_URL . '/' . $filename;
}

try {
    $pdo = db();
    ensure_team_budget_column($pdo);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        validate_csrf();
        $action = $_POST['action'] ?? '';

        if ($action === 'create_team') {
            $teamName = trim((string) ($_POST['team_name'] ?? ''));
            $teamBudget = normalize_amount_input((string) ($_POST['team_budget'] ?? '0'), 'Team allotment');

            if ($teamName === '') {
                throw new RuntimeException('Team name is required.');
            }

            $stmt = $pdo->prepare('INSERT INTO teams (name, budget_total) VALUES (:name, :budget_total)');
            $stmt->execute([
                'name' => $teamName,
                'budget_total' => $teamBudget,
            ]);
            $_SESSION['flash_success'] = 'Team created successfully.';
            redirect_admin();
        }

        if ($action === 'update_team_budget') {
            $teamId = filter_var($_POST['team_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $teamBudget = normalize_amount_input((string) ($_POST['team_budget'] ?? '0'), 'Team allotment');

            if (!$teamId) {
                throw new RuntimeException('Valid team is required.');
            }

            $commitments = team_commitments($pdo, (int) $teamId);
            if ($teamBudget < $commitments['committed']) {
                throw new RuntimeException(
                    'Team allotment cannot be below current sold and reserved amount of ₹' .
                    number_format($commitments['committed'], 0) .
                    '.'
                );
            }

            $existsStmt = $pdo->prepare('SELECT COUNT(*) FROM teams WHERE id = :id');
            $existsStmt->execute(['id' => (int) $teamId]);
            if ((int) $existsStmt->fetchColumn() !== 1) {
                throw new RuntimeException('Team was not found.');
            }

            $stmt = $pdo->prepare('UPDATE teams SET budget_total = :budget_total WHERE id = :id');
            $stmt->execute([
                'budget_total' => $teamBudget,
                'id' => (int) $teamId,
            ]);

            $_SESSION['flash_success'] = 'Team allotment updated successfully.';
            redirect_admin();
        }

        if ($action === 'add_player') {
            $playerName = trim((string) ($_POST['player_name'] ?? ''));
            $baseBid = normalize_amount_input((string) ($_POST['base_bid'] ?? '0'), 'Player base amount');

            if ($playerName === '') {
                throw new RuntimeException('Player name is required.');
            }

            $imagePath = save_player_image($_FILES['player_image'] ?? []);
            $stmt = $pdo->prepare(
                'INSERT INTO players (name, image_path, base_bid, current_bid)
                 VALUES (:name, :image_path, :base_bid, :current_bid)'
            );
            $stmt->execute([
                'name' => $playerName,
                'image_path' => $imagePath,
                'base_bid' => $baseBid,
                'current_bid' => $baseBid,
            ]);

            $_SESSION['flash_success'] = 'Player added successfully.';
            redirect_admin();
        }

        throw new RuntimeException('Unknown admin action.');
    }

    if (!empty($_SESSION['flash_success'])) {
        $messages[] = $_SESSION['flash_success'];
        unset($_SESSION['flash_success']);
    }

    $teams = $pdo->query(
        'SELECT t.*,
            COALESCE(pc.player_count, 0) AS player_count,
            COALESCE(tl.amount, 0) AS spent_amount,
            COALESCE(tl.sold_count, 0) AS sold_count,
            COALESCE(reserved.reserved_amount, 0) AS reserved_amount
         FROM teams t
         LEFT JOIN team_leaderboard tl ON tl.team_id = t.id
         LEFT JOIN (
            SELECT team_id, COUNT(*) AS player_count
            FROM players
            WHERE team_id IS NOT NULL AND is_active = 1
            GROUP BY team_id
         ) pc ON pc.team_id = t.id
         LEFT JOIN (
            SELECT team_id, COALESCE(SUM(GREATEST(COALESCE(current_bid, 0), COALESCE(base_bid, 0))), 0) AS reserved_amount
            FROM players
            WHERE team_id IS NOT NULL AND is_active = 1 AND COALESCE(is_sold, 0) = 0
            GROUP BY team_id
         ) reserved ON reserved.team_id = t.id
         ORDER BY t.created_at DESC, t.id DESC'
    )->fetchAll();

    $players = $pdo->query(
        'SELECT p.*, t.name AS team_name
         FROM players p
         LEFT JOIN teams t ON t.id = p.team_id
         ORDER BY p.created_at DESC, p.id DESC'
    )->fetchAll();
} catch (Throwable $exception) {
    $errors[] = $exception->getMessage();
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>SMHA FIFA Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
<style>
    * {
        box-sizing: border-box;
    }

    body {
        min-height: 100vh;
        margin: 0;
        background:
            radial-gradient(circle at 10% 10%, rgba(0, 150, 255, 0.16), transparent 32%),
            radial-gradient(circle at 90% 80%, rgba(0, 200, 83, 0.16), transparent 32%),
            #0f141a;
        color: #fff;
        font-family: "Plus Jakarta Sans", sans-serif;
    }

    body::before {
        content: "";
        position: fixed;
        inset: 0;
        pointer-events: none;
        background-image: repeating-linear-gradient(45deg, rgba(255,255,255,0.028) 0 1px, transparent 1px 16px);
        opacity: 0.7;
    }

    a {
        color: inherit;
    }

    .shell {
        position: relative;
        z-index: 1;
        width: min(1180px, calc(100% - 32px));
        margin: 0 auto;
        padding: 34px 0 48px;
    }

    .topbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        margin-bottom: 24px;
    }

    h1,
    h2,
    h3 {
        margin: 0;
        font-family: "Sora", sans-serif;
        text-transform: uppercase;
    }

    h1 {
        font-size: clamp(30px, 5vw, 64px);
        line-height: 0.95;
    }

    .sub {
        margin: 10px 0 0;
        color: rgba(255, 255, 255, 0.55);
        font-family: "Space Grotesk", sans-serif;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.12em;
        text-transform: uppercase;
    }

    .nav {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .nav a,
    button {
        min-height: 42px;
        border: 0;
        border-radius: 999px;
        padding: 0 18px;
        background: linear-gradient(90deg, #00c853, #0096ff);
        color: #06110a;
        font-family: "Space Grotesk", sans-serif;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-decoration: none;
        text-transform: uppercase;
        cursor: pointer;
    }

    .nav a {
        display: inline-flex;
        align-items: center;
    }

    .grid {
        display: grid;
        grid-template-columns: minmax(0, 0.9fr) minmax(0, 1.1fr);
        gap: 18px;
    }

    .card {
        border: 1px solid rgba(255, 255, 255, 0.12);
        border-radius: 24px;
        background: rgba(255, 255, 255, 0.055);
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.38), inset 0 1px 0 rgba(255, 255, 255, 0.12);
        backdrop-filter: blur(22px);
        padding: 22px;
    }

    .stack {
        display: grid;
        gap: 18px;
    }

    form {
        display: grid;
        gap: 14px;
        margin-top: 18px;
    }

    label {
        display: grid;
        gap: 8px;
        color: rgba(255, 255, 255, 0.62);
        font-family: "Space Grotesk", sans-serif;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    input,
    select {
        width: 100%;
        min-height: 48px;
        border: 1px solid rgba(255, 255, 255, 0.13);
        border-bottom-color: rgba(0, 150, 255, 0.75);
        border-radius: 12px;
        background: rgba(0, 0, 0, 0.26);
        color: #fff;
        font: inherit;
        padding: 0 14px;
        outline: none;
    }

    input[type="file"] {
        padding: 12px 14px;
    }

    select option {
        color: #111;
    }

    .message,
    .error {
        margin-bottom: 14px;
        border-radius: 14px;
        padding: 13px 16px;
        font-weight: 700;
    }

    .message {
        background: rgba(0, 200, 83, 0.14);
        color: #cfffcc;
    }

    .error {
        background: rgba(255, 82, 82, 0.14);
        color: #ffd4d4;
    }

    .list {
        display: grid;
        gap: 10px;
        margin-top: 18px;
    }

    .row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        min-height: 58px;
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 16px;
        background: rgba(255, 255, 255, 0.05);
        padding: 10px 14px;
    }

    .row strong {
        display: block;
    }

    .meta {
        color: rgba(255, 255, 255, 0.48);
        font-size: 13px;
    }

    .team-wallet {
        display: grid;
        gap: 8px;
        min-width: min(100%, 260px);
    }

    .wallet-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 6px;
        color: rgba(255, 255, 255, 0.62);
        font-family: "Space Grotesk", sans-serif;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    .wallet-grid strong {
        color: #fff;
        font-size: 12px;
    }

    .budget-form {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 8px;
        margin: 0;
    }

    .budget-form input {
        min-height: 36px;
        border-radius: 999px;
        font-size: 12px;
    }

    .budget-form button {
        min-height: 36px;
        padding: 0 12px;
        font-size: 10px;
    }

    .player {
        display: grid;
        grid-template-columns: 62px minmax(0, 1fr);
        align-items: center;
        gap: 12px;
    }

    .player img {
        width: 62px;
        height: 62px;
        border-radius: 14px;
        object-fit: cover;
    }

    @media (max-width: 850px) {
        .topbar,
        .grid {
            grid-template-columns: 1fr;
            display: grid;
        }
    }
</style>
</head>
<body>
<main class="shell">
    <header class="topbar">
        <div>
            <h1>Auction Admin</h1>
            <p class="sub">Create teams and upload student players</p>
        </div>
        <nav class="nav" aria-label="Admin navigation">
            <a href="index.php">Auction board</a>
            <a href="bidding.php">Bidding velocity</a>
        </nav>
    </header>

    <?php foreach ($messages as $message): ?>
        <div class="message"><?= e($message) ?></div>
    <?php endforeach; ?>

    <?php foreach ($errors as $error): ?>
        <div class="error"><?= e($error) ?></div>
    <?php endforeach; ?>

    <section class="grid">
        <div class="stack">
            <article class="card">
                <h2>Create Team</h2>
                <p class="sub">Register auction teams before assigning players</p>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="action" value="create_team">
                    <label>
                        Team name
                        <input name="team_name" maxlength="120" required placeholder="Example: Falcons FC">
                    </label>
                    <label>
                        Team allotment (₹)
                        <input name="team_budget" type="number" min="0" max="999999999" step="1" required placeholder="Example: 30000000">
                    </label>
                    <button type="submit">Create Team</button>
                </form>
            </article>

            <article class="card">
                <h2>Teams</h2>
                <div class="list">
                    <?php if (!$teams): ?>
                        <div class="row"><span class="meta">No teams created yet.</span></div>
                    <?php endif; ?>
                    <?php foreach ($teams as $team): ?>
                        <?php
                            $budgetTotal = (float) ($team['budget_total'] ?? 0);
                            $spentAmount = (float) ($team['spent_amount'] ?? 0);
                            $reservedAmount = (float) ($team['reserved_amount'] ?? 0);
                            $availableAmount = max(0, $budgetTotal - $spentAmount - $reservedAmount);
                        ?>
                        <div class="row">
                            <div>
                                <strong><?= e($team['name']) ?></strong>
                                <span class="meta">
                                    <?= (int) $team['player_count'] ?> players
                                    &middot; <?= (int) ($team['sold_count'] ?? 0) ?> sold
                                </span>
                            </div>
                            <div class="team-wallet">
                                <div class="wallet-grid">
                                    <span>Budget <strong>₹<?= number_format($budgetTotal, 0) ?></strong></span>
                                    <span>Available <strong>₹<?= number_format($availableAmount, 0) ?></strong></span>
                                    <span>Spent <strong>₹<?= number_format($spentAmount, 0) ?></strong></span>
                                    <span>Reserved <strong>₹<?= number_format($reservedAmount, 0) ?></strong></span>
                                </div>
                                <form method="post" class="budget-form">
                                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                                    <input type="hidden" name="action" value="update_team_budget">
                                    <input type="hidden" name="team_id" value="<?= (int) $team['id'] ?>">
                                    <input name="team_budget" type="number" min="<?= (int) ceil($spentAmount + $reservedAmount) ?>" max="999999999" step="1" value="<?= (int) round($budgetTotal) ?>" aria-label="Team allotment for <?= e($team['name']) ?>">
                                    <button type="submit">Update</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </article>
        </div>

        <div class="stack">
            <article class="card">
                <h2>Add Player</h2>
                <p class="sub">The newest uploaded active player appears on the auction board</p>
                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="action" value="add_player">
                    <label>
                        Player name
                        <input name="player_name" maxlength="140" required placeholder="Example: Aarav Menon">
                    </label>
                    <label>
                        Base amount (₹)
                        <input name="base_bid" type="number" min="0" max="999999999" step="1" required placeholder="Example: 5000">
                    </label>
                    <label>
                        Player image
                        <input type="file" name="player_image" accept="image/jpeg,image/png,image/webp,image/gif" required>
                    </label>
                    <button type="submit">Add Player</button>
                </form>
            </article>

            <article class="card">
                <h2>Players</h2>
                <div class="list">
                    <?php if (!$players): ?>
                        <div class="row"><span class="meta">No players uploaded yet.</span></div>
                    <?php endif; ?>
                    <?php foreach ($players as $player): ?>
                        <div class="row">
                            <div class="player">
                                <img src="<?= e($player['image_path']) ?>" alt="<?= e($player['name']) ?>">
                                <div>
                                    <strong><?= e($player['name']) ?></strong>
                                    <span class="meta">
                                        <?= e($player['team_name'] ?: 'No team') ?>
                                        &middot; Base ₹<?= number_format((float) $player['base_bid'], 0) ?>
                                        &middot; Current ₹<?= number_format((float) $player['current_bid'], 0) ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </article>
        </div>
    </section>
</main>
</body>
</html>
