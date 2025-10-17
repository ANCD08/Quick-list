<?php
$host = 'localhost';
$user = 'root'; 
$pass = 'DAPHNY08';   
$dbname = 'quicklist';

echo "Setting up Quick List database...\n";

try {
    $conn = new mysqli($host, $user, $pass);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    $sql = "CREATE DATABASE IF NOT EXISTS $dbname";
    if ($conn->query($sql) === TRUE) {
        echo "✓ Database created successfully\n";
    } else {
        throw new Exception("Error creating database: " . $conn->error);
    }
    
    $conn->select_db($dbname);
    
    $sql = "CREATE TABLE IF NOT EXISTS lists (
        id VARCHAR(50) PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "✓ Lists table created successfully\n";
    } else {
        throw new Exception("Error creating lists table: " . $conn->error);
    }
    $sql = "CREATE TABLE IF NOT EXISTS items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        list_id VARCHAR(50) NOT NULL,
        text TEXT NOT NULL,
        completed TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (list_id) REFERENCES lists(id) ON DELETE CASCADE,
        INDEX idx_list_id (list_id),
        INDEX idx_updated_at (updated_at)
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "✓ Items table created successfully\n";
    } else {
        throw new Exception("Error creating items table: " . $conn->error);
    }
    
    $default_lists = [
        ['community-picnic', 'Community Picnic Supplies', 'Add items you can bring to the community picnic. Check off items that are already covered.'],
        ['feature-wishlist', 'Feature Wishlist', 'Suggest features you would like to see in our application'],
        ['team-tasks', 'Team Task List', 'Shared task list for our team projects']
    ];
    
    $stmt = $conn->prepare("INSERT IGNORE INTO lists (id, name, description) VALUES (?, ?, ?)");
    foreach ($default_lists as $list) {
        $stmt->bind_param("sss", $list[0], $list[1], $list[2]);
        $stmt->execute();
    }
    echo "✓ Default lists created successfully\n";
    $sample_items = [
        ['community-picnic', 'Paper Plates', 1],
        ['community-picnic', 'Plastic Forks', 0],
        ['community-picnic', 'Napkins', 1],
        ['community-picnic', 'Charcoal', 0],
        ['feature-wishlist', 'Dark mode theme', 1],
        ['feature-wishlist', 'Mobile app version', 0]
    ];
    
    $stmt = $conn->prepare("INSERT IGNORE INTO items (list_id, text, completed) VALUES (?, ?, ?)");
    foreach ($sample_items as $item) {
        $stmt->bind_param("ssi", $item[0], $item[1], $item[2]);
        $stmt->execute();
    }
    echo "✓ Sample items created successfully\n";
    
    $conn->close();
    echo "\n Database setup complete! Quick List is ready to use.\n";
    echo "\nNext steps:\n";
    echo "1. Update the database credentials in api.php\n";
    echo "2. Upload all files to your web server\n";
    echo "3. Visit index.html in your browser\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

?>

