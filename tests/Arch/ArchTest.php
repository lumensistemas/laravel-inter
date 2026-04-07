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
