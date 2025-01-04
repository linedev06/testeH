<?php
// Criar ou abrir um banco de dados SQLite
$db = new SQLite3('books.db');

// Criar a tabela se não existir
$db->exec("CREATE TABLE IF NOT EXISTS books (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    author TEXT NOT NULL,
    current_page INTEGER NOT NULL,
    cover TEXT NOT NULL
)");

// Definir o cabeçalho para JSON
header('Content-Type: application/json');

// Rota para salvar o livro atual
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $title = $data['title'];
    $author = $data['author'];
    $currentPage = $data['currentPage'];
    $cover = $data['cover'];

    // Verifica se o livro já existe
    $stmt = $db->prepare("SELECT * FROM books WHERE title = :title AND author = :author");
    $stmt->bindValue(':title', $title, SQLITE3_TEXT);
    $stmt->bindValue(':author', $author, SQLITE3_TEXT);
    $result = $stmt->execute();

    if ($result->fetchArray(SQLITE3_ASSOC)) {
        // Se o livro já existe, atualiza a página atual
        $stmt = $db->prepare("UPDATE books SET current_page = :current_page WHERE title = :title AND author = :author");
        $stmt->bindValue(':current_page', $currentPage, SQLITE3_INTEGER);
        $stmt->bindValue(':title', $title, SQLITE3_TEXT);
        $stmt->bindValue(':author', $author, SQLITE3_TEXT);
        $stmt->execute();
    } else {
        // Se o livro não existe, insere um novo
        $stmt = $db->prepare("INSERT INTO books (title, author, current_page, cover) VALUES (:title, :author, :current_page, :cover)");
        $stmt->bindValue(':title', $title, SQLITE3_TEXT);
        $stmt->bindValue(':author', $author, SQLITE3_TEXT);
        $stmt->bindValue(':current_page', $currentPage, SQLITE3_INTEGER);
        $stmt->bindValue(':cover', $cover, SQLITE3_TEXT);
        $stmt->execute();
    }

    echo json_encode(['message' => 'Book saved successfully']);
}

// Rota para obter o livro atual
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['id'])) {
        $id = $_GET['id'];
        $result = $db->query("SELECT * FROM books WHERE id = $id");

        if ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            echo json_encode($row);
        } else {
            echo json_encode(['message' => 'Book not found']);
        }
    } else {
        // Rota para obter todos os livros
        $result = $db->query("SELECT * FROM books");
        $books = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $books[] = $row;
        }
        echo json_encode($books);
    }
}

$db->close();
?>