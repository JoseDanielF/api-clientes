<?php
defined('BASEPATH') or exit('No direct script access allowed');

use chriskacerguis\RestServer\RestController;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Users extends RestController
{

    private $jwt_key;

    function __construct()
    {
        parent::__construct();
        $this->load->model('User_model', 'user');
        $this->load->library('form_validation');

        $this->jwt_key = $this->config->item('jwt_key');
    }

    private function verify_token()
    {
        $authHeader = $this->input->get_request_header('Authorization');

        if (!$authHeader) {
            $this->response(['message' => 'Token de autenticação não fornecido.'], RestController::HTTP_UNAUTHORIZED);
            exit(); 
        }

        list($token) = sscanf($authHeader, 'Bearer %s');

        if (!$token) {
            $this->response(['message' => 'Formato do token inválido.'], RestController::HTTP_UNAUTHORIZED);
            exit();
        }

        try {
            JWT::decode($token, new Key($this->jwt_key, 'HS256'));
        } catch (Exception $e) {
            $this->response(['message' => 'Token inválido ou expirado.'], RestController::HTTP_UNAUTHORIZED);
            exit();
        }
    }

    public function index_get($id = null)
    {
        $this->verify_token();

        if ($id !== null) {
            $user = $this->user->get($id);
            if ($user) {
                $this->response($user, RestController::HTTP_OK);
            } else {
                $this->response([
                    'status' => 'error',
                    'message' => 'Usuário não encontrado.'
                ], RestController::HTTP_NOT_FOUND);
            }
        } else {
            $users = $this->user->get();
            $this->response($users, RestController::HTTP_OK);
        }
    }

    public function index_post()
    {
        
        $this->form_validation->set_rules('name', 'Nome', 'trim|required');
        $this->form_validation->set_rules('email', 'Email', 'trim|required|valid_email|is_unique[users.email]');
        $this->form_validation->set_rules('password', 'Senha', 'trim|required|min_length[8]');

        $this->form_validation->set_data($this->post());

        if ($this->form_validation->run() == FALSE) {
            $this->response([
                'status' => 'error',
                'message' => 'Os dados fornecidos são inválidos.',
                'errors' => $this->form_validation->error_array()
            ], RestController::HTTP_BAD_REQUEST);
        } else {
            $hashed_password = password_hash($this->post('password'), PASSWORD_BCRYPT);

            $data = [
                'name' => $this->post('name'),
                'email' => $this->post('email'),
                'password' => $hashed_password
            ];

            if ($this->user->insert($data)) {
                $this->response([
                    'status' => 'success',
                    'message' => 'Usuário criado com sucesso.'
                ], RestController::HTTP_CREATED);
            } else {
                $this->response([
                    'status' => 'error',
                    'message' => 'Ocorreu um erro no servidor ao criar o usuário.'
                ], RestController::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
    }

    public function index_put($id)
    {
        $this->verify_token();

        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data)) {
            $this->response(['status' => 'error', 'message' => 'Nenhum dado fornecido.'], RestController::HTTP_BAD_REQUEST);
            return;
        }

        $existing_user = $this->user->get($id);
        if (!$existing_user) {
            $this->response(['status' => 'error', 'message' => 'Usuário não encontrado.'], RestController::HTTP_NOT_FOUND);
            return;
        }

        $is_unique_rule = '';
        if (isset($data['email']) && $data['email'] != $existing_user['email']) {
            $is_unique_rule = '|is_unique[users.email]';
        }

        $this->form_validation->set_rules('name', 'Nome', 'trim|required');
        $this->form_validation->set_rules('email', 'Email', 'trim|required|valid_email' . $is_unique_rule);

        if (!empty($data['password'])) {
            $this->form_validation->set_rules('password', 'Senha', 'trim|min_length[8]');
        }

        $this->form_validation->set_data($data);

        if ($this->form_validation->run() == FALSE) {
            $this->response([
                'status' => 'error',
                'message' => 'Os dados fornecidos são inválidos.',
                'errors' => $this->form_validation->error_array()
            ], RestController::HTTP_BAD_REQUEST);
        } else {
            $updateData = [
                'name' => $data['name'],
                'email' => $data['email']
            ];

            if (!empty($data['password'])) {
                $updateData['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
            }

            if ($this->user->update($id, $updateData)) {
                $this->response(['status' => 'success', 'message' => 'Usuário atualizado com sucesso.'], RestController::HTTP_OK);
            } else {
                $this->response(['status' => 'error', 'message' => 'Ocorreu um erro no servidor ao atualizar o usuário.'], RestController::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
    }

    public function index_delete($id)
    {
        $this->verify_token();
        
        if (!$this->user->get($id)) {
            $this->response([
                'status' => 'error',
                'message' => 'Usuário não encontrado.'
            ], RestController::HTTP_NOT_FOUND);
            return;
        }

        if ($this->user->delete($id)) {
            $this->response([
                'status' => 'success',
                'message' => 'Usuário deletado com sucesso.'
            ], RestController::HTTP_OK);
        } else {
            $this->response([
                'status' => 'error',
                'message' => 'Ocorreu um erro no servidor ao deletar o usuário.'
            ], RestController::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
