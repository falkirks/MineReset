<?php

declare(strict_types=1);

namespace falkirks\minereset\libs\forms;

use pocketmine\form\FormValidationException;
use pocketmine\player\Player;
use pocketmine\utils\Utils;
use function gettype;
use function is_bool;

class ModalForm extends BaseForm
{

	/** @phpstan-param \Closure(Player, bool) : mixed $onSubmit */
	public function __construct(
		string  $title,
		public  /*readonly*/ string $content,
		private /*readonly*/ \Closure $onSubmit,
		public  /*readonly*/ string $button1 = "gui.yes",
		public  /*readonly*/ string $button2 = "gui.no",
	) {
		/** @phpstan-ignore-next-line */
		Utils::validateCallableSignature(function (Player $player, bool $choice) {
		}, $onSubmit);
		parent::__construct($title);
	}

	/** @phpstan-param \Closure(Player) : mixed $onConfirm */
	public static function confirm(string $title, string $content, \Closure $onConfirm): self {
		/** @phpstan-ignore-next-line */
		Utils::validateCallableSignature(function (Player $player) {
		}, $onConfirm);
		return new self($title, $content, static function (Player $player, bool $response) use ($onConfirm): void {
			if ($response) {
				$onConfirm($player);
			}
		});
	}

	protected function getType(): string {
		return "modal";
	}

	protected function serializeFormData(): array {
		return [
			"content" => $this->content,
			"button1" => $this->button1,
			"button2" => $this->button2,
		];
	}

	final public function handleResponse(Player $player, mixed $data): void {
		if (!is_bool($data)) {
			throw new FormValidationException("Expected bool, got " . gettype($data));
		}

		($this->onSubmit)($player, $data);
	}
}
