<?php
require 'authenticate.php';

// Pagination logic
$recipes_per_page = 12; // Maximum of 10 recipes per page
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $recipes_per_page;

// Get total number of recipes
$total_stmt = $db->query("SELECT COUNT(*) FROM recipes");
$total_recipes = $total_stmt->fetchColumn();
$total_pages = ceil($total_recipes / $recipes_per_page);

// Get all available recipes (content items)
function getAvailableContent($db) {
    global $recipes_per_page, $offset; // Use global variables for pagination
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
                r.created_at DESC
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
                <li><a href="index.php">Home</a></li>
                <li><a href="category.php">Categories</a></li>
                <?php if (isset($_SESSION['role'])): ?>
                    <li><a href="create_page.php">Submit Recipe</a></li>
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
                                    <a href="recipe.php?id=<?php echo $content['id']; ?>" class="content-link">
                                        <?php echo htmlspecialchars($content['title']); ?>
                                    </a>
                                </td>
                                <td>
                                    <?php if (!empty($content['image_path'])): ?>
                                        <img src="<?php echo htmlspecialchars($content['image_path']); ?>" class="thumbnail" alt="Recipe image">
                                    <?php else: ?>
                                        No image
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="content-type-badge">
                                        <?php echo !empty($content['category_name']) ? htmlspecialchars($content['category_name']) : 'Uncategorized'; ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($content['created_at'])); ?></td>
                                <td>
                                    <a href="recipe.php?id=<?php echo $content['id']; ?>">View</a>
                                    <?php if (isset($_SESSION['user_id'])): ?>
                                        | <a href="edit.php?id=<?php echo $content['id']; ?>">Edit</a>
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
                            <a href="recipe.php?id=<?php echo $content['id']; ?>" class="content-link">
                                <h3><?php echo htmlspecialchars($content['title']); ?></h3>
                            </a>
                            <?php if (!empty($content['image_path'])): ?>
                                <img src="<?php echo htmlspecialchars($content['image_path']); ?>" class="thumbnail" alt="Recipe Image">
                            <?php else: ?>
                                <p>No image available</p>
                            <?php endif; ?>
                            <p>
                                <span class="content-type-badge">
                                    <?php echo !empty($content['category_name']) ? htmlspecialchars($content['category_name']) : 'Uncategorized'; ?>
                                </span>
                            </p>
                            <p>Created: <?php echo date('M d, Y', strtotime($content['created_at'])); ?></p>
                            <p>
                                <a href="recipe.php?id=<?php echo $content['id']; ?>">View</a>
                                <?php if (isset($_SESSION['user_id'])): ?>
                                    | <a href="edit.php?id=<?php echo $content['id']; ?>">Edit</a>
                                <?php endif; ?>
                            </p>
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