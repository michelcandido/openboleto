<?php
/**
 * OpenBoleto - Geração de boletos bancários em PHP
 *
 * Classe base para geração de boletos bancários
 *
 * LICENSE: The MIT License (MIT)
 *
 * Copyright (C) 2013 Estrada Virtual
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this
 * software and associated documentation files (the "Software"), to deal in the Software
 * without restriction, including without limitation the rights to use, copy, modify,
 * merge, publish, distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to the following
 * conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies
 * or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A
 * PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
 * SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 * @package    OpenBoleto
 * @author     Daniel Garajau <http://github.com/kriansa>
 * @copyright  Copyright (c) 2013 Estrada Virtual (http://www.estradavirtual.com.br)
 * @license    MIT License
 * @version    0.1
 */

namespace OpenBoleto;
use DateTime;
use Exception;
use OpenBoleto\Agente;

abstract class BoletoAbstract
{
    /**
     * Moedas disponíveis
     */
    const MOEDA_REAL = 9;

    /**
     * @var array Nome espécie das moedas
     */
    protected static $especie = array(
        self::MOEDA_REAL => 'REAL'
    );

    /**
     * Código do banco
     * @var string
     */
    protected $codigoBanco;

    /**
     * Moeda
     * @var int
     */
    protected $moeda = self::MOEDA_REAL;

    /**
     * Valor total do boleto
     * @var float
     */
    protected $valor;

    /**
     * Valor para pagamento mínimo em boletos de contra apresentação
     * @var float
     */
    protected $pagamentoMinimo;

    /**
     * Data do documento
     * @var \DateTime
     */
    protected $dataDocumento;

    /**
     * Data de emissão
     * @var \DateTime
     */
    protected $dataProcessamento;

    /**
     * Data de vencimento
     * @var \DateTime
     */
    protected $dataVencimento;

    /**
     * Define se o boleto é para contra-apresentação
     * @var bool
     */
    protected $contraApresentacao = false;

    /**
     * Campo de aceite
     * @var string
     */
    protected $aceite = 'N';

    /**
     * Espécie do documento, geralmente DM (Duplicata Mercantil)
     * @var string
     */
    protected $especieDoc;

    /**
     * Número do documento
     * @var int
     */
    protected $numeroDocumento;

    /**
     * Campo de uso do banco no boleto
     * @var string
     */
    protected $usoBanco;

    /**
     * Agência
     * @var int
     */
    protected $agencia;

    /**
     * Dígito da agência
     * @var string|int
     */
    protected $agenciaDv;

    /**
     * Conta
     * @var int
     */
    protected $conta;

    /**
     * Dígito da conta
     * @var int
     */
    protected $contaDv;

    /**
     * Modalidade de cobrança do cliente, geralmente Cobrança Simples ou Registrada
     * @var int
     */
    protected $carteira;

    /**
     * Define as carteiras disponíveis para cada banco
     * @var array
     */
    protected $carteiras = array();

    /**
     * Define as carteiras disponíveis para cada banco
     * @var array
     */
    protected $carteirasNomes = array();

    /**
     * Identificador único do boleto
     * @var int
     */
    protected $nossoNumero;

    /**
     * Entidade cedente (quem emite o boleto)
     * @var \OpenBoleto\Agente
     */
    protected $cedente;
    
    /**
     * Entidade sacada (de quem se cobra o boleto)
     * @var \OpenBoleto\Agente
     */
    protected $sacado;

    /**
     * Entidade sacador avalista
     * @var \OpenBoleto\Agente
     */
    protected $sacadorAvalista;

    /**
     * Array com as linhas do demonstrativo (descrição do pagamento)
     * @var array
     */
    protected $descricaoDemonstrativo;

    /**
     * Linha de local de pagamento
     * @var string
     */
    protected $localPagamento = 'Pagável em qualquer agência bancária até o vencimento.';

    /**
     * Array com as linhas de instruções
     * @var array
     */
    protected $instrucoes = array('Pagar até a data do vencimento.');

    /**
     * Nome do arquivo de template a ser usado
     * @var string
     */
    protected $layout = 'default.phtml';

