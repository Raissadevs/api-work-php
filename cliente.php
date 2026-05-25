<?php
// Configuracoes de resposta da API
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, PUT, GET, DELETE");
header("Access-Control-Allow-Headers: Content-Type");

include("conexao.php");

$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? $_GET['id'] : null;

// Encerra a requisicao sempre retornando JSON padronizado.
function responder($status, $dados)
{
    http_response_code($status);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit();
}

// Valida se o ID foi informado e se e um numero inteiro positivo.
function validarId($id)
{
    if ($id === null || $id === '' || !filter_var($id, FILTER_VALIDATE_INT) || (int)$id <= 0) {
        responder(400, ["error" => "ID obrigatório."]);
    }

    return (int)$id;
}

// Busca um usuario pelo ID usando prepared statement.
function buscarUsuarioPorId($conn, $id)
{
    $sql = "SELECT id, nome, sobrenome, email, telefone FROM usuarios WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        responder(500, ["error" => "Erro interno no servidor."]);
    }

    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);

    mysqli_stmt_bind_result($stmt, $usuarioId, $nome, $sobrenome, $email, $telefone);

    $usuario = null;
    if (mysqli_stmt_fetch($stmt)) {
        $usuario = [
            "id" => $usuarioId,
            "nome" => $nome,
            "sobrenome" => $sobrenome,
            "email" => $email,
            "telefone" => $telefone
        ];
    }

    mysqli_stmt_close($stmt);

    return $usuario;
}

// Garante que o usuario existe antes de atualizar, buscar ou deletar.
function validarUsuarioExistente($conn, $id)
{
    $usuario = buscarUsuarioPorId($conn, $id);

    if (!$usuario) {
        responder(404, ["error" => "Usuário não encontrado."]);
    }

    return $usuario;
}

// Lê e valida o JSON recebido no POST e PUT.
function obterDadosObrigatorios()
{
    $data = json_decode(file_get_contents("php://input"), true);

    if (!is_array($data)) {
        responder(400, ["error" => "Todos os campos são obrigatórios."]);
    }

    $campos = ["nome", "sobrenome", "email", "telefone"];

    foreach ($campos as $campo) {
        if (!isset($data[$campo]) || trim($data[$campo]) === '') {
            responder(400, ["error" => "Todos os campos são obrigatórios."]);
        }
    }

    return [
        "nome" => trim($data["nome"]),
        "sobrenome" => trim($data["sobrenome"]),
        "email" => trim($data["email"]),
        "telefone" => trim($data["telefone"])
    ];
}

if ($method == 'POST') {
    $data = obterDadosObrigatorios();

    $sql = "INSERT INTO usuarios (nome, sobrenome, email, telefone) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        responder(500, ["error" => "Erro interno no servidor."]);
    }

    mysqli_stmt_bind_param($stmt, "ssss", $data["nome"], $data["sobrenome"], $data["email"], $data["telefone"]);
    $result = mysqli_stmt_execute($stmt);

    if ($result) {
        $idCliente = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);

        responder(201, [
            "message" => "Usuário criado com sucesso.",
            "id" => $idCliente
        ]);
    }

    mysqli_stmt_close($stmt);
    responder(500, ["error" => "Erro ao criar usuário."]);
}

if ($method == 'GET') {
    if ($id !== null) {
        $id = validarId($id);
        $usuario = validarUsuarioExistente($conn, $id);
        responder(200, $usuario);
    }

    $sql = "SELECT id, nome, sobrenome, email, telefone FROM usuarios";
    $result = mysqli_query($conn, $sql);

    if (!$result) {
        responder(500, ["error" => "Erro ao buscar usuários."]);
    }

    $usuarios = [];
    while ($usuario = mysqli_fetch_assoc($result)) {
        $usuarios[] = $usuario;
    }

    responder(200, $usuarios);
}

if ($method == 'PUT') {
    $id = validarId($id);
    validarUsuarioExistente($conn, $id);
    $data = obterDadosObrigatorios();

    $sql = "UPDATE usuarios SET nome = ?, sobrenome = ?, email = ?, telefone = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        responder(500, ["error" => "Erro interno no servidor."]);
    }

    mysqli_stmt_bind_param($stmt, "ssssi", $data["nome"], $data["sobrenome"], $data["email"], $data["telefone"], $id);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if ($result) {
        responder(200, ["message" => "Usuário atualizado com sucesso."]);
    }

    responder(500, ["error" => "Erro ao atualizar usuário."]);
}

if ($method == 'DELETE') {
    $id = validarId($id);
    validarUsuarioExistente($conn, $id);

    $sql = "DELETE FROM usuarios WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        responder(500, ["error" => "Erro interno no servidor."]);
    }

    mysqli_stmt_bind_param($stmt, "i", $id);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if ($result) {
        responder(200, ["message" => "Usuário deletado com sucesso."]);
    }

    responder(500, ["error" => "Erro ao deletar usuário."]);
}

responder(400, ["error" => "Método não permitido."]);
?>
cd C:\xampp\htdocs\Mayron