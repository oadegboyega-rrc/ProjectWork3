<?php
require 'connect.php';
require 'page.php';

// Get page ID from URL
$page_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// If no valid page ID, redirect to homepage
if ($page_id <= 0) {
    header('Location: index.php');
    exit;
}

// Function to get a specific page by ID
function getPageById($id) {
    global $db;
    $stmt = $db->prepare("SELECT * FROM pages WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get the page data
$page = getPageById($page_id);

// If page doesn't exist, show error or redirect
if (!$page) {
    header('Location: index.php');
    exit;
}

// Get category name for this page
$category_name = getCategoryName($page['category_id']);

// Include header
include 'header.php';
?>

<div class="page-container">
    <h1><?php echo htmlspecialchars($page['title']); ?></h1>
    
    <div class="page-meta">
        <div class="page-category">
            Category: <a href="category.php?id=<?php echo $page['category_id']; ?>"><?php echo htmlspecialchars($category_name); ?></a>
        </div>
        <div class="page-date">
            Created: <?php echo $page['created_at']; ?><br>
            Last updated: <?php echo $page['updated_at']; ?>
        </div>
    </div>
    
    <div class="page-content">
        <?php echo $page['content']; ?>
    </div>
    
    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $page['user_id']): ?>
    <div class="page-actions">
        <a href="edit_page.php?id=<?php echo $page['id']; ?>" class="btn">Edit Page</a>
        <a href="delete_page.php?id=<?php echo $page['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this page?');">Delete Page</a>
    </div>
    <?php endif; ?>
    
    <div class="page-navigation">
        <a href="index.php">Back to All Pages</a> | 
        <a href="category.php?id=<?php echo $page['category_id']; ?>">Back to <?php echo htmlspecialchars($category_name); ?> Pages</a>
    </div>
</div>

<?php
// Include footer
include 'footer.php';
?>