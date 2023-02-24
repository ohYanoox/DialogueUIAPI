<?php

namespace Yanoox\DialogueUIAPI\element;

use Closure;
use pocketmine\player\Player;
use pocketmine\utils\Utils;

final class DialogueButton
{

    public const TYPE_COMMAND = 1;
    public const MODE_BUTTON = 0;

    public const CMD_VER = 17; //1.18.X

    /**
     * @var Closure
     * @phpstan-var \Closure(Player $player) : void
     */
    private Closure $handler;

    /**
     * @param string $name
     * @param bool $closeOnClick
     */
    public function __construct(protected string $name, protected bool $closeOnClick = true){}

    /**
     * @param string $name
     * @param bool $closeOnClick
     * @return DialogueButton
     */
    public static function create(string $name, bool $closeOnClick = true): DialogueButton
    {
        return new self($name, $closeOnClick);
    }

    /**
     * @phpstan-param \Closure(Player $player) : void $handler
     */
    public function setHandler(Closure $handler): self
    {
        Utils::validateCallableSignature(static function (Player $player, string $buttonName): void {}, $handler);
        $this->handler = $handler;
        return $this;
    }

    public function getCloseOnClick(): bool
    {
        return $this->closeOnClick;
    }

    /**
     * @return Closure|null
     * @phpstan-return \Closure(Player $player) : void
     */
    public function getHandler(): ?Closure
    {
        return $this->handler;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function dump(): array
    {
        return [
            "button_name" => $this->name,
            "mode" => self::MODE_BUTTON,
            "type" => self::TYPE_COMMAND,
            "text" => '',
            "data" => array_map(static fn($commands) => [
                "cmd_line" => $commands, //TODO
                "cmd_ver" => self::CMD_VER
            ], explode("\n", ''))
        ];
    }
}