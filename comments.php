<?php
// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure the recipe_id is set
if (!isset($recipe_id)) {
    die("Recipe ID is required to display comments.");
}

// Handle comment submission with CAPTCHA validation
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['comment'])) {
    $comment = trim($_POST['comment']);
    $captcha_input = trim($_POST['captcha_input']);

    // Validate CAPTCHA
    if (!isset($_SESSION['captcha']) || $captcha_input !== $_SESSION['captcha']) {
        echo "<p class='error-message'>Invalid CAPTCHA. Please try again.</p>";
    } else {
        unset($_SESSION['captcha']); // Clear the CAPTCHA after successful validation

        if (!empty($comment)) {
            try {
                $stmt = $db->prepare("INSERT INTO comments (recipe_id, user_name, comment, created_at) VALUES (?, ?, ?, NOW())");
                $stmt->execute([$recipe_id, $_SESSION['username'] ?? 'Anonymous', $comment]);
                echo "<p class='success-message'>Comment submitted successfully.</p>";
            } catch (PDOException $e) {
                echo "<p class='error-message'>Error saving comment: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        } else {
            echo "<p class='error-message'>Comment cannot be empty.</p>";
        }
    }
}

// Handle comment moderation (admin-only)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    $comment_id = (int)$_POST['comment_id'];
    $action = $_POST['action'];

    try {
        if ($action === 'delete') {
            // Delete the comment
            $stmt = $db->prepare("DELETE FROM comments WHERE id = ?");
            $stmt->execute([$comment_id]);
        } elseif ($action === 'hide') {
            // Hide the comment
            $stmt = $db->prepare("UPDATE comments SET hidden = 1 WHERE id = ?");
            $stmt->execute([$comment_id]);
        } elseif ($action === 'disemvowel') {
            // Disemvowel the comment
            $stmt = $db->prepare("SELECT comment FROM comments WHERE id = ?");
            $stmt->execute([$comment_id]);
            $comment = $stmt->fetchColumn();
            if ($comment) {
                $disemvoweled = preg_replace('/[aeiouAEIOU]/', '', $comment);
                $stmt = $db->prepare("UPDATE comments SET comment = ? WHERE id = ?");
                $stmt->execute([$disemvoweled, $comment_id]);
            }
        }
    } catch (PDOException $e) {
        echo "<p class='error-message'>Error moderating comment: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

// Fetch and display comments
try {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        // Admins can see all comments
        $stmt = $db->prepare("SELECT id, user_name, comment, rating, created_at, hidden FROM comments WHERE recipe_id = ? ORDER BY created_at DESC");
    } else {
        // Non-admins see only non-hidden comments
        $stmt = $db->prepare("SELECT id, user_name, comment, rating, created_at FROM comments WHERE recipe_id = ? AND hidden = 0 ORDER BY created_at DESC");
    }
    $stmt->execute([$recipe_id]);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "<p class='error-message'>Error fetching comments: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

<div class="comments-section">
    <h2>Comments</h2>
    <!-- 
    <div class="comment-form">
    <h3>Leave a Comment</h3> -->
    <form method="POST">
        <div class="form-group">
            <label for="comment">Your Comment:</label>
            <textarea name="comment" id="comment" class="form-control" rows="4" required></textarea>
        </div>
        <div class="form-group">
            <label for="captcha_input">Enter the CAPTCHA:</label>
            <img src="captcha.php" alt="CAPTCHA Image" id="captcha-image">
            <input type="text" name="captcha_input" id="captcha_input" class="form-control" required>
            <button type="button" onclick="refreshCaptcha()">Refresh CAPTCHA</button>
        </div>
        <button type="submit" class="btn btn-primary">Submit Comment</button>
    </form>


    <!-- Comments Display -->
    <div id="commentsContainer" aria-live="polite">
        <?php if (empty($comments)): ?>
            <p>No comments yet. Be the first to comment!</p>
        <?php else: ?>
            <?php foreach ($comments as $comment): ?>
                <div class="comment <?= isset($comment['hidden']) && $comment['hidden'] ? 'hidden-comment' : ''; ?>">
                    <div class="comment-header">
                        <span class="comment-author"><?= htmlspecialchars($comment['user_name'] ?? 'Anonymous') ?></span>
                        <span class="comment-date"><?= date("F j, Y, g:i a", strtotime($comment['created_at'])) ?></span>
                        <?php if (!empty($comment['rating'])): ?>
                            <span class="comment-rating">Rating: <?= $comment['rating'] ?></span>
                        <?php endif; ?>
                        <?php if (isset($comment['hidden']) && $comment['hidden']): ?>
                            <span class="comment-hidden">(Hidden)</span>
                        <?php endif; ?>
                    </div>
                    <div class="comment-content">
                        <?= nl2br(htmlspecialchars($comment['comment'])) ?>
                    </div>

                    <!-- Moderation Buttons (Admin Only) -->
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <form method="POST" class="moderation-form">
                            <input type="hidden" name="comment_id" value="<?= $comment['id'] ?>">
                            <button type="submit" name="action" value="delete" class="btn btn-danger">Delete</button>
                            <button type="submit" name="action" value="hide" class="btn btn-warning">Hide</button>
                            <button type="submit" name="action" value="disemvowel" class="btn btn-secondary">Disemvowel</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
    function refreshCaptcha() {
        document.getElementById('captcha-image').src = 'captcha.php?' + Date.now();
    }
</script>

<style>
    /* General Styles */
    .comments-section {
        margin-top: 30px;
        padding: 20px;
        background-color: #f9f9f9;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .comments-section h2 {
        margin-bottom: 20px;
        font-size: 24px;
        color: #333;
    }

    .comment {
        padding: 15px;
        margin-bottom: 15px;
        border: 1px solid #ddd;
        border-radius: 8px;
        background-color: #fff;
    }

    .comment.hidden-comment {
        background-color: #f8d7da;
        border-color: #f5c6cb;
    }

    .comment-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
        font-size: 0.9em;
        color: #666;
    }

    .comment-author {
        font-weight: bold;
        color: #007BFF;
    }

    .comment-rating {
        background-color: #4CAF50;
        color: white;
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 0.8em;
    }

    .comment-content {
        font-size: 1em;
        color: #333;
        line-height: 1.5;
    }

    .comment-hidden {
        font-style: italic;
        color: #999;
    }

    /* Moderation Buttons */
    .moderation-form {
        margin-top: 10px;
    }

    .moderation-form button {
        margin-right: 5px;
        padding: 5px 10px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }

    .btn-danger {
        background-color: #dc3545;
        color: white;
    }

    .btn-warning {
        background-color: #ffc107;
        color: black;
    }

    .btn-secondary {
        background-color: #6c757d;
        color: white;
    }

    .btn-danger:hover {
        background-color: #c82333;
    }

    .btn-warning:hover {
        background-color: #e0a800;
    }

    .btn-secondary:hover {
        background-color: #5a6268;
    }
</style>