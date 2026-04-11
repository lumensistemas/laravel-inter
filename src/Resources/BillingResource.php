<?php

declare(strict_types=1);

namespace LumenSistemas\Inter\Resources;

use LumenSistemas\Inter\Concerns\HasPagination;
use LumenSistemas\Inter\Http\PaginatedResponse;
use LumenSistemas\Inter\Http\Response;

class BillingResource extends Resource
{
    use HasPagination;

    /**
     * Issue a new billing (boleto + Pix QR code).
     *
     * ```php
     * $response = $inter->billing()->create(
     *     seuNumero: 'INV-001',
     *     valorNominal: 150.00,
     *     dataVencimento: '2026-05-01',
     *     numDiasAgenda: 30,
     *     pagador: [
     *         'cpfCnpj' => '12345678901',
     *         'tipoPessoa' => 'FISICA',
     *         'nome' => 'John Doe',
     *         'endereco' => 'Rua Example, 123',
     *         'cidade' => 'Curitiba',
     *         'uf' => 'PR',
     *         'cep' => '80000000',
     *     ],
     * );
     * ```
     *
     * @param array<string, mixed> $pagador Payer info: {cpfCnpj, tipoPessoa (FISICA|JURIDICA), nome,
     *                                      endereco, numero, complemento, bairro, cidade, uf, cep, email, ddd, telefone}
     * @param null|array<string, mixed> $desconto Discount: {codigo (NAOTEMDESCONTO|VALORFIXODATAINFORMADA|
     *                                            PERCENTUALDATAINFORMADA|VALORANTECIPACAODIAUTIL|PERCENTUALVALORNOMINALDIACORRIDO|
     *                                            PERCENTUALVALORNOMINALDIAUTIL), quantidadeDias, taxa, valor}
     * @param null|array<string, mixed> $multa Fine: {codigo (NAOTEMMULTA|VALORFIXO|PERCENTUAL), taxa, valor}
     * @param null|array<string, mixed> $mora Late interest: {codigo (VALORDIA|TAXAMENSAL|ISENTO|CONTROLEDOBANCO), taxa, valor}
     * @param null|array<string, mixed> $mensagem Custom message: {linha1, linha2, linha3, linha4, linha5}
     * @param null|array<string, mixed> $beneficiarioFinal Final beneficiary (same keys as $pagador)
     *
     * @return Response Response data: {codigoSolicitacao}
     */
    public function create(
        string $seuNumero,
        float $valorNominal,
        string $dataVencimento,
        int $numDiasAgenda,
        array $pagador,
        ?string $formasRecebimento = null,
        ?array $desconto = null,
        ?array $multa = null,
        ?array $mora = null,
        ?array $mensagem = null,
        ?array $beneficiarioFinal = null,
    ): Response {
        return $this->client->post($this->resourcePath(), $this->filterNulls([
            'seuNumero' => $seuNumero,
            'valorNominal' => $valorNominal,
            'dataVencimento' => $dataVencimento,
            'numDiasAgenda' => $numDiasAgenda,
            'pagador' => $pagador,
            'formasRecebimento' => $formasRecebimento,
            'desconto' => $desconto,
            'multa' => $multa,
            'mora' => $mora,
            'mensagem' => $mensagem,
            'beneficiarioFinal' => $beneficiarioFinal,
        ]));
    }

    /**
     * Retrieve a single billing by its request code.
     *
     * ```php
     * $billing = $inter->billing()->find('abc-123-def');
     * ```
     *
     * @return Response Response data: {
     *                  cobranca: {codigoSolicitacao, seuNumero, dataEmissao, dataVencimento, valorNominal,
     *                  tipoCobranca (SIMPLES|PARCELADO|RECORRENTE),
     *                  situacao (A_RECEBER|RECEBIDO|MARCADO_RECEBIDO|ATRASADO|CANCELADO|EXPIRADO|FALHA_EMISSAO|EM_PROCESSAMENTO),
     *                  dataSituacao, valorTotalRecebido, origemRecebimento (BOLETO|PIX),
     *                  motivoCancelamento, arquivada, descontos[], multa, mora, pagador},
     *                  boleto: {nossoNumero, codigoBarras, linhaDigitavel},
     *                  pix: {txid, pixCopiaECola}
     *                  }
     */
    public function find(string $codigoSolicitacao): Response
    {
        return $this->client->get($this->resourcePath().'/'.$codigoSolicitacao);
    }

