<?php
use Slim\App;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Firebase\JWT\JWT;

return function (App $app, PDO $pdo) {
    // Clave secreta para JWT (en producción usar .env)
    $secretKey = "clave_super_secreta";

    // ---------------- REGISTRO DE USUARIO ----------------
    $app->post('/usuarios/register', function (Request $request, Response $response) use ($pdo) {
        $data = $request->getParsedBody();
        if (!isset($data['nombre'], $data['email'], $data['password'])) {
            $response->getBody()->write(json_encode(['error' => 'Faltan datos']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $hash = password_hash($data['password'], PASSWORD_BCRYPT);

        $sql = "INSERT INTO usuarios (nombre, email, hash_password, rol_id, activo, tz, creado_en, actualizado_en)
                VALUES (:nombre, :email, :hash_password, 1, 1, 'UTC', NOW(), NOW())";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nombre' => $data['nombre'],
            ':email' => $data['email'],
            ':hash_password' => $hash
        ]);

        $response->getBody()->write(json_encode(['success' => true]));
        return $response->withHeader('Content-Type', 'application/json');
    });

    // ---------------- LOGIN ----------------
    $app->post('/usuarios/login', function (Request $request, Response $response) use ($pdo, $secretKey) {
        $data = $request->getParsedBody();

        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';

        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['hash_password'])) {
            $payload = [
                "sub" => $user['id'],
                "email" => $user['email'],
                "rol" => $user['rol_id'],
                "iat" => time(),
                "exp" => time() + 3600
            ];
            $jwt = JWT::encode($payload, $secretKey, 'HS256');

            $response->getBody()->write(json_encode(["token" => $jwt]));
            return $response->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode(["error" => "Credenciales inválidas"]));
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
    });
// ---------------- MARCAJES ----------------
$app->get('/marcajes', function (Request $request, Response $response) use ($pdo) {
    // 🔹 Ahora se hace JOIN con usuarios para obtener el nombre
    $sql = "SELECT m.id, u.nombre AS usuario, m.tipo, m.marcado_en, m.lat, m.lng, m.precision_m, m.fuente, m.ip
            FROM marcajes m
            JOIN usuarios u ON m.usuario_id = u.id
            ORDER BY m.marcado_en DESC";

    $stmt = $pdo->query($sql);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $response->getBody()->write(json_encode($data));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->post('/marcajes', function (Request $request, Response $response) use ($pdo) {
    $data = $request->getParsedBody();

    // 🔹 Validar tipo (mayúsculas obligatorias)
    $tipo = strtoupper($data['tipo'] ?? '');
    if (!in_array($tipo, ['IN', 'OUT'])) {
        $response->getBody()->write(json_encode([
            'success' => false,
            'error' => 'El tipo debe ser "IN" o "OUT"'
        ]));
        return $response->withHeader('Content-Type', 'application/json')
                        ->withStatus(400);
    }

    $sql = "INSERT INTO marcajes (usuario_id, tipo, marcado_en, lat, lng, precision_m, fuente, ip)
            VALUES (:usuario_id, :tipo, NOW(3), :lat, :lng, :precision_m, :fuente, :ip)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':usuario_id'   => $data['usuario_id'],
        ':tipo'         => $tipo,
        ':lat'          => $data['lat'] ?? null,
        ':lng'          => $data['lng'] ?? null,
        ':precision_m'  => $data['precision_m'] ?? null,
        ':fuente'       => $data['fuente'] ?? 'APP',
        ':ip'           => $_SERVER['REMOTE_ADDR'] ?? null
    ]);

    $response->getBody()->write(json_encode(['success' => true]));
    return $response->withHeader('Content-Type', 'application/json');
});



};
