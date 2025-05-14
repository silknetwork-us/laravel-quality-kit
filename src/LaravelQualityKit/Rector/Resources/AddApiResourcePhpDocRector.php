<?php

declare(strict_types=1);

namespace SilkNetwork\LaravelQualityKit\Rector\Resources;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use Rector\Rector\AbstractRector;
use PhpParser\Comment\Doc;

/**
 * @see \SilkNetwork\LaravelQualityKit\Tests\Rector\Resources\AddApiResourcePhpDocRector\AddApiResourcePhpDocRectorTest
 */
final class AddApiResourcePhpDocRector extends AbstractRector
{
    public function getNodeTypes(): array
    {
        return [Class_::class];
    }

    /**
     * @param Class_ $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($node->isAnonymous()) {
            return null;
        }

        $className = $this->nodeNameResolver->getName($node);
        if ($className === null) {
            return null;
        }

        // Detection: Extends JsonResource or name ends with "Resource"
        $isJsonResource = $this->isObjectType($node, new \PHPStan\Type\ObjectType('Illuminate\Http\Resources\Json\JsonResource'));
        $hasResourceSuffix = str_ends_with($className, 'Resource');

        if (!$isJsonResource && !$hasResourceSuffix) {
            return null;
        }

        $shortClassName = $this->nodeNameResolver->getShortName($node);
        if (empty($shortClassName)) {
            return null;
        }

        // Model Resolution: Strip "Resource" suffix
        $modelName = preg_replace('/Resource$/', '', $shortClassName);
        if (empty($modelName)) {
            return null;
        }

        $modelFqcn = 'App\\Models\\' . $modelName;

        $mixinAnnotation = '@mixin \\' . $modelFqcn;

        $docComment = $node->getDocComment();
        $currentDocText = $docComment ? $docComment->getText() : "/**\n */";

        $hasChanged = false;
        $newDocText = $currentDocText;


        // Check and add @mixin
        if (!str_contains($newDocText, $mixinAnnotation)) {
            $newDocText = $this->addAnnotationToDocBlock($newDocText, $mixinAnnotation);
            $hasChanged = true;
        }

        if ($hasChanged) {
            $node->setDocComment(new Doc($newDocText));
            return $node;
        }

        return null;
    }

    private function addAnnotationToDocBlock(string $docBlockText, string $annotation): string
    {
        $lines = explode("\n", $docBlockText);
        if (count($lines) === 1) { // Potentially a non-standard single line docblock like "/** */"
            return "/**\n * " . $annotation . "\n */";
        }

        $lastLineIndex = count($lines) - 1;
        $newLines = [];

        if (trim($lines[$lastLineIndex]) === '*/' && $lastLineIndex > 0) {
            // Insert before the closing */
            array_splice($lines, $lastLineIndex, 0, [' * ' . $annotation]);
        } else {
            // If not a standard block, or it's empty, create a new one
            $newLines[] = '/**';
            $newLines[] = ' * ' . $annotation;
            $newLines[] = ' */';
            return implode("\n", $newLines);
        }
        return implode("\n", $lines);
    }
}