    /**
     * Pasta de localização de views
     * @var string
     */
    protected $viewPath = '../resources/views';

    /**
     * Pasta de localização das imagens, pode ser na web, ex: http://seusite.com/imagens
     * @var string
     */
    protected $imagePath = './images';

    /**
     * Localização do logotipo da empresa
     * @var string
     */
    protected $logoPath;

    /**
     * Localização do logotipo do banco, referente ao diretório de imagens
     * @var string
     */
    protected $logoBanco;

    /**
     * Construtor
     *
     * @param array $params Parâmetros iniciais para construção do objeto
     */
    public function  __construct($params = array())
    {
        foreach ($params as $param => $value)
        {
            if (method_exists($this, 'set' . $param)) {
                $this->{'set' . $param}($value);
            }
        }

        // Marca a data de emissão para hoje, caso não especificada
        if (!$this->getDataDocumento()) {
            $this->setDataDocumento(new DateTime());
        }

        // Marca a data de processamento para hoje, caso não especificada
        if (!$this->getDataProcessamento()) {
            $this->setDataProcessamento(new DateTime());
        }

        // Marca a data de vencimento para daqui a 5 dias, caso não especificada
        if (!$this->getDataVencimento()) {
            $this->setDataVencimento(new DateTime(date('Y-m-d', strtotime('+5 days'))));
        }
    }

    /**
     * Define a agência
     *
     * @param int $agencia
     */
    public function setAgencia($agencia)
    {
        $this->agencia = $agencia;
    }

    /**
     * Retorna a agência
     *
     * @return int
     */
    public function getAgencia()
    {
        return $this->agencia;
    }

    /**
     * Define o dígito da agência
     *
     * @param string|int $agenciaDv
     */
    public function setAgenciaDv($agenciaDv)
    {
        $this->agenciaDv = $agenciaDv;
    }

    /**
     * Retorna o dígito da agência
     *
     * @return string|int
     */
    public function getAgenciaDv()
    {
        return $this->agenciaDv;
    }

    /**
     * Define o código da carteira (Com ou sem registro)
     *
     * @param string $carteira
     * @throws \Exception
     */
    public function setCarteira($carteira)
    {
        if (!in_array($carteira, $this->getCarteiras())) {
            throw new Exception("Carteira não disponível!");
        }

        $this->carteira = $carteira;
    }

    /**
     * Retorna o código da carteira (Com ou sem registro)
     *
     * @return string
     */
    public function getCarteira()
    {
        return $this->carteira;
    }

    /**
     * Retorna as carteiras disponíveis para este banco
     *
     * @return array
     */
    public function getCarteiras()
    {
        return $this->carteiras;
    }

    /**
     * Define a entidade cedente
     *
     * @param \OpenBoleto\Agente $cedente
     */
    public function setCedente(Agente $cedente)
    {
        $this->cedente = $cedente;
    }

    /**
     * Retorna a entidade cedente
     *
     * @return \OpenBoleto\Agente
     */
    public function getCedente()
    {
        return $this->cedente;
    }

    /**
     * Retorna o código do banco
     *
     * @return string
     */
    public function getCodigoBanco()
    {
        return $this->codigoBanco;
    }

    /**
     * Define o número da conta
     *
     * @param int $conta
     */
    public function setConta($conta)
    {
        $this->conta = $conta;
    }

    /**
     * Retorna o número da conta
     *
     * @return int
     */
    public function getConta()
    {
        return $this->conta;
    }

    /**
     * Define o dígito verificador da conta
     *
     * @param int $contaDv
     */
    public function setContaDv($contaDv)
    {
        $this->contaDv = $contaDv;
    }

    /**
     * Retorna o dígito verificador da conta
     *
     * @return int
     */
    public function getContaDv()
    {
        return $this->contaDv;
    }

    /**
     * Define a data de vencimento
     *
     * @param \DateTime $dataVencimento
     */
    public function setDataVencimento(DateTime $dataVencimento)
    {
        $this->dataVencimento = $dataVencimento;
    }

    /**
     * Retorna a data de vencimento
     *
     * @return \DateTime
     */
    public function getDataVencimento()
    {
        return $this->dataVencimento;
    }

