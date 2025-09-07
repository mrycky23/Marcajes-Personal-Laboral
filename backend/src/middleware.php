<?php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Slim\Exception\HttpUnauthorizedException;

$secretKey = "clave_super_secreta";

$jwtMiddleware = function ($request, $handler) use ($secretKey) {
    $authHeader = $request->getHeaderLine('Authorization');

    if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        throw new HttpUnauthorizedException($request, "Token no proporcionado");
    }

    $jwt = $matches[1];
    try {
        $decoded = JWT::decode($jwt, new Key($secretKey, 'HS256'));
        $request = $request->withAttribute("user", $decoded);
    } catch (Exception $e) {
        throw new HttpUnauthorizedException($request, "Token inválido o expirado");
    }

    return $handler->handle($request);
};
