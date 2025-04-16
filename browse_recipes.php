<?php
require 'authenticate.php';
include 'connect.php';

// Pagination logic
$recipes_per_page = 12; // Maximum of 12 recipes per page
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $recipes_per_page;

// Get search query and category filter
$query = isset($_GET['query']) ? trim($_GET['query']) : '';
$category_id = isset($_GET['category_id']) && is_numeric($_GET['category_id']) ? (int)$_GET['category_id'] : null;

// Fetch categories for the dropdown
try {
    $categoriesStmt = $db->query("SELECT id, name FROM categories ORDER BY name ASC");
    $categories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching categories: " . $e->getMessage());
}

// Fetch filtered recipes
function getAvailableContent($db, $query, $category_id, $recipes_per_page, $offset) {
    $sql = "SELECT 
                r.id, 
                r.title, 
                r.description,
                r.image_path,
                r.ingredients,
                r.created_at, 
                c.name AS category_name,
                u.username AS created_by
            FROM 
                recipes r
            LEFT JOIN 
                categories c ON r.category_id = c.id
            LEFT JOIN 
                users u ON r.user_id = u.id
            WHERE 1=1";

    // Add search filter
    if (!empty($query)) {
        $sql .= " AND (r.title LIKE :query OR r.description LIKE :query)";
    }

    // Add category filter
    if (!empty($category_id)) {
        $sql .= " AND r.category_id = :category_id";
    }

    $sql .= " ORDER BY r.created_at DESC LIMIT :limit OFFSET :offset";

    $stmt = $db->prepare($sql);

    // Bind parameters
    if (!empty($query)) {
        $stmt->bindValue(':query', '%' . $query . '%', PDO::PARAM_STR);
    }
    if (!empty($category_id)) {
        $stmt->bindValue(':category_id', $category_id, PDO::PARAM_INT);
    }
    $stmt->bindValue(':limit', $recipes_per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$availableContent = getAvailableContent($db, $query, $category_id, $recipes_per_page, $offset);

// Get total number of recipes for pagination
$total_sql = "SELECT COUNT(*) FROM recipes WHERE 1=1";
if (!empty($query)) {
    $total_sql .= " AND (title LIKE :query OR description LIKE :query)";
}
if (!empty($category_id)) {
    $total_sql .= " AND category_id = :category_id";
}
$total_stmt = $db->prepare($total_sql);
if (!empty($query)) {
    $total_stmt->bindValue(':query', '%' . $query . '%', PDO::PARAM_STR);
}
if (!empty($category_id)) {
    $total_stmt->bindValue(':category_id', $category_id, PDO::PARAM_INT);
}
$total_stmt->execute();
$total_recipes = $total_stmt->fetchColumn();
$total_pages = ceil($total_recipes / $recipes_per_page);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Recipes</title>
    <link rel="stylesheet" href="Style/browse_recipesstyle.css">
</head>
<body>
    <header>
        <h1>Winnipeg Recipe Hub</h1>
        <nav>
            <ul>
                <?php if (isset($_SESSION['role'])): ?>
                    <li><a href="create_page.php">Create New Recipe</a></li>
                <?php endif; ?>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li><a href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="login.php">Login</a></li>
                <?php endif; ?>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <li><a href="admin.php">Admin Panel</a></li>
                <?php endif; ?>
            </ul>

            <form method="GET" action="" class="search-form">
                <label for="query">Search Recipes:</label>
                <input type="text" name="query" placeholder="Search recipes..." value="<?= htmlspecialchars($query); ?>">
                <select name="category_id">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= $category['id']; ?>" <?= ($category_id == $category['id']) ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit">Search</button>
            </form>
        </nav>
    </header>

    <div class="container">
        <h1>Available Recipes</h1>
        
        <div class="view-options">
            <button class="view-btn active" onclick="switchView('table')">Table View</button>
            <button class="view-btn" onclick="switchView('grid')">Grid View</button>
        </div>
        
        <!-- Table View of Content -->
        <div id="table-view">
            <?php if (!empty($availableContent)): ?>
                <table class="content-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Image</th>
                            <th>Category</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($availableContent as $content): ?>
                            <tr>
                                <td>
                                    <a href="recipe.php?id=<?= $content['id']; ?>" class="content-link">
                                        <?= htmlspecialchars($content['title']); ?>
                                    </a>
                                </td>
                                <td>
                                    <?php if (!empty($content['image_path'])): ?>
                                        <img src="<?= htmlspecialchars($content['image_path']); ?>" class="thumbnail" alt="Recipe image">
                                    <?php else: ?>
                                        No image
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($content['category_name'] ?? 'Uncategorized'); ?>
                                </td>
                                <td><?= date('M d, Y', strtotime($content['created_at'])); ?></td>
                                <td>
                                    <a href="recipe.php?id=<?= $content['id']; ?>">View</a>
                                    <?php if (isset($_SESSION['user_id'])): ?>
                                        | <a href="edit.php?id=<?= $content['id']; ?>">Edit</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No recipes available at this time.</p>
            <?php endif; ?>
        </div>
        
        <!-- Grid View of Content -->
        <div id="grid-view" style="display: none;">
            <?php if (!empty($availableContent)): ?>
                <div class="content-grid">
                    <?php foreach ($availableContent as $content): ?>
                        <div class="content-item">
                            <a href="recipe.php?id=<?= $content['id']; ?>" class="content-link">
                                <h3><?= htmlspecialchars($content['title']); ?></h3>
                            </a>
                            <?php if (!empty($content['image_path'])): ?>
                                <img src="<?= htmlspecialchars($content['image_path']); ?>" class="thumbnail" alt="Recipe Image">
                            <?php else: ?>
                                <p>No image available</p>
                            <?php endif; ?>
                            <p><?= htmlspecialchars($content['category_name'] ?? 'Uncategorized'); ?></p>
                            <p>Created: <?= date('M d, Y', strtotime($content['created_at'])); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>No recipes available at this time.</p>
            <?php endif; ?>
        </div>
        
        <!-- Pagination Links -->
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>&query=<?= urlencode($query); ?>&category_id=<?= $category_id; ?>">Previous</a>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?= $i ?>&query=<?= urlencode($query); ?>&category_id=<?= $category_id; ?>" class="<?= $i === $page ? 'active' : ''; ?>"><?= $i; ?></a>
            <?php endfor; ?>
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?= $page + 1 ?>&query=<?= urlencode($query); ?>&category_id=<?= $category_id; ?>">Next</a>
            <?php endif; ?>
        </div>
    </div>
    
    <footer>
        <p>&copy; 2025 Winnipeg Recipe Hub</p>
    </footer>
    
    <script>
        function switchView(viewType) {
            const tableView = document.getElementById('table-view');
            const gridView = document.getElementById('grid-view');
            const buttons = document.querySelectorAll('.view-btn');
            
            buttons.forEach(button => {
                button.classList.remove('active');
            });
            
            if (viewType === 'table') {
                tableView.style.display = 'block';
                gridView.style.display = 'none';
                document.querySelector('.view-btn:nth-child(1)').classList.add('active');
            } else if (viewType === 'grid') {
                tableView.style.display = 'none';
                gridView.style.display = 'block';
                document.querySelector('.view-btn:nth-child(2)').classList.add('active');
            }
        }
    </script>
</body>
</html>