    /**
     * Define se o boleto é Contra-apresentação, ou seja, a data de vencimento e o valor são deixados em branco
     * É sugerido que se use o campo pagamento mínimo ($this->setPagamentoMinimo())
     *
     * @param boolean $contraApresentacao
     */
    public function setContraApresentacao($contraApresentacao)
    {
        $this->contraApresentacao = $contraApresentacao;
    }

    /**
     * Retorna se o boleto é Contra-apresentação, ou seja, a data de vencimento é indefinida
     *
     * @return boolean
     */
    public function getContraApresentacao()
    {
        return $this->contraApresentacao;
    }

    /**
     * Define a data do documento
     *
     * @param \DateTime $dataDocumento
     */
    public function setDataDocumento(DateTime $dataDocumento)
    {
        $this->dataDocumento = $dataDocumento;
    }

    /**
     * Retorna a data do documento
     *
     * @return \DateTime
     */
    public function getDataDocumento()
    {
        return $this->dataDocumento;
    }

    /**
     * Define o campo aceite
     *
     * @param string $aceite
     */
    public function setAceite($aceite)
    {
        $this->aceite = $aceite;
    }

    /**
     * Retorna o campo aceite
     *
     * @return string
     */
    public function getAceite()
    {
        return $this->aceite;
    }

    /**
     * Define o campo Espécie Doc, geralmente DM (Duplicata Mercantil)
     *
     * @param string $especieDoc
     */
    public function setEspecieDoc($especieDoc)
    {
        $this->especieDoc = $especieDoc;
    }

    /**
     * Retorna o campo Espécie Doc, geralmente DM (Duplicata Mercantil)
     *
     * @return string
     */
    public function getEspecieDoc()
    {
        return $this->especieDoc;
    }

    /**
     * Define o campo Número do documento
     *
     * @param int $numeroDocumento
     */
    public function setNumeroDocumento($numeroDocumento)
    {
        $this->numeroDocumento = $numeroDocumento;
    }

    /**
     * Retorna o campo Número do documento
     *
     * @return int
     */
    public function getNumeroDocumento()
    {
        return $this->numeroDocumento;
    }

    /**
     * Define o campo Uso do banco
     *
     * @param string $usoBanco
     */
    public function setUsoBanco($usoBanco)
    {
        $this->usoBanco = $usoBanco;
    }

    /**
     * Retorna o campo Uso do banco
     *
     * @return string
     */
    public function getUsoBanco()
    {
        return $this->usoBanco;
    }

    /**
     * Define a data de geração do boleto
     *
     * @param \DateTime $dataProcessamento
     */
    public function setDataProcessamento(DateTime $dataProcessamento)
    {
        $this->dataProcessamento = $dataProcessamento;
    }

    /**
     * Retorna a data de geração do boleto
     *
     * @return \DateTime
     */
    public function getDataProcessamento()
    {
        return $this->dataProcessamento;
    }

    /**
     * Define a localização da pasta de imagens
     *
     * @param string $imagePath
     */
    public function setImagePath($imagePath)
    {
        $this->imagePath = $imagePath;
    }

    /**
     * Retorna a localização da pasta de imagens
     *
     * @return string
     */
    public function getImagePath()
    {
        return $this->imagePath;
    }

    /**
     * Define um array com instruções (máximo 8) para pagamento
     *
     * @param array $instrucoes
     */
    public function setInstrucoes($instrucoes)
    {
        $this->instrucoes = $instrucoes;
    }

    /**
     * Retorna um array com instruções (máximo 8) para pagamento
     *
     * @return array
     */
    public function getInstrucoes()
    {
        return $this->instrucoes;
    }

    /**
     * Define um array com a descrição do demonstrativo (máximo 5)
     *
     * @param array $descricaoDemonstrativo
     */
    public function setDescricaoDemonstrativo($descricaoDemonstrativo)
    {
        $this->descricaoDemonstrativo = $descricaoDemonstrativo;
    }

    /**
     * Retorna um array com a descrição do demonstrativo (máximo 5)
     *
     * @return array
     */
    public function getDescricaoDemonstrativo()
    {
        return $this->descricaoDemonstrativo;
    }

