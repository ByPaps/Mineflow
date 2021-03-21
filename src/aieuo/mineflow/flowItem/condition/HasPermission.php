<?php

namespace aieuo\mineflow\flowItem\condition;

use aieuo\mineflow\flowItem\base\PlayerFlowItem;
use aieuo\mineflow\flowItem\base\PlayerFlowItemTrait;
use aieuo\mineflow\flowItem\FlowItem;
use aieuo\mineflow\formAPI\element\mineflow\ExampleInput;
use aieuo\mineflow\formAPI\element\mineflow\PlayerVariableDropdown;
use aieuo\mineflow\recipe\Recipe;
use aieuo\mineflow\utils\Category;
use aieuo\mineflow\utils\Language;

class HasPermission extends FlowItem implements Condition, PlayerFlowItem {
    use PlayerFlowItemTrait;

    protected $id = self::HAS_PERMISSION;

    protected $name = "condition.hasPermission.name";
    protected $detail = "condition.hasPermission.detail";
    protected $detailDefaultReplace = ["player", "permission"];

    protected $category = Category::PLAYER;

    /** @var string */
    private $playerPermission;

    public function __construct(string $player = "", string $permission = "") {
        $this->setPlayerVariableName($player);
        $this->playerPermission = $permission;
    }

    public function setPlayerPermission(string $playerPermission): void {
        $this->playerPermission = $playerPermission;
    }

    public function getPlayerPermission(): string {
        return $this->playerPermission;
    }

    public function isDataValid(): bool {
        return $this->getPlayerVariableName() !== "" and $this->getPlayerPermission() !== "";
    }

    public function getDetail(): string {
        if (!$this->isDataValid()) return $this->getName();
        return Language::get($this->detail, [$this->getPlayerVariableName(), $this->getPlayerPermission()]);
    }

    public function execute(Recipe $source): \Generator {
        $this->throwIfCannotExecute();

        $player = $this->getPlayer($source);
        $this->throwIfInvalidPlayer($player);

        $permission = $this->getPlayerPermission();

        yield true;
        return $player->hasPermission($permission);
    }

    public function getEditFormElements(array $variables): array {
        return [
            new PlayerVariableDropdown($variables, $this->getPlayerVariableName()),
            new ExampleInput("@condition.hasPermission.form.permission", "mineflow.customcommand.op", $this->getPlayerPermission(), true),
        ];
    }

    public function loadSaveData(array $content): FlowItem {
        $this->setPlayerVariableName($content[0]);
        $this->setPlayerPermission($content[1]);
        return $this;
    }

    public function serializeContents(): array {
        return [$this->getPlayerVariableName(), $this->getPlayerPermission()];
    }
}