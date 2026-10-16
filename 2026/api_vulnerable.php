<?php
/**
 * ============================================================
 * DESAFÍO #5: API REST VULNERABLE
 * Unidad de Ciencia y Tecnología — UPTPC 2026
 * ============================================================
 * 
 * 🧠 CONOCIMIENTOS REQUERIDOS:
 * - Arquitectura RESTful, métodos HTTP (GET, POST, PUT, DELETE) y formato JSON.
 * - Inyección lógica / SQL en payloads JSON.
 * 
 * 🛠️ SOLUCIÓN OFICIAL:
 * 1. Enviar una petición POST a la ruta de login con el payload JSON:
 *    { "nombre": "admin' OR '1'='1", "password": "" }
 * 2. La API devolverá la bandera FLAG{API_HACKED}.
 * 
 * 🔀 ALTERNATIVAS DE RESOLUCIÓN:
 * - Método A: Usar la interfaz web provista en api_lab.html.
 * - Método B: Consumir el endpoint mediante Postman o cURL en consola.
 * ============================================================
 */

require_once __DIR__ . '/conf/functions.php';

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");

$usuarios = [
    1 => ["id" => 1, "nombre" => "admin", "password" => "admin123", "rol" => "admin"],
    2 => ["id" => 2, "nombre" => "agente99", "password" => "x9k2m", "rol" => "operador"],
    3 => ["id" => 3, "nombre" => "invitado", "password" => "1234", "rol" => "visitante"],
    4 => ["id" => 4, "nombre" => "shadow", "password" => "h4ck3r", "rol" => "anonimo"]
];

$method = $_SERVER['REQUEST_METHOD'];
$path = isset($_GET['ruta']) ? $_GET['ruta'] : '';

switch($path) {
    
    case 'usuarios':
        if($method == 'GET') {
            echo json_encode(array_values($usuarios));
        }
        break;
    
    case 'usuario':
        if($method == 'GET') {
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            if(isset($usuarios[$id])) {
                echo json_encode($usuarios[$id]);
            } else {
                echo json_encode(["error" => "not found"]);
            }
        }
        break;
    
    case 'login':
        if($method == 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            $nombre = isset($input['nombre']) ? $input['nombre'] : '';
            $password = isset($input['password']) ? $input['password'] : '';
            
            if(strpos($nombre, "'") !== false || preg_match("/OR/i", $nombre)) {
                echo json_encode(["success" => true, "message" => "welcome", "flag" => "FLAG{API_HACKED}"]);
            } else {
                $encontrado = false;
                foreach($usuarios as $user) {
                    if($user['nombre'] == $nombre && $user['password'] == $password) {
                        $encontrado = $user;
                        break;
                    }
                }
                if($encontrado) {
                    echo json_encode(["success" => true, "user" => $encontrado]);
                } else {
                    echo json_encode(["success" => false]);
                }
            }
        }
        break;
    
    case 'usuario':
        if($method == 'PUT') {
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            $input = json_decode(file_get_contents('php://input'), true);
            if(isset($usuarios[$id])) {
                if(isset($input['rol'])) $usuarios[$id]['rol'] = $input['rol'];
                echo json_encode(["updated" => true]);
            }
        }
        break;
    
    case 'usuario':
        if($method == 'DELETE') {
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            if(isset($usuarios[$id])) {
                unset($usuarios[$id]);
                echo json_encode(["deleted" => true]);
            }
        }
        break;
    
    default:
        echo json_encode(["error" => "ruta invalida", "endpoints" => ["usuarios", "usuario", "login"]]);
        break;
}
?>