    /**
     * Define o local de pagamento do boleto
     *
     * @param string $localPagamento
     */
    public function setLocalPagamento($localPagamento)
    {
        $this->localPagamento = $localPagamento;
    }

    /**
     * Retorna o local de pagamento do boleto
     *
     * @return string
     */
    public function getLocalPagamento()
    {
        return $this->localPagamento;
    }

    /**
     * Define a moeda utilizada pelo boleto
     *
     * @param int $moeda
     */
    public function setMoeda($moeda)
    {
        $this->moeda = $moeda;
    }

    /**
     * Retorna a moeda utilizada pelo boleto
     *
     * @return int
     */
    public function getMoeda()
    {
        return $this->moeda;
    }

    /**
     * Define o valor do Nosso Número (identificador único do boleto)
     *
     * @param int $nossoNumero
     */
    public function setNossoNumero($nossoNumero)
    {
        $this->nossoNumero = $nossoNumero;
    }

    /**
     * Retorna o valor do Nosso Número (identificador único do boleto)
     *
     * @return int
     */
    public function getNossoNumero()
    {
        return $this->nossoNumero;
    }

    /**
     * Define o objeto do sacado
     *
     * @param \OpenBoleto\Agente $sacado
     */
    public function setSacado(Agente $sacado)
    {
        $this->sacado = $sacado;
    }

    /**
     * Retorna o objeto do sacado
     *
     * @return \OpenBoleto\Agente
     */
    public function getSacado()
    {
        return $this->sacado;
    }

    /**
     * Define o objeto sacador avalista do boleto
     *
     * @param \OpenBoleto\Agente $sacadorAvalista
     */
    public function setSacadorAvalista(Agente $sacadorAvalista)
    {
        $this->sacadorAvalista = $sacadorAvalista;
    }

    /**
     * Retorna o objeto sacador avalista do boleto
     *
     * @return \OpenBoleto\Agente
     */
    public function getSacadorAvalista()
    {
        return $this->sacadorAvalista;
    }

    /**
     * Define o valor total do boleto (incluindo taxas)
     *
     * @param float $valor
     */
    public function setValor($valor)
    {
        $this->valor = $valor;
    }

    /**
     * Retorna o valor total do boleto (incluindo taxas)
     *
     * @return float
     */
    public function getValor()
    {
        return $this->getContraApresentacao() ? 0.00 : $this->valor;
    }

    /**
     * Define valor para pagamento mínimo em boletos de contra apresentação.
     * Quando definido, remove o valor normal do boleto.
     *
     * @param float $pagamentoMinimo
     */
    public function setPagamentoMinimo($pagamentoMinimo)
    {
        $this->pagamentoMinimo = $pagamentoMinimo;
        $this->setContraApresentacao(true);
    }

    /**
     * Retorna o valor para pagamento mínimo em boletos de contra apresentação.
     *
     * @return float
     */
    public function getPagamentoMinimo()
    {
        return $this->pagamentoMinimo;
    }

    /**
     * Define o nome da atual arquivo de view (template)
     *
     * @param string $layout
     */
    public function setLayout($layout)
    {
        $this->layout = $layout;
    }

    /**
     * Retorna o nome da atual arquivo de view (template)
     *
     * @return string
     */
    public function getLayout()
    {
        return $this->layout;
    }

    /**
     * Retorna a localização da pasta de views
     *
     * @param string $viewPath
     */
    public function setViewPath($viewPath)
    {
        $this->viewPath = $viewPath;
    }

    /**
     * Define a localização da pasta de views
     *
     * @return string
     */
    public function getViewPath()
    {
        return $this->viewPath;
    }

    /**
     * Define a localização do logotipo do banco relativo à pasta de imagens
     *
     * @param string $logoBanco
     */
    public function setLogoBanco($logoBanco)
    {
        $this->logoBanco = $logoBanco;
    }

    /**
     * Retorna a localização do logotipo do banco relativo à pasta de imagens
     *
     * @return string
     */
    public function getLogoBanco()
    {
        return $this->logoBanco;
    }

