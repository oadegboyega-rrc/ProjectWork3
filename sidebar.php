<?php
//Function to get all categories 
function get_all_categories() {
    global $db;
    $stmt = $db->prepare("SELECT id, name FROM categories ORDER BY name");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Add this to sidebar.php
?>
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Categories</h5>
    </div>
    <div class="card-body">
        <ul class="list-group list-group-flush">
            <?php
            $categories = get_all_categories();
            foreach ($categories as $category) {
                echo '<li class="list-group-item">';
                echo '<a href="category.php?id=' . htmlspecialchars($category['id']) . '">';
                echo htmlspecialchars($category['name']);
                echo '</a>';
                echo '</li>';
            }
            ?>
        </ul>
    </div>
</div>