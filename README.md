# Laravel Inter

[![Latest Version on Packagist](https://img.shields.io/packagist/v/lumensistemas/laravel-inter.svg?style=flat-square)](https://packagist.org/packages/lumensistemas/laravel-inter)
[![Tests](https://img.shields.io/github/actions/workflow/status/lumensistemas/laravel-inter/package-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/lumensistemas/laravel-inter/actions/workflows/package-tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/lumensistemas/laravel-inter.svg?style=flat-square)](https://packagist.org/packages/lumensistemas/laravel-inter)

A typed, testable Laravel API client for [Banco Inter](https://developers.inter.co/). Supports boleto, Pix, banking operations, webhooks, and multi-tenancy with OAuth 2.0 + mTLS authentication.

**Requirements:** PHP 8.4+, Laravel 12+

## Installation

You can install the package via composer:

```bash
composer require lumensistemas/laravel-inter
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag=inter-config
```

Add your credentials to `.env`:

```env
INTER_CLIENT_ID=your-client-id
INTER_CLIENT_SECRET=your-client-secret
INTER_CERTIFICATE=/path/to/certificate.crt
INTER_PRIVATE_KEY=/path/to/private.key
INTER_ENVIRONMENT=sandbox
```

## Usage

```php
use LumenSistemas\Inter\Facades\Inter;

// Create a boleto
$boleto = Inter::boletos()->create(
    seuNumero: 'INV-001',
    valorNominal: 150.00,
    dataVencimento: '2026-05-01',
    numDiasAgenda: 30,
    pagador: [
        'cpfCnpj' => '12345678901',
        'nome' => 'John Doe',
        'endereco' => 'Rua Example, 123',
        'cidade' => 'Curitiba',
        'uf' => 'PR',
        'cep' => '80000000',
    ],
);

// List boletos
$boletos = Inter::boletos()->list(
    dataInicial: '2026-04-01',
    dataFinal: '2026-04-30',
);

// Pix immediate charge
$cobranca = Inter::pixCobrancas()->create(
    calendario: ['expiracao' => 3600],
    valor: ['original' => '100.00'],
    chave: 'your-pix-key',
);

// Banking - check balance
$saldo = Inter::banking()->balance();
```

### Multi-Tenancy

Each tenant can use its own credentials:

```php
$tenant = Inter::client(
    clientId: $tenant->inter_client_id,
    clientSecret: $tenant->inter_client_secret,
    certificate: $tenant->inter_certificate_path,
    privateKey: $tenant->inter_private_key_path,
);

$tenant->boletos()->create(...);
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](https://github.com/lumensistemas/.github/blob/main/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Lucas Vasconcelos](https://github.com/lucasvscn)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