    /**
     * Define a localização exata do logotipo da empresa.
     * Note que este não é relativo à pasta de imagens
     *
     * @param string $logoPath
     */
    public function setLogoPath($logoPath)
    {
        $this->logoPath = $logoPath;
    }

    /**
     * Retorna a localização completa do logotipo da empresa
     * Note que este não é relativo à pasta de imagens
     *
     * @return string
     */
    public function getLogoPath()
    {
        return $this->logoPath;
    }

    /**
     * Método mágico para obter algum parâmetro opcional. Por exemplo, vamos supor que o banco X
     * tenha um campo de avalista. Crie os métodos setAvalista e getAvalista e a propriedade avalista
     * na classe do banco, e você poderá usar este método $boleto->get('avalista');
     *
     *
     * @param string $param
     * @return mixed
     */
    public function get($param)
    {
        return method_exists($this, 'get' . $param) ? $this->{'get' . $param}() : null;
    }

    /**
     * Método onde qualquer boleto deve extender para gerar o código da posição de 20 a 44
     *
     * @return string
     */
    protected abstract function getChaveAsbace();

    /**
     * Em alguns bancos, a visualização de alguns campos do boleto são diferentes.
     * Nestes casos, sobrescreva este método na classe do banco e retorne um array
     * contendo estes campos alterados
     *
     *
     * Ex:
     * <code>
     * return array(
     *      'carteira' => 'SR'
     * )
     * </code>
     * Mostrará SR no campo "Carteira" do boleto.
     *
     * Mas também permite utilizar campos personalizados, por exemplo, caso exista
     * o campo ABC no boleto, você pode definí-lo na classe do banco, retornar o
     * valor dele através deste método e mostrá-lo na view correspondente.
     *
     *
     * @return array
     */
    public function getViewVars() {}

    /**
     * Retorna o HTML do boleto gerado
     *
     * @return string
     */
    public function getOutput()
    {
        ob_start();

        extract(array(
            'linha_digitavel' => $this->getLinhaDigitavel(),
            'cedente' => $this->getCedente()->getNome(),
            'cedente_cpf_cnpj' => $this->getCedente()->getDocumento(),
            'cedente_endereco1' => $this->getCedente()->getEndereco(),
            'cedente_endereco2' => $this->getCedente()->getCepCidadeUf(),
            'logo_banco' => $this->getLogoBanco(),
            'logotipo' => $this->getLogoPath(),
            'images' => $this->getImagePath(),
            'codigo_banco_com_dv' => $this->getCodigoBancoComDv(),
            'especie' => static::$especie[$this->getMoeda()],
            'quantidade' => $this->get('quantidade'),
            'data_vencimento' => $this->getContraApresentacao() ? 'Contra Apresenta&ccedil;&atilde;o' : $this->getDataVencimento()->format('d/m/Y'),
            'data_processamento'  => $this->getDataProcessamento()->format('d/m/Y'),
            'data_documento' => $this->getDataDocumento()->format('d/m/Y'),
            'pagamento_minimo' => static::formataDinheiro($this->getPagamentoMinimo()),
            'valor_documento' => static::formataDinheiro($this->getValor()),
            'desconto_abatimento' => static::formataDinheiro($this->get('descontosAbatimentos')),
            'outras_deducoes' => static::formataDinheiro($this->get('outrasDeducoes')),
            'mora_multa' => static::formataDinheiro($this->get('moraMulta')),
            'outros_acrescimos' => static::formataDinheiro($this->get('outrosAcrescimos')),
            'valor_cobrado' => static::formataDinheiro($this->get('valorCobrado')),
            'valor_unitario' => static::formataDinheiro($this->get('valorUnitario')),
            'sacador_avalista' => $this->getSacadorAvalista() ? $this->getSacadorAvalista()->getNomeDocumento() : null,
            'sacado' => $this->getSacado()->getNome(),
            'sacado_endereco1' => $this->getSacado()->getEndereco(),
            'sacado_endereco2' => $this->getSacado()->getCepCidadeUf(),
            'demonstrativo' => (array) $this->getDescricaoDemonstrativo() + array(null, null, null, null, null), // Max: 5 linhas
            'instrucoes' => (array) $this->getInstrucoes() + array(null, null, null, null, null, null, null, null), // Max: 8 linhas
            'local_pagamento' => $this->getLocalPagamento(),
            'numero_documento' => $this->getNumeroDocumento(),
            'agencia_codigo_cedente'=> $this->getAgenciaCodigoCedente(),
            'nosso_numero' => $this->getNossoNumero(),
            'especie_doc' => $this->getEspecieDoc(),
            'aceite' => $this->getAceite(),
            'carteira' => $this->getCarteiraNome(),
            'uso_banco' => $this->getUsoBanco(),
            'avalista' => $this->get('avalista'),
            'codigo_barras' => $this->getImagemCodigoDeBarras(),
        ));

        // Override view variables when rendering
        extract($this->getViewVars());

        include $this->getViewPath() . '/' . $this->getLayout();

        return ob_get_clean();
    }

