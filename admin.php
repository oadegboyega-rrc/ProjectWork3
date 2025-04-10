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

// Sorting logic
$valid_columns = ['title', 'created_at', 'category_name'];
$sort_column = isset($_GET['sort']) && in_array($_GET['sort'], $valid_columns) ? $_GET['sort'] : 'created_at';
$sort_direction = isset($_GET['direction']) && $_GET['direction'] === 'desc' ? 'DESC' : 'ASC';
$next_direction = $sort_direction === 'ASC' ? 'desc' : 'asc';
$arrow = $sort_direction === 'ASC' ? '▲' : '▼';

// Fetch total number of recipes (with search filter)
if (!empty($search_query)) {
    $total_recipes_stmt = $db->prepare("SELECT COUNT(*) FROM recipes WHERE title LIKE :search");
    $total_recipes_stmt->execute(['search' => '%' . $search_query . '%']);
} else {
    $total_recipes_stmt = $db->query("SELECT COUNT(*) FROM recipes");
}
$total_recipes = $total_recipes_stmt->fetchColumn();
$total_pages = ceil($total_recipes / $recipes_per_page);

// Fetch recipes with pagination, search filter, and sorting
if (!empty($search_query)) {
    $stmt = $db->prepare("SELECT recipes.*, categories.name AS category_name 
                          FROM recipes 
                          LEFT JOIN categories ON recipes.category_id = categories.id 
                          WHERE recipes.title LIKE :search 
                          ORDER BY $sort_column $sort_direction 
                          LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':search', '%' . $search_query . '%', PDO::PARAM_STR);
} else {
    $stmt = $db->prepare("SELECT recipes.*, categories.name AS category_name 
                          FROM recipes 
                          LEFT JOIN categories ON recipes.category_id = categories.id 
                          ORDER BY $sort_column $sort_direction 
                          LIMIT :limit OFFSET :offset");
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
    <a href="index.php">Back to Home</a> | <a href="logout.php">Logout</a> | <a href="manage_category.php">Manage Categories</a> | <a href="manage_users.php" class="button">Manage Users</a>
</div>

<div class="admin-container">
    <h2>Recipe Management</h2>

    <!-- Search Form -->
    <form method="GET" action="admin.php" class="search-form">
        <input type="text" name="search" placeholder="Search recipes..." value="<?= htmlspecialchars($search_query); ?>">
        <button type="submit">Search</button>
        <a href="admin.php" class="clear-search">Clear</a>
    </form>

    <table>
        <tr>
            <th>
                <a href="?sort=title&direction=<?= $next_direction ?>&page=<?= $page ?>&search=<?= urlencode($search_query); ?>">
                    Title <?= $sort_column === 'title' ? $arrow : '' ?>
                </a>
            </th>
            <th>
                <a href="?sort=created_at&direction=<?= $next_direction ?>&page=<?= $page ?>&search=<?= urlencode($search_query); ?>">
                    Created At <?= $sort_column === 'created_at' ? $arrow : '' ?>
                </a>
            </th>
            <th>Image</th>
            <th>Actions</th>
            <th>
                <a href="?sort=category_name&direction=<?= $next_direction ?>&page=<?= $page ?>&search=<?= urlencode($search_query); ?>">
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
                    <a href="admin.php?delete_recipe=<?= $recipe['id']; ?>&page=<?= $page; ?>&search=<?= urlencode($search_query); ?>">Delete</a> |
                    <a href="edit_recipe.php?id=<?= $recipe['id']; ?>">Edit</a>
                </td>
                <td><?= htmlspecialchars($recipe['category_name']); ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <!-- Pagination Links -->
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="admin.php?page=<?= $page - 1; ?>&sort=<?= $sort_column ?>&direction=<?= $sort_direction ?>&search=<?= urlencode($search_query); ?>">&laquo; Previous</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="admin.php?page=<?= $i; ?>&sort=<?= $sort_column ?>&direction=<?= $sort_direction ?>&search=<?= urlencode($search_query); ?>" class="<?= $i === $page ? 'active' : ''; ?>">
                <?= $i; ?>
            </a>
        <?php endfor; ?>

        <?php if ($page < $total_pages): ?>
            <a href="admin.php?page=<?= $page + 1; ?>&sort=<?= $sort_column ?>&direction=<?= $sort_direction ?>&search=<?= urlencode($search_query); ?>">Next &raquo;</a>
        <?php endif; ?>
    </div>
</div>
<!-- <footer>
<iframe src="https://www.google.com/maps/embed?pb=!1m16!1m12!1m3!1d164629.0130851549!2d-97.20123141094467!3d49.85507685227652!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!2m1!1smap!5e0!3m2!1sen!2sca!4v1744141923100!5m2!1sen!2sca" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
</footer> -->

</body>
</html>