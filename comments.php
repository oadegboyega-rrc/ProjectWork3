<!-- filepath: c:\xampp\htdocs\ProjectWork3\comments.php -->
<?php
// Ensure the recipe_id is set
if (!isset($recipe_id)) {
    die("Recipe ID is required to display comments.");
}

// Handle comment submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['comment'])) {
    $comment = trim($_POST['comment']);
    $rating = isset($_POST['rating']) && is_numeric($_POST['rating']) ? (int)$_POST['rating'] : null;
    $user_name = isset($_POST['name']) && !empty(trim($_POST['name'])) ? trim($_POST['name']) : 'Anonymous'; // Default to "Anonymous"

    if (!empty($comment)) {
        try {
            $stmt = $db->prepare("INSERT INTO comments (recipe_id, user_name, comment, rating, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$recipe_id, $user_name, $comment, $rating]);
            echo "<p class='success-message'>Comment submitted successfully.</p>";
        } catch (PDOException $e) {
            echo "<p class='error-message'>Error saving comment: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    } else {
        echo "<p class='error-message'>Comment cannot be empty.</p>";
    }
}

// Fetch and display comments
try {
    $stmt = $db->prepare("SELECT user_name, comment, rating, created_at FROM comments WHERE recipe_id = ? ORDER BY created_at DESC");
    $stmt->execute([$recipe_id]);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "<p class='error-message'>Error fetching comments: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

<div class="comments-section">
    <h2>Comments</h2>

    <!-- Comment Form -->
    <div class="comment-form">
        <h3>Leave a Comment</h3>
        <form method="POST">
            <?php if (!isset($_SESSION['user_id'])): ?>
                <!-- Name input for non-logged-in users -->
                <div class="form-group">
                    <label for="name">Your Name (optional):</label>
                    <input type="text" name="name" id="name" class="form-control">
                </div>
            <?php endif; ?>
            <div class="form-group">
                <label for="content">Your Comment:</label>
                <textarea name="comment" id="content" class="form-control" rows="4" required></textarea>
            </div>
            <div class="form-group">
                <label for="rating">Your Rating (optional):</label>
                <select name="rating" id="rating" class="form-control">
                    <option value="">No Rating</option>
                    <option value="1">1 - Poor</option>
                    <option value="2">2 - Fair</option>
                    <option value="3">3 - Good</option>
                    <option value="4">4 - Very Good</option>
                    <option value="5">5 - Excellent</option>
                </select>
            </div>
            <input type="hidden" name="recipe_id" value="<?php echo htmlspecialchars($recipe_id); ?>">
            <button type="submit" class="btn btn-primary">Submit Comment</button>
        </form>
    </div>

    <!-- Comments Display -->
    <div id="commentsContainer">
        <?php if (empty($comments)): ?>
            <p>No comments yet. Be the first to comment!</p>
        <?php else: ?>
            <?php foreach ($comments as $comment): ?>
                <div class="comment">
                    <div class="comment-header">
                        <span class="comment-author"><?= htmlspecialchars($comment['user_name'] ?? 'Anonymous') ?></span>
                        <span class="comment-date"><?= date("F j, Y, g:i a", strtotime($comment['created_at'])) ?></span>
                        <?php if (!empty($comment['rating'])): ?>
                            <span class="comment-rating">Rating: <?= $comment['rating'] ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="comment-content">
                        <?= nl2br(htmlspecialchars($comment['comment'])) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<style>
    body {
        font-family: Arial, sans-serif;
        max-width: 800px;
        margin: 0 auto;
        padding: 20px;
    }
    .success-message {
        color: green;
        font-weight: bold;
        margin-bottom: 10px;
    }
    .error-message {
        color: red;
        font-weight: bold;
        margin-bottom: 10px;
    }
    .comments-section {
        margin-top: 30px;
    }
    .comment-form {
        background-color: #f5f5f5;
        padding: 20px;
        border-radius: 5px;
        margin-bottom: 30px;
    }
    .comment-form textarea {
        width: 100%;
        min-height: 100px;
        margin-bottom: 10px;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }
    .comment-form input {
        padding: 8px;
        margin-bottom: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        width: 100%;
    }
    .comment-form button {
        background-color: #4CAF50;
        color: white;
        padding: 10px 15px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }
    .comment-form button:hover {
        background-color: #45a049;
    }
    .comment {
        border-bottom: 1px solid #eee;
        padding: 15px 0;
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
    }
</style>