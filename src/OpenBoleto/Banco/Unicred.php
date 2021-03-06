<?php
/**
 * OpenBoleto - Geração de boletos bancários em PHP
 *
 * Classe boleto Banco do Brasil S/A
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

namespace OpenBoleto\Banco;
use OpenBoleto\BoletoAbstract;
use OpenBoleto\Exception;

class Unicred extends BoletoAbstract
{
    /**
     * Código do banco
     * @var string
     */
    protected $codigoBanco = '090';

    /**
     * Localização do logotipo do banco, referente ao diretório de imagens
     * @var string
     */
    protected $logoBanco = 'unicred.jpg';

    /**
     * Define as carteiras disponíveis para este banco
     * @var array
     */
    protected $carteiras = array('11', '21', '31', '41', '51');

    /**
     * Define o campo nosso número do boleto, que é diferente do que é definido
     * @var string
     */
    protected $nossoNumeroOutput;

    /**
     * Método para gerar o código da posição de 20 a 44
     *
     * @return string
     * @throws \OpenBoleto\Exception
     */
    public function getCampoLivre()
    {
        // Define o output do nosso número no campo do boleto
        $nossoNumero = self::zeroFill($this->nossoNumero, 12);
        $this->nossoNumeroOutput = $this->getCarteira() . '/' . $nossoNumero; //$nossoNumero;

        return self::zeroFill($this->getAgencia(), 4) . self::zeroFill($this->getConta(), 10) . str_replace('-', '', $nossoNumero);
    }

    /**
     * Define nomes de campos específicos do boleto do Banco do Brasil
     *
     * @return array
     */
    public function getViewVars()
    {
        return array(
            'nosso_numero' => $this->nossoNumeroOutput,
        );
    }
}
