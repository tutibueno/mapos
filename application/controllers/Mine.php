<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Mine extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Conecte_model');
    }

    public function index()
    {
        $this->load->view('conecte/login');
    }

    public function sair()
    {
        $this->session->sess_destroy();
        redirect('mine');
    }

    public function login()
    {
        $this->load->library('form_validation');
        $this->form_validation->set_rules('email', 'Email', 'valid_email|required|trim');
        $this->form_validation->set_rules('documento', 'Documento', 'required|trim');
        $ajax = $this->input->get('ajax');
        if ($this->form_validation->run() == false) {
            if ($ajax == true) {
                $json = ['result' => false];
                echo json_encode($json);
            } else {
                $this->session->set_flashdata('error', 'Os dados de acesso estão incorretos.');
                redirect('mine');
            }
        } else {
            $email = $this->input->post('email');
            $documento = $this->input->post('documento');

            $this->db->where('email', $email);
            $this->db->where('documento', $documento);
            $this->db->limit(1);
            $cliente = $this->db->get('clientes');
            if ($cliente->num_rows() > 0) {
                $cliente = $cliente->row();
                $dados = ['nome' => $cliente->nomeCliente, 'cliente_id' => $cliente->idClientes, 'email' => $cliente->email, 'conectado' => true, 'isCliente' => true];
                $this->session->set_userdata($dados);

                if ($ajax == true) {
                    $json = ['result' => true];
                    echo json_encode($json);
                } else {
                    redirect(site_url() . '/mine');
                }
            } else {
                if ($ajax == true) {
                    $json = ['result' => false];
                    echo json_encode($json);
                } else {
                    $this->session->set_flashdata('error', 'Os dados de acesso estão incorretos.');
                    redirect(site_url() . '/mine');
                }
            }
        }
    }

    public function painel()
    {
        if (!session_id() || !$this->session->userdata('conectado')) {
            redirect('mine');
        }

        $data['menuPainel'] = 'painel';
        $data['compras'] = $this->Conecte_model->getLastCompras($this->session->userdata('cliente_id'));
        $data['os'] = $this->Conecte_model->getLastOs($this->session->userdata('cliente_id'));
        $data['output'] = 'conecte/painel';
        $this->load->view('conecte/template', $data);
    }

    public function conta()
    {
        if (!session_id() || !$this->session->userdata('conectado')) {
            redirect('mine');
        }

        $data['menuConta'] = 'conta';
        $data['result'] = $this->Conecte_model->getDados();

        $data['output'] = 'conecte/conta';
        $this->load->view('conecte/template', $data);
    }

    public function editarDados($id = null)
    {
        if (!session_id() || !$this->session->userdata('conectado')) {
            redirect('mine');
        }

        $data['menuConta'] = 'conta';

        $this->load->library('form_validation');
        $data['custom_error'] = '';

        if ($this->form_validation->run('clientes') == false) {
            $this->data['custom_error'] = (validation_errors() ? '<div class="form_error">' . validation_errors() . '</div>' : false);
        } else {
            $data = [
                'nomeCliente' => $this->input->post('nomeCliente'),
                'documento' => $this->input->post('documento'),
                'telefone' => $this->input->post('telefone'),
                'celular' => $this->input->post('celular'),
                'email' => $this->input->post('email'),
                'rua' => $this->input->post('rua'),
                'numero' => $this->input->post('numero'),
                'complemento' => $this->input->post('complemento'),
                'bairro' => $this->input->post('bairro'),
                'cidade' => $this->input->post('cidade'),
                'estado' => $this->input->post('estado'),
                'cep' => $this->input->post('cep'),
            ];

            if ($this->Conecte_model->edit('clientes', $data, 'idClientes', $this->input->post('idClientes')) == true) {
                $this->session->set_flashdata('success', 'Dados editados com sucesso!');
                redirect(base_url() . 'index.php/mine/conta');
            } else {
            }
        }

        $data['result'] = $this->Conecte_model->getDados();

        $data['output'] = 'conecte/editar_dados';
        $this->load->view('conecte/template', $data);
    }

    public function compras()
    {
        if (!session_id() || !$this->session->userdata('conectado')) {
            redirect('mine');
        }

        $data['menuVendas'] = 'vendas';
        $this->load->library('pagination');

        $config['base_url'] = base_url() . 'index.php/mine/compras/';
        $config['total_rows'] = $this->Conecte_model->count('vendas', $this->session->userdata('cliente_id'));
        $config['per_page'] = 10;
        $config['next_link'] = 'Próxima';
        $config['prev_link'] = 'Anterior';
        $config['full_tag_open'] = '<div class="pagination alternate"><ul>';
        $config['full_tag_close'] = '</ul></div>';
        $config['num_tag_open'] = '<li>';
        $config['num_tag_close'] = '</li>';
        $config['cur_tag_open'] = '<li><a style="color: #2D335B"><b>';
        $config['cur_tag_close'] = '</b></a></li>';
        $config['prev_tag_open'] = '<li>';
        $config['prev_tag_close'] = '</li>';
        $config['next_tag_open'] = '<li>';
        $config['next_tag_close'] = '</li>';
        $config['first_link'] = 'Primeira';
        $config['last_link'] = 'Última';
        $config['first_tag_open'] = '<li>';
        $config['first_tag_close'] = '</li>';
        $config['last_tag_open'] = '<li>';
        $config['last_tag_close'] = '</li>';

        $this->pagination->initialize($config);

        $data['results'] = $this->Conecte_model->getCompras('vendas', '*', '', $config['per_page'], $this->uri->segment(3), '', '', $this->session->userdata('cliente_id'));

        $data['output'] = 'conecte/compras';
        $this->load->view('conecte/template', $data);
    }

    public function cobrancas()
    {
        if (!session_id() || !$this->session->userdata('conectado')) {
            redirect('mine');
        }

        $this->load->library('pagination');
        $this->load->config('payment_gateways');

        $data['menuCobrancas'] = 'cobrancas';

        $config['base_url'] = base_url() . 'index.php/mine/cobrancas/';
        $config['total_rows'] = $this->Conecte_model->count('cobrancas', $this->session->userdata('cliente_id'));
        $config['per_page'] = 10;
        $config['next_link'] = 'Próxima';
        $config['prev_link'] = 'Anterior';
        $config['full_tag_open'] = '<div class="pagination alternate"><ul>';
        $config['full_tag_close'] = '</ul></div>';
        $config['num_tag_open'] = '<li>';
        $config['num_tag_close'] = '</li>';
        $config['cur_tag_open'] = '<li><a style="color: #2D335B"><b>';
        $config['cur_tag_close'] = '</b></a></li>';
        $config['prev_tag_open'] = '<li>';
        $config['prev_tag_close'] = '</li>';
        $config['next_tag_open'] = '<li>';
        $config['next_tag_close'] = '</li>';
        $config['first_link'] = 'Primeira';
        $config['last_link'] = 'Última';
        $config['first_tag_open'] = '<li>';
        $config['first_tag_close'] = '</li>';
        $config['last_tag_open'] = '<li>';
        $config['last_tag_close'] = '</li>';

        $this->pagination->initialize($config);

        $data['results'] = $this->Conecte_model->getCobrancas('cobrancas', '*', '', $config['per_page'], $this->uri->segment(3), '', '', $this->session->userdata('cliente_id'));
        $data['output'] = 'conecte/cobrancas';

        $this->load->view('conecte/template', $data);
    }

    public function atualizarcobranca($id = null)
    {
        if (!session_id() || !$this->session->userdata('conectado')) {
            redirect('mine');
        }

        if (!$this->uri->segment(3) || !is_numeric($this->uri->segment(3))) {
            $this->session->set_flashdata('error', 'Item não pode ser encontrado, parâmetro não foi passado corretamente.');
            redirect('mapos');
        }

        if (!$this->permission->checkPermission($this->session->userdata('permissao'), 'eCobranca')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para atualizar cobrança.');
            redirect(base_url());
        }

        $this->load->model('cobrancas_model');
        $this->cobrancas_model->atualizarStatus($this->uri->segment(3));

        redirect(site_url('mine/cobrancas/'));
    }

    public function enviarcobranca()
    {
        if (!session_id() || !$this->session->userdata('conectado')) {
            redirect('mine');
        }

        if (!$this->uri->segment(3) || !is_numeric($this->uri->segment(3))) {
            $this->session->set_flashdata('error', 'Item não pode ser encontrado, parâmetro não foi passado corretamente.');
            redirect('mapos');
        }

        if (!$this->permission->checkPermission($this->session->userdata('permissao'), 'eCobranca')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para atualizar cobrança.');
            redirect(base_url());
        }

        $this->load->model('cobrancas_model');
        $this->cobrancas_model->enviarEmail($this->uri->segment(3));
        $this->session->set_flashdata('success', 'Email adicionado na fila.');

        redirect(site_url('mine/cobrancas/'));
    }

    public function os()
    {
        if (!session_id() || !$this->session->userdata('conectado')) {
            redirect('mine');
        }

        $data['menuOs'] = 'os';
        $this->load->library('pagination');

        $config['base_url'] = base_url() . 'index.php/mine/os/';
        $config['total_rows'] = $this->Conecte_model->count('os', $this->session->userdata('cliente_id'));
        $config['per_page'] = 10;
        $config['next_link'] = 'Próxima';
        $config['prev_link'] = 'Anterior';
        $config['full_tag_open'] = '<div class="pagination alternate"><ul>';
        $config['full_tag_close'] = '</ul></div>';
        $config['num_tag_open'] = '<li>';
        $config['num_tag_close'] = '</li>';
        $config['cur_tag_open'] = '<li><a style="color: #2D335B"><b>';
        $config['cur_tag_close'] = '</b></a></li>';
        $config['prev_tag_open'] = '<li>';
        $config['prev_tag_close'] = '</li>';
        $config['next_tag_open'] = '<li>';
        $config['next_tag_close'] = '</li>';
        $config['first_link'] = 'Primeira';
        $config['last_link'] = 'Última';
        $config['first_tag_open'] = '<li>';
        $config['first_tag_close'] = '</li>';
        $config['last_tag_open'] = '<li>';
        $config['last_tag_close'] = '</li>';

        $this->pagination->initialize($config);

        $data['results'] = $this->Conecte_model->getOs('os', '*', '', $config['per_page'], $this->uri->segment(3), '', '', $this->session->userdata('cliente_id'));

        $data['output'] = 'conecte/os';
        $this->load->view('conecte/template', $data);
    }

    public function visualizarOs($id = null)
    {
        if (!session_id() || !$this->session->userdata('conectado')) {
            redirect('mine');
        }

        $data['menuOs'] = 'os';
        $this->data['custom_error'] = '';
        $this->load->model('mapos_model');
        $this->load->model('os_model');

        $data['result'] = $this->os_model->getById($this->uri->segment(3));
        $data['produtos'] = $this->os_model->getProdutos($this->uri->segment(3));
        $data['servicos'] = $this->os_model->getServicos($this->uri->segment(3));
        $data['emitente'] = $this->mapos_model->getEmitente();
        $data['anotacoes'] = $this->os_model->getAnotacoes($this->uri->segment(3));
        $data['anexos'] = $this->os_model->getAnexos($this->uri->segment(3));

        if ($data['result']->idClientes != $this->session->userdata('cliente_id')) {
            $this->session->set_flashdata('error', 'Esta OS não pertence ao cliente logado.');
            redirect('mine/painel');
        }

        $data['output'] = 'conecte/visualizar_os';
        $this->load->view('conecte/template', $data);
    }

    public function gerarPagamentoGerencianetBoleto()
    {
        $json = ['code' => 4001, 'error' => 'Erro interno' , 'errorDescription' => 'Cobrança não pode ser gerada pelo lado do cliente'];
        print_r(json_encode($json));

        return;
    }

    public function gerarPagamentoGerencianetLink()
    {
        $json = ['code' => 4001, 'error' => 'Erro interno' , 'errorDescription' => 'Cobrança não pode ser gerada pelo lado do cliente'];
        print_r(json_encode($json));

        return;
    }

    public function imprimirOs($id = null)
    {
        if (!session_id() || !$this->session->userdata('conectado')) {
            redirect('mine');
        }

        $data['menuOs'] = 'os';
        $this->data['custom_error'] = '';
        $this->load->model('mapos_model');
        $this->load->model('os_model');
        $data['result'] = $this->os_model->getById($this->uri->segment(3));
        $data['produtos'] = $this->os_model->getProdutos($this->uri->segment(3));
        $data['servicos'] = $this->os_model->getServicos($this->uri->segment(3));
        $data['emitente'] = $this->mapos_model->getEmitente();
        $data['anotacoes'] = $this->os_model->getAnotacoes($this->uri->segment(3));
        $data['anexos'] = $this->os_model->getAnexos($this->uri->segment(3));

        if ($data['result']->idClientes != $this->session->userdata('cliente_id')) {
            $this->session->set_flashdata('error', 'Esta OS não pertence ao cliente logado.');
            redirect('mine/painel');
        }

        $this->load->view('conecte/imprimirOs', $data);
    }

    public function visualizarCompra($id = null)
    {
        if (!session_id() || !$this->session->userdata('conectado')) {
            redirect('mine');
        }

        $data['menuVendas'] = 'vendas';
        $data['custom_error'] = '';
        $this->load->model('mapos_model');
        $this->load->model('vendas_model');

        $data['result'] = $this->vendas_model->getById($this->uri->segment(3));
        $data['produtos'] = $this->vendas_model->getProdutos($this->uri->segment(3));
        $data['emitente'] = $this->mapos_model->getEmitente();

        if ($data['result']->clientes_id != $this->session->userdata('cliente_id')) {
            $this->session->set_flashdata('error', 'Esta OS não pertence ao cliente logado.');
            redirect('mine/painel');
        }

        $data['output'] = 'conecte/visualizar_compra';

        $this->load->view('conecte/template', $data);
    }

    public function imprimirCompra($id = null)
    {
        if (!session_id() || !$this->session->userdata('conectado')) {
            redirect('mine');
        }

        $data['menuVendas'] = 'vendas';
        $data['custom_error'] = '';
        $this->load->model('mapos_model');
        $this->load->model('vendas_model');
        $data['result'] = $this->vendas_model->getById($this->uri->segment(3));
        $data['produtos'] = $this->vendas_model->getProdutos($this->uri->segment(3));
        $data['emitente'] = $this->mapos_model->getEmitente();

        if ($data['result']->clientes_id != $this->session->userdata('cliente_id')) {
            $this->session->set_flashdata('error', 'Esta OS não pertence ao cliente logado.');
            redirect('mine/painel');
        }

        $this->load->view('conecte/imprimirVenda', $data);
    }

    public function minha_ordem_de_servico($y = null, $when = null)
    {
        if (($y != null) && (is_numeric($y))) {

            // Do not forget this number -> 44023
            // function sending => y = (7653 * ID) + 44023
            // function recieving => x = (y - 44023) / 7653

            // Example ID = 2 | y = 59329

            $y = intval($y);
            $id = ($y - 44023) / 7653;

            $data['menuOs'] = 'os';
            $this->data['custom_error'] = '';
            $this->load->model('mapos_model');
            $this->load->model('os_model');
            $data['result'] = $this->os_model->getById($id);
            if ($data['result'] == null) {
                // Resposta em caso de não encontrar a ordem de serviço
                //$this->load->view('conecte/login');
            } else {
                $data['produtos'] = $this->os_model->getProdutos($id);
                $data['servicos'] = $this->os_model->getServicos($id);
                $data['anotacoes'] = $this->os_model->getAnotacoes($id);
                $data['anexos'] = $this->os_model->getAnexos($id);
                $data['emitente'] = $this->mapos_model->getEmitente();

                $this->load->view('conecte/minha_os', $data);
            }
        } else {
            // Resposta em caso de não encontrar a ordem de serviço
            //$this->load->view('conecte/');
        }
    }

    // Cadastro de OS pelo cliente
    public function adicionarOs()
    {
        $this->load->library('form_validation');
        
        $this->form_validation->set_rules('numeroSerie', 'Numero de Série', 'required');
        $this->form_validation->set_rules('descricaoProduto', 'Descrição', 'required');
        $this->form_validation->set_rules('defeito', 'Defeito');
        $this->form_validation->set_rules('observacoes', 'Observações');
        
        if ($this->form_validation->run() == false) {
            $this->data['custom_error'] = (validation_errors() ? true : false);
        } else {
            $id = null;
            $this->db->where('idUsuarios', 1);
            $usuario = $this->db->get('usuarios')->row();
            if ($usuario->usuarios_id == null) {
                $this->db->where('situacao', 1);
                $this->db->limit(1);
                $usuario = $this->db->get('usuarios')->row();

                if ($usuario->idUsuarios == null) {
                    $this->session->set_flashdata('error', 'Ocorreu um erro ao cadastrar a ordem de serviço, por favor contate o administrador do sistema.');
                    redirect('mine/os');
                } else {
                    $id = $usuario->idUsuarios;
                }
            } else {
                $id = $usuario->usuarios_id;
            }

            $data = [
                'dataInicial' => date('Y-m-d'),
                'clientes_id' => $this->session->userdata('cliente_id'), //set_value('idCliente'),
                'usuarios_id' => $id, //set_value('idUsuario'),
                'dataFinal' => date('Y-m-d'),
                'numeroSerie' => $this->input->post('numeroSerie'),
                'descricaoProduto' => $this->input->post('descricaoProduto'),
                'defeito' => $this->input->post('defeito'),
                'status' => 'Aberto',
                'observacoes' => set_value('observacoes'),
                'faturado' => 0,
            ];

            if (is_numeric($id = $this->Conecte_model->add('os', $data, true))) {
                $this->load->model('mapos_model');
                $this->load->model('usuarios_model');

                $idOs = $id;
                $os = $this->Conecte_model->getById($id);

                $remetentes = [];
                $usuarios = $this->usuarios_model->getAll();

                foreach ($usuarios as $usuario) {
                    array_push($remetentes, $usuario->email);
                }
                array_push($remetentes, $os->email);

                $this->enviarOsPorEmail($idOs, $remetentes, 'Nova Ordem de Serviço #'.$idOs.' - Criada pelo Cliente');
                $this->session->set_flashdata('success', 'OS adicionada com sucesso!');
                redirect('mine/detalhesOs/' . $id);
            } else {
                $this->data['custom_error'] = '<div class="form_error"><p>Ocorreu um erro.</p></div>';
            }
        }

        $data['output'] = 'conecte/adicionarOs';
        $this->load->view('conecte/template', $data);
    }

    public function detalhesOs($id = null)
    {
        if (is_numeric($id) && $id != null) {
            $this->load->model('mapos_model');
            $this->load->model('os_model');

            $this->data['result'] = $this->os_model->getById($id);
            $this->data['produtos'] = $this->os_model->getProdutos($id);
            $this->data['servicos'] = $this->os_model->getServicos($id);
            $this->data['anexos'] = $this->os_model->getAnexos($id);

            if ($this->data['result']->idClientes != $this->session->userdata('cliente_id')) {
                $this->session->set_flashdata('error', 'Esta OS não pertence ao cliente logado.');
                redirect('mine/painel');
            }

            $this->data['output'] = 'conecte/detalhes_os';
            $this->load->view('conecte/template', $this->data);
        } else {
            echo "teste";
        }
    }

    // método para clientes se cadastratem
    public function cadastrar()
    {
        $this->load->model('clientes_model', '', true);
        $this->load->library('form_validation');
        $this->data['custom_error'] = '';
        $id = 0;

        if ($this->form_validation->run('clientes') == false) {
            $this->data['custom_error'] = (validation_errors() ? '<div class="form_error">' . validation_errors() . '</div>' : false);
        } else {
            $data = [
                'nomeCliente' => set_value('nomeCliente'),
                'documento' => set_value('documento'),
                'telefone' => set_value('telefone'),
                'celular' => $this->input->post('celular'),
                'email' => set_value('email'),
                'rua' => set_value('rua'),
                'complemento' => set_value('complemento'),
                'numero' => set_value('numero'),
                'bairro' => set_value('bairro'),
                'cidade' => set_value('cidade'),
                'estado' => set_value('estado'),
                'cep' => set_value('cep'),
                'dataCadastro' => date('Y-m-d'),
            ];

            $id = $this->clientes_model->add('clientes', $data);

            if ($id > 0) {
                $this->enviarEmailBoasVindas($id);
                $this->enviarEmailTecnicoNotificaClienteNovo($id);
                $this->session->set_flashdata('success', 'Cadastro realizado com sucesso! <br> Um e-mail de boas vindas será enviado para '.$data['email']);
                redirect(base_url() . 'index.php/mine');
            } else {
                $this->session->set_flashdata('error', 'Falha ao realizar cadastro!');
            }
        }
        $data = '';
        $this->load->view('conecte/cadastrar', $data);
    }

    public function downloadanexo($id = null)
    {
        if ($id != null && is_numeric($id)) {
            $this->db->where('idAnexos', $id);
            $file = $this->db->get('anexos', 1)->row();

            $this->load->library('zip');
            $path = $file->path;
            $this->zip->read_file($path . '/' . $file->anexo);
            $this->zip->download('file' . date('d-m-Y-H.i.s') . '.zip');
        }
    }

    private function enviarOsPorEmail($idOs, $remetentes, $assunto)
    {
        $dados = [];

        $this->load->model('mapos_model');
        $this->load->model('os_model');
        $dados['result'] = $this->os_model->getById($idOs);
        if (!isset($dados['result']->email)) {
            return false;
        }

        $dados['produtos'] = $this->os_model->getProdutos($idOs);
        $dados['servicos'] = $this->os_model->getServicos($idOs);
        $data['anotacoes'] = $this->os_model->getAnotacoes($idOs);
        $data['anexos'] = $this->os_model->getAnexos($idOs);
        $dados['emitente'] = $this->mapos_model->getEmitente();

        $emitente = $dados['emitente'][0]->email;
        $emitenteNome = $dados['emitente'][0]->nome;
        if (!isset($emitente)) {
            return false;
        }

        $html = $this->load->view('os/emails/os', $dados, true);

        $this->load->model('email_model');

        $remetentes = array_unique($remetentes);
        foreach ($remetentes as $remetente) {
            $headers = [
                'From' => "$emitenteNome <$emitente>",
                'Subject' => $assunto,
                'Return-Path' => ''
            ];
            $email = [
                'to' => $remetente,
                'message' => $html,
                'status' => 'pending',
                'date' => date('Y-m-d H:i:s'),
                'headers' => serialize($headers),
            ];
            $this->email_model->add('email_queue', $email);
        }

        return true;
    }

    private function enviarEmailBoasVindas($id)
    {
        $dados = [];
        $this->load->model('mapos_model');
        $this->load->model('clientes_model', '', true);

        $dados['emitente'] = $this->mapos_model->getEmitente();
        $dados['cliente'] = $this->clientes_model->getById($id);

        $emitente = $dados['emitente'][0]->email;
        $emitenteNome = $dados['emitente'][0]->nome;
        $remetente = $dados['cliente']->email;
        $assunto = 'Bem-vindo!';

        $html = $this->load->view('os/emails/clientenovo', $dados, true);

        $this->load->model('email_model');

        $headers = [
            'From' => "$emitenteNome <$emitente>",
            'Subject' => $assunto,
            'Return-Path' => ''
        ];
        $email = [
            'to' => $remetente,
            'message' => $html,
            'status' => 'pending',
            'date' => date('Y-m-d H:i:s'),
            'headers' => serialize($headers),
        ];

        return $this->email_model->add('email_queue', $email);
    }

    private function enviarEmailTecnicoNotificaClienteNovo($id)
    {
        $dados = [];
        $this->load->model('mapos_model');
        $this->load->model('clientes_model', '', true);
        $this->load->model('usuarios_model');

        $dados['emitente'] = $this->mapos_model->getEmitente();
        $dados['cliente'] = $this->clientes_model->getById($id);

        $emitente = $dados['emitente'][0]->email;
        $emitenteNome = $dados['emitente'][0]->nome;
        $assunto = 'Novo Cliente Cadastrado no Sistema';

        $usuarios = [];
        $usuarios = $this->usuarios_model->getAll();

        foreach ($usuarios as $usuario) {
            $dados['usuario'] = $usuario;
            $html = $this->load->view('os/emails/clientenovonotifica', $dados, true);
            $headers = [
                'From' => "$emitenteNome <$emitente>",
                'Subject' => $assunto,
                'Return-Path' => ''
            ];
            $email = [
                'to' => $usuario->email,
                'message' => $html,
                'status' => 'pending',
                'date' => date('Y-m-d H:i:s'),
                'headers' => serialize($headers),
            ];
            $this->email_model->add('email_queue', $email);
        }
    }
}

/* End of file conecte.php */
/* Location: ./application/controllers/conecte.php */
