<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use chriskacerguis\RestServer\RestController;

class Users extends RestController {

    function __construct() {
        parent::__construct();
        $this->load->model('User_model', 'user');
        $this->load->library('form_validation');
    }

    public function index_get($id = null) {
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

    public function index_post() {
        $this->form_validation->set_rules('name', 'Nome', 'trim|required');
        $this->form_validation->set_rules('email', 'Email', 'trim|required|valid_email|is_unique[users.email]');
        
        $this->form_validation->set_data($this->post());

        // Executa a validação
        if ($this->form_validation->run() == FALSE) {
            $this->response([
                'status' => 'error',
                'message' => 'Os dados fornecidos são inválidos.',
                'errors' => $this->form_validation->error_array()
            ], RestController::HTTP_BAD_REQUEST);
        } else {
            $data = [
                'name' => $this->post('name'),
                'email' => $this->post('email')
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

    public function index_put($id) {
        $data = $this->put();

        $existing_user = $this->user->get($id);
        if (!$existing_user) {
            $this->response([
                'status' => 'error',
                'message' => 'Usuário não encontrado.'
            ], RestController::HTTP_NOT_FOUND);
            return;
        }

        $is_unique_rule = '';
        if (isset($data['email']) && $data['email'] != $existing_user['email']) {
            $is_unique_rule = '|is_unique[users.email]';
        }

        $this->form_validation->set_rules('name', 'Nome', 'trim|required');
        $this->form_validation->set_rules('email', 'Email', 'trim|required|valid_email' . $is_unique_rule);
        
        $this->form_validation->set_data($data);
        
        if ($this->form_validation->run() == FALSE) {
            $this->response([
                'status' => 'error',
                'message' => 'Os dados fornecidos são inválidos.',
                'errors' => $this->form_validation->error_array()
            ], RestController::HTTP_BAD_REQUEST);
        } else {
            if ($this->user->update($id, $data)) {
                $this->response([
                    'status' => 'success',
                    'message' => 'Usuário atualizado com sucesso.'
                ], RestController::HTTP_OK);
            } else {
                $this->response([
                    'status' => 'error',
                    'message' => 'Ocorreu um erro no servidor ao atualizar o usuário.'
                ], RestController::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
    }

    public function index_delete($id) {
        if (!$this->user->get($id)) {
            $this->response([
                'status' => 'error',
                'message' => 'Usuário não encontrado.'
            ], RestController::HTTP_NOT_FOUND);
            return;
        }

        if ($this->user->delete($id)) {
            // Sucesso na deleção
            $this->response([
                'status' => 'success',
                'message' => 'Usuário deletado com sucesso.'
            ], RestController::HTTP_OK);
        } else {
            // Erro de servidor
            $this->response([
                'status' => 'error',
                'message' => 'Ocorreu um erro no servidor ao deletar o usuário.'
            ], RestController::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}