    /**
     * Retorna o campo Agência/Cedente do boleto
     *
     * @return string
     */
    public function getAgenciaCodigoCedente()
    {
        $agencia = $this->getAgenciaDv() ? $this->getAgencia() . '-' . $this->getAgenciaDv() : $this->getAgencia();
        $conta = $this->getContaDv() ? $this->getConta() . '-' . $this->getContaDv() : $this->getConta();
        return $agencia . ' / ' . $conta;
    }

    /**
     * Retorna o nome da carteira para impressão no boleto
     *
     * Caso o nome da carteira a ser impresso no boleto seja diferente do número
     * Então crie uma variável na classe do banco correspondente $carteirasNomes
     * sendo uma array cujos índices sejam os números das carteiras e os valores
     * seus respectivos nomes
     *
     * @return string
     */
    public function getCarteiraNome()
    {
        return isset($this->carteirasNomes[$this->getCarteira()]) ? $this->carteirasNomes[$this->getCarteira()] : self::zeroFill($this->getCarteira(), 2);
    }

    /**
     * Retorna o número Febraban
     *
     * @return string
     */
    public function getNumeroFebraban()
    {
        return self::zeroFill($this->getCodigoBanco(), 3) . $this->getMoeda() . $this->getDigitoVerificador() . $this->getFatorVencimento() . $this->getValorZeroFill() . $this->getChaveAsbace();
    }

    /**
     * Retorna o código do banco com o dígito verificador
     *
     * @return string
     */
    public function getCodigoBancoComDv()
    {
        $codigoBanco = $this->getCodigoBanco();
        $digitoVerificador = $this->modulo11($codigoBanco);

        return $codigoBanco . '-' . $digitoVerificador['digito'];
    }

    /**
     * Retorna a linha digitável do boleto
     *
     * @return string
     */
    public function getLinhaDigitavel()
    {
        $chave = $this->getChaveAsbace();

        // Break down febraban positions 20 to 44 into 3 blocks of 5, 10 and 10
        // characters each.
        $blocks = array(
            '20-24' => substr($chave, 0, 5),
            '25-34' => substr($chave, 5, 10),
            '35-44' => substr($chave, 15, 10),
        );

        // Concatenates bankCode + currencyCode + first block of 5 characters and
        // calculates its check digit for part1.
        $check_digit = $this->modulo10($this->getCodigoBanco() . $this->getMoeda() . $blocks['20-24']);

        // Shift in a dot on block 20-24 (5 characters) at its 2nd position.
        $blocks['20-24'] = substr_replace($blocks['20-24'], '.', 1, 0);

        // Concatenates bankCode + currencyCode + first block of 5 characters +
        // checkDigit.
        $part1 = $this->getCodigoBanco(). $this->getMoeda() . $blocks['20-24'] . $check_digit;

        // Calculates part2 check digit from 2nd block of 10 characters.
        $check_digit = $this->modulo10($blocks['25-34']);

        $part2 = $blocks['25-34'] . $check_digit;
        // Shift in a dot at its 6th position.
        $part2 = substr_replace($part2, '.', 5, 0);

        // Calculates part3 check digit from 3rd block of 10 characters.
        $check_digit = $this->modulo10($blocks['35-44']);

        // As part2, we do the same process again for part3.
        $part3 = $blocks['35-44'] . $check_digit;
        $part3 = substr_replace($part3, '.', 5, 0);

        // Check digit for the human readable number.
        $cd = $this->getDigitoVerificador();

        // Put part4 together.
        $part4  = $this->getFatorVencimento() . $this->getValorZeroFill();

        // Now put everything together.
        return "$part1 $part2 $part3 $cd $part4";
    }

