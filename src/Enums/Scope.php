<?php

declare(strict_types=1);

namespace LumenSistemas\Inter\Enums;

enum Scope: string
{
    case BoletoCobrancaRead = 'boleto-cobranca.read';
    case BoletoCobrancaWrite = 'boleto-cobranca.write';
    case CobRead = 'cob.read';
    case CobWrite = 'cob.write';
    case CobvRead = 'cobv.read';
    case CobvWrite = 'cobv.write';
    case PixRead = 'pix.read';
    case PixWrite = 'pix.write';
    case WebhookRead = 'webhook.read';
    case WebhookWrite = 'webhook.write';
    case PayloadLocationRead = 'payloadlocation.read';
    case PayloadLocationWrite = 'payloadlocation.write';
    case PagamentoPixRead = 'pagamento-pix.read';
    case PagamentoPixWrite = 'pagamento-pix.write';
    case PagamentoDarfRead = 'pagamento-darf.read';
    case PagamentoDarfWrite = 'pagamento-darf.write';
    case PagamentoBoletoRead = 'pagamento-boleto.read';
    case PagamentoBoletoWrite = 'pagamento-boleto.write';
    case ExtratoRead = 'extrato.read';
}
