<?php

/*
 * This file is part of PHP CS Fixer.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace PhpCsFixer\Fixer\Casing;

use PhpCsFixer\Utils;
use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\Fixer\ConfigurationDefinitionFixerInterface;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;

/**
 * @author Linus Juhlin <linus.juhlin@protonmail.com>
 */
final class VariableCasingFixer extends AbstractFixer implements ConfigurationDefinitionFixerInterface
{
    /**
     * @internal
     */
    const CAMEL_CASE = 'camel_case';
    /**
     * @internal
     */
    const SNAKE_CASE = 'snake_case';

    private $superGlobals = [
        '$GLOBALS',
        '$_SERVER',
        '$_GET',
        '$_POST',
        '$_FILES',
        '$_COOKIE',
        '$_SESSION',
        '$_REQUEST',
        '$_ENV',
    ];

    private $accessors = [
        'public',
        'private',
        'protected',
    ];

    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'PHP variables MUST be using the correct casing.',
            [
                new CodeSample(
                    '<?php
$FUCK
'
                ),
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isTokenKindFound(T_STRING);
    }

    /**
     * {@inheritdoc}
     */
    protected function applyFix(\SplFileInfo $file,
        Tokens $tokens)
    {
        foreach ($tokens as $index => $token) {
            if (! $token->isGivenKind(T_VARIABLE)) {
                continue;
            }

            if (in_array($token->getContent(), $this->superGlobals)) {
                continue;
            }

            $previous = $tokens->getPrevMeaningfulToken($index);

            if (in_array($tokens[$previous]->getContent(), $this->accessors)) {
                continue;
            }

            $newContent = $this->updateVariableCasing($token->getContent());

            $tokens[$index] = new Token([$token->getId(), $newContent]);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function createConfigurationDefinition()
    {
        return new FixerConfigurationResolver([
            (new FixerOptionBuilder('case', 'Apply camel or snake case to variables'))
                ->setAllowedValues([self::CAMEL_CASE, self::SNAKE_CASE])
                ->setDefault(self::CAMEL_CASE)
                ->getOption(),
        ]);
    }

    /**
     * @param string $variableName
     *
     * @return string
     */
    private function updateVariableCasing($variableName)
    {
        if ($this->configuration['case'] === self::CAMEL_CASE) {
            $newVariableName = $variableName;
            $newVariableName = ucwords($newVariableName, '_');
            $newVariableName = str_replace('_', '', $newVariableName);
            $newVariableName = $newVariableName[0] . lcfirst(substr($newVariableName, 1));
        } else {
            $newVariableName = Utils::camelCaseToUnderscore($variableName);
        }

        return $newVariableName;
    }
}