    /**
     * Retorna a string contendo as imagens do código de barras, segundo o padrão Febraban
     *
     * @return string
     */
    public function getImagemCodigoDeBarras()
    {
        $fino = 1;
        $largo = 3;
        $altura = 50;
        $codigo = $this->getNumeroFebraban();
        $imagePath = $this->getImagePath();

        $barcodes = array('00110', '10001', '01001', '11000', '00101', '10100', '01100', '00011', '10010', '01010');

        for ($f1 = 9; $f1 >= 0; $f1--) {
            for ($f2 = 9; $f2 >= 0; $f2--) {

                $f = ($f1 * 10) + $f2;
                $texto = '';

                for ($i = 1; $i < 6; $i++) {
                    $texto .= substr($barcodes[$f1], ($i - 1), 1) . substr($barcodes[$f2], ($i - 1), 1);
                }

                $barcodes[$f] = $texto;
            }
        }

        // Guarda inicial
        $retorno = "<img src='{$imagePath}/p.png' width='{$fino}' height='{$altura}' border='0'>"
            . "<img src='{$imagePath}/b.png' width='{$fino}' height='{$altura}' border='0'>"
            . "<img src='{$imagePath}/p.png' width='{$fino}' height='{$altura}' border='0'>"
            . "<img src='{$imagePath}/b.png' width='{$fino}' height='{$altura}' border='0'>";

        if (strlen($codigo) % 2 != 0) {
            $codigo = "0" . $codigo;
        }

        // Draw dos dados
        while (strlen($codigo) > 0) {

            $i = round(self::caracteresEsquerda($codigo, 2));
            $codigo = self::caracteresDireita($codigo, strlen($codigo) - 2);
            $f = $barcodes[$i];

            for ($i = 1; $i < 11; $i += 2) {

                if (substr($f, ($i - 1), 1) == "0") {
                    $f1 = $fino;
                } else {
                    $f1 = $largo;
                }

                $retorno .= "<img src='{$imagePath}/p.png' width='{$f1}' height='{$altura}' border='0'>";

                if (substr($f, $i, 1) == "0") {
                    $f2 = $fino;
                } else {
                    $f2 = $largo;
                }

                $retorno .= "<img src='{$imagePath}/b.png' width='{$f2}' height='{$altura}' border='0'>";
            }
        }

        // Draw guarda final
        return $retorno . "<img src='{$imagePath}/p.png' width='{$largo}' height='{$altura}' border='0'>"
            . "<img src='{$imagePath}/b.png' width='{$fino}' height='{$altura}' border='0'>"
            . "<img src='{$imagePath}/p.png' width='{$fino}' height='{$altura}' border='0'>";
    }

    /**
     * Retorna o valor do boleto com 10 dígitos e remoção dos pontos/vírgulas
     *
     * @return string
     */
    protected function getValorZeroFill()
    {
        return str_pad(str_replace(array(',', '.'), '', (string) $this->getValor()), 10, '0', STR_PAD_LEFT);
    }

    /**
     * Retorna o número de dias de 07/10/1997 até a data de vencimento do boleto
     * Ou 0000 caso não tenha data de vencimento (contra-apresentação)
     *
     * @return string
     */
    protected function getFatorVencimento()
    {
        if (!$this->getContraApresentacao()) {
            $date = new DateTime('1997-10-07');
            return $date->diff($this->getDataVencimento())->days;
        } else {
            return '0000';
        }
    }

    /**
     * Retorna o dígito verificador do código Febraban
     *
     * @return int
     */
    protected function getDigitoVerificador()
    {
        $num = self::zeroFill($this->getCodigoBanco(), 4) . $this->getMoeda() . $this->getFatorVencimento() . $this->getValorZeroFill() . $this->getChaveAsbace();

        $modulo = $this->modulo11($num);
        if ($modulo['resto'] == 0 || $modulo['resto'] == 1 || $modulo['resto'] == 10) {
            $dv = 1;
        } else {
            $dv = 11 - $modulo['resto'];
        }

        return $dv;
    }

