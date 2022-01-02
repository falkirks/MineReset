<?php

declare(strict_types=1);

namespace falkirks\minereset\libs\forms\element;

/** @phpstan-extends BaseSelector<int> */
class StepSlider extends BaseSelector
{

	protected function getType(): string {
		return "step_slider";
	}

	protected function serializeElementData(): array {
		return [
			"steps" => $this->options,
			"default" => $this->default,
		];
	}
}
