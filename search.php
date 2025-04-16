<!-- filepath: c:\xampp\htdocs\ProjectWork3\search.php -->
<?php
session_start();
require 'connect.php';

// Get search query and category filter
$query = isset($_GET['query']) ? trim($_GET['query']) : '';
$category_id = isset($_GET['category_id']) && is_numeric($_GET['category_id']) ? (int)$_GET['category_id'] : null;

// Pagination logic
$results_per_page = isset($_GET['n']) && is_numeric($_GET['n']) ? (int)$_GET['n'] : 10; // Default to 10 results per page
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $results_per_page;

if (empty($query)) {
    die("Please enter a search term.");
}

try {
    // Build the SQL query with optional category filtering
    $sql = "
        SELECT 'page' AS type, id, title AS name, description 
        FROM pages 
        WHERE (title LIKE :query OR description LIKE :query)
    ";
    if ($category_id) {
        $sql .= " AND category_id = :category_id";
    }
    $sql .= " UNION
        SELECT 'recipe' AS type, r.id, r.title AS name, r.description 
        FROM recipes r
        WHERE (r.title LIKE :query OR r.description LIKE :query)
    ";
    if ($category_id) {
        $sql .= " AND r.category_id = :category_id";
    }
    $sql .= " UNION
        SELECT 'category' AS type, c.id, c.name AS name, NULL AS description
        FROM categories c
        WHERE c.name LIKE :query
    ";
    $sql .= " LIMIT :limit OFFSET :offset";

    $stmt = $db->prepare($sql);
    $stmt->bindValue(':query', '%' . $query . '%', PDO::PARAM_STR);
    if ($category_id) {
        $stmt->bindValue(':category_id', $category_id, PDO::PARAM_INT);
    }
    $stmt->bindValue(':limit', $results_per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get total results count for pagination
    $count_sql = "
        SELECT COUNT(*) 
        FROM (
            SELECT id FROM pages WHERE (title LIKE :query OR description LIKE :query)
    ";
    if ($category_id) {
        $count_sql .= " AND category_id = :category_id";
    }
    $count_sql .= " UNION
            SELECT id FROM recipes WHERE (title LIKE :query OR description LIKE :query)
    ";
    if ($category_id) {
        $count_sql .= " AND category_id = :category_id";
    }
    $count_sql .= " UNION
            SELECT id FROM categories WHERE name LIKE :query
        ) AS total_results";
    $count_stmt = $db->prepare($count_sql);
    $count_stmt->bindValue(':query', '%' . $query . '%', PDO::PARAM_STR);
    if ($category_id) {
        $count_stmt->bindValue(':category_id', $category_id, PDO::PARAM_INT);
    }
    $count_stmt->execute();
    $total_results = $count_stmt->fetchColumn();
    $total_pages = ceil($total_results / $results_per_page);
} catch (PDOException $e) {
    die("Error fetching search results: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results</title>
    <link rel="stylesheet" href="Style/searchstyle.css">
</head>
<body>
<header>
    <h1>Search Results</h1>
</header>

<div class="container">
    <?php if (empty($results)): ?>
        <p>No results found for "<?= htmlspecialchars($query); ?>".</p>
    <?php else: ?>
        <p>Results for "<?= htmlspecialchars($query); ?>":</p>
        <ul>
            <?php foreach ($results as $result): ?>
                <li>
                    <?php if ($result['type'] === 'page'): ?>
                        <a href="page.php?id=<?= $result['id']; ?>">
                            <?= htmlspecialchars($result['name']); ?> (Page)
                        </a>
                    <?php elseif ($result['type'] === 'recipe'): ?>
                        <a href="recipe.php?id=<?= $result['id']; ?>">
                            <?= htmlspecialchars($result['name']); ?> (Recipe)
                        </a>
                    <?php elseif ($result['type'] === 'category'): ?>
                        <a href="view_category.php?id=<?= $result['id']; ?>">
                            <?= htmlspecialchars($result['name']); ?> (Category)
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($result['description'])): ?>
                        <p><?= htmlspecialchars(substr($result['description'], 0, 100)) . '...'; ?></p>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>

        <!-- Pagination Links -->
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?query=<?= urlencode($query); ?>&category_id=<?= $category_id; ?>&page=<?= $page - 1; ?>&n=<?= $results_per_page; ?>">Previous</a>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?query=<?= urlencode($query); ?>&category_id=<?= $category_id; ?>&page=<?= $i; ?>&n=<?= $results_per_page; ?>" class="<?= $i === $page ? 'active' : ''; ?>">
                    <?= $i; ?>
                </a>
            <?php endfor; ?>
            <?php if ($page < $total_pages): ?>
                <a href="?query=<?= urlencode($query); ?>&category_id=<?= $category_id; ?>&page=<?= $page + 1; ?>&n=<?= $results_per_page; ?>">Next</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<footer>
    <p>&copy; 2025 Winnipeg Recipe Hub</p>
</footer>
</body>
</html>