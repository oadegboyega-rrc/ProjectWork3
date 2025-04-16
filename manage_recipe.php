<?php
require 'authenticate.php';

// Determine sorting column and direction
$valid_columns = ['title', 'created_at', 'category_name'];
$sort_column = isset($_GET['sort']) && in_array($_GET['sort'], $valid_columns) ? $_GET['sort'] : 'created_at';
$sort_direction = isset($_GET['direction']) && $_GET['direction'] === 'desc' ? 'DESC' : 'ASC';
$next_direction = $sort_direction === 'ASC' ? 'desc' : 'asc';
$arrow = $sort_direction === 'ASC' ? '▲' : '▼';

// Pagination logic
$recipes_per_page = 12; // Maximum of 12 recipes per page
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $recipes_per_page;

// Get total number of recipes
$total_stmt = $db->query("SELECT COUNT(*) FROM recipes");
$total_recipes = $total_stmt->fetchColumn();
$total_pages = ceil($total_recipes / $recipes_per_page);

// Get all available recipes (content items)
function getAvailableContent($db) {
    global $recipes_per_page, $offset, $sort_column, $sort_direction; // Use global variables for sorting and pagination
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
            ORDER BY 
                $sort_column $sort_direction
            LIMIT :limit OFFSET :offset";

    $stmt = $db->prepare($sql);
    $stmt->bindValue(':limit', $recipes_per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get all available content
$availableContent = getAvailableContent($db);
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
                            <th>
                                <a href="?sort=title&direction=<?= $next_direction ?>&page=<?= $page ?>">
                                    Title <?= $sort_column === 'title' ? $arrow : '' ?>
                                </a>
                            </th>
                            <th>Image</th>
                            <th>
                                <a href="?sort=category_name&direction=<?= $next_direction ?>&page=<?= $page ?>">
                                    Category <?= $sort_column === 'category_name' ? $arrow : '' ?>
                                </a>
                            </th>
                            <th>
                                <a href="?sort=created_at&direction=<?= $next_direction ?>&page=<?= $page ?>">
                                    Created <?= $sort_column === 'created_at' ? $arrow : '' ?>
                                </a>
                            </th>
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
                <a href="?page=<?= $page - 1 ?>">Previous</a>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?= $i ?>" class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?= $page + 1 ?>">Next</a>
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