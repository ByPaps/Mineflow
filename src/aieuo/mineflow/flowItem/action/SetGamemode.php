<?php

namespace aieuo\mineflow\flowItem\action;

use aieuo\mineflow\flowItem\base\PlayerFlowItem;
use aieuo\mineflow\flowItem\base\PlayerFlowItemTrait;
use aieuo\mineflow\flowItem\FlowItem;
use aieuo\mineflow\flowItem\FlowItemExecutor;
use aieuo\mineflow\formAPI\element\Dropdown;
use aieuo\mineflow\formAPI\element\mineflow\PlayerVariableDropdown;
use aieuo\mineflow\utils\Category;
use aieuo\mineflow\utils\Language;

class SetGamemode extends FlowItem implements PlayerFlowItem {
    use PlayerFlowItemTrait;

    protected $id = self::SET_GAMEMODE;

    protected $name = "action.setGamemode.name";
    protected $detail = "action.setGamemode.detail";
    protected $detailDefaultReplace = ["player", "gamemode"];

    protected $category = Category::PLAYER;

    private $gamemodes = [
        "action.gamemode.survival",
        "action.gamemode.creative",
        "action.gamemode.adventure",
        "action.gamemode.spectator"
    ];

    /** @var string */
    private $gamemode;

    public function __construct(string $player = "", string $gamemode = "") {
        $this->setPlayerVariableName($player);
        $this->gamemode = $gamemode;
    }

    public function setGamemode(string $gamemode): void {
        $this->gamemode = $gamemode;
    }

    public function getGamemode(): string {
        return $this->gamemode;
    }

    public function isDataValid(): bool {
        return $this->getPlayerVariableName() !== "" and $this->gamemode !== "";
    }

    public function getDetail(): string {
        if (!$this->isDataValid()) return $this->getName();
        return Language::get($this->detail, [$this->getPlayerVariableName(), Language::get($this->gamemodes[$this->getGamemode()])]);
    }

    public function execute(FlowItemExecutor $source): \Generator {
        $this->throwIfCannotExecute();

        $gamemode = $source->replaceVariables($this->getGamemode());
        $this->throwIfInvalidNumber($gamemode, 0, 3);

        $player = $this->getPlayer($source);
        $this->throwIfInvalidPlayer($player);

        $player->setGamemode((int)$gamemode);
        yield true;
    }

    public function getEditFormElements(array $variables): array {
        return [
            new PlayerVariableDropdown($variables, $this->getPlayerVariableName()),
            new Dropdown("@action.setGamemode.form.gamemode", array_map(function (string $mode) {
                return Language::get($mode);
            }, $this->gamemodes), (int)$this->getGamemode()),
        ];
    }

    public function parseFromFormData(array $data): array {
        return [$data[0], (string)$data[1]];
    }

    public function loadSaveData(array $content): FlowItem {
        $this->setPlayerVariableName($content[0]);
        $this->setGamemode($content[1]);
        return $this;
    }

    public function serializeContents(): array {
        return [$this->getPlayerVariableName(), $this->getGamemode()];
    }
}