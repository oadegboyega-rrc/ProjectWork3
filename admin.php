<?php
require 'authenticate.php';

// Check if the user is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Pagination logic
$recipes_per_page = 10; // Number of recipes per page
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $recipes_per_page;

// Search logic
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_id = isset($_GET['category_id']) && is_numeric($_GET['category_id']) ? (int)$_GET['category_id'] : null;

// Sorting logic
$valid_columns = ['title', 'created_at', 'category_name'];
$sort_column = isset($_GET['sort']) && in_array($_GET['sort'], $valid_columns) ? $_GET['sort'] : 'created_at';
$sort_direction = isset($_GET['direction']) && $_GET['direction'] === 'desc' ? 'DESC' : 'ASC';
$next_direction = $sort_direction === 'ASC' ? 'desc' : 'asc';
$arrow = $sort_direction === 'ASC' ? '▲' : '▼';

// Fetch categories for the dropdown
try {
    $categoriesStmt = $db->query("SELECT id, name FROM categories ORDER BY name ASC");
    $categories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching categories: " . $e->getMessage());
}

// Fetch total number of recipes (with search and category filters)
$total_sql = "SELECT COUNT(*) FROM recipes WHERE 1=1";
if (!empty($search_query)) {
    $total_sql .= " AND (title LIKE :search OR description LIKE :search)";
}
if (!empty($category_id)) {
    $total_sql .= " AND category_id = :category_id";
}
$total_stmt = $db->prepare($total_sql);
if (!empty($search_query)) {
    $total_stmt->bindValue(':search', '%' . $search_query . '%', PDO::PARAM_STR);
}
if (!empty($category_id)) {
    $total_stmt->bindValue(':category_id', $category_id, PDO::PARAM_INT);
}
$total_stmt->execute();
$total_recipes = $total_stmt->fetchColumn();
$total_pages = ceil($total_recipes / $recipes_per_page);

// Fetch recipes with pagination, search, and sorting
$sql = "SELECT recipes.*, categories.name AS category_name 
        FROM recipes 
        LEFT JOIN categories ON recipes.category_id = categories.id 
        WHERE 1=1";
if (!empty($search_query)) {
    $sql .= " AND (recipes.title LIKE :search OR recipes.description LIKE :search)";
}
if (!empty($category_id)) {
    $sql .= " AND recipes.category_id = :category_id";
}
$sql .= " ORDER BY $sort_column $sort_direction LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($sql);
if (!empty($search_query)) {
    $stmt->bindValue(':search', '%' . $search_query . '%', PDO::PARAM_STR);
}
if (!empty($category_id)) {
    $stmt->bindValue(':category_id', $category_id, PDO::PARAM_INT);
}
$stmt->bindValue(':limit', $recipes_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$recipes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="Style/adminstyle.css">
</head>
<body>

<div class="header">
    <div class="logo">
        <img src="uploads/logo_image.png" alt="Winnipeg Recipe Logo">
        <h1>Winnipeg Recipe!</h1>
    </div>
    <h1>Admin Dashboard</h1>
    <a href="manage_recipe.php">Back to Home</a> | <a href="logout.php">Logout</a> | <a href="manage_category.php">Manage Categories</a> | <a href="manage_users.php" class="button">Manage Users</a>

    <!-- Search Form -->
    <form method="GET" action="admin.php" class="search-form">
        <input type="text" name="search" placeholder="Search recipes..." value="<?= htmlspecialchars($search_query); ?>">
        <select name="category_id">
            <option value="">All Categories</option>
            <?php foreach ($categories as $category): ?>
                <option value="<?= $category['id']; ?>" <?= ($category_id == $category['id']) ? 'selected' : ''; ?>>
                    <?= htmlspecialchars($category['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Search</button>
        <a href="admin.php" class="clear-search">Clear</a>
    </form>
</div>

<div class="admin-container">
    <main>
        <h2>Recipe Management</h2>

        <table>
            <tr>
                <th>
                    <a href="?sort=title&direction=<?= $next_direction ?>&page=<?= $page ?>&search=<?= urlencode($search_query); ?>&category_id=<?= $category_id; ?>">
                        Title <?= $sort_column === 'title' ? $arrow : '' ?>
                    </a>
                </th>
                <th>
                    <a href="?sort=created_at&direction=<?= $next_direction ?>&page=<?= $page ?>&search=<?= urlencode($search_query); ?>&category_id=<?= $category_id; ?>">
                        Created At <?= $sort_column === 'created_at' ? $arrow : '' ?>
                    </a>
                </th>
                <th>Image</th>
                <th>Actions</th>
                <th>
                    <a href="?sort=category_name&direction=<?= $next_direction ?>&page=<?= $page ?>&search=<?= urlencode($search_query); ?>&category_id=<?= $category_id; ?>">
                        Category <?= $sort_column === 'category_name' ? $arrow : '' ?>
                    </a>
                </th>
            </tr>
            <?php foreach ($recipes as $recipe): ?>
                <tr>
                    <td><?= htmlspecialchars($recipe['title']); ?></td>
                    <td><?= date("F j, Y, g:i a", strtotime($recipe['created_at'])); ?></td>
                    <td>
                        <?php if (!empty($recipe['image_path'])): ?>
                            <img src="<?= htmlspecialchars($recipe['image_path']); ?>" width="100">
                        <?php else: ?>
                            No Image
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="edit_recipe.php?id=<?= $recipe['id']; ?>">Edit</a> |
                        <a href="delete_recipe.php?id=<?= $recipe['id']; ?>">Delete</a>
                    </td>
                    <td><?= htmlspecialchars($recipe['category_name']); ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </main>

    <!-- Pagination Links -->
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="admin.php?page=<?= $page - 1; ?>&sort=<?= $sort_column ?>&direction=<?= $sort_direction ?>&search=<?= urlencode($search_query); ?>&category_id=<?= $category_id; ?>">&laquo; Previous</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="admin.php?page=<?= $i; ?>&sort=<?= $sort_column ?>&direction=<?= $sort_direction ?>&search=<?= urlencode($search_query); ?>&category_id=<?= $category_id; ?>" class="<?= $i === $page ? 'active' : ''; ?>">
                <?= $i; ?>
            </a>
        <?php endfor; ?>

        <?php if ($page < $total_pages): ?>
            <a href="admin.php?page=<?= $page + 1; ?>&sort=<?= $sort_column ?>&direction=<?= $sort_direction ?>&search=<?= urlencode($search_query); ?>&category_id=<?= $category_id; ?>">Next &raquo;</a>
        <?php endif; ?>
    </div>
</div>

</body>
</html>