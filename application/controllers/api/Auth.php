<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use chriskacerguis\RestServer\RestController;
use \Firebase\JWT\JWT;
use \Firebase\JWT\ExpiredException;
use \Firebase\JWT\SignatureInvalidException;
use \Firebase\JWT\Key;

class Auth extends RestController {

    public function __construct() {
        parent::__construct();
        $this->load->model('User_model', 'user');
    }

    public function login_post() {
        $email = $this->post('email');
        $password = $this->post('password');

        $user = $this->user->get_user_by_email($email);

        if ($user && password_verify($password, $user['password'])) {
            
            $jwt_key = $this->config->item('jwt_key');
            
            $payload = [
                'iss' => "api_clientes", 
                'aud' => "api_clientes", 
                'iat' => time(),
                'exp' => time() + 3600,
                'data' => [
                    'userId' => $user['id'],
                    'email' => $user['email']
                ]
            ];

            $token = JWT::encode($payload, $jwt_key, 'HS256');

            $this->response(['token' => $token], RestController::HTTP_OK);

        } else {
            $this->response(['message' => 'Credenciais inválidas.'], RestController::HTTP_UNAUTHORIZED);
        }
    }

     public function verify_token_get() {
        $authHeader = $this->input->get_request_header('Authorization');

        if (!$authHeader) {
            $this->response(['message' => 'Token de autenticação não fornecido.'], RestController::HTTP_UNAUTHORIZED);
            return;
        }

        list($token) = sscanf($authHeader, 'Bearer %s');

        if (!$token) {
            $this->response(['message' => 'Formato do token inválido.'], RestController::HTTP_UNAUTHORIZED);
            return;
        }

        try {
            $jwt_key = $this->config->item('jwt_key');

            $decoded_payload = JWT::decode($token, new Key($jwt_key, 'HS256'));

            $this->response([
                'status' => 'success',
                'message' => 'Token válido.',
                'data' => $decoded_payload->data 
            ], RestController::HTTP_OK);

        } catch (ExpiredException $e) {
            $this->response(['message' => 'Token expirado.'], RestController::HTTP_UNAUTHORIZED);
        } catch (SignatureInvalidException $e) {
            $this->response(['message' => 'Assinatura do token inválida.'], RestController::HTTP_UNAUTHORIZED);
        } catch (Exception $e) {
            $this->response(['message' => 'Token inválido: ' . $e->getMessage()], RestController::HTTP_UNAUTHORIZED);
        }
    }
}