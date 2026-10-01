<?php

/**
 * Base ECS config — global reference for new & existing PHP/Craft projects.
 *
 * Source of truth for the rules: ~/.claude/CLAUDE.md (# PHP → Formatting).
 * Install / run how-to: ~/.claude/refs/ecs-setup.md
 *
 * Maintenance: update THIS stub whenever a formatting preference changes
 * globally (i.e. not locally scoped to one project). Project-only tweaks stay
 * in that project's own ecs.php, not here.
 *
 * Install (Craft): php composer.phar require craftcms/ecs:dev-main --dev
 * Run:  vendor/bin/ecs check   |   vendor/bin/ecs check --fix
 */

declare(strict_types=1);

use craft\ecs\SetList;
use PhpCsFixer\Fixer\CastNotation\CastSpacesFixer;
use PhpCsFixer\Fixer\Operator\BinaryOperatorSpacesFixer;
use PhpCsFixer\Fixer\Operator\NotOperatorWithSuccessorSpaceFixer;
use PhpCsFixer\Fixer\Whitespace\BlankLineBeforeStatementFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;

return static function(ECSConfig $ecsConfig): void {
    $ecsConfig->parallel();

    // Adjust per project: modules/, config/, or a plugin's src/.
    $ecsConfig->paths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
        __FILE__,
    ]);

    // Craft's base standard. Craft 5 uses the CRAFT_CMS_4 set — the highest
    // craftcms/ecs ships; switch to CRAFT_CMS_5 if/when SetList adds it.
    // Non-Craft PHP: install symplify/easy-coding-standard and drop this line.
    // Translation files are data: aligning `=>` to keys of 200+ characters helps nobody.
    $ecsConfig->skip([
        __DIR__ . '/src/translations',
    ]);

    $ecsConfig->sets([SetList::CRAFT_CMS_4]);

    // Global formatting preferences (confirm/override on top of the Craft set):
    $ecsConfig->rule(NotOperatorWithSuccessorSpaceFixer::class);           // ! $foo
    $ecsConfig->ruleWithConfiguration(CastSpacesFixer::class, [            // (int) $foo
        'space' => 'single',
    ]);
    $ecsConfig->ruleWithConfiguration(BlankLineBeforeStatementFixer::class, [
        'statements' => ['return', 'if', 'for', 'foreach', 'while', 'do', 'switch', 'try', 'throw'],
    ]);
    $ecsConfig->ruleWithConfiguration(BinaryOperatorSpacesFixer::class, [  // align => in arrays
        'operators' => ['=>' => 'align_single_space_minimal'],
    ]);
};
