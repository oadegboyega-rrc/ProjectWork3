<?php
session_start();
include 'connect.php';


// Determine sorting column and direction
$valid_columns = ['title', 'created_at', 'update_at'];
$sort_column = isset($_GET['sort']) && in_array($_GET['sort'], $valid_columns) ? $_GET['sort'] : 'created_at';
$sort_direction = isset($_GET['direction']) && $_GET['direction'] === 'desc' ? 'DESC' : 'ASC';

// Pagination logic
$recipes_per_page = isset($_GET['n']) && is_numeric($_GET['n']) ? (int)$_GET['n'] : 10; // Allow dynamic adjustment of N
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $recipes_per_page;

// Fetch all categories for the dropdown
try {
    $categoriesStmt = $db->query("SELECT id, name FROM categories ORDER BY name ASC");
    $categories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error fetching categories: " . $e->getMessage();
}


// Fetch recent recipes from the database with sorting and pagination
try {
    $stmt = $db->prepare("SELECT r.id, r.title, r.description, r.ingredients, r.instructions, r.created_at, r.update_at, r.image_path, u.username 
                          FROM recipes r 
                          JOIN users u ON r.user_id = u.id 
                          ORDER BY $sort_column $sort_direction 
                          LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':limit', $recipes_per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $recipes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get the total number of recipes for pagination
    $total_stmt = $db->query("SELECT COUNT(*) FROM recipes");
    $total_recipes = $total_stmt->fetchColumn();
    $total_pages = ceil($total_recipes / $recipes_per_page);
} catch (PDOException $e) {
    echo "Error fetching recipes: " . $e->getMessage();
}

// Determine the next sorting direction
$next_direction = $sort_direction === 'ASC' ? 'desc' : 'asc';

// Determine the arrow for the current sorting direction
$arrow = $sort_direction === 'ASC' ? '▲' : '▼';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Winnipeg Recipe Hub</title>
    <link rel="stylesheet" href="Style/headerstyle.css"> 
</head>
<body>
<header>
    <div class="logo">
        <img src="uploads/logo_image.png" alt="Winnipeg Recipe Logo">
        <h1>Winnipeg Recipe Hub</h1>
    </div>
    <nav>
        <ul>
            <li><a href="index.php">Home</a></li>
            <li class="dropdown">
                <a href="#">Categories</a>
                <div class="dropdown-content">
                    <?php foreach ($categories as $category): ?>
                        <a href="view_category.php?id=<?= $category['id']; ?>">
                            <?= htmlspecialchars($category['name']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </li>
            <li><a href="browse_recipes.php">Browse All Recipes</a></li>
            <?php if (isset($_SESSION['user_id'])): ?>
                <li><a href="logout.php">Logout</a></li>
            <?php else: ?>
                <li><a href="login.php">Login</a></li>
            <?php endif; ?>
        </ul>
    </nav>

    <form method="GET" action="" class="search-form">
        <input type="text" name="query" placeholder="Search recipes..." value="<?= htmlspecialchars($_GET['query'] ?? '') ?>">
        <select name="category_id">
            <option value="">All Categories</option>
            <?php foreach ($categories as $category): ?>
                <option value="<?= $category['id']; ?>" <?= (isset($_GET['category_id']) && $_GET['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                    <?= htmlspecialchars($category['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Search</button>
    </form>
</header>

<main>
    <div class="container">
        <h2>Recent Recipes</h2>
        <table>
            <thead>
                <tr>
                    <th>
                        <a href="?sort=title&direction=<?= $next_direction ?>&page=<?= $page ?>&n=<?= $recipes_per_page ?>">
                            Title <?= $sort_column === 'title' ? $arrow : '' ?>
                        </a>
                    </th>
                    <th>
                        <a href="?sort=created_at&direction=<?= $next_direction ?>&page=<?= $page ?>&n=<?= $recipes_per_page ?>">
                            Created At <?= $sort_column === 'created_at' ? $arrow : '' ?>
                        </a>
                    </th>
                    <th>
                        <a href="?sort=update_at&direction=<?= $next_direction ?>&page=<?= $page ?>&n=<?= $recipes_per_page ?>">
                            Updated At <?= $sort_column === 'update_at' ? $arrow : '' ?>
                        </a>
                    </th>
                    <th>Image</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recipes as $recipe): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($recipe['title']); ?></td>
                        <td><?php echo date("F j, Y, g:i a", strtotime($recipe['created_at'])); ?></td>
                        <td><?php echo date("F j, Y, g:i a", strtotime($recipe['update_at'])); ?></td>
                        <td>
                            <?php if (!empty($recipe['image_path'])): ?>
                                <img src="<?php echo htmlspecialchars($recipe['image_path']); ?>" alt="<?php echo htmlspecialchars($recipe['title']); ?>" width="100">
                            <?php else: ?>
                                <span>No image available</span>
                            <?php endif; ?>
                        </td>
                        
                        <td>
                            <a href="recipe.php?id=<?php echo $recipe['id']; ?>">View Recipe</a> |
                            <a href="edit.php?id=<?php echo $recipe['id']; ?>">Edit</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Pagination Links -->
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>&sort=<?= $sort_column ?>&direction=<?= $sort_direction ?>&n=<?= $recipes_per_page ?>">Previous</a>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?= $i ?>&sort=<?= $sort_column ?>&direction=<?= $sort_direction ?>&n=<?= $recipes_per_page ?>" 
                   class="<?= $i === $page ? 'active' : '' ?>">
                   <?= $i ?>
                </a>
            <?php endfor; ?>
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?= $page + 1 ?>&sort=<?= $sort_column ?>&direction=<?= $sort_direction ?>&n=<?= $recipes_per_page ?>">Next</a>
            <?php endif; ?>
        </div>
    </div>
</main>

<footer>
    <p>&copy; 2025 Winnipeg Recipe Hub</p>
</footer>
</body>
</html>