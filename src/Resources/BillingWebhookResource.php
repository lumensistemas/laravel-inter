<?php

declare(strict_types=1);

namespace LumenSistemas\Inter\Resources;

use LumenSistemas\Inter\Http\PaginatedResponse;
use LumenSistemas\Inter\Http\Response;

class BillingWebhookResource extends Resource
{
    /**
     * Register or update the billing webhook URL.
     *
     * ```php
     * $inter->billingWebhook()->create('https://example.com/webhooks/inter/billing');
     * ```
     *
     * @return Response Empty response on success
     */
    public function create(string $webhookUrl): Response
    {
        return $this->client->put($this->resourcePath(), [
            'webhookUrl' => $webhookUrl,
        ]);
    }

    /**
     * Retrieve the current billing webhook configuration.
     *
     * ```php
     * $webhook = $inter->billingWebhook()->retrieve();
     * // $webhook->data['webhookUrl'], $webhook->data['criacao']
     * ```
     *
     * @return Response Response data: {webhookUrl, criacao}
     */
    public function retrieve(): Response
    {
        return $this->client->get($this->resourcePath());
    }

    /**
     * Delete the billing webhook.
     *
     * ```php
     * $inter->billingWebhook()->delete();
     * ```
     *
     * @return Response Empty response on success
     */
    public function delete(): Response
    {
        return $this->client->delete($this->resourcePath());
    }

    /**
     * Retrieve a paginated list of webhook callback delivery attempts.
     *
     * ```php
     * $callbacks = $inter->billingWebhook()->callbacks(
     *     dataHoraInicio: '2026-04-01T00:00:00Z',
     *     dataHoraFim: '2026-04-30T23:59:59Z',
     * );
     * ```
     *
     * @return PaginatedResponse Each item contains: {webhookUrl, numeroTentativa, dataHoraDisparo,
     *                           sucesso, httpStatus, mensagemErro, payload[]}
     */
    public function callbacks(
        string $dataHoraInicio,
        string $dataHoraFim,
        ?int $pagina = null,
        ?int $itensPorPagina = null,
        ?string $codigoSolicitacao = null,
    ): PaginatedResponse {
        return $this->client->list($this->resourcePath().'/callbacks', 'data', $this->filterNulls([
            'dataHoraInicio' => $dataHoraInicio,
            'dataHoraFim' => $dataHoraFim,
            'pagina' => $pagina,
            'itensPorPagina' => $itensPorPagina,
            'codigoSolicitacao' => $codigoSolicitacao,
        ]));
    }

    protected function resourcePath(): string
    {
        return '/cobranca/v3/cobrancas/webhook';
    }

    protected function collectionKey(): string
    {
        return 'data';
    }
}
