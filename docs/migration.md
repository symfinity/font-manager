# Migration from neuralglitch/font-manager

Use this table when replacing the legacy Packagist package with `symfinity/font-manager` in a Symfony app.

## Package identity

| Item | Legacy (`neuralglitch/*`) | Symfinity (`symfinity/*`) |
|------|---------------------------|---------------------------|
| Composer name | `neuralglitch/font-manager` | `symfinity/font-manager` |
| PSR-4 namespace | `NeuralGlitch\FontManager\` | `Symfinity\FontManager\` |
| Test namespace | `NeuralGlitch\FontManager\Tests\` | `Symfinity\FontManager\Tests\` |
| Bundle class | `NeuralGlitch\FontManager\FontManagerBundle` | `Symfinity\FontManager\FontManagerBundle` |
| Config root key | `font_manager:` | `font_manager:` (unchanged) |
| Config file | `config/packages/symfinity_font_manager.yaml` | `config/packages/symfinity_font_manager.yaml` |

## Composer and Symfony floor

| Constraint | Legacy | Symfinity port |
|------------|--------|----------------|
| PHP | `>=8.2` | `>=8.2` |
| Symfony components | `^7.4` (current upstream) | `^7.4` (org consumer floor) |

## Application changes

1. **Require** the new package and remove the old one:

   ```bash
   composer remove neuralglitch/font-manager
   composer require symfinity/font-manager
   ```

2. **Update imports** in PHP and tests: `NeuralGlitch\FontManager` → `Symfinity\FontManager`.

3. **Update `config/bundles.php`** if the class is registered manually:

   ```php
   // Before
   NeuralGlitch\FontManager\FontManagerBundle::class => ['all' => true],

   // After
   Symfinity\FontManager\FontManagerBundle::class => ['all' => true],
   ```

4. **Twig** — `font_manager()` is unchanged.

5. **CLI** — command names unchanged (`fonts:lock`, `fonts:search`, `fonts:status`, `fonts:prune`, …).

6. **Flex recipe** — after `composer require symfinity/font-manager`, config matches `recipes/symfinity/font-manager/0.1/` (`google` default, `css_variables` export only).

## Abandon timeline

Do not abandon `neuralglitch/font-manager` on Packagist until `symfinity/font-manager` is installable and documented (wave-1 program gate G5 / intake M4).

See also: [Migration from neuralglitch/google-fonts](./migration-from-google-fonts.md).
