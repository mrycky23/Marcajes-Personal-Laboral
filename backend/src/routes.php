<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// Clave secreta para firmar los tokens (guárdala segura en .env)
$secretKey = "clave_super_secreta";

//-----------------REGSITRO----------------
$app->post('/usuarios/register', function ($request, $response) {
    $pdo = $this->get(PDO::class);
    $data = $request->getParsedBody();

    if (!isset($data['nombre'], $data['email'], $data['password'])) {
        $response->getBody()->write(json_encode(['error' => 'Faltan datos']));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }

    $hash = password_hash($data['password'], PASSWORD_BCRYPT);

    $sql = "INSERT INTO usuarios (nombre, email, password) VALUES (:nombre, :email, :password)";
    $stmt = $pdo->prepare($sql);

    try {
        $stmt->execute([
            ':nombre' => $data['nombre'],
            ':email' => $data['email'],
            ':password' => $hash
        ]);
        $response->getBody()->write(json_encode(['success' => true]));
        return $response->withHeader('Content-Type', 'application/json');
    } catch (PDOException $e) {
        $response->getBody()->write(json_encode(['error' => 'El correo ya está registrado']));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }
});

// ---------------- LOGIN ----------------
$app->post('/usuarios/login', function ($request, $response) {
    $pdo = $this->get(PDO::class);
    $data = $request->getParsedBody();

    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => $data['email']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $response->getBody()->write(json_encode(['error' => 'Usuario no encontrado']));
        return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
    }

    if (!password_verify($data['password'], $user['password'])) {
        $response->getBody()->write(json_encode(['error' => 'Contraseña incorrecta']));
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
    }

    // Aquí deberías generar un JWT en producción, por ahora ejemplo básico:
    $token = base64_encode(random_bytes(20));

    $response->getBody()->write(json_encode(['token' => $token]));
    return $response->withHeader('Content-Type', 'application/json');
});


// ---------------- MARCAJES ----------------
$app->get('/marcajes', function (Request $request, Response $response) {
    $pdo = $this->get(PDO::class);
    $stmt = $pdo->query("SELECT * FROM marcajes ORDER BY marcado_en DESC");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $response->getBody()->write(json_encode($data));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->post('/marcajes', function (Request $request, Response $response) {
    $pdo = $this->get(PDO::class);
    $data = $request->getParsedBody();

    $sql = "INSERT INTO marcajes (usuario_id, tipo, marcado_en, lat, lng, precision_m, fuente, ip)
            VALUES (:usuario_id, :tipo, NOW(3), :lat, :lng, :precision_m, :fuente, :ip)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':usuario_id' => $data['usuario_id'],
        ':tipo' => $data['tipo'],
        ':lat' => $data['lat'],
        ':lng' => $data['lng'],
        ':precision_m' => $data['precision_m'] ?? null,
        ':fuente' => $data['fuente'] ?? 'APP',
        ':ip' => $_SERVER['REMOTE_ADDR'] ?? null
    ]);

    $response->getBody()->write(json_encode(['success' => true]));
    return $response->withHeader('Content-Type', 'application/json');
});