    /**
     * Helper para Zerofill (0 à esqueda)
     *
     * @param int $valor
     * @param int $digitos
     * @return string
     */
    protected static function zeroFill($valor, $digitos)
    {
        return str_pad($valor, $digitos, '0', STR_PAD_LEFT);
    }

    /**
     * Formata o valor para apresentação em Real (1.000,00)
     *
     * @param int|float $valor
     * @param bool $mostrar_zero Se true, retorna 0,00 caso o valor seja 0, se false, retorna vazio
     * @return string
     */
    protected static function formataDinheiro($valor, $mostrar_zero = false)
    {
        return $valor ? number_format($valor, 2, ',', '.') : ($mostrar_zero ? '0,00' : '');
    }

    /**
     * Helper para obter os caracteres à esquerda
     *
     * @param string $string
     * @param int $num Quantidade de caracteres para se obter
     * @return string
     */
    protected static function caracteresEsquerda($string, $num)
    {
        return substr($string, 0, $num);
    }

    /**
     * Helper para se obter os caracteres à direita
     *
     * @param string $string
     * @param int $num Quantidade de caracteres para se obter
     * @return string
     */
    protected static function caracteresDireita($string, $num)
    {
        return substr($string, strlen($string)-$num, $num);
    }

    /**
     * Calcula e retorna o dígito verificador usando o algoritmo Modulo 10
     *
     * @param string $num
     * @see Documentação em http://www.febraban.org.br/Acervo1.asp?id_texto=195&id_pagina=173&palavra=
     * @return int
     */
    protected static function modulo10($num)
    {
        $numtotal10 = 0;
        $fator = 2;

        //  Separacao dos numeros.
        for ($i = strlen($num); $i > 0; $i--) {
            //  Pega cada numero isoladamente.
            $numeros[$i] = substr($num,$i-1,1);
            //  Efetua multiplicacao do numero pelo (falor 10).
            $temp = $numeros[$i] * $fator;
            $temp0=0;
            foreach (preg_split('// ',$temp,-1,PREG_SPLIT_NO_EMPTY) as $v){ $temp0+=$v; }
            $parcial10[$i] = $temp0; // $numeros[$i] * $fator;
            //  Monta sequencia para soma dos digitos no (modulo 10).
            $numtotal10 += $parcial10[$i];
            if ($fator == 2) {
                $fator = 1;
            }
            else {
                // Intercala fator de multiplicacao (modulo 10).
                $fator = 2;
            }
        }

        $remainder  = $numtotal10 % 10;
        $digito = 10 - $remainder;

        // Make it zero if check digit is 10.
        $digito = ($digito == 10) ? 0 : $digito;

        return $digito;
    }

    /**
     * Calcula e retorna o dígito verificador usando o algoritmo Modulo 11
     *
     * @param string $num
     * @param int $base
     * @see Documentação em http://www.febraban.org.br/Acervo1.asp?id_texto=195&id_pagina=173&palavra=
     * @return array Retorna um array com as chaves 'digito' e 'resto'
     */
    protected static function modulo11($num, $base=9)
    {
        $fator = 2;

        $soma  = 0;
        // Separacao dos numeros.
        for ($i = strlen($num); $i > 0; $i--) {
            //  Pega cada numero isoladamente.
            $numeros[$i] = substr($num,$i-1,1);
            //  Efetua multiplicacao do numero pelo falor.
            $parcial[$i] = $numeros[$i] * $fator;
            //  Soma dos digitos.
            $soma += $parcial[$i];
            if ($fator == $base) {
                //  Restaura fator de multiplicacao para 2.
                $fator = 1;
            }
            $fator++;
        }
        $result = array(
            'digito' => ($soma * 10) % 11,
            // Remainder.
            'resto'  => $soma % 11,
        );
        if ($result['digito'] == 10){
            $result['digito'] = 0;
        }
        return $result;
    }
}