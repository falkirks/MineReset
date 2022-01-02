<?php

declare(strict_types=1);

namespace falkirks\minereset\libs\forms\menu;

class Image implements \JsonSerializable
{

	private function __construct(public /*readonly*/ string $data, public /*readonly*/ string $type) {
	}

	public static function url(string $data): self {
		return new self($data, "url");
	}

	public static function path(string $data): self {
		return new self($data, "path");
	}

	/** @phpstan-return array<string, mixed> */
	public function jsonSerialize(): array {
		return [
			"type" => $this->type,
			"data" => $this->data,
		];
	}
}