    /**
     * Update the due date and/or nominal value of an existing billing.
     *
     * ```php
     * $inter->billing()->update(
     *     codigoSolicitacao: 'abc-123-def',
     *     dataVencimento: '2026-06-15',
     *     valorNominal: 200.00,
     * );
     * ```
     *
     * @return Response Empty response on success
     */
    public function update(
        string $codigoSolicitacao,
        ?string $dataVencimento = null,
        ?float $valorNominal = null,
    ): Response {
        return $this->client->patch($this->resourcePath().'/'.$codigoSolicitacao, $this->filterNulls([
            'dataVencimento' => $dataVencimento,
            'valorNominal' => $valorNominal,
        ]));
    }

    /**
     * Check the processing status of a billing update.
     *
     * ```php
     * $response = $inter->billing()->updateStatus('edit-456-def');
     *
     * $response->data['status']; // "PROCESSANDO", "SUCESSO", or "FALHA"
     * ```
     *
     * @return Response Response data: {status} (PROCESSANDO|SUCESSO|FALHA)
     */
    public function updateStatus(string $codigoEdicao): Response
    {
        return $this->client->get($this->resourcePath().'/edicao/'.$codigoEdicao);
    }

    /**
     * Retrieve a paginated collection of billings within a date range.
     *
     * ```php
     * $page = $inter->billing()->list(
     *     dataInicial: '2026-04-01',
     *     dataFinal: '2026-04-30',
     *     situacao: 'A_RECEBER',
     *     tipoOrdenacao: 'DESC',
     * );
     * ```
     *
     * @return PaginatedResponse Each item contains: {cobranca, boleto, pix} (same structure as find())
     */
    public function list(
        string $dataInicial,
        string $dataFinal,
        ?string $situacao = null,
        ?string $filtrarDataPor = null,
        ?string $pessoaPagadora = null,
        ?string $cpfCnpjPessoaPagadora = null,
        ?string $seuNumero = null,
        ?string $tipoCobranca = null,
        ?string $ordenarPor = null,
        ?string $tipoOrdenacao = null,
    ): PaginatedResponse {
        return $this->client->list($this->resourcePath(), $this->collectionKey(), $this->filterNulls([
            'dataInicial' => $dataInicial,
            'dataFinal' => $dataFinal,
            'situacao' => $situacao,
            'filtrarDataPor' => $filtrarDataPor,
            'pessoaPagadora' => $pessoaPagadora,
            'cpfCnpjPessoaPagadora' => $cpfCnpjPessoaPagadora,
            'seuNumero' => $seuNumero,
            'tipoCobranca' => $tipoCobranca,
            'ordenarPor' => $ordenarPor,
            'tipoOrdenacao' => $tipoOrdenacao,
        ]));
    }

    /**
     * Retrieve the billing PDF (base64-encoded).
     *
     * ```php
     * $pdf = $inter->billing()->pdf('abc-123-def');
     * ```
     *
     * @return Response Response data: {pdf} (base64-encoded string)
     */
    public function pdf(string $codigoSolicitacao): Response
    {
        return $this->client->get($this->resourcePath().'/'.$codigoSolicitacao.'/pdf');
    }

    /**
     * Cancel an existing billing.
     *
     * ```php
     * $inter->billing()->cancel('abc-123-def', 'APEDIDODOCLIENTE');
     * ```
     *
     * @return Response Empty response on success
     */
    public function cancel(string $codigoSolicitacao, string $motivoCancelamento): Response
    {
        return $this->client->post($this->resourcePath().'/'.$codigoSolicitacao.'/cancelar', [
            'motivoCancelamento' => $motivoCancelamento,
        ]);
    }

    /**
     * Retrieve a summary of billings grouped by status within a date range.
     *
     * ```php
     * $summary = $inter->billing()->summary(
     *     dataInicial: '2026-04-01',
     *     dataFinal: '2026-04-30',
     * );
     * ```
     *
     * @return Response Response data: list of {situacao, quantidade, valor}
     */
    public function summary(
        string $dataInicial,
        string $dataFinal,
        ?string $situacao = null,
        ?string $filtrarDataPor = null,
    ): Response {
        return $this->client->get($this->resourcePath().'/sumario', $this->filterNulls([
            'dataInicial' => $dataInicial,
            'dataFinal' => $dataFinal,
            'situacao' => $situacao,
            'filtrarDataPor' => $filtrarDataPor,
        ]));
    }

    protected function resourcePath(): string
    {
        return '/cobranca/v3/cobrancas';
    }

    protected function collectionKey(): string
    {
        return 'cobrancas';
    }
}
