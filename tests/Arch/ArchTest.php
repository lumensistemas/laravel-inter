<?php

// ──────────────────────────────────────────────────────────────
// Presets
// ──────────────────────────────────────────────────────────────

arch()->preset()->php();

arch()->preset()->security();

// ──────────────────────────────────────────────────────────────
// Global rules
// ──────────────────────────────────────────────────────────────

arch('Source code uses strict types everywhere')
    ->expect('LumenSistemas\Inter')
    ->toUseStrictTypes();

// ──────────────────────────────────────────────────────────────
// Contracts
// ──────────────────────────────────────────────────────────────

arch('Contracts are interfaces')
    ->expect('LumenSistemas\Inter\Contracts')
    ->toBeInterfaces();

// ──────────────────────────────────────────────────────────────
// Enums
// ──────────────────────────────────────────────────────────────

arch('Enums are enums')
    ->expect('LumenSistemas\Inter\Enums')
    ->toBeEnums();

// ──────────────────────────────────────────────────────────────
// Exceptions
// ──────────────────────────────────────────────────────────────

arch('Exceptions extend InterException')
    ->expect('LumenSistemas\Inter\Exceptions')
    ->toExtend(LumenSistemas\Inter\Exceptions\InterException::class)
    ->ignoring(LumenSistemas\Inter\Exceptions\InterException::class);

// ──────────────────────────────────────────────────────────────
// DTOs
// ──────────────────────────────────────────────────────────────

arch('Response DTOs are final and readonly')
    ->expect(LumenSistemas\Inter\Http\Response::class)
    ->toBeFinal()
    ->toBeReadonly();

arch('PaginatedResponse is final and readonly')
    ->expect(LumenSistemas\Inter\Http\PaginatedResponse::class)
    ->toBeFinal()
    ->toBeReadonly();

// ──────────────────────────────────────────────────────────────
// Resources
// ──────────────────────────────────────────────────────────────

arch('Resources extend the base Resource class')
    ->expect('LumenSistemas\Inter\Resources')
    ->toExtend(LumenSistemas\Inter\Resources\Resource::class)
    ->ignoring(LumenSistemas\Inter\Resources\Resource::class);

// ──────────────────────────────────────────────────────────────
// Facades
// ──────────────────────────────────────────────────────────────

arch('Facades extend Illuminate Facade')
    ->expect('LumenSistemas\Inter\Facades')
    ->toExtend(Illuminate\Support\Facades\Facade::class);

// ──────────────────────────────────────────────────────────────
// Service Provider
// ──────────────────────────────────────────────────────────────

arch('Service provider extends Illuminate ServiceProvider')
    ->expect(LumenSistemas\Inter\InterServiceProvider::class)
    ->toExtend(Illuminate\Support\ServiceProvider::class);

// ──────────────────────────────────────────────────────────────
// Dependency rules
// ──────────────────────────────────────────────────────────────

arch('Resources do not depend on Illuminate directly')
    ->expect('LumenSistemas\Inter\Resources')
    ->toOnlyUse([
        'LumenSistemas\Inter\Contracts',
        'LumenSistemas\Inter\Concerns',
        'LumenSistemas\Inter\Http',
    ]);

arch('Exceptions do not depend on vendor code')
    ->expect('LumenSistemas\Inter\Exceptions')
    ->toOnlyUse([
        'LumenSistemas\Inter\Exceptions',
        'Exception',
    ]);
