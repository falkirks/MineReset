<?php

declare(strict_types=1);

namespace falkirks\minereset\libs\forms\element;

use pocketmine\form\FormValidationException;
use function gettype;
use function is_null;

/** @phpstan-extends BaseElementWithValue<null> */
class Label extends BaseElementWithValue
{

	public function __construct(string $text) {
		parent::__construct($text);
	}

	protected function getType(): string {
		return "label";
	}

	protected function validateValue(mixed $value): void {
		if (!is_null($value)) {
			throw new FormValidationException("Expected null, got " . gettype($value));
		}
	}

	protected function serializeElementData(): array {
		return [];
	}
}
