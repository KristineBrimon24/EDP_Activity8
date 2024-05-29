<?php
header("Content-Type: application/json");

$host = 'localhost';
$db = 'hr';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

$pdo = new PDO($dsn, $user, $pass, $options);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->query("
        SELECT 
            a.userid, 
            a.username, 
            a.pass, 
            a.email, 
            s.student_number, 
            s.name, 
            s.school 
        FROM 
            accounts a
        JOIN 
            studentprofile s ON a.userid = s.student_id
    ");
    $users = $stmt->fetchAll();
    echo json_encode($users);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    try {
        // Start a transaction
        $pdo->beginTransaction();
        
        // Insert into accounts table
        $sqlAccounts = "INSERT INTO accounts (username, pass, email) VALUES (?, ?, ?)";
        $stmtAccounts = $pdo->prepare($sqlAccounts);
        $stmtAccounts->execute([$input['username'], $input['pass'], $input['email']]);
        
        // Get the last inserted user_id
        $userId = $pdo->lastInsertId();
        
        // Insert into userprofile table
        $sqlProfile = "INSERT INTO studentprofile (student_id, student_number, name, school) VALUES (?, ?, ?, ?)";
        $stmtProfile = $pdo->prepare($sqlProfile);
        $stmtProfile->execute([$userId, $input['student_number'], $input['name'], $input['school']]);
        
        // Commit the transaction
        $pdo->commit();
        
        echo json_encode(['message' => 'User added successfully']);
    } catch (Exception $e) {
        // Rollback the transaction in case of an error
        $pdo->rollBack();
        echo json_encode(['error' => 'Failed to add user: ' . $e->getMessage()]);
    }
